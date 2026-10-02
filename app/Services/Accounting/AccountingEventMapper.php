<?php

namespace App\Services\Accounting;

use App\Enums\VoucherType;
use App\Events\ActivityTransactionRecorded;
use App\Events\CashSessionClosed;
use App\Events\CreditNoteIssued;
use App\Events\CutTransferRegistered;
use App\Events\DebitNoteIssued;
use App\Events\InternalLoanDisbursed;
use App\Events\InternalLoanRepaid;
use App\Events\PaymentReceived;
use App\Events\PurchaseRecorded;
use App\Events\SaleCompleted;
use App\Models\ActivityTransaction;
use App\Models\ArchingCash;
use App\Models\Billing;
use App\Models\Buy;
use App\Models\ChartOfAccount;
use App\Models\RdrCutTransfer;
use App\Models\RdrInternalLoan;
use App\Models\SaleNote;
use Carbon\Carbon;
use DomainException;
use Illuminate\Database\Eloquent\Model;

class AccountingEventMapper
{
    /**
     * Cache for resolved Chart of Account models by code.
     *
     * @var array<string, ChartOfAccount>
     */
    protected array $accountCache = [];

    /**
     * Map a domain event or model to journal entry header and lines.
     * Returns null if the event/model should not generate a journal entry
     * (e.g., ActivityTransaction referencing a core voucher).
     */
    public function map(mixed $eventOrModel): ?array
    {
        // 1. Domain Events
        if ($eventOrModel instanceof SaleCompleted) {
            return $eventOrModel->documentKind === 'sale_note'
                ? $this->mapSaleNote($eventOrModel->sale)
                : $this->mapBilling($eventOrModel->sale);
        }

        if ($eventOrModel instanceof CreditNoteIssued) {
            return $this->mapCreditNote($eventOrModel->creditNote, $eventOrModel->parentBilling);
        }

        if ($eventOrModel instanceof DebitNoteIssued) {
            return $this->mapDebitNote($eventOrModel->debitNote, $eventOrModel->parentBilling);
        }

        if ($eventOrModel instanceof PurchaseRecorded) {
            return $this->mapBuy($eventOrModel->buy);
        }

        if ($eventOrModel instanceof PaymentReceived) {
            return $this->mapPaymentReceived($eventOrModel);
        }

        if ($eventOrModel instanceof CashSessionClosed) {
            return $this->mapCashSessionClosed($eventOrModel);
        }

        if ($eventOrModel instanceof CutTransferRegistered) {
            return $this->mapCutTransfer($eventOrModel->cutTransfer);
        }

        if ($eventOrModel instanceof InternalLoanDisbursed) {
            return $this->mapInternalLoan($eventOrModel->loan, 'DISBURSEMENT');
        }

        if ($eventOrModel instanceof InternalLoanRepaid) {
            return $this->mapInternalLoan($eventOrModel->loan, 'REPAYMENT', $eventOrModel->repaidAmount);
        }

        if ($eventOrModel instanceof ActivityTransactionRecorded) {
            return $this->mapActivityTransaction($eventOrModel->transaction);
        }

        // 2. Direct Models (useful for backfill)
        if ($eventOrModel instanceof Billing) {
            $docType = (string) ($eventOrModel->typeDocument?->codigo ?? $eventOrModel->idtipo_comprobante);
            if ($docType === '07' || $eventOrModel->idtipo_comprobante == 3) {
                return $this->mapCreditNote($eventOrModel);
            }
            if ($docType === '08' || $eventOrModel->idtipo_comprobante == 4) {
                return $this->mapDebitNote($eventOrModel);
            }
            return $this->mapBilling($eventOrModel);
        }

        if ($eventOrModel instanceof SaleNote) {
            return $this->mapSaleNote($eventOrModel);
        }

        if ($eventOrModel instanceof Buy) {
            return $this->mapBuy($eventOrModel);
        }

        if ($eventOrModel instanceof ArchingCash) {
            return $this->mapArchingCashModel($eventOrModel);
        }

        if ($eventOrModel instanceof RdrCutTransfer) {
            return $this->mapCutTransfer($eventOrModel);
        }

        if ($eventOrModel instanceof RdrInternalLoan) {
            return $this->mapInternalLoan($eventOrModel, 'DISBURSEMENT');
        }

        if ($eventOrModel instanceof ActivityTransaction) {
            return $this->mapActivityTransaction($eventOrModel);
        }

        return null;
    }

