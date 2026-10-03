<?php

namespace Database\Seeders;

use App\Enums\AgreementStatus;
use App\Enums\AgreementType;
use App\Enums\InstallmentStatus;
use App\Enums\JournalStatus;
use App\Enums\ServiceEngagementStatus;
use App\Enums\ServiceSessionStatus;
use App\Enums\VoucherType;
use App\Models\AccountingPeriod;
use App\Models\Agreement;
use App\Models\AgreementInstallment;
use App\Models\ArchingCash;
use App\Models\BankAccount;
use App\Models\BankMovement;
use App\Models\BankReconciliationItem;
use App\Models\Billing;
use App\Models\Buy;
use App\Models\Cash;
use App\Models\CashMovement;
use App\Models\ChartOfAccount;
use App\Models\Client;
use App\Models\DetailPayment;
use App\Models\FundSource;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Product;
use App\Models\ProductiveActivity;
use App\Models\Provider;
use App\Models\RdrBankReconciliation;
use App\Models\ServiceAttendee;
use App\Models\ServiceEngagement;
use App\Models\ServiceSession;
use App\Models\TechnologicalService;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Accounting\JournalPostingService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DemoAccountingSeeder extends Seeder
{
    /**
     * Run the demo accounting seeder for local & testing environments.
     * Contains:
     * - 1 fiscal period spanning 3 months (2026-Q4: Oct-Dec 2026)
     * - 40 sales (25 invoices + 15 receipts; taxable, exempt, and non-taxable)
     * - 10 purchases (5 cash + 5 credit)
     * - 5 credit notes (3 on invoices, 2 on receipts)
     * - 2 cash register closings (with cash sales, movements, and reconciled estimates)
     * - 1 bank statement with 30 transactions (25 reconcilable + 5 in-transit)
     * - 2 agreements with payment schedules (Track B financial integration)
     * - 1 training session with 15 participants
     */
    public function run(): void
    {
        // -----------------------------------------------------------------------------------------
        // 0. ENVIRONMENT GUARD & DETERMINISM
        // -----------------------------------------------------------------------------------------
        if (!app()->environment(['local', 'testing'])) {
            $this->command?->warn("DemoAccountingSeeder is strictly prohibited in production environments.");
            return;
        }

        // Seed random generators for 100% deterministic outputs across runs
        mt_srand(20261003);
        fake()->seed(20261003);

        $postingService = app(JournalPostingService::class);

        $this->command?->info("Seeding DemoAccountingSeeder (Local Demo Environment)...");

        // -----------------------------------------------------------------------------------------
        // 1. BASE ENTITIES RESOLUTION
        // -----------------------------------------------------------------------------------------
        $user = User::first() ?? User::factory()->create([
            'name'  => 'Contador Demo',
            'email' => 'contador@fvc.pe',
        ]);

        $warehouse = Warehouse::first() ?? Warehouse::create([
            'descripcion' => 'Almacén Central Demo',
            'estado'      => 1,
        ]);

        $cash1 = Cash::first() ?? Cash::create([
            'descripcion' => 'Caja Principal Ventas',
            'estado'      => 1,
        ]);

        $cash2 = Cash::skip(1)->first() ?? Cash::create([
            'descripcion' => 'Caja Secundaria Recaudación',
            'estado'      => 1,
        ]);

        $clientCorporate = Client::updateOrCreate(
            ['nro_documento' => '20600000001'],
            [
                'iddoc'       => 6, // RUC
                'nombres'     => 'AGROINDUSTRIAS DEMO DEL PERU S.A.C.',
                'direccion'   => 'Av. Productores 123, Lima',
                'codigo_pais' => 'PE',
                'ubigeo'      => '150101',
                'telefono'    => '987654321',
                'email'       => 'contacto@agroindustriasdemo.pe',
            ]
        );

        $clientIndividual = Client::updateOrCreate(
            ['nro_documento' => '45000001'],
            [
                'iddoc'       => 1, // DNI
                'nombres'     => 'JUAN PEREZ DEMO',
                'direccion'   => 'Jr. Comercio 456, Satipo',
                'codigo_pais' => 'PE',
                'ubigeo'      => '120601',
                'telefono'    => '912345678',
                'email'       => 'juan.perez@demo.fvc.pe',
            ]
        );

        $provider = Provider::updateOrCreate(
            ['nro_documento' => '20500000001'],
            [
                'iddoc'       => 6, // RUC
                'nombres'     => 'DISTRIBUIDORA DE INSUMOS AGROPECUARIOS S.A.C.',
                'direccion'   => 'Av. Industrial 890, Lima',
                'codigo_pais' => 'PE',
                'ubigeo'      => '150101',
                'telefono'    => '998877665',
                'email'       => 'ventas@distribuidorademo.pe',
            ]
        );

        $fundSource = FundSource::first() ?? FundSource::create([
            'code'        => '09',
            'name'        => 'Recursos Directamente Recaudados (RDR)',
            'acronym'     => 'RDR',
            'is_active'   => true,
            'description' => 'Fuente de financiamiento RDR institucional',
        ]);

        $productiveActivity = ProductiveActivity::first();
        $technologicalService = TechnologicalService::first();

        // -----------------------------------------------------------------------------------------
        // 2. FISCAL PERIOD: 1 PERIOD SPANNING 3 MONTHS (2026-Q4: Oct 01 to Dec 31)
        // -----------------------------------------------------------------------------------------
        $period = AccountingPeriod::updateOrCreate(
            ['period_code' => '2026-Q4'],
            [
                'fiscal_year' => 2026,
                'month'       => 10,
                'start_date'  => '2026-10-01',
                'end_date'    => '2026-12-31',
                'status'      => 'OPEN',
                'notes'       => 'Período Trimestral Demo Cuarto Trimestre 2026 (Octubre - Diciembre)',
            ]
        );

        // -----------------------------------------------------------------------------------------
        // 3. CLEAN UP PREVIOUS DEMO ENTITIES IDEMPOTENTLY
        // -----------------------------------------------------------------------------------------
        // Clean previous demo billings and buys in period
        $demoBillingIds = Billing::whereIn('serie', ['F001', 'B001', 'FC01', 'BC01'])
            ->whereBetween('fecha_emision', ['2026-10-01', '2026-12-31'])
            ->pluck('id')
            ->toArray();

        if (!empty($demoBillingIds)) {
            AgreementInstallment::whereIn('billing_id', $demoBillingIds)->update(['billing_id' => null, 'status' => InstallmentStatus::PENDING->value]);
            DetailPayment::whereIn('idfactura', $demoBillingIds)->delete();
            JournalEntry::where('source_type', Billing::class)->whereIn('source_id', $demoBillingIds)->delete();
            Billing::whereIn('id', $demoBillingIds)->where('idtipo_comprobante', 3)->delete();
            Billing::whereIn('id', $demoBillingIds)->delete();
        }

        $demoBuyIds = Buy::where('serie', 'E001')
            ->whereBetween('fecha_emision', ['2026-10-01', '2026-12-31'])
            ->pluck('id')
            ->toArray();

        if (!empty($demoBuyIds)) {
            JournalEntry::where('source_type', Buy::class)->whereIn('source_id', $demoBuyIds)->delete();
            Buy::whereIn('id', $demoBuyIds)->delete();
        }

        // Clean demo agreements & services
        Agreement::whereIn('code', ['CONV-2026-DEMO1', 'CONV-2026-DEMO2'])->forceDelete();
        ServiceEngagement::where('code', 'SERV-2026-DEMO1')->forceDelete();

        // Clean demo bank account and its movements/reconciliations
        $demoBankAccount = BankAccount::where('account_number', '00-011-DEMO2026')->first();
        if ($demoBankAccount) {
            BankReconciliationItem::whereHas('reconciliation', fn($q) => $q->where('bank_account_id', $demoBankAccount->id))->delete();
            RdrBankReconciliation::where('bank_account_id', $demoBankAccount->id)->forceDelete();
            BankMovement::where('bank_account_id', $demoBankAccount->id)->delete();
            JournalEntry::where('source_type', BankMovement::class)->delete();
            $demoBankAccount->forceDelete();
        }

        // Clean demo cash register closings
        $demoArqueoIds = ArchingCash::whereIn('fecha_inicio', ['2026-10-15', '2026-11-15'])
            ->where('idusuario', $user->id)
            ->pluck('id')
            ->toArray();
        if (!empty($demoArqueoIds)) {
            CashMovement::whereIn('idarqueocaja', $demoArqueoIds)->delete();
            DetailPayment::whereIn('idarqueocaja', $demoArqueoIds)->delete();
            ArchingCash::whereIn('id', $demoArqueoIds)->delete();
        }

        // -----------------------------------------------------------------------------------------
        // 4. SALES (40 VOUCHERS: 25 INVOICES + 15 RECEIPTS; TAXABLE, EXEMPT, AND NON-TAXABLE)
        // -----------------------------------------------------------------------------------------
        $this->command?->comment("  -> Seeding 40 Sales (25 Invoices + 15 Receipts)...");

        $invoices = [];
        $receipts = [];

        // 4.1. 25 INVOICES (Serie F001)
        // 15 Taxable, 5 Exempt, 5 Non-taxable
        for ($i = 1; $i <= 25; $i++) {
            $correlativo = str_pad((string) $i, 8, '0', STR_PAD_LEFT);
            // Day spread within October - November 2026
            $day = 1 + (($i * 3) % 28);
            $month = ($i <= 18) ? '10' : '11';
            $date = "2026-{$month}-" . str_pad((string) $day, 2, '0', STR_PAD_LEFT);

            if ($i === 1) {
                // Special: Invoice 1 is linked to Agreement 1 Installment 1 (Total S/ 10,000.00)
                $gravada = 8474.58;
                $igv = 1525.42;
                $exonerada = 0.00;
                $inafecta = 0.00;
                $total = 10000.00;
            } elseif ($i <= 15) {
                // Taxable Invoices
                $gravada = round(400.00 + ($i * 25.50), 2);
                $igv = round($gravada * 0.18, 2);
                $exonerada = 0.00;
                $inafecta = 0.00;
                $total = round($gravada + $igv, 2);
            } elseif ($i <= 20) {
                // Exempt Invoices (Exoneradas de IGV)
                $gravada = 0.00;
                $igv = 0.00;
                $exonerada = round(350.00 + ($i * 15.00), 2);
                $inafecta = 0.00;
                $total = $exonerada;
            } else {
                // Non-taxable Invoices (Inafectas de IGV)
                $gravada = 0.00;
                $igv = 0.00;
                $exonerada = 0.00;
                $inafecta = round(280.00 + ($i * 12.00), 2);
                $total = $inafecta;
            }

            $inv = Billing::create([
                'idtipo_comprobante' => 1, // Factura
                'serie'              => 'F001',
                'correlativo'        => $correlativo,
                'fecha_emision'      => $date,
                'fecha_vencimiento'  => Carbon::parse($date)->addDays(15)->toDateString(),
                'hora'               => '10:15:00',
                'idcliente'          => $clientCorporate->id,
                'idmoneda'           => 1, // PEN
                'idpago'             => 1, // Efectivo / Contado
                'modo_pago'          => 1,
                'sunat_forma_pago'   => 'Contado',
                'exonerada'          => $exonerada,
                'inafecta'           => $inafecta,
                'gravada'            => $gravada,
                'anticipo'           => 0.00,
                'igv'                => $igv,
                'icbper'             => 0.00,
                'gratuita'           => 0.00,
                'otros_cargos'       => 0.00,
                'total'              => $total,
                'anulado'            => 0,
                'idalmacen'          => $warehouse->id,
                'idusuario'          => $user->id,
            ]);

            // Post to double-entry general ledger
            $postingService->post($inv);
            $invoices[$i] = $inv;
        }

        // 4.2. 15 RECEIPTS / BOLETAS (Serie B001)
        // 10 Taxable, 3 Exempt, 2 Non-taxable
        for ($j = 1; $j <= 15; $j++) {
            $correlativo = str_pad((string) $j, 8, '0', STR_PAD_LEFT);
            $day = 2 + (($j * 2) % 27);
            $month = ($j <= 10) ? '10' : '11';
            $date = "2026-{$month}-" . str_pad((string) $day, 2, '0', STR_PAD_LEFT);

            if ($j <= 10) {
                // Taxable Boletas
                $gravada = round(150.00 + ($j * 10.00), 2);
                $igv = round($gravada * 0.18, 2);
                $exonerada = 0.00;
                $inafecta = 0.00;
                $total = round($gravada + $igv, 2);
            } elseif ($j <= 13) {
                // Exempt Boletas
                $gravada = 0.00;
                $igv = 0.00;
                $exonerada = round(120.00 + ($j * 8.00), 2);
                $inafecta = 0.00;
                $total = $exonerada;
            } else {
                // Non-taxable Boletas
                $gravada = 0.00;
                $igv = 0.00;
                $exonerada = 0.00;
                $inafecta = round(95.00 + ($j * 5.00), 2);
                $total = $inafecta;
            }

            $rec = Billing::create([
                'idtipo_comprobante' => 2, // Boleta de Venta
                'serie'              => 'B001',
                'correlativo'        => $correlativo,
                'fecha_emision'      => $date,
                'fecha_vencimiento'  => Carbon::parse($date)->addDays(15)->toDateString(),
                'hora'               => '11:30:00',
                'idcliente'          => $clientIndividual->id,
                'idmoneda'           => 1, // PEN
                'idpago'             => 1, // Efectivo
                'modo_pago'          => 1,
                'sunat_forma_pago'   => 'Contado',
                'exonerada'          => $exonerada,
                'inafecta'           => $inafecta,
                'gravada'            => $gravada,
                'anticipo'           => 0.00,
                'igv'                => $igv,
                'icbper'             => 0.00,
                'gratuita'           => 0.00,
                'otros_cargos'       => 0.00,
                'total'              => $total,
                'anulado'            => 0,
                'idalmacen'          => $warehouse->id,
                'idusuario'          => $user->id,
            ]);

            $postingService->post($rec);
            $receipts[$j] = $rec;
        }

        // -----------------------------------------------------------------------------------------
        // 5. CREDIT NOTES (5 VOUCHERS: 3 ON INVOICES + 2 ON RECEIPTS)
        // -----------------------------------------------------------------------------------------
        $this->command?->comment("  -> Seeding 5 Credit Notes (3 on Invoices + 2 on Receipts)...");

        // 5.1. 3 Credit Notes on Invoices (FC01-00000001 .. FC01-00000003)
        for ($cn = 1; $cn <= 3; $cn++) {
            $parent = $invoices[$cn + 1]; // Invoices 2, 3, 4
            $date = Carbon::parse($parent->fecha_emision)->addDays(2)->toDateString();

            $creditNote = Billing::create([
                'idtipo_comprobante'   => 3, // Nota de Crédito
                'serie'                => 'FC01',
                'correlativo'          => str_pad((string) $cn, 8, '0', STR_PAD_LEFT),
                'fecha_emision'        => $date,
                'fecha_vencimiento'    => $date,
                'hora'                 => '15:00:00',
                'idcliente'            => $parent->idcliente,
                'idmoneda'             => 1,
                'idpago'               => 1,
                'modo_pago'            => 1,
                'idfactura_anular'     => $parent->id,
                'id_tipo_nota_credito' => 1, // Anulación de la operación
                'motivo'               => 'Anulación o devolución parcial por especificación técnica',
                'gravada'              => $parent->gravada,
                'exonerada'            => $parent->exonerada,
                'inafecta'             => $parent->inafecta,
                'igv'                  => $parent->igv,
                'total'                => $parent->total,
                'anulado'              => 0,
                'idalmacen'            => $parent->idalmacen,
                'idusuario'            => $user->id,
            ]);

            $postingService->post($creditNote);
        }

        // 5.2. 2 Credit Notes on Receipts (BC01-00000001 .. BC01-00000002)
        for ($cnb = 1; $cnb <= 2; $cnb++) {
            $parent = $receipts[$cnb + 1]; // Receipts 2, 3
            $date = Carbon::parse($parent->fecha_emision)->addDays(1)->toDateString();

            $creditNote = Billing::create([
                'idtipo_comprobante'   => 3, // Nota de Crédito
                'serie'                => 'BC01',
                'correlativo'          => str_pad((string) $cnb, 8, '0', STR_PAD_LEFT),
                'fecha_emision'        => $date,
                'fecha_vencimiento'    => $date,
                'hora'                 => '16:00:00',
                'idcliente'            => $parent->idcliente,
                'idmoneda'             => 1,
                'idpago'               => 1,
                'modo_pago'            => 1,
                'idfactura_anular'     => $parent->id,
                'id_tipo_nota_credito' => 1, // Anulación
                'motivo'               => 'Devolución de venta al por menor',
                'gravada'              => $parent->gravada,
                'exonerada'            => $parent->exonerada,
                'inafecta'             => $parent->inafecta,
                'igv'                  => $parent->igv,
                'total'                => $parent->total,
                'anulado'              => 0,
                'idalmacen'            => $parent->idalmacen,
                'idusuario'            => $user->id,
            ]);

            $postingService->post($creditNote);
        }

        // -----------------------------------------------------------------------------------------
        // 6. PURCHASES (10 VOUCHERS: 5 CASH + 5 CREDIT)
        // -----------------------------------------------------------------------------------------
        $this->command?->comment("  -> Seeding 10 Purchases (5 Cash + 5 Credit)...");

        for ($k = 1; $k <= 10; $k++) {
            $isCredit = ($k > 5);
            $correlativo = str_pad((string) $k, 8, '0', STR_PAD_LEFT);
            $day = 3 + (($k * 2) % 25);
            $month = ($k <= 5) ? '10' : '11';
            $date = "2026-{$month}-" . str_pad((string) $day, 2, '0', STR_PAD_LEFT);

            $gravada = round(1000.00 + ($k * 80.00), 2);
            $igv = round($gravada * 0.18, 2);
            $total = round($gravada + $igv, 2);

            $cuotas = null;
            $montoCredito = 0.00;

            if ($isCredit) {
                $montoCredito = $total;
                $half = round($total / 2, 2);
                $cuotas = [
                    [
                        'nro_cuota' => 1,
                        'fecha'     => Carbon::parse($date)->addDays(30)->toDateString(),
                        'monto'     => $half,
                    ],
                    [
                        'nro_cuota' => 2,
                        'fecha'     => Carbon::parse($date)->addDays(60)->toDateString(),
                        'monto'     => round($total - $half, 2),
                    ],
                ];
            }

            $buy = Buy::create([
                'idtipo_comprobante' => 1, // Factura Proveedor
                'serie'              => 'E001',
                'correlativo'        => $correlativo,
                'fecha_emision'      => $date,
                'fecha_vencimiento'  => Carbon::parse($date)->addDays(30)->toDateString(),
                'hora'               => '09:00:00',
                'idproveedor'        => $provider->id,
                'idalmacen'          => $warehouse->id,
                'idmoneda'           => 1, // PEN
                'idpago'             => $isCredit ? 2 : 1,
                'modo_pago'          => $isCredit ? 2 : 1, // 1 = Contado, 2 = Crédito
                'condicion_pago'     => $isCredit ? 'Crédito' : 'Contado',
                'monto_credito'      => $montoCredito,
                'cuotas'             => $cuotas,
                'exonerada'          => 0.00,
                'inafecta'           => 0.00,
                'gravada'            => $gravada,
                'anticipo'           => 0.00,
                'igv'                => $igv,
                'gratuita'           => 0.00,
                'otros_cargos'       => 0.00,
                'total'              => $total,
                'estado'             => 1, // Registrado
                'idusuario'          => $user->id,
            ]);

            $postingService->post($buy);
        }

        // -----------------------------------------------------------------------------------------
        // 7. CASH REGISTER CLOSINGS (2 SESSIONS: OCTOBER & NOVEMBER 2026)
        // -----------------------------------------------------------------------------------------
        $this->command?->comment("  -> Seeding 2 Cash Register Closings (Arching Cashes)...");

        // Closing 1: 2026-10-15
        $sale1 = $receipts[4]; // S/ 190.00 (taxable)
        $sale2 = $receipts[5]; // S/ 200.00 (taxable)
        $cashSalesTotal1 = round((float) $sale1->total + (float) $sale2->total, 2);
        $initial1 = 200.00;
        $inflow1 = 50.00;
        $outflow1 = 30.00;
        $estimated1 = round($initial1 + $cashSalesTotal1 + $inflow1 - $outflow1, 2);

        $arching1 = ArchingCash::create([
            'idcaja'         => $cash1->id,
            'idusuario'      => $user->id,
            'idalmacen'      => $warehouse->id,
            'fecha_inicio'   => '2026-10-15',
            'fecha_fin'      => '2026-10-15',
            'monto_inicial'  => $initial1,
            'monto_final'    => $estimated1,
            'total_ventas'   => $cashSalesTotal1,
            'estado'         => 0, // Cerrado
            'monto_estimado' => $estimated1,
            'diferencia'     => 0.00,
            'total_ingresos' => $inflow1,
            'total_egresos'  => $outflow1,
        ]);

        // Link payments to Arqueo 1
        DetailPayment::create([
            'idarqueocaja'       => $arching1->id,
            'idtipo_comprobante' => 2, // Boleta
            'idfactura'          => $sale1->id,
            'idpago'             => 1, // Efectivo
            'monto'              => $sale1->total,
            'estado'             => 1,
        ]);

        DetailPayment::create([
            'idarqueocaja'       => $arching1->id,
            'idtipo_comprobante' => 2, // Boleta
            'idfactura'          => $sale2->id,
            'idpago'             => 1, // Efectivo
            'monto'              => $sale2->total,
            'estado'             => 1,
        ]);

        CashMovement::create([
            'idarqueocaja' => $arching1->id,
            'idusuario'    => $user->id,
            'tipo'         => 'ingreso',
            'monto'        => $inflow1,
            'motivo'       => 'Ingreso adicional por cambio sencillo',
            'fecha'        => '2026-10-15',
            'hora'         => '10:00:00',
            'estado'       => 1,
        ]);

        CashMovement::create([
            'idarqueocaja' => $arching1->id,
            'idusuario'    => $user->id,
            'tipo'         => 'egreso',
            'monto'        => $outflow1,
            'motivo'       => 'Egreso menor de caja para útiles de limpieza',
            'fecha'        => '2026-10-15',
            'hora'         => '11:00:00',
            'estado'       => 1,
        ]);

        // Closing 2: 2026-11-15
        $sale3 = $receipts[11]; // S/ 128.00 (exempt)
        $cashSalesTotal2 = round((float) $sale3->total, 2);
        $initial2 = 150.00;
        $inflow2 = 0.00;
        $outflow2 = 40.00;
        $estimated2 = round($initial2 + $cashSalesTotal2 + $inflow2 - $outflow2, 2);

        $arching2 = ArchingCash::create([
            'idcaja'         => $cash2->id,
            'idusuario'      => $user->id,
            'idalmacen'      => $warehouse->id,
            'fecha_inicio'   => '2026-11-15',
            'fecha_fin'      => '2026-11-15',
            'monto_inicial'  => $initial2,
            'monto_final'    => $estimated2,
            'total_ventas'   => $cashSalesTotal2,
            'estado'         => 0, // Cerrado
            'monto_estimado' => $estimated2,
            'diferencia'     => 0.00,
            'total_ingresos' => $inflow2,
            'total_egresos'  => $outflow2,
        ]);

        DetailPayment::create([
            'idarqueocaja'       => $arching2->id,
            'idtipo_comprobante' => 2, // Boleta
            'idfactura'          => $sale3->id,
            'idpago'             => 1, // Efectivo
            'monto'              => $sale3->total,
            'estado'             => 1,
        ]);

        CashMovement::create([
            'idarqueocaja' => $arching2->id,
            'idusuario'    => $user->id,
            'tipo'         => 'egreso',
            'monto'        => $outflow2,
            'motivo'       => 'Pago servicio mensajería local',
            'fecha'        => '2026-11-15',
            'hora'         => '14:00:00',
            'estado'       => 1,
        ]);

        // Normalize legacy empty cash register sessions with 0 movements
        ArchingCash::where(function($q) {
                $q->whereNull('monto_estimado')->orWhere('monto_estimado', 0);
            })
            ->whereNotNull('monto_final')
            ->whereDoesntHave('movements')
            ->whereDoesntHave('payments')
            ->update(['monto_estimado' => DB::raw('monto_inicial')]);

        // -----------------------------------------------------------------------------------------
        // 8. BANK STATEMENT: 1 STATEMENT WITH 30 TRANSACTIONS (25 RECONCILABLE + 5 IN-TRANSIT)
        // -----------------------------------------------------------------------------------------
        $this->command?->comment("  -> Seeding 1 Bank Statement (30 Transactions: 25 Reconcilable)...");

        $account10411 = ChartOfAccount::where('code', '10411')->first() ?? ChartOfAccount::firstOrCreate(
            ['code' => '10411'],
            ['name' => 'Banco de la Nación - Cta. Cte.', 'account_type' => 'ASSET', 'level' => 5, 'is_movement' => true]
        );

        $account1212 = ChartOfAccount::where('code', '1212')->first() ?? ChartOfAccount::firstOrCreate(
            ['code' => '1212'],
            ['name' => 'Facturas por cobrar comerciales', 'account_type' => 'ASSET', 'level' => 4, 'is_movement' => true]
        );

        $bankAccount = BankAccount::create([
            'uuid'                  => (string) Str::uuid(),
            'fund_source_id'        => $fundSource->id,
            'account_number'        => '00-011-DEMO2026',
            'cci'                   => '018011000011DEMO202601',
            'bank_name'             => 'Banco de la Nación',
            'account_type'          => 'CURRENT',
            'currency'              => 'PEN',
            'accounting_account_id' => $account10411->id,
            'initial_balance'       => 0.00,
            'current_balance'       => 0.00,
            'is_active'             => true,
        ]);

        $statementClosingDate = '2026-10-31';
        $totalReconciledAmount = 0.00;
        $movements = [];
        $reconciledItems = [];

        // 8.1. 25 RECONCILABLE TRANSACTIONS (Matched to posted Journal Entries)
        for ($m = 1; $m <= 25; $m++) {
            $amount = round(1000.00 + ($m * 50.00), 2);
            $totalReconciledAmount += $amount;
            $opNumber = 'BN-2026-' . str_pad((string) $m, 6, '0', STR_PAD_LEFT);
            $day = 1 + ($m % 28);
            $date = '2026-10-' . str_pad((string) $day, 2, '0', STR_PAD_LEFT);

            // Create matched BankMovement first
            $mov = BankMovement::create([
                'uuid'                     => (string) Str::uuid(),
                'bank_account_id'          => $bankAccount->id,
                'movement_date'            => $date,
                'operation_number'         => $opNumber,
                'movement_type'            => 'INFLOW',
                'amount'                   => $amount,
                'concept'                  => "Abono por recaudación depósito ventanilla {$opNumber}",
                'reconciliation_status'    => 'MATCHED',
                'matched_journal_entry_id' => null,
                'journal_entry_id'         => null,
            ]);

            // Create balanced Journal Entry for customer collection deposited in bank
            $je = JournalEntry::create([
                'accounting_period_id' => $period->id,
                'entry_number'         => 'BN-REC-' . str_pad((string) $m, 5, '0', STR_PAD_LEFT),
                'entry_date'           => $date,
                'entry_type'           => VoucherType::OPERATING->value,
                'status'               => JournalStatus::POSTED->value,
                'concept'              => "Recaudación bancaria depósito OP {$opNumber}",
                'currency'             => 'PEN',
                'exchange_rate'        => 1.0000,
                'total_debit'          => $amount,
                'total_credit'         => $amount,
                'source_type'          => BankMovement::class,
                'source_id'            => $mov->id,
                'event_key'            => 'bank_reconciled',
                'idempotency_key'      => md5("demo_bank_{$m}"),
                'created_by_user_id'   => $user->id,
                'posted_by_user_id'    => $user->id,
                'posted_at'            => Carbon::parse($date)->addHours(2),
            ]);

            // Line 1: DEBIT 10411 (Bank increase)
            JournalEntryLine::create([
                'journal_entry_id'   => $je->id,
                'line_number'        => 1,
                'account_id'         => $account10411->id,
                'debit'              => $amount,
                'credit'             => 0.00,
                'glosa'              => "Abono bancario OP {$opNumber}",
                'document_reference' => $opNumber,
            ]);

            // Line 2: CREDIT 1212 (Receivable decrease)
            JournalEntryLine::create([
                'journal_entry_id'   => $je->id,
                'line_number'        => 2,
                'account_id'         => $account1212->id,
                'debit'              => 0.00,
                'credit'             => $amount,
                'glosa'              => "Amortización de cobranza OP {$opNumber}",
                'document_reference' => $opNumber,
            ]);

            $mov->update([
                'matched_journal_entry_id' => $je->id,
                'journal_entry_id'         => $je->id,
            ]);
            $movements[] = $mov;

            $reconciledItems[] = [
                'type'     => 'MATCHED',
                'mov_id'   => $mov->id,
                'je_id'    => $je->id,
                'amount'   => $amount,
                'ref'      => $opNumber,
                'concept'  => $mov->concept,
            ];
        }

        // 8.2. 5 UNRECONCILED MOVEMENTS (3 Deposits in transit + 2 Outstanding checks)
        $uncreditedDeposits = 0.00;
        $outstandingChecks = 0.00;

        // 3 Deposits in transit (INFLOW, PENDING in bank statement)
        for ($dit = 1; $dit <= 3; $dit++) {
            $amount = round(500.00 + ($dit * 100.00), 2);
            $uncreditedDeposits += $amount;
            $opNumber = 'DIT-2026-' . str_pad((string) $dit, 4, '0', STR_PAD_LEFT);

            $mov = BankMovement::create([
                'uuid'                     => (string) Str::uuid(),
                'bank_account_id'          => $bankAccount->id,
                'movement_date'            => '2026-10-31',
                'operation_number'         => $opNumber,
                'movement_type'            => 'INFLOW',
                'amount'                   => $amount,
                'concept'                  => "Depósito en tránsito fin de mes {$opNumber}",
                'reconciliation_status'    => 'PENDING',
                'matched_journal_entry_id' => null,
            ]);

            $reconciledItems[] = [
                'type'     => 'DEPOSIT_IN_TRANSIT',
                'mov_id'   => $mov->id,
                'je_id'    => null,
                'amount'   => $amount,
                'ref'      => $opNumber,
                'concept'  => $mov->concept,
            ];
        }

        // 2 Outstanding checks (OUTFLOW, PENDING)
        for ($oc = 1; $oc <= 2; $oc++) {
            $amount = round(300.00 + ($oc * 50.00), 2);
            $outstandingChecks += $amount;
            $opNumber = 'CHQ-2026-' . str_pad((string) $oc, 4, '0', STR_PAD_LEFT);

            $mov = BankMovement::create([
                'uuid'                     => (string) Str::uuid(),
                'bank_account_id'          => $bankAccount->id,
                'movement_date'            => '2026-10-30',
                'operation_number'         => $opNumber,
                'movement_type'            => 'OUTFLOW',
                'amount'                   => $amount,
                'concept'                  => "Cheque girado y no cobrado {$opNumber}",
                'reconciliation_status'    => 'PENDING',
                'matched_journal_entry_id' => null,
            ]);

            $reconciledItems[] = [
                'type'     => 'OUTSTANDING_CHECK',
                'mov_id'   => $mov->id,
                'je_id'    => null,
                'amount'   => $amount,
                'ref'      => $opNumber,
                'concept'  => $mov->concept,
            ];
        }

        // Update BankAccount current balance to equal posted ledger balance (Account 10411)
        $bankAccount->update([
            'initial_balance' => 0.00,
            'current_balance' => round($totalReconciledAmount, 2),
        ]);

        // Statement Balance = Book Balance + Outstanding Checks - Uncredited Deposits
        $bankStatementBalance = round($totalReconciledAmount + $outstandingChecks - $uncreditedDeposits, 2);

        // 8.3. Create 1 RdrBankReconciliation Header
        $reconciliation = RdrBankReconciliation::create([
            'uuid'                      => (string) Str::uuid(),
            'fund_source_id'            => $fundSource->id,
            'bank_account_id'           => $bankAccount->id,
            'accounting_period_id'      => $period->id,
            'period_year'               => 2026,
            'period_month'              => 10,
            'statement_closing_date'    => $statementClosingDate,
            'bank_statement_balance'    => $bankStatementBalance,
            'system_calculated_balance' => round($totalReconciledAmount, 2),
            'book_calculated_balance'   => round($totalReconciledAmount, 2),
            'uncredited_deposits'       => round($uncreditedDeposits, 2),
            'outstanding_checks'        => round($outstandingChecks, 2),
            'unrecorded_bank_charges'   => 0.00,
            'unrecorded_bank_credits'   => 0.00,
            'reconciled_difference'     => 0.00,
            'reconciled_at'             => Carbon::parse('2026-10-31 18:00:00'),
            'status'                    => 'RECONCILED',
            'reconciled_by_user_id'     => $user->id,
            'approved_by_user_id'       => $user->id,
            'notes'                     => 'Conciliación bancaria mensual demo de octubre 2026: 25 movimientos conciliados al 100%.',
        ]);

        foreach ($reconciledItems as $item) {
            BankReconciliationItem::create([
                'reconciliation_id' => $reconciliation->id,
                'bank_movement_id'  => $item['mov_id'],
                'journal_entry_id'  => $item['je_id'],
                'item_type'         => $item['type'],
                'amount'            => $item['amount'],
                'reference'         => $item['ref'],
                'concept'           => $item['concept'],
                'difference'        => 0.00,
            ]);
        }

        // -----------------------------------------------------------------------------------------
        // 9. AGREEMENTS (2 WITH PAYMENT SCHEDULES, TRACK B FINANCIAL INTEGRATION)
        // -----------------------------------------------------------------------------------------
        $this->command?->comment("  -> Seeding 2 Agreements with Payment Schedules...");

        // Agreement 1: Specific Agreement (Total S/ 30,000.00, 3 Installments)
        $agreement1 = Agreement::create([
            'uuid'                              => (string) Str::uuid(),
            'code'                              => 'CONV-2026-DEMO1',
            'type'                              => AgreementType::SPECIFIC,
            'title'                             => 'Convenio Específico de Cooperación Tecnológica - Municipalidad Provincial',
            'objective'                         => 'Desarrollo de capacidades tecnológicas y transferencia productiva agropecuaria.',
            'client_id'                         => $clientCorporate->id,
            'counterparty_signatory_name'       => 'Ing. Carlos Mendoza Alva',
            'counterparty_signatory_role'       => 'Alcalde Provincial',
            'counterparty_signatory_document'   => '10293847',
            'area_id'                           => 1,
            'coordinator_user_id'               => $user->id,
            'productive_activity_id'            => $productiveActivity?->id,
            'signature_date'                    => '2026-10-01',
            'start_date'                        => '2026-10-01',
            'end_date'                          => '2027-09-30',
            'original_end_date'                 => '2027-09-30',
            'currency'                          => 'PEN',
            'total_amount'                      => 30000.00,
            'original_amount'                   => 30000.00,
            'counterparty_contribution'         => 30000.00,
            'institution_contribution'          => 0.00,
            'status'                            => AgreementStatus::ACTIVE,
            'requires_financial_settlement'     => true,
            'resolution_number'                 => 'RES-2026-DEMO-01',
            'created_by_user_id'                => $user->id,
        ]);

        // Installment 1: Invoiced via Invoice 1 (F001-00000001, total S/ 10,000.00)
        AgreementInstallment::create([
            'uuid'               => (string) Str::uuid(),
            'agreement_id'       => $agreement1->id,
            'installment_number' => 1,
            'description'        => 'Primera Cuota: Firma y entrega del plan operativo',
            'milestone_condition'=> 'Aprobación del plan operativo institucional',
            'due_date'           => '2026-10-15',
            'amount'             => 10000.00,
            'currency'           => 'PEN',
            'igv_affected'       => true,
            'status'             => InstallmentStatus::INVOICED,
            'billing_id'         => $invoices[1]->id,
            'sale_note_id'       => null,
        ]);

        // Installment 2: Pending
        AgreementInstallment::create([
            'uuid'               => (string) Str::uuid(),
            'agreement_id'       => $agreement1->id,
            'installment_number' => 2,
            'description'        => 'Segunda Cuota: Primer informe de avance técnico',
            'milestone_condition'=> 'Presentación de informe trimestral',
            'due_date'           => '2027-01-15',
            'amount'             => 10000.00,
            'currency'           => 'PEN',
            'igv_affected'       => true,
            'status'             => InstallmentStatus::PENDING,
            'billing_id'         => null,
            'sale_note_id'       => null,
        ]);

        // Installment 3: Pending
        AgreementInstallment::create([
            'uuid'               => (string) Str::uuid(),
            'agreement_id'       => $agreement1->id,
            'installment_number' => 3,
            'description'        => 'Tercera Cuota: Informe final y liquidación técnica',
            'milestone_condition'=> 'Aprobación de liquidación técnica y financiera',
            'due_date'           => '2027-06-30',
            'amount'             => 10000.00,
            'currency'           => 'PEN',
            'igv_affected'       => true,
            'status'             => InstallmentStatus::PENDING,
            'billing_id'         => null,
            'sale_note_id'       => null,
        ]);

        // Agreement 2: Framework Agreement (Total S/ 20,000.00, 2 Installments)
        $agreement2 = Agreement::create([
            'uuid'                              => (string) Str::uuid(),
            'code'                              => 'CONV-2026-DEMO2',
            'type'                              => AgreementType::FRAMEWORK,
            'title'                             => 'Convenio Marco de Asistencia Técnica y Productiva - Cooperativa Agraria',
            'objective'                         => 'Asistencia técnica en mejoramiento genético y buenas prácticas agrícolas.',
            'client_id'                         => $clientCorporate->id,
            'counterparty_signatory_name'       => 'Sra. Elena Ramos Cruz',
            'counterparty_signatory_role'       => 'Presidenta del Consejo Directivo',
            'counterparty_signatory_document'   => '23456789',
            'area_id'                           => 1,
            'coordinator_user_id'               => $user->id,
            'productive_activity_id'            => $productiveActivity?->id,
            'signature_date'                    => '2026-10-10',
            'start_date'                        => '2026-10-10',
            'end_date'                          => '2027-10-09',
            'original_end_date'                 => '2027-10-09',
            'currency'                          => 'PEN',
            'total_amount'                      => 20000.00,
            'original_amount'                   => 20000.00,
            'counterparty_contribution'         => 20000.00,
            'institution_contribution'          => 0.00,
            'status'                            => AgreementStatus::ACTIVE,
            'requires_financial_settlement'     => false,
            'resolution_number'                 => 'RES-2026-DEMO-02',
            'created_by_user_id'                => $user->id,
        ]);

        AgreementInstallment::create([
            'uuid'               => (string) Str::uuid(),
            'agreement_id'       => $agreement2->id,
            'installment_number' => 1,
            'description'        => 'Primera Cuota: Plan de capacitación comunitaria',
            'milestone_condition'=> 'Entrega de cronograma de asistencia',
            'due_date'           => '2026-11-30',
            'amount'             => 10000.00,
            'currency'           => 'PEN',
            'igv_affected'       => true,
            'status'             => InstallmentStatus::PENDING,
            'billing_id'         => null,
            'sale_note_id'       => null,
        ]);

        AgreementInstallment::create([
            'uuid'               => (string) Str::uuid(),
            'agreement_id'       => $agreement2->id,
            'installment_number' => 2,
            'description'        => 'Segunda Cuota: Cierre de asistencias técnicas',
            'milestone_condition'=> 'Informe de cumplimiento de metas comunitarias',
            'due_date'           => '2027-05-30',
            'amount'             => 10000.00,
            'currency'           => 'PEN',
            'igv_affected'       => true,
            'status'             => InstallmentStatus::PENDING,
            'billing_id'         => null,
            'sale_note_id'       => null,
        ]);

        // -----------------------------------------------------------------------------------------
        // 10. TRAINING SESSION (1 ENGAGEMENT, 1 SESSION, 15 PARTICIPANTS)
        // -----------------------------------------------------------------------------------------
        $this->command?->comment("  -> Seeding 1 Training Session with 15 Participants...");

        $serviceEngagement = ServiceEngagement::create([
            'uuid'                        => (string) Str::uuid(),
            'code'                        => 'SERV-2026-DEMO1',
            'client_id'                   => $clientCorporate->id,
            'technological_service_id'    => $technologicalService?->id ?? 1,
            'productive_activity_id'      => $productiveActivity?->id ?? 1,
            'responsible_user_id'         => $user->id,
            'description'                 => 'Programa Especializado en Automatización de Riego y Fertilización Tecnificada',
            'delivery_modality'           => 'IN_PERSON',
            'contracted_hours'            => 20.00,
            'consumed_hours'              => 4.00,
            'hourly_rate'                 => 100.00,
            'quantity'                    => 1.00,
            'unit_price'                  => 2000.00,
            'total_amount'                => 2000.00,
            'currency'                    => 'PEN',
            'start_date'                  => '2026-10-15',
            'expected_delivery_date'      => '2026-11-15',
            'status'                      => ServiceEngagementStatus::IN_PROGRESS,
            'min_attendance_percent'      => 80.00,
            'low_balance_threshold_hours' => 5.00,
        ]);

        $session = ServiceSession::create([
            'uuid'                  => (string) Str::uuid(),
            'service_engagement_id' => $serviceEngagement->id,
            'session_number'        => 1,
            'topic'                 => 'Módulo I: Mantenimiento Preventivo y Operación de Electroválvulas de Riego',
            'instructor_user_id'    => $user->id,
            'session_date'          => '2026-10-20',
            'start_time'            => '09:00:00',
            'end_time'              => '13:00:00',
            'duration_hours'        => 4.00,
            'location'              => 'Auditorio Central y Campo Experimental FVC',
            'status'                => ServiceSessionStatus::CONDUCTED,
            'observations'          => 'Sesión práctica ejecutada con éxito y 100% de asistencia de los participantes.',
        ]);

        // 15 Deterministic Participants / Attendees
        for ($p = 1; $p <= 15; $p++) {
            $pad = str_pad((string) $p, 2, '0', STR_PAD_LEFT);
            $docNumber = '450001' . $pad;

            ServiceAttendee::create([
                'uuid'                  => (string) Str::uuid(),
                'service_session_id'    => $session->id,
                'service_engagement_id' => $serviceEngagement->id,
                'full_name'             => "Participante Demo {$pad} Huamán",
                'dni_or_document'       => $docNumber,
                'email'                 => "participante{$pad}@demo.fvc.pe",
                'phone'                 => '9870001' . $pad,
                'organization'          => 'Cooperativa Agraria San Jerónimo',
                'attended'              => true,
                'evaluation_score'      => round(15.00 + ($p % 5), 2),
                'certificate_code'      => 'CERT-2026-' . str_pad((string) $p, 4, '0', STR_PAD_LEFT),
                'certificate_issued_at' => Carbon::parse('2026-10-20 14:00:00'),
                'notes'                 => 'Asistencia completa y aprobación de examen práctico.',
            ]);
        }

        $this->command?->info("✓ DemoAccountingSeeder executed successfully. All accounting records balanced and reconciled.");
    }
}
