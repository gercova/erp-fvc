<?php

namespace App\Services\Treasury;

use App\Models\AccountPayable;
use App\Models\ActivityOrder;
use App\Models\Billing;
use App\Models\Buy;
use App\Models\Client;
use App\Models\Provider;
use App\Models\SaleNote;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AgingReportService
{
    /**
     * Get accounts receivable aging view (Clientes con ventas a crédito y pedidos a crédito).
     *
     * Buckets:
     * - current (0 - 30 days)
     * - days_31_60 (31 - 60 days)
     * - days_61_90 (61 - 90 days)
     * - over_90 (> 90 days)
     */
    public function getReceivablesAging(?int $clientId = null, ?string $asOfDate = null): array
    {
        $asOf = $asOfDate ? Carbon::parse($asOfDate)->endOfDay() : Carbon::now()->endOfDay();
        $items = collect();

        // 1. Electronic Billings with Credit
        $billingsQuery = Billing::query()
            ->with(['client', 'typeDocument'])
            ->where('anulado', false)
            ->where(function ($q) {
                $q->where('sunat_forma_pago', 'Credito')
                  ->orWhere('modo_pago', 2)
                  ->orWhere('monto_credito', '>', 0);
            });

        if ($clientId) {
            $billingsQuery->where('idcliente', $clientId);
        }

        $billings = $billingsQuery->get();

        foreach ($billings as $billing) {
            $totalCredit = (float) ($billing->monto_credito > 0 ? $billing->monto_credito : $billing->total);
            
            // Check installments or single amount
            $installments = is_array($billing->cuotas) ? $billing->cuotas : json_decode($billing->cuotas ?? '[]', true);

            if (!empty($installments)) {
                foreach ($installments as $idx => $cuota) {
                    $cuotaAmount = (float) ($cuota['monto'] ?? 0);
                    if ($cuotaAmount <= 0) {
                        continue;
                    }
                    $dueDate = !empty($cuota['fecha']) ? Carbon::parse($cuota['fecha']) : Carbon::parse($billing->fecha_vencimiento ?? $billing->fecha_emision);
                    $days = $this->calculateDays($asOf, $dueDate, Carbon::parse($billing->fecha_emision));

                    $items->push([
                        'source'           => 'BILLING',
                        'document_type'    => $billing->typeDocument?->descripcion ?? 'Factura / Boleta',
                        'document_number'  => "{$billing->serie}-{$billing->correlativo} (Cuota " . ($idx + 1) . ")",
                        'client_id'        => $billing->idcliente,
                        'client_name'      => $billing->client?->rzn_social_usuario ?? 'Cliente',
                        'client_document'  => $billing->client?->num_doc ?? '',
                        'issue_date'       => Carbon::parse($billing->fecha_emision)->format('Y-m-d'),
                        'due_date'         => $dueDate->format('Y-m-d'),
                        'total_amount'     => $cuotaAmount,
                        'paid_amount'      => 0.00,
                        'pending_balance'  => $cuotaAmount,
                        'days_elapsed'     => $days,
                        'bucket'           => $this->resolveBucket($days),
                    ]);
                }
            } else {
                $dueDate = $billing->fecha_vencimiento ? Carbon::parse($billing->fecha_vencimiento) : Carbon::parse($billing->fecha_emision)->addDays(30);
                $days = $this->calculateDays($asOf, $dueDate, Carbon::parse($billing->fecha_emision));

                $items->push([
                    'source'           => 'BILLING',
                    'document_type'    => $billing->typeDocument?->descripcion ?? 'Factura / Boleta',
                    'document_number'  => "{$billing->serie}-{$billing->correlativo}",
                    'client_id'        => $billing->idcliente,
                    'client_name'      => $billing->client?->nombres ?? $billing->client?->rzn_social_usuario ?? 'Cliente',
                    'client_document'  => $billing->client?->nro_documento ?? $billing->client?->num_doc ?? '',
                    'issue_date'       => Carbon::parse($billing->fecha_emision)->format('Y-m-d'),
                    'due_date'         => $dueDate->format('Y-m-d'),
                    'total_amount'     => $totalCredit,
                    'paid_amount'      => 0.00,
                    'pending_balance'  => $totalCredit,
                    'days_elapsed'     => $days,
                    'bucket'           => $this->resolveBucket($days),
                ]);
            }
        }

        // 2. Activity Sales Orders with Credit (activity_orders)
        if (Schema::hasTable('activity_orders')) {
            $ordersQuery = ActivityOrder::query()
                ->with('client')
                ->where('balance_pending', '>', 0)
                ->whereNotIn('status', ['cancelled']);

            if ($clientId) {
                $ordersQuery->where('client_id', $clientId);
            }

            foreach ($ordersQuery->get() as $order) {
                $dueDate = $order->expected_delivery_date
                    ? Carbon::parse($order->expected_delivery_date)
                    : Carbon::parse($order->order_date)->addDays(30);
                $days = $this->calculateDays($asOf, $dueDate, Carbon::parse($order->order_date));
                $balance = (float) $order->balance_pending;

                $items->push([
                    'source'           => 'ACTIVITY_ORDER',
                    'document_type'    => 'Pedido de Actividad APE',
                    'document_number'  => $order->order_code,
                    'client_id'        => $order->client_id,
                    'client_name'      => $order->client?->nombres ?? $order->client?->rzn_social_usuario ?? 'Cliente',
                    'client_document'  => $order->client?->nro_documento ?? $order->client?->num_doc ?? '',
                    'issue_date'       => Carbon::parse($order->order_date)->format('Y-m-d'),
                    'due_date'         => $dueDate->format('Y-m-d'),
                    'total_amount'     => (float) $order->total_amount,
                    'paid_amount'      => (float) $order->advance_payment,
                    'pending_balance'  => $balance,
                    'days_elapsed'     => $days,
                    'bucket'           => $this->resolveBucket($days),
                ]);
            }
        }

        // 3. Sale Notes with Credit
        $notesQuery = SaleNote::query()
            ->with('client')
            ->where(function ($q) {
                $q->where('modo_pago', 2)->orWhere('monto_credito', '>', 0);
            });

        if ($clientId) {
            $notesQuery->where('idcliente', $clientId);
        }

        foreach ($notesQuery->get() as $note) {
            $credit = (float) ($note->monto_credito > 0 ? $note->monto_credito : $note->total);
            if ($credit <= 0) {
                continue;
            }
            $issueDate = Carbon::parse($note->fecha_emision ?? $note->created_at);
            $dueDate = $issueDate->copy()->addDays(30);
            $days = $this->calculateDays($asOf, $dueDate, $issueDate);

            $items->push([
                'source'           => 'SALE_NOTE',
                'document_type'    => 'Nota de Venta',
                'document_number'  => "NV-{$note->serie}-{$note->correlativo}",
                'client_id'        => $note->idcliente,
                'client_name'      => $note->client?->nombres ?? $note->client?->rzn_social_usuario ?? 'Cliente',
                'client_document'  => $note->client?->nro_documento ?? $note->client?->num_doc ?? '',
                'issue_date'       => $issueDate->format('Y-m-d'),
                'due_date'         => $dueDate->format('Y-m-d'),
                'total_amount'     => $credit,
                'paid_amount'      => 0.00,
                'pending_balance'  => $credit,
                'days_elapsed'     => $days,
                'bucket'           => $this->resolveBucket($days),
            ]);
        }

        return $this->formatAgingSummary($items, 'client_id', 'client_name', 'client_document');
    }

    /**
     * Get accounts payable aging view (Proveedores con compras a crédito).
     */
    public function getPayablesAging(?int $providerId = null, ?string $asOfDate = null): array
    {
        $asOf = $asOfDate ? Carbon::parse($asOfDate)->endOfDay() : Carbon::now()->endOfDay();
        $items = collect();

        // 1. accounts_payable table if exists
        $handledBuyIds = [];
        if (Schema::hasTable('accounts_payable')) {
            $apQuery = DB::table('accounts_payable')
                ->join('providers', 'providers.id', '=', 'accounts_payable.idproveedor')
                ->leftJoin('buys', 'buys.id', '=', 'accounts_payable.idcompra')
                ->where('accounts_payable.saldo', '>', 0)
                ->where('accounts_payable.estado', '!=', 'PAGADO')
                ->select(
                    'accounts_payable.*',
                    'providers.nombres as provider_name',
                    'providers.nro_documento as provider_document',
                    'buys.serie as buy_serie',
                    'buys.correlativo as buy_correlativo'
                );

            if ($providerId) {
                $apQuery->where('accounts_payable.idproveedor', $providerId);
            }

            foreach ($apQuery->get() as $ap) {
                $handledBuyIds[] = $ap->idcompra;
                $issueDate = Carbon::parse($ap->fecha_emision);
                $dueDate = Carbon::parse($ap->fecha_vencimiento ?? $ap->fecha_emision);
                $days = $this->calculateDays($asOf, $dueDate, $issueDate);
                $balance = (float) $ap->saldo;

                $items->push([
                    'source'           => 'ACCOUNTS_PAYABLE',
                    'document_type'    => 'Comprobante de Compra',
                    'document_number'  => $ap->buy_serie ? "{$ap->buy_serie}-{$ap->buy_correlativo}" : "C-{$ap->idcompra}",
                    'provider_id'      => $ap->idproveedor,
                    'provider_name'    => $ap->provider_name ?? 'Proveedor',
                    'provider_document'=> $ap->provider_document ?? '',
                    'issue_date'       => $issueDate->format('Y-m-d'),
                    'due_date'         => $dueDate->format('Y-m-d'),
                    'total_amount'     => (float) $ap->monto_total,
                    'paid_amount'      => (float) $ap->monto_pagado,
                    'pending_balance'  => $balance,
                    'days_elapsed'     => $days,
                    'bucket'           => $this->resolveBucket($days),
                ]);
            }
        }

        // 2. Buys directly marked as Credit not yet in accounts_payable
        $buysQuery = Buy::query()
            ->with('provider')
            ->where(function ($q) {
                $q->where('condicion_pago', 'Credito')
                  ->orWhere('monto_credito', '>', 0);
            });

        if (!empty($handledBuyIds)) {
            $buysQuery->whereNotIn('id', $handledBuyIds);
        }

        if ($providerId) {
            $buysQuery->where('idproveedor', $providerId);
        }

        foreach ($buysQuery->get() as $buy) {
            $totalCredit = (float) ($buy->monto_credito > 0 ? $buy->monto_credito : $buy->total);
            if ($totalCredit <= 0) {
                continue;
            }
            $issueDate = Carbon::parse($buy->fecha_emision);
            $dueDate = $buy->fecha_vencimiento ? Carbon::parse($buy->fecha_vencimiento) : $issueDate->copy()->addDays(30);
            $days = $this->calculateDays($asOf, $dueDate, $issueDate);

            $items->push([
                'source'           => 'BUY',
                'document_type'    => 'Factura / Guía de Compra',
                'document_number'  => "{$buy->serie}-{$buy->correlativo}",
                'provider_id'      => $buy->idproveedor,
                'provider_name'    => $buy->provider?->nombres ?? $buy->provider?->rzn_social ?? 'Proveedor',
                'provider_document'=> $buy->provider?->nro_documento ?? $buy->provider?->ruc ?? '',
                'issue_date'       => $issueDate->format('Y-m-d'),
                'due_date'         => $dueDate->format('Y-m-d'),
                'total_amount'     => $totalCredit,
                'paid_amount'      => 0.00,
                'pending_balance'  => $totalCredit,
                'days_elapsed'     => $days,
                'bucket'           => $this->resolveBucket($days),
            ]);
        }

        return $this->formatAgingSummary($items, 'provider_id', 'provider_name', 'provider_document');
    }

    /**
     * Calculate days elapsed relative to asOfDate.
     * If due date is in the past, days overdue = asOf - dueDate.
     * If due date is in the future, days = 0 (current).
     */
    protected function calculateDays(Carbon $asOf, Carbon $dueDate, Carbon $issueDate): int
    {
        if ($asOf->gt($dueDate)) {
            return (int) $dueDate->diffInDays($asOf);
        }
        return 0; // Not yet overdue
    }

    /**
     * Map days elapsed to standard aging bucket.
     */
    protected function resolveBucket(int $days): string
    {
        if ($days <= 30) {
            return 'current'; // 0 to 30 days
        }
        if ($days <= 60) {
            return 'days_31_60'; // 31 to 60 days
        }
        if ($days <= 90) {
            return 'days_61_90'; // 61 to 90 days
        }
        return 'over_90'; // > 90 days
    }

    /**
     * Group items by entity (Client or Provider) and compute bucket subtotals and grand totals.
     */
    protected function formatAgingSummary(Collection $items, string $idKey, string $nameKey, string $docKey): array
    {
        $grouped = [];

        $grandTotals = [
            'total_pending' => 0.00,
            'current'       => 0.00,
            'days_31_60'    => 0.00,
            'days_61_90'    => 0.00,
            'over_90'       => 0.00,
        ];

        foreach ($items as $item) {
            $entityId = $item[$idKey];
            $bucket = $item['bucket'];
            $balance = (float) $item['pending_balance'];

            if (!isset($grouped[$entityId])) {
                $grouped[$entityId] = [
                    'id'            => $entityId,
                    'name'          => $item[$nameKey],
                    'document'      => $item[$docKey],
                    'total_pending' => 0.00,
                    'current'       => 0.00,
                    'days_31_60'    => 0.00,
                    'days_61_90'    => 0.00,
                    'over_90'       => 0.00,
                    'details'       => [],
                ];
            }

            $grouped[$entityId]['total_pending'] += $balance;
            $grouped[$entityId][$bucket] += $balance;
            $grouped[$entityId]['details'][] = $item;

            $grandTotals['total_pending'] += $balance;
            $grandTotals[$bucket] += $balance;
        }

        // Round numbers
        foreach ($grouped as &$g) {
            $g['total_pending'] = round($g['total_pending'], 2);
            $g['current']       = round($g['current'], 2);
            $g['days_31_60']    = round($g['days_31_60'], 2);
            $g['days_61_90']    = round($g['days_61_90'], 2);
            $g['over_90']       = round($g['over_90'], 2);
        }

        $grandTotals['total_pending'] = round($grandTotals['total_pending'], 2);
        $grandTotals['current']       = round($grandTotals['current'], 2);
        $grandTotals['days_31_60']    = round($grandTotals['days_31_60'], 2);
        $grandTotals['days_61_90']    = round($grandTotals['days_61_90'], 2);
        $grandTotals['over_90']       = round($grandTotals['over_90'], 2);

        return [
            'as_of_date'   => now()->format('Y-m-d'),
            'entities'     => array_values($grouped),
            'grand_totals' => $grandTotals,
            'items_count'  => $items->count(),
        ];
    }
}