    /**
     * Map a commercial Billing (Factura/Boleta) into double-entry accounting.
     * Rule:
     *   DEBIT:  1212 (Cuentas por cobrar comerciales) for total
     *   CREDIT: 40111 (IGV Cuenta propia) for IGV
     *   CREDIT: 70111 (Venta de mercaderías) for taxable_base (gravada + exonerada + inafecta)
     * Free-of-charge items (gratuita) have NO impact on revenue or receivable.
     */
    public function mapBilling(Billing $billing): ?array
    {
        $total = round((float) $billing->total, 2);
        if ($total <= 0) {
            return null;
        }

        $igv = round((float) ($billing->igv ?? 0.0), 2);
        $gravada = round((float) ($billing->gravada ?? 0.0), 2);
        $exonerada = round((float) ($billing->exonerada ?? 0.0), 2);
        $inafecta = round((float) ($billing->inafecta ?? 0.0), 2);
        // Note: $billing->gratuita is explicitly ignored for revenue as per prompt rule.

        // Revenue Base = total taxable, exempt, and unaffected components
        $revenueBase = round($gravada + $exonerada + $inafecta, 2);

        // In case gravada/exonerada wasn't explicitly broken down on legacy records, calculate base:
        if ($revenueBase <= 0 && $total > 0) {
            $revenueBase = round($total - $igv, 2);
        }

        // Check arithmetic balancing: total must equal revenueBase + igv
        $diff = round($total - ($revenueBase + $igv), 2);
        if (abs($diff) > 0.001) {
            $revenueBase = round($revenueBase + $diff, 2);
        }

        $accReceivable = $this->getAccount('1212');
        $accIgv        = $this->getAccount('40111');
        $accRevenue    = $this->getAccount('70111');

        $docRef = trim(($billing->serie ?? '') . '-' . ($billing->correlativo ?? ''));
        $client = $billing->customer;
        $thirdDoc = $client?->nro_documento;

        $lines = [
            [
                'account_id'           => $accReceivable->id,
                'debit'                => $total,
                'credit'               => 0.00,
                'glosa'                => "CxC Venta {$docRef}",
                'third_party_type'     => $client ? 'Client' : null,
                'third_party_id'       => $client?->id,
                'third_party_document' => $thirdDoc,
                'cost_center_id'       => $billing->idalmacen,
                'document_reference'   => $docRef,
            ],
        ];

        if ($igv > 0) {
            $lines[] = [
                'account_id'           => $accIgv->id,
                'debit'                => 0.00,
                'credit'               => $igv,
                'glosa'                => "IGV Débito Fiscal {$docRef}",
                'third_party_type'     => $client ? 'Client' : null,
                'third_party_id'       => $client?->id,
                'third_party_document' => $thirdDoc,
                'cost_center_id'       => $billing->idalmacen,
                'document_reference'   => $docRef,
            ];
        }

        if ($revenueBase > 0) {
            $lines[] = [
                'account_id'           => $accRevenue->id,
                'debit'                => 0.00,
                'credit'               => $revenueBase,
                'glosa'                => "Ingreso Venta Mercaderías {$docRef}",
                'third_party_type'     => $client ? 'Client' : null,
                'third_party_id'       => $client?->id,
                'third_party_document' => $thirdDoc,
                'cost_center_id'       => $billing->idalmacen,
                'document_reference'   => $docRef,
            ];
        }

        $entryDate = $billing->fecha_emision instanceof Carbon
            ? $billing->fecha_emision->toDateString()
            : ($billing->fecha_emision ? Carbon::parse($billing->fecha_emision)->toDateString() : now()->toDateString());

        return [
            'header' => [
                'entry_date'         => $entryDate,
                'entry_type'         => VoucherType::OPERATING,
                'concept'            => "Venta comercial {$docRef} - " . ($client?->nombres ?? 'Cliente General'),
                'currency'           => 'PEN',
                'exchange_rate'      => 1.0000,
                'source_type'        => get_class($billing),
                'source_id'          => $billing->id,
                'event_key'          => 'posted',
                'created_by_user_id' => $billing->idusuario ?? 1,
            ],
            'lines' => $lines,
        ];
    }

    /**
     * Map internal SaleNote into double-entry accounting.
     * Rule:
     *   DEBIT:  1219 (Otras cuentas por cobrar - Notas de venta)
     *   CREDIT: 70111 (Venta de mercaderías)
     */
    public function mapSaleNote(SaleNote $saleNote): ?array
    {
        $total = round((float) $saleNote->total, 2);
        if ($total <= 0) {
            return null;
        }

        $accReceivable = $this->getAccount('1219');
        $accRevenue    = $this->getAccount('70111');

        $docRef = trim(($saleNote->serie ?? '') . '-' . ($saleNote->correlativo ?? ''));
        $client = $saleNote->cliente;
        $thirdDoc = $client?->nro_documento;

        $lines = [
            [
                'account_id'           => $accReceivable->id,
                'debit'                => $total,
                'credit'               => 0.00,
                'glosa'                => "CxC Nota de Venta {$docRef}",
                'third_party_type'     => $client ? 'Client' : null,
                'third_party_id'       => $client?->id,
                'third_party_document' => $thirdDoc,
                'cost_center_id'       => null,
                'document_reference'   => $docRef,
            ],
            [
                'account_id'           => $accRevenue->id,
                'debit'                => 0.00,
                'credit'               => $total,
                'glosa'                => "Ingreso Nota de Venta {$docRef}",
                'third_party_type'     => $client ? 'Client' : null,
                'third_party_id'       => $client?->id,
                'third_party_document' => $thirdDoc,
                'cost_center_id'       => null,
                'document_reference'   => $docRef,
            ],
        ];

        $entryDate = $saleNote->fecha_emision instanceof Carbon
            ? $saleNote->fecha_emision->toDateString()
            : ($saleNote->fecha_emision ? Carbon::parse($saleNote->fecha_emision)->toDateString() : now()->toDateString());

        return [
            'header' => [
                'entry_date'         => $entryDate,
                'entry_type'         => VoucherType::OPERATING,
                'concept'            => "Venta interna Nota de Venta {$docRef} - " . ($client?->nombres ?? 'Venta Mostrador'),
                'currency'           => 'PEN',
                'exchange_rate'      => 1.0000,
                'source_type'        => get_class($saleNote),
                'source_id'          => $saleNote->id,
                'event_key'          => 'posted',
                'created_by_user_id' => $saleNote->idusuario ?? 1,
            ],
            'lines' => $lines,
        ];
    }

    /**
     * Map a Credit Note (partial or total) on a Billing.
     * Contra-entry reduces revenue (70111), reduces IGV (40111), and reduces accounts receivable (1212).
     * Rule:
     *   DEBIT:  70111 (Reducción de ingreso / venta)
     *   DEBIT:  40111 (Reducción de IGV débito fiscal)
     *   CREDIT: 1212 (Reducción de cuenta por cobrar)
     */
    public function mapCreditNote(Billing $creditNote, ?Billing $parentBilling = null): ?array
    {
        $total = round((float) $creditNote->total, 2);
        if ($total <= 0) {
            return null;
        }

        $igv = round((float) ($creditNote->igv ?? 0.0), 2);
        $gravada = round((float) ($creditNote->gravada ?? 0.0), 2);
        $exonerada = round((float) ($creditNote->exonerada ?? 0.0), 2);
        $inafecta = round((float) ($creditNote->inafecta ?? 0.0), 2);

        $base = round($gravada + $exonerada + $inafecta, 2);
        if ($base <= 0 && $total > 0) {
            $base = round($total - $igv, 2);
        }

        $diff = round($total - ($base + $igv), 2);
        if (abs($diff) > 0.001) {
            $base = round($base + $diff, 2);
        }

        $accReceivable = $this->getAccount('1212');
        $accIgv        = $this->getAccount('40111');
        $accRevenue    = $this->getAccount('70111');

        $docRef = trim(($creditNote->serie ?? '') . '-' . ($creditNote->correlativo ?? ''));
        $parentRef = $parentBilling ? trim(($parentBilling->serie ?? '') . '-' . ($parentBilling->correlativo ?? '')) : '';
        $client = $creditNote->customer;
        $thirdDoc = $client?->nro_documento;

        $lines = [];

        if ($base > 0) {
            $lines[] = [
                'account_id'           => $accRevenue->id,
                'debit'                => $base,
                'credit'               => 0.00,
                'glosa'                => "Descuento/Devolución NC {$docRef}" . ($parentRef ? " s/{$parentRef}" : ''),
                'third_party_type'     => $client ? 'Client' : null,
                'third_party_id'       => $client?->id,
                'third_party_document' => $thirdDoc,
                'cost_center_id'       => $creditNote->idalmacen,
                'document_reference'   => $docRef,
            ];
        }

        if ($igv > 0) {
            $lines[] = [
                'account_id'           => $accIgv->id,
                'debit'                => $igv,
                'credit'               => 0.00,
                'glosa'                => "Ajuste IGV Débito Fiscal NC {$docRef}",
                'third_party_type'     => $client ? 'Client' : null,
                'third_party_id'       => $client?->id,
                'third_party_document' => $thirdDoc,
                'cost_center_id'       => $creditNote->idalmacen,
                'document_reference'   => $docRef,
            ];
        }

        $lines[] = [
            'account_id'           => $accReceivable->id,
            'debit'                => 0.00,
            'credit'               => $total,
            'glosa'                => "Reducción CxC NC {$docRef}" . ($parentRef ? " s/{$parentRef}" : ''),
            'third_party_type'     => $client ? 'Client' : null,
            'third_party_id'       => $client?->id,
            'third_party_document' => $thirdDoc,
            'cost_center_id'       => $creditNote->idalmacen,
            'document_reference'   => $docRef,
        ];

        $entryDate = $creditNote->fecha_emision instanceof Carbon
            ? $creditNote->fecha_emision->toDateString()
            : ($creditNote->fecha_emision ? Carbon::parse($creditNote->fecha_emision)->toDateString() : now()->toDateString());

        return [
            'header' => [
                'entry_date'         => $entryDate,
                'entry_type'         => VoucherType::ADJUSTMENT,
                'concept'            => "Nota de Crédito {$docRef}" . ($parentRef ? " aplicada a {$parentRef}" : '') . " - " . ($client?->nombres ?? 'Cliente'),
                'currency'           => 'PEN',
                'exchange_rate'      => 1.0000,
                'source_type'        => get_class($creditNote),
                'source_id'          => $creditNote->id,
                'event_key'          => 'credit_note',
                'created_by_user_id' => $creditNote->idusuario ?? 1,
            ],
            'lines' => $lines,
        ];
    }

    /**
     * Map a Debit Note on a Billing.
     * Increases accounts receivable (1212), increases IGV (40111), and recognizes financial/management income (772/70111).
     */
    public function mapDebitNote(Billing $debitNote, ?Billing $parentBilling = null): ?array
    {
        $total = round((float) $debitNote->total, 2);
        if ($total <= 0) {
            return null;
        }

        $igv = round((float) ($debitNote->igv ?? 0.0), 2);
        $base = round($total - $igv, 2);

        $accReceivable = $this->getAccount('1212');
        $accIgv        = $this->getAccount('40111');
        $accRevenue    = $this->getAccount('772') ?? $this->getAccount('70111');

        $docRef = trim(($debitNote->serie ?? '') . '-' . ($debitNote->correlativo ?? ''));
        $parentRef = $parentBilling ? trim(($parentBilling->serie ?? '') . '-' . ($parentBilling->correlativo ?? '')) : '';
        $client = $debitNote->customer;
        $thirdDoc = $client?->nro_documento;

        $lines = [
            [
                'account_id'           => $accReceivable->id,
                'debit'                => $total,
                'credit'               => 0.00,
                'glosa'                => "Incremento CxC ND {$docRef}",
                'third_party_type'     => $client ? 'Client' : null,
                'third_party_id'       => $client?->id,
                'third_party_document' => $thirdDoc,
                'cost_center_id'       => $debitNote->idalmacen,
                'document_reference'   => $docRef,
            ],
        ];

        if ($igv > 0) {
            $lines[] = [
                'account_id'           => $accIgv->id,
                'debit'                => 0.00,
                'credit'               => $igv,
                'glosa'                => "IGV Débito Fiscal ND {$docRef}",
                'third_party_type'     => $client ? 'Client' : null,
                'third_party_id'       => $client?->id,
                'third_party_document' => $thirdDoc,
                'cost_center_id'       => $debitNote->idalmacen,
                'document_reference'   => $docRef,
            ];
        }

        if ($base > 0) {
            $lines[] = [
                'account_id'           => $accRevenue->id,
                'debit'                => 0.00,
                'credit'               => $base,
                'glosa'                => "Ingreso complementario ND {$docRef}",
                'third_party_type'     => $client ? 'Client' : null,
                'third_party_id'       => $client?->id,
                'third_party_document' => $thirdDoc,
                'cost_center_id'       => $debitNote->idalmacen,
                'document_reference'   => $docRef,
            ];
        }

        $entryDate = $debitNote->fecha_emision instanceof Carbon
            ? $debitNote->fecha_emision->toDateString()
            : ($debitNote->fecha_emision ? Carbon::parse($debitNote->fecha_emision)->toDateString() : now()->toDateString());

        return [
            'header' => [
                'entry_date'         => $entryDate,
                'entry_type'         => VoucherType::ADJUSTMENT,
                'concept'            => "Nota de Débito {$docRef}" . ($parentRef ? " sobre {$parentRef}" : '') . " - " . ($client?->nombres ?? 'Cliente'),
                'currency'           => 'PEN',
                'exchange_rate'      => 1.0000,
                'source_type'        => get_class($debitNote),
                'source_id'          => $debitNote->id,
                'event_key'          => 'debit_note',
                'created_by_user_id' => $debitNote->idusuario ?? 1,
            ],
            'lines' => $lines,
        ];
    }

    /**
     * Map a Purchase (Buy) from a supplier into double-entry accounting.
     * Rule:
     *   DEBIT:  6011 (Compras mercaderías / insumos) for taxable base
     *   DEBIT:  40112 (IGV Crédito Fiscal) for IGV
     *   CREDIT: 4212 (Facturas por pagar a proveedores) for total
     */
    public function mapBuy(Buy $buy): ?array
    {
        $total = round((float) $buy->total, 2);
        if ($total <= 0) {
            return null;
        }

        $igv = round((float) ($buy->igv ?? 0.0), 2);
        $gravada = round((float) ($buy->gravada ?? 0.0), 2);
        $exonerada = round((float) ($buy->exonerada ?? 0.0), 2);
        $inafecta = round((float) ($buy->inafecta ?? 0.0), 2);

        $base = round($gravada + $exonerada + $inafecta, 2);
        if ($base <= 0 && $total > 0) {
            $base = round($total - $igv, 2);
        }

        $diff = round($total - ($base + $igv), 2);
        if (abs($diff) > 0.001) {
            $base = round($base + $diff, 2);
        }

        $accPurchase = $this->getAccount('6011');
        $accIgv      = $this->getAccount('40112') ?? $this->getAccount('40111');
        $accPayable  = $this->getAccount('4212');

        $docRef = trim(($buy->serie ?? '') . '-' . ($buy->correlativo ?? ''));
        $provider = $buy->provider;
        $thirdDoc = $provider?->nro_documento;

        $lines = [];

        if ($base > 0) {
            $lines[] = [
                'account_id'           => $accPurchase->id,
                'debit'                => $base,
                'credit'               => 0.00,
                'glosa'                => "Compra mercaderías/insumos {$docRef}",
                'third_party_type'     => $provider ? 'Provider' : null,
                'third_party_id'       => $provider?->id,
                'third_party_document' => $thirdDoc,
                'cost_center_id'       => $buy->idalmacen,
                'document_reference'   => $docRef,
            ];
        }

        if ($igv > 0) {
            $lines[] = [
                'account_id'           => $accIgv->id,
                'debit'                => $igv,
                'credit'               => 0.00,
                'glosa'                => "IGV Crédito Fiscal {$docRef}",
                'third_party_type'     => $provider ? 'Provider' : null,
                'third_party_id'       => $provider?->id,
                'third_party_document' => $thirdDoc,
                'cost_center_id'       => $buy->idalmacen,
                'document_reference'   => $docRef,
            ];
        }

        $lines[] = [
            'account_id'           => $accPayable->id,
            'debit'                => 0.00,
            'credit'               => $total,
            'glosa'                => "CxP Proveedor {$docRef}",
            'third_party_type'     => $provider ? 'Provider' : null,
            'third_party_id'       => $provider?->id,
            'third_party_document' => $thirdDoc,
            'cost_center_id'       => $buy->idalmacen,
            'document_reference'   => $docRef,
        ];

        $entryDate = $buy->fecha_emision instanceof Carbon
            ? $buy->fecha_emision->toDateString()
            : ($buy->fecha_emision ? Carbon::parse($buy->fecha_emision)->toDateString() : now()->toDateString());

        return [
            'header' => [
                'entry_date'         => $entryDate,
                'entry_type'         => VoucherType::OPERATING,
                'concept'            => "Compra proveedor {$docRef} - " . ($provider?->nombre_razon_social ?? 'Proveedor'),
                'currency'           => 'PEN',
                'exchange_rate'      => 1.0000,
                'source_type'        => get_class($buy),
                'source_id'          => $buy->id,
                'event_key'          => 'purchase',
                'created_by_user_id' => $buy->idusuario ?? 1,
            ],
            'lines' => $lines,
        ];
    }

    /**
     * Map installment collections or payments into double-entry accounting.
     * When client pays installment:
     *   DEBIT:  1011 (Caja) or 10411 (Banco)
     *   CREDIT: 1212 (CxC Facturas) or 1219 (CxC Notas de Venta)
     * When paying supplier installment:
     *   DEBIT:  4212 (CxP Proveedores)
     *   CREDIT: 10411 (Banco) or 1011 (Caja)
     */
    public function mapPaymentReceived(PaymentReceived $event): ?array
    {
        $amount = round((float) $event->amount, 2);
        if ($amount <= 0) {
            return null;
        }

        $doc = $event->document;
        $isSupplier = $doc instanceof Buy;

        // Determine liquidity account: cash vs bank based on payment mode
        $methodId = (int) $event->paymentMethodId;
        $isCash = in_array($methodId, [1, 5], true); // 1 = Efectivo, 5 = Caja Chica
        $accLiquidity = $isCash ? $this->getAccount('1011') : $this->getAccount('10411');

        $docRef = $event->reference ?: ($doc->serie ? ($doc->serie . '-' . $doc->correlativo) : "Doc #{$doc->id}");
        $cuotaSuffix = $event->installmentNumber ? " (Cuota {$event->installmentNumber})" : '';

        if ($isSupplier) {
            // Supplier payment
            $accPayable = $this->getAccount('4212');
            $provider = $doc->provider;
            $thirdDoc = $provider?->nro_documento;

            $lines = [
                [
                    'account_id'           => $accPayable->id,
                    'debit'                => $amount,
                    'credit'               => 0.00,
                    'glosa'                => "Pago a proveedor {$docRef}{$cuotaSuffix}",
                    'third_party_type'     => $provider ? 'Provider' : null,
                    'third_party_id'       => $provider?->id,
                    'third_party_document' => $thirdDoc,
                    'cost_center_id'       => $doc->idalmacen,
                    'document_reference'   => $docRef,
                ],
                [
                    'account_id'           => $accLiquidity->id,
                    'debit'                => 0.00,
                    'credit'               => $amount,
                    'glosa'                => "Egreso Tesorería {$docRef}{$cuotaSuffix}",
                    'third_party_type'     => $provider ? 'Provider' : null,
                    'third_party_id'       => $provider?->id,
                    'third_party_document' => $thirdDoc,
                    'cost_center_id'       => $doc->idalmacen,
                    'document_reference'   => $docRef,
                ],
            ];

            $concept = "Pago proveedor {$docRef}{$cuotaSuffix} - " . ($provider?->nombre_razon_social ?? 'Proveedor');
        } else {
            // Customer collection
            $isSaleNote = $doc instanceof SaleNote;
            $accReceivable = $isSaleNote ? $this->getAccount('1219') : $this->getAccount('1212');
            $client = $isSaleNote ? $doc->cliente : $doc->customer;
            $thirdDoc = $client?->nro_documento;

            $lines = [
                [
                    'account_id'           => $accLiquidity->id,
                    'debit'                => $amount,
                    'credit'               => 0.00,
                    'glosa'                => "Cobranza venta {$docRef}{$cuotaSuffix}",
                    'third_party_type'     => $client ? 'Client' : null,
                    'third_party_id'       => $client?->id,
                    'third_party_document' => $thirdDoc,
                    'cost_center_id'       => $doc->idalmacen ?? null,
                    'document_reference'   => $docRef,
                ],
                [
                    'account_id'           => $accReceivable->id,
                    'debit'                => 0.00,
                    'credit'               => $amount,
                    'glosa'                => "Amortización CxC {$docRef}{$cuotaSuffix}",
                    'third_party_type'     => $client ? 'Client' : null,
                    'third_party_id'       => $client?->id,
                    'third_party_document' => $thirdDoc,
                    'cost_center_id'       => $doc->idalmacen ?? null,
                    'document_reference'   => $docRef,
                ],
            ];

            $concept = "Cobranza cliente {$docRef}{$cuotaSuffix} - " . ($client?->nombres ?? 'Cliente');
        }

        $eventKey = 'payment_' . ($event->installmentNumber ?: '1') . '_' . round($amount, 2);

        return [
            'header' => [
                'entry_date'         => now()->toDateString(),
                'entry_type'         => VoucherType::OPERATING,
                'concept'            => $concept,
                'currency'           => 'PEN',
                'exchange_rate'      => 1.0000,
                'source_type'        => get_class($doc),
                'source_id'          => $doc->id,
                'event_key'          => $eventKey,
                'created_by_user_id' => $event->userId ?? 1,
            ],
            'lines' => $lines,
        ];
    }

    /**
     * Map Cash Register Closing (surplus or shortage).
     * If counted < expected (shortage):
     *   DEBIT:  1629 (Personal - Faltantes de caja)
     *   CREDIT: 1011 (Caja Central / Mostrador)
     * If counted > expected (surplus):
     *   DEBIT:  1011 (Caja)
     *   CREDIT: 7599 (Otros ingresos - Sobrantes de caja)
     */
    public function mapCashSessionClosed(CashSessionClosed $event): ?array
    {
        $diff = round((float) $event->difference, 2);
        if (abs($diff) <= 0.001) {
            return null; // Balanced cash session, no discrepancy entry needed
        }

        $cash = $event->archingCash;
        $accCash = $this->getAccount('1011');
        $user = $cash->user;
        $thirdDoc = $user?->dni ?? $user?->email;

        if ($diff < 0) {
            // Shortage
            $shortage = abs($diff);
            $accShortage = $this->getAccount('1629');

            $lines = [
                [
                    'account_id'           => $accShortage->id,
                    'debit'                => $shortage,
                    'credit'               => 0.00,
                    'glosa'                => "Faltante en cierre de caja #{$cash->id}",
                    'third_party_type'     => $user ? 'User' : null,
                    'third_party_id'       => $user?->id,
                    'third_party_document' => $thirdDoc,
                    'cost_center_id'       => null,
                    'document_reference'   => "ARQ-{$cash->id}",
                ],
                [
                    'account_id'           => $accCash->id,
                    'debit'                => 0.00,
                    'credit'               => $shortage,
                    'glosa'                => "Ajuste de caja por faltante #{$cash->id}",
                    'third_party_type'     => $user ? 'User' : null,
                    'third_party_id'       => $user?->id,
                    'third_party_document' => $thirdDoc,
                    'cost_center_id'       => null,
                    'document_reference'   => "ARQ-{$cash->id}",
                ],
            ];

            $concept = "Faltante en arqueo de caja #{$cash->id} - Cajero: " . ($user?->name ?? 'Usuario');
        } else {
            // Surplus
            $surplus = $diff;
            $accSurplus = $this->getAccount('7599') ?? $this->getAccount('759');

            $lines = [
                [
                    'account_id'           => $accCash->id,
                    'debit'                => $surplus,
                    'credit'               => 0.00,
                    'glosa'                => "Ingreso de caja por sobrante #{$cash->id}",
                    'third_party_type'     => $user ? 'User' : null,
                    'third_party_id'       => $user?->id,
                    'third_party_document' => $thirdDoc,
                    'cost_center_id'       => null,
                    'document_reference'   => "ARQ-{$cash->id}",
                ],
                [
                    'account_id'           => $accSurplus->id,
                    'debit'                => 0.00,
                    'credit'               => $surplus,
                    'glosa'                => "Sobrante de caja no justificado #{$cash->id}",
                    'third_party_type'     => $user ? 'User' : null,
                    'third_party_id'       => $user?->id,
                    'third_party_document' => $thirdDoc,
                    'cost_center_id'       => null,
                    'document_reference'   => "ARQ-{$cash->id}",
                ],
            ];

            $concept = "Sobrante en arqueo de caja #{$cash->id} - Cajero: " . ($user?->name ?? 'Usuario');
        }

        return [
            'header' => [
                'entry_date'         => ($cash->fecha_fin ?? $cash->fecha_inicio) ? Carbon::parse($cash->fecha_fin ?? $cash->fecha_inicio)->toDateString() : now()->toDateString(),
                'entry_type'         => VoucherType::ADJUSTMENT,
                'concept'            => $concept,
                'currency'           => 'PEN',
                'exchange_rate'      => 1.0000,
                'source_type'        => get_class($cash),
                'source_id'          => $cash->id,
                'event_key'          => 'cash_reconciliation',
                'created_by_user_id' => $cash->idusuario ?? 1,
            ],
            'lines' => $lines,
        ];
    }

    /**
     * Helper for direct ArchingCash model (backfill).
     */
    public function mapArchingCashModel(ArchingCash $cash): ?array
    {
        $expected = (float) $cash->monto_estimado;
        $counted = (float) $cash->monto_real;
        $difference = round($counted - $expected, 2);

        $fakeEvent = new CashSessionClosed(
            archingCash: $cash,
            expectedAmount: $expected,
            countedAmount: $counted,
            difference: $difference
        );

        return $this->mapCashSessionClosed($fakeEvent);
    }

    /**
     * Map CUT Transfer (RdrCutTransfer).
     * Rule:
     *   DEBIT:  1071 (Fondos sujetos a restricción - CUT)
     *   CREDIT: 10411 (Banco de la Nación RDR)
     */
    public function mapCutTransfer(RdrCutTransfer $cutTransfer): ?array
    {
        $amount = round((float) $cutTransfer->amount, 2);
        if ($amount <= 0) {
            return null;
        }

        $accCut  = $this->getAccount('1071');
        $accBank = $this->getAccount('10411');

        $docRef = $cutTransfer->transfer_code ?: "CUT-{$cutTransfer->id}";

        $lines = [
            [
                'account_id'           => $accCut->id,
                'debit'                => $amount,
                'credit'               => 0.00,
                'glosa'                => "Traslado de fondos a la CUT {$docRef}",
                'cost_center_id'       => $cutTransfer->productive_activity_id,
                'fund_source_id'       => $cutTransfer->source_fund_id,
                'document_reference'   => $cutTransfer->bank_operation_number ?: $docRef,
            ],
            [
                'account_id'           => $accBank->id,
                'debit'                => 0.00,
                'credit'               => $amount,
                'glosa'                => "Salida Banco de la Nación RDR {$docRef}",
                'cost_center_id'       => $cutTransfer->productive_activity_id,
                'fund_source_id'       => $cutTransfer->source_fund_id,
                'document_reference'   => $cutTransfer->bank_operation_number ?: $docRef,
            ],
        ];

        $entryDate = $cutTransfer->transfer_date instanceof Carbon
            ? $cutTransfer->transfer_date->toDateString()
            : ($cutTransfer->transfer_date ? Carbon::parse($cutTransfer->transfer_date)->toDateString() : now()->toDateString());

        return [
            'header' => [
                'entry_date'         => $entryDate,
                'entry_type'         => VoucherType::OPERATING,
                'concept'            => "Transferencia de fondos a la Cuenta Única del Tesoro ({$docRef})",
                'currency'           => 'PEN',
                'exchange_rate'      => 1.0000,
                'source_type'        => get_class($cutTransfer),
                'source_id'          => $cutTransfer->id,
                'event_key'          => 'cut_transfer',
                'created_by_user_id' => $cutTransfer->requested_by_user_id ?? 1,
            ],
            'lines' => $lines,
        ];
    }

    /**
     * Map Internal Loan (Disbursement or Repayment).
     * Disbursement:
     *   DEBIT:  1413 (Préstamos al personal y viáticos)
     *   CREDIT: 10411 (Banco Nación) or 1011
     * Repayment:
     *   DEBIT:  10411 or 1011
     *   CREDIT: 1413
     */
    public function mapInternalLoan(RdrInternalLoan $loan, string $type = 'DISBURSEMENT', float $amount = 0.0): ?array
    {
        $amount = $type === 'REPAYMENT' ? round($amount, 2) : round((float) $loan->amount_lent, 2);
        if ($amount <= 0) {
            return null;
        }

        $accLoan = $this->getAccount('1413');
        $accBank = $this->getAccount('10411');

        $docRef = $loan->loan_code ?: "PRES-{$loan->id}";
        $beneficiary = $loan->beneficiary;
        $thirdDoc = $beneficiary?->dni ?? $beneficiary?->email;

        if ($type === 'REPAYMENT') {
            $lines = [
                [
                    'account_id'           => $accBank->id,
                    'debit'                => $amount,
                    'credit'               => 0.00,
                    'glosa'                => "Devolución préstamo interno {$docRef}",
                    'third_party_type'     => $beneficiary ? 'User' : null,
                    'third_party_id'       => $beneficiary?->id,
                    'third_party_document' => $thirdDoc,
                    'cost_center_id'       => $loan->productive_activity_id,
                    'fund_source_id'       => $loan->source_fund_id,
                    'document_reference'   => $loan->repayment_reference ?: $docRef,
                ],
                [
                    'account_id'           => $accLoan->id,
                    'debit'                => 0.00,
                    'credit'               => $amount,
                    'glosa'                => "Cancelación préstamo interno {$docRef}",
                    'third_party_type'     => $beneficiary ? 'User' : null,
                    'third_party_id'       => $beneficiary?->id,
                    'third_party_document' => $thirdDoc,
                    'cost_center_id'       => $loan->productive_activity_id,
                    'fund_source_id'       => $loan->source_fund_id,
                    'document_reference'   => $loan->repayment_reference ?: $docRef,
                ],
            ];

            $concept = "Devolución/Liquidación de préstamo interno {$docRef} - " . ($beneficiary?->name ?? 'Personal');
            $eventKey = 'loan_repayment_' . round($amount, 2);
        } else {
            $lines = [
                [
                    'account_id'           => $accLoan->id,
                    'debit'                => $amount,
                    'credit'               => 0.00,
                    'glosa'                => "Desembolso préstamo interno {$docRef}",
                    'third_party_type'     => $beneficiary ? 'User' : null,
                    'third_party_id'       => $beneficiary?->id,
                    'third_party_document' => $thirdDoc,
                    'cost_center_id'       => $loan->productive_activity_id,
                    'fund_source_id'       => $loan->source_fund_id,
                    'document_reference'   => $docRef,
                ],
                [
                    'account_id'           => $accBank->id,
                    'debit'                => 0.00,
                    'credit'               => $amount,
                    'glosa'                => "Salida de fondos préstamo {$docRef}",
                    'third_party_type'     => $beneficiary ? 'User' : null,
                    'third_party_id'       => $beneficiary?->id,
                    'third_party_document' => $thirdDoc,
                    'cost_center_id'       => $loan->productive_activity_id,
                    'fund_source_id'       => $loan->source_fund_id,
                    'document_reference'   => $docRef,
                ],
            ];

            $concept = "Habilitación préstamo interno/viáticos {$docRef} - " . ($beneficiary?->name ?? 'Personal');
            $eventKey = 'loan_disbursement';
        }

        $entryDate = $loan->issue_date instanceof Carbon
            ? $loan->issue_date->toDateString()
            : ($loan->issue_date ? Carbon::parse($loan->issue_date)->toDateString() : now()->toDateString());

        return [
            'header' => [
                'entry_date'         => $entryDate,
                'entry_type'         => VoucherType::OPERATING,
                'concept'            => $concept,
                'currency'           => 'PEN',
                'exchange_rate'      => 1.0000,
                'source_type'        => get_class($loan),
                'source_id'          => $loan->id,
                'event_key'          => $eventKey,
                'created_by_user_id' => $loan->beneficiary_user_id ?? 1,
            ],
            'lines' => $lines,
        ];
    }

    /**
     * Map autonomous ActivityTransaction (Single Accounting Origin Rule).
     * If transaction has billing_id, buy_id, or sale_note_id => RETURN NULL.
     * Otherwise:
     *   INCOME:  DEBIT 1012, CREDIT 759
     *   EXPENSE: DEBIT 659,  CREDIT 1012
     */
    public function mapActivityTransaction(ActivityTransaction $transaction): ?array
    {
        // CRITICAL SINGLE ORIGIN RULE:
        // Transactions referencing a core commercial voucher MUST NOT generate their own journal entry!
        if (!empty($transaction->billing_id) || !empty($transaction->buy_id) || !empty($transaction->sale_note_id)) {
            return null;
        }

        $amount = round((float) $transaction->amount, 2);
        if ($amount <= 0) {
            return null;
        }

        $isIncome = strtoupper((string) $transaction->transaction_type) === 'INCOME';
        $accLiquidity = $this->getAccount('1012') ?? $this->getAccount('1011');
        $docRef = $transaction->voucher_number ?: $transaction->transaction_code;

        if ($isIncome) {
            $accRevenue = $this->getAccount('759');
            $lines = [
                [
                    'account_id'           => $accLiquidity->id,
                    'debit'                => $amount,
                    'credit'               => 0.00,
                    'glosa'                => "Ingreso directo APE {$docRef}",
                    'cost_center_id'       => $transaction->productive_activity_id,
                    'fund_source_id'       => $transaction->fund_source_id,
                    'document_reference'   => $docRef,
                ],
                [
                    'account_id'           => $accRevenue->id,
                    'debit'                => 0.00,
                    'credit'               => $amount,
                    'glosa'                => "Otros ingresos de gestión APE {$docRef}",
                    'cost_center_id'       => $transaction->productive_activity_id,
                    'fund_source_id'       => $transaction->fund_source_id,
                    'document_reference'   => $docRef,
                ],
            ];

            $concept = "Ingreso directo APE ({$docRef}): " . ($transaction->concept ?: 'Ingreso autónomo de campo');
        } else {
            $accExpense = $this->getAccount('659');
            $lines = [
                [
                    'account_id'           => $accExpense->id,
                    'debit'                => $amount,
                    'credit'               => 0.00,
                    'glosa'                => "Gasto directo campo APE {$docRef}",
                    'cost_center_id'       => $transaction->productive_activity_id,
                    'fund_source_id'       => $transaction->fund_source_id,
                    'document_reference'   => $docRef,
                ],
                [
                    'account_id'           => $accLiquidity->id,
                    'debit'                => 0.00,
                    'credit'               => $amount,
                    'glosa'                => "Egreso de fondos campo {$docRef}",
                    'cost_center_id'       => $transaction->productive_activity_id,
                    'fund_source_id'       => $transaction->fund_source_id,
                    'document_reference'   => $docRef,
                ],
            ];

            $concept = "Gasto directo campo APE ({$docRef}): " . ($transaction->concept ?: 'Declaración Jurada / Gasto menor');
        }

        $entryDate = $transaction->transaction_date instanceof Carbon
            ? $transaction->transaction_date->toDateString()
            : ($transaction->transaction_date ? Carbon::parse($transaction->transaction_date)->toDateString() : now()->toDateString());

        return [
            'header' => [
                'entry_date'         => $entryDate,
                'entry_type'         => VoucherType::OPERATING,
                'concept'            => $concept,
                'currency'           => 'PEN',
                'exchange_rate'      => 1.0000,
                'source_type'        => get_class($transaction),
                'source_id'          => $transaction->id,
                'event_key'          => 'activity_tx',
                'created_by_user_id' => $transaction->registered_by_user_id ?? 1,
            ],
            'lines' => $lines,
        ];
    }

    /**
     * Resolve account model by code with internal caching.
     */
    protected function getAccount(string $code): ChartOfAccount
    {
        if (isset($this->accountCache[$code])) {
            return $this->accountCache[$code];
        }

        $account = ChartOfAccount::query()
            ->where('code', $code)
            ->where('accepts_movements', true)
            ->first();

        if (!$account) {
            // Fallback to any account with code prefix if movement account not found
            $account = ChartOfAccount::query()
                ->where('code', 'LIKE', $code . '%')
                ->where('accepts_movements', true)
                ->first();
        }

        if (!$account) {
            throw new DomainException("Account PCGE '{$code}' not found or does not accept movements.");
        }

        $this->accountCache[$code] = $account;
        return $account;
    }
}
