<?php

namespace App\Console\Commands;

use App\Enums\AgreementStatus;
use App\Enums\InstallmentStatus;
use App\Enums\JournalStatus;
use App\Models\AccountingPeriod;
use App\Models\Agreement;
use App\Models\AgreementInstallment;
use App\Models\BankAccount;
use App\Models\Billing;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Product;
use App\Models\SaleNote;
use App\Models\StockProduct;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReconciliationAuditCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reconciliation:audit
                            {--year= : Filtrar auditoría por ejercicio fiscal (ej. 2026)}
                            {--period= : Filtrar por ID específico de período contable}
                            {--agreement= : Filtrar por código o ID de convenio específico}
                            {--warehouse= : Filtrar por ID de almacén para revisión de stock}
                            {--product= : Filtrar por ID de producto específico}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Auditoría integral de reconciliación financiera, operativa y tributaria (Block C5 / Track B).';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $year        = $this->option('year') ? (int) $this->option('year') : null;
        $periodId    = $this->option('period') ? (int) $this->option('period') : null;
        $agreementArg= $this->option('agreement') ? trim((string)$this->option('agreement')) : null;
        $warehouseId = $this->option('warehouse') ? (int) $this->option('warehouse') : null;
        $productId   = $this->option('product') ? (int) $this->option('product') : null;

        $this->newLine();
        $this->info("╔════════════════════════════════════════════════════════════════════════════╗");
        $this->info("║        ERP-FVC: AUDITORÍA DE CONCILIACIÓN INTEGRAL Y REVENUE (C5)          ║");
        $this->info("║               Partida Doble • Ventas • Kardex • Caja • Bancos • Convenios   ║");
        $this->info("╚════════════════════════════════════════════════════════════════════════════╝");
        if ($year) $this->line("  Filtro Ejercicio: {$year}");
        if ($periodId) $this->line("  Filtro Período ID: {$periodId}");
        if ($agreementArg) $this->line("  Filtro Convenio: {$agreementArg}");

        $totalIssues = 0;
        $summaryTable = [];

        // -------------------------------------------------------------------------------------------------
        // CHECK 1: Sum of debits = sum of credits per entry, per period, and globally
        // -------------------------------------------------------------------------------------------------
        $this->newLine();
        $this->comment("▶ [1/6] Partida Doble: Verificando Suma Débitos = Créditos (Asiento, Período, Global)...");

        // 1.1 Por Asiento Individual
        $unbalancedEntries = DB::table('journal_entries as je')
            ->join('journal_entry_lines as jel', 'je.id', '=', 'jel.journal_entry_id')
            ->selectRaw("
                je.id,
                je.entry_number,
                je.entry_date,
                je.status,
                ROUND(SUM(jel.debit), 2) as sum_debit,
                ROUND(SUM(jel.credit), 2) as sum_credit,
                ROUND(ABS(SUM(jel.debit) - SUM(jel.credit)), 2) as diff
            ")
            ->when($year, fn($q) => $q->whereYear('je.entry_date', $year))
            ->when($periodId, fn($q) => $q->where('je.accounting_period_id', $periodId))
            ->groupBy('je.id', 'je.entry_number', 'je.entry_date', 'je.status')
            ->havingRaw('diff > 0.001')
            ->get();

        // 1.2 Por Período Contable
        $unbalancedPeriods = DB::table('accounting_periods as ap')
            ->join('journal_entries as je', 'ap.id', '=', 'je.accounting_period_id')
            ->join('journal_entry_lines as jel', 'je.id', '=', 'jel.journal_entry_id')
            ->where('je.status', 'POSTED')
            ->when($year, fn($q) => $q->where('ap.fiscal_year', $year))
            ->when($periodId, fn($q) => $q->where('ap.id', $periodId))
            ->selectRaw("
                ap.id as period_id,
                ap.period_code as period_name,
                ap.fiscal_year,
                ap.status as period_status,
                ROUND(SUM(jel.debit), 2) as period_debit,
                ROUND(SUM(jel.credit), 2) as period_credit,
                ROUND(ABS(SUM(jel.debit) - SUM(jel.credit)), 2) as period_diff
            ")
            ->groupBy('ap.id', 'ap.period_code', 'ap.fiscal_year', 'ap.status')
            ->havingRaw('period_diff > 0.001')
            ->get();

        // 1.3 Global
        $globalBalance = DB::table('journal_entries as je')
            ->join('journal_entry_lines as jel', 'je.id', '=', 'jel.journal_entry_id')
            ->where('je.status', 'POSTED')
            ->when($year, fn($q) => $q->whereYear('je.entry_date', $year))
            ->selectRaw("
                COUNT(DISTINCT je.id) as total_entries,
                ROUND(COALESCE(SUM(jel.debit), 0), 2) as global_debit,
                ROUND(COALESCE(SUM(jel.credit), 0), 2) as global_credit,
                ROUND(ABS(COALESCE(SUM(jel.debit), 0) - COALESCE(SUM(jel.credit), 0)), 2) as global_diff
            ")
            ->first();

        if ($unbalancedEntries->isEmpty() && $unbalancedPeriods->isEmpty() && ($globalBalance->global_diff ?? 0) <= 0.001) {
            $this->info("  ✓ Partida Doble Cuadrada: {$globalBalance->total_entries} asientos posteados | Débito: S/ {$globalBalance->global_debit} = Crédito: S/ {$globalBalance->global_credit}");
            $summaryTable[] = ['1. Partida Doble (Débito = Crédito)', 'CORRECTO', "Global: S/ {$globalBalance->global_debit} (Diff: S/ 0.00)"];
        } else {
            $totalIssues += $unbalancedEntries->count() + $unbalancedPeriods->count();
            if ($globalBalance->global_diff > 0.001) $totalIssues++;
            $this->error("  ✗ Descuadre en Partida Doble detectado.");
            if ($unbalancedEntries->isNotEmpty()) {
                $this->table(['ID Asiento', 'Número', 'Fecha', 'Estado', 'Débito', 'Crédito', 'Diferencia'], $unbalancedEntries->toArray());
            }
            if ($unbalancedPeriods->isNotEmpty()) {
                $this->table(['ID Período', 'Nombre', 'Año', 'Estado', 'Débito Período', 'Crédito Período', 'Diferencia'], $unbalancedPeriods->toArray());
            }
            $summaryTable[] = ['1. Partida Doble (Débito = Crédito)', 'DESCUADRE', "Asientos con error: {$unbalancedEntries->count()}, Períodos: {$unbalancedPeriods->count()}"];
        }

        // -------------------------------------------------------------------------------------------------
        // CHECK 2: Total sales = Invoices + Sale notes (no duplicates) = General Ledger (70x + 40111)
        // -------------------------------------------------------------------------------------------------
        $this->newLine();
        $this->comment("▶ [2/6] Ventas Comerciales vs Libro Mayor: Facturas + Boletas + Notas Venta vs Ctas 70x y 40111...");

        // Facturación Activa (Facturas y Boletas, no anuladas)
        $billingTotals = DB::table('billings')
            ->whereIn('idtipo_comprobante', [1, 2]) // 1=Factura, 2=Boleta
            ->where('anulado', 0)
            ->when($year, fn($q) => $q->whereYear('fecha_emision', $year))
            ->selectRaw("
                COUNT(*) as count,
                ROUND(COALESCE(SUM(gravada), 0), 2) as gravada,
                ROUND(COALESCE(SUM(exonerada), 0), 2) as exonerada,
                ROUND(COALESCE(SUM(inafecta), 0), 2) as inafecta,
                ROUND(COALESCE(SUM(igv), 0), 2) as igv,
                ROUND(COALESCE(SUM(total), 0), 2) as total
            ")
            ->first();

        // Notas de Crédito emitidas (restan ventas)
        $creditNotesTotals = DB::table('billings')
            ->where('idtipo_comprobante', 3) // 3=Nota de Crédito
            ->where('anulado', 0)
            ->when($year, fn($q) => $q->whereYear('fecha_emision', $year))
            ->selectRaw("
                COUNT(*) as count,
                ROUND(COALESCE(SUM(total), 0), 2) as total
            ")
            ->first();

        // Notas de Venta Independientes (estado=1 y NO facturadas para evitar doble cómputo)
        $saleNoteTotals = DB::table('sale_notes')
            ->where('estado', 1)
            ->whereNull('billing_id')
            ->when($year, fn($q) => $q->whereYear('fecha_emision', $year))
            ->selectRaw("
                COUNT(*) as count,
                ROUND(COALESCE(SUM(total), 0), 2) as total
            ")
            ->first();

        $commercialSalesNet = round(($billingTotals->total ?? 0) + ($saleNoteTotals->total ?? 0) - ($creditNotesTotals->total ?? 0), 2);

        // Ventas en Libro Mayor: Créditos en cuentas 70x + 40111 menos débitos en 70x por notas de crédito
        $ledgerSales = DB::table('journal_entry_lines as jel')
            ->join('journal_entries as je', 'jel.journal_entry_id', '=', 'je.id')
            ->join('chart_of_accounts as coa', 'jel.account_id', '=', 'coa.id')
            ->where('je.status', 'POSTED')
            ->where(function($q) {
                $q->where('coa.code', 'LIKE', '70%')
                  ->orWhere('coa.code', 'LIKE', '40111%');
            })
            ->when($year, fn($q) => $q->whereYear('je.entry_date', $year))
            ->selectRaw("
                ROUND(COALESCE(SUM(CASE WHEN coa.code LIKE '70%' THEN (jel.credit - jel.debit) ELSE 0 END), 0), 2) as net_revenue_70,
                ROUND(COALESCE(SUM(CASE WHEN coa.code LIKE '40111%' THEN (jel.credit - jel.debit) ELSE 0 END), 0), 2) as net_igv_40111,
                ROUND(COALESCE(SUM(jel.credit - jel.debit), 0), 2) as ledger_total_sales
            ")
            ->first();

        $salesDiff = round(abs($commercialSalesNet - ($ledgerSales->ledger_total_sales ?? 0)), 2);

        if ($salesDiff <= 0.05) {
            $this->info("  ✓ Ventas Comerciales vs Mayor Cuadradas: Total Comercial S/ {$commercialSalesNet} == Total Mayor S/ {$ledgerSales->ledger_total_sales}");
            $summaryTable[] = ['2. Ventas vs Mayor (70x + 40111)', 'CORRECTO', "Comercial: S/ {$commercialSalesNet} | Mayor: S/ {$ledgerSales->ledger_total_sales}"];
        } else {
            $totalIssues++;
            $this->error("  ✗ Descuadre entre Ventas Comerciales y Libro Mayor:");
            $this->table(
                ['Concepto Comercial', 'Total S/', 'Concepto Contable', 'Total S/', 'Diferencia'],
                [
                    ['Facturas + Boletas Netas', number_format($billingTotals->total ?? 0, 2), 'Ingresos Operativos (70x)', number_format($ledgerSales->net_revenue_70 ?? 0, 2), '-'],
                    ['Notas Venta No Duplicadas', number_format($saleNoteTotals->total ?? 0, 2), 'IGV Cuenta Propia (40111)', number_format($ledgerSales->net_igv_40111 ?? 0, 2), '-'],
                    ['(-) Notas de Crédito', number_format($creditNotesTotals->total ?? 0, 2), 'Total Mayor (70x + 40111)', number_format($ledgerSales->ledger_total_sales ?? 0, 2), number_format($salesDiff, 2)],
                    ['TOTAL COMERCIAL NETO', number_format($commercialSalesNet, 2), 'TOTAL MAYOR', number_format($ledgerSales->ledger_total_sales ?? 0, 2), number_format($salesDiff, 2)],
                ]
            );
            $summaryTable[] = ['2. Ventas vs Mayor (70x + 40111)', 'DESCUADRE', "Dif: S/ {$salesDiff} (Comercial S/ {$commercialSalesNet} vs Mayor S/ {$ledgerSales->ledger_total_sales})"];
        }

        // -------------------------------------------------------------------------------------------------
        // CHECK 3: Physical stock in stock_products = Kardex balance per product and warehouse
        // -------------------------------------------------------------------------------------------------
        $this->newLine();
        $this->comment("▶ [3/6] Existencias Físicas: Verificando stock_products vs Saldo Teórico de Kardex...");

        // Verificamos por producto y almacén (solo productos físicos opción = 1)
        $stockDiscrepancies = DB::table('stock_products as sp')
            ->join('products as p', 'sp.idproducto', '=', 'p.id')
            ->join('warehouses as w', 'sp.idalmacen', '=', 'w.id')
            ->where('p.opcion', 1) // 1=Producto físico, 2=Servicio (nunca almacenable)
            ->when($warehouseId, fn($q) => $q->where('sp.idalmacen', $warehouseId))
            ->when($productId, fn($q) => $q->where('sp.idproducto', $productId))
            ->selectRaw("
                sp.idalmacen,
                w.descripcion as warehouse_name,
                sp.idproducto,
                p.descripcion as product_name,
                sp.stock_actual as physical_stock,
                COALESCE(sp.stock_entrada, 0) as initial_stock,
                (
                    SELECT COALESCE(SUM(db.cantidad), 0)
                    FROM detail_buys db
                    JOIN buys b ON b.id = db.idcompra
                    WHERE b.estado = 1
                      AND db.idalmacen = sp.idalmacen
                      AND db.idproducto = sp.idproducto
                ) as total_buys,
                (
                    SELECT COALESCE(SUM(dto.cantidad), 0)
                    FROM detail_transfer_orders dto
                    JOIN transfer_orders tor ON tor.id = dto.idorden_traslado
                    WHERE tor.estado = 1
                      AND tor.idalmacen_receptor = sp.idalmacen
                      AND dto.idproducto = sp.idproducto
                ) as total_transfer_in,
                (
                    SELECT COALESCE(SUM(dbi.cantidad), 0)
                    FROM detail_billings dbi
                    JOIN billings bi ON bi.id = dbi.idfacturacion
                    WHERE bi.anulado = 0
                      AND bi.idalmacen = sp.idalmacen
                      AND dbi.idproducto = sp.idproducto
                ) as total_sales_billing,
                (
                    SELECT COALESCE(SUM(dsn.cantidad), 0)
                    FROM detail_sale_notes dsn
                    JOIN sale_notes sn ON sn.id = dsn.idnotaventa
                    WHERE sn.estado = 1
                      AND sn.billing_id IS NULL
                      AND dsn.idalmacen = sp.idalmacen
                      AND dsn.idproducto = sp.idproducto
                ) as total_sales_notes,
                (
                    SELECT COALESCE(SUM(dto2.cantidad), 0)
                    FROM detail_transfer_orders dto2
                    JOIN transfer_orders tor2 ON tor2.id = dto2.idorden_traslado
                    WHERE tor2.estado = 1
                      AND tor2.idalmacen_despacho = sp.idalmacen
                      AND dto2.idproducto = sp.idproducto
                ) as total_transfer_out
            ")
            ->get()
            ->map(function($row) {
                $kardexCalculated = ($row->initial_stock + $row->total_buys + $row->total_transfer_in)
                    - ($row->total_sales_billing + $row->total_sales_notes + $row->total_transfer_out);
                $diff = abs((float)$row->physical_stock - $kardexCalculated);
                return (object) array_merge((array)$row, [
                    'kardex_calculated' => $kardexCalculated,
                    'stock_diff'        => $diff,
                ]);
            })
            ->filter(fn($row) => $row->stock_diff > 0.001);

        // Validar también que ningún servicio tenga filas en stock_products
        $serviceStockIllegalCount = DB::table('stock_products as sp')
            ->join('products as p', 'sp.idproducto', '=', 'p.id')
            ->where('p.opcion', 2)
            ->count();

        if ($stockDiscrepancies->isEmpty() && $serviceStockIllegalCount === 0) {
            $this->info("  ✓ Control de Stock Cuadrado: Todos los productos físicos coinciden con el Kardex. Cero existencias ilegales de servicios.");
            $summaryTable[] = ['3. Stock Físico vs Kardex', 'CORRECTO', 'Existencias en stock_products = Kardex balance en todos los almacenes'];
        } else {
            $totalIssues += $stockDiscrepancies->count() + $serviceStockIllegalCount;
            $this->error("  ✗ Discrepancias encontradas en existencias de almacén:");
            if ($stockDiscrepancies->isNotEmpty()) {
                $this->table(
                    ['Almacén', 'Producto', 'Stock Actual', 'Inicial', 'Compras', 'Transf. In', 'Ventas Fact', 'Ventas NV', 'Transf. Out', 'Kardex Teórico', 'Diferencia'],
                    $stockDiscrepancies->map(fn($r) => [
                        $r->warehouse_name,
                        $r->product_name,
                        $r->physical_stock,
                        $r->initial_stock,
                        $r->total_buys,
                        $r->total_transfer_in,
                        $r->total_sales_billing,
                        $r->total_sales_notes,
                        $r->total_transfer_out,
                        $r->kardex_calculated,
                        $r->stock_diff,
                    ])->toArray()
                );
            }
            if ($serviceStockIllegalCount > 0) {
                $this->error("  ✗ ALERTA: Existen {$serviceStockIllegalCount} registros de servicios en stock_products (violación de invariante).");
            }
            $summaryTable[] = ['3. Stock Físico vs Kardex', 'DESCUADRE', "Discrepancias: {$stockDiscrepancies->count()} productos | Servicios ilegales: {$serviceStockIllegalCount}"];
        }

        // -------------------------------------------------------------------------------------------------
        // CHECK 4: Cash: expected count result = recorded payments by payment method
        // -------------------------------------------------------------------------------------------------
        $this->newLine();
        $this->comment("▶ [4/6] Cuadre de Caja: Arqueos cerrados (monto_estimado) vs Medios de Pago y Movimientos...");

        $archingDiscrepancies = DB::table('arching_cashes as ac')
            ->leftJoin('detail_payments as dp', function($j) {
                $j->on('ac.id', '=', 'dp.idarqueocaja')->where('dp.estado', 1);
            })
            ->leftJoin('cash_movements as cm', function($j) {
                $j->on('ac.id', '=', 'cm.idarqueocaja')->where('cm.estado', 1);
            })
            ->selectRaw("
                ac.id,
                ac.fecha_inicio,
                ac.fecha_fin,
                ac.estado,
                COALESCE(ac.monto_inicial, 0) as monto_inicial,
                COALESCE(ac.monto_final, 0) as monto_final,
                COALESCE(ac.monto_estimado, 0) as monto_estimado_db,
                (
                    SELECT COALESCE(SUM(monto), 0)
                    FROM detail_payments
                    WHERE idarqueocaja = ac.id
                      AND idpago = 1 -- Efectivo
                      AND estado = 1
                ) as total_cash_sales,
                (
                    SELECT COALESCE(SUM(monto), 0)
                    FROM detail_payments
                    WHERE idarqueocaja = ac.id
                      AND idpago <> 1 -- Medios electrónicos (Yape, Plin, Tarjeta, etc)
                      AND estado = 1
                ) as total_electronic_sales,
                (
                    SELECT COALESCE(SUM(monto), 0)
                    FROM cash_movements
                    WHERE idarqueocaja = ac.id
                      AND tipo = 'ingreso'
                      AND estado = 1
                ) as total_cash_inflow,
                (
                    SELECT COALESCE(SUM(monto), 0)
                    FROM cash_movements
                    WHERE idarqueocaja = ac.id
                      AND tipo = 'egreso'
                      AND estado = 1
                ) as total_cash_outflow
            ")
            ->whereNotNull('ac.monto_final') // Sesiones cerradas
            ->when($year, fn($q) => $q->whereYear('ac.fecha_inicio', $year))
            ->groupBy('ac.id', 'ac.fecha_inicio', 'ac.fecha_fin', 'ac.estado', 'ac.monto_inicial', 'ac.monto_final', 'ac.monto_estimado')
            ->get()
            ->map(function($r) {
                $expectedCashInDrawer = round($r->monto_inicial + $r->total_cash_sales + $r->total_cash_inflow - $r->total_cash_outflow, 2);
                $diffWithEstimate = abs($expectedCashInDrawer - (float)$r->monto_estimado_db);
                return (object) array_merge((array)$r, [
                    'expected_calculated' => $expectedCashInDrawer,
                    'diff'                => $diffWithEstimate,
                ]);
            })
            ->filter(fn($r) => $r->diff > 0.05);

        if ($archingDiscrepancies->isEmpty()) {
            $this->info("  ✓ Arqueos y Medios de Pago Cuadrados: El monto estimado coincide con los medios de pago y movimientos.");
            $summaryTable[] = ['4. Arqueos de Caja vs Medios de Pago', 'CORRECTO', 'Monto estimado = Apertura + Ventas Efectivo + Ingresos - Egresos'];
        } else {
            $totalIssues += $archingDiscrepancies->count();
            $this->error("  ✗ Descuadres en sesiones de caja detectados:");
            $this->table(
                ['ID Arqueo', 'Fecha Inicio', 'Apertura', 'Vtas Efectivo', 'Ingresos Ext.', 'Egresos Ext.', 'Cálculo Teórico', 'Monto Estimado BD', 'Diferencia'],
                $archingDiscrepancies->map(fn($r) => [
                    $r->id,
                    $r->fecha_inicio,
                    $r->monto_inicial,
                    $r->total_cash_sales,
                    $r->total_cash_inflow,
                    $r->total_cash_outflow,
                    $r->expected_calculated,
                    $r->monto_estimado_db,
                    $r->diff,
                ])->toArray()
            );
            $summaryTable[] = ['4. Arqueos de Caja vs Medios de Pago', 'DESCUADRE', "{$archingDiscrepancies->count()} arqueos con diferencias de estimación"];
        }

        // -------------------------------------------------------------------------------------------------
        // CHECK 5: Reconciled bank balance = accounting balance (account 104x)
        // -------------------------------------------------------------------------------------------------
        $this->newLine();
        $this->comment("▶ [5/6] Conciliación Bancaria vs Mayor: Saldo en Cuentas 104x vs Libro Bancos...");

        $bankDiscrepancies = DB::table('bank_accounts as ba')
            ->join('chart_of_accounts as coa', 'ba.accounting_account_id', '=', 'coa.id')
            ->leftJoin('journal_entry_lines as jel', 'jel.account_id', '=', 'coa.id')
            ->leftJoin('journal_entries as je', function($j) {
                $j->on('jel.journal_entry_id', '=', 'je.id')->where('je.status', 'POSTED');
            })
            ->selectRaw("
                ba.id as bank_account_id,
                ba.bank_name,
                ba.account_number,
                coa.code as account_code,
                coa.name as account_name,
                ROUND(COALESCE(ba.current_balance, 0), 2) as bank_current_balance,
                ROUND(COALESCE(SUM(jel.debit - jel.credit), 0), 2) as ledger_balance,
                ROUND(ABS(COALESCE(ba.current_balance, 0) - COALESCE(SUM(jel.debit - jel.credit), 0)), 2) as diff
            ")
            ->where('ba.is_active', true)
            ->groupBy('ba.id', 'ba.bank_name', 'ba.account_number', 'coa.code', 'coa.name', 'ba.current_balance')
            ->havingRaw('diff > 0.05')
            ->get();

        if ($bankDiscrepancies->isEmpty()) {
            $this->info("  ✓ Conciliación Bancaria Cuadrada: Todas las cuentas bancarias activas coinciden con sus cuentas 104x en Mayor.");
            $summaryTable[] = ['5. Conciliación Bancaria (104x)', 'CORRECTO', 'Saldo en bank_accounts = Saldo Neto (Débito - Crédito) en Mayor'];
        } else {
            $totalIssues += $bankDiscrepancies->count();
            $this->error("  ✗ Descuadre en saldos de cuentas bancarias vs Mayor:");
            $this->table(
                ['ID Banco', 'Entidad', 'N° Cuenta', 'Cta Contable', 'Nombre Cuenta', 'Saldo Bancario', 'Saldo Mayor', 'Diferencia'],
                $bankDiscrepancies->toArray()
            );
            $summaryTable[] = ['5. Conciliación Bancaria (104x)', 'DESCUADRE', "{$bankDiscrepancies->count()} cuentas bancarias con diferencias"];
        }

        // -------------------------------------------------------------------------------------------------
        // CHECK 6: Agreement-based revenue = linked invoices = General Ledger (Track B)
        // -------------------------------------------------------------------------------------------------
        $this->newLine();
        $this->comment("▶ [6/6] Ingresos por Convenios (Track B): Cuotas Facturadas = Comprobantes Vinculados = Asientos Mayor...");

        $agreementQuery = DB::table('agreement_installments as ai')
            ->join('agreements as a', 'ai.agreement_id', '=', 'a.id')
            ->leftJoin('billings as b', 'ai.billing_id', '=', 'b.id')
            ->leftJoin('sale_notes as sn', 'ai.sale_note_id', '=', 'sn.id')
            ->where(function($q) {
                $q->whereNotNull('ai.billing_id')
                  ->orWhereNotNull('ai.sale_note_id')
                  ->orWhereIn('ai.status', ['INVOICED', 'COLLECTED']);
            })
            ->when($agreementArg, function($q) use ($agreementArg) {
                $q->where(function($sub) use ($agreementArg) {
                    $sub->where('a.code', $agreementArg)
                        ->orWhere('a.id', is_numeric($agreementArg) ? (int)$agreementArg : 0);
                });
            })
            ->selectRaw("
                ai.id as installment_id,
                a.code as agreement_code,
                ai.installment_number,
                ai.status as installment_status,
                ROUND(ai.amount, 2) as installment_amount,
                ai.billing_id,
                ROUND(COALESCE(b.total, 0), 2) as billing_total,
                ai.sale_note_id,
                ROUND(COALESCE(sn.total, 0), 2) as sale_note_total,
                ROUND(COALESCE(b.total, sn.total, 0), 2) as linked_voucher_total,
                (
                    SELECT ROUND(COALESCE(SUM(jel.credit), 0), 2)
                    FROM journal_entry_lines jel
                    JOIN journal_entries je ON jel.journal_entry_id = je.id
                    JOIN chart_of_accounts coa ON jel.account_id = coa.id
                    WHERE je.status = 'POSTED'
                      AND (coa.code LIKE '70%' OR coa.code LIKE '40111%')
                      AND (
                          (je.source_type = 'App\\\\Models\\\\Billing' AND je.source_id = ai.billing_id) OR
                          (je.source_type = 'App\\\\Models\\\\SaleNote' AND je.source_id = ai.sale_note_id)
                      )
                ) as ledger_revenue_total
            ")
            ->get();

        $agreementIssues = $agreementQuery->filter(function($item) {
            $voucherDiff = abs((float)$item->installment_amount - (float)$item->linked_voucher_total);
            // If posted to ledger, ledger must match voucher
            $hasLedgerDiff = ($item->ledger_revenue_total > 0) && (abs((float)$item->linked_voucher_total - (float)$item->ledger_revenue_total) > 0.05);
            return $voucherDiff > 0.05 || $hasLedgerDiff;
        });

        // Anti-duplication check: ensure no installment has BOTH billing_id and sale_note_id or is linked multiple times
        $duplicateInstallmentLinks = DB::table('agreement_installments')
            ->whereNotNull('billing_id')
            ->whereNotNull('sale_note_id')
            ->count();

        if ($agreementIssues->isEmpty() && $duplicateInstallmentLinks === 0) {
            $totalInvoiced = $agreementQuery->sum('installment_amount');
            $this->info("  ✓ Ingresos de Convenios (Track B) Cuadrados: {$agreementQuery->count()} cuotas facturadas (Total: S/ " . number_format($totalInvoiced, 2) . ") corresponden exactamente a sus comprobantes y asientos del Mayor.");
            $summaryTable[] = ['6. Convenios Track B (Cuota = CPE = Mayor)', 'CORRECTO', "{$agreementQuery->count()} cuotas conciliadas sin duplicidad"];
        } else {
            $totalIssues += $agreementIssues->count() + $duplicateInstallmentLinks;
            $this->error("  ✗ Inconsistencias en Ingresos de Convenios (Track B):");
            if ($agreementIssues->isNotEmpty()) {
                $this->table(
                    ['ID Cuota', 'Convenio', 'N°', 'Estado', 'Monto Cuota', 'ID Factura', 'Total Fact', 'ID Nota', 'Total Nota', 'Total Mayor', 'Observación'],
                    $agreementIssues->map(fn($i) => [
                        $i->installment_id,
                        $i->agreement_code,
                        $i->installment_number,
                        $i->installment_status,
                        $i->installment_amount,
                        $i->billing_id ?? '-',
                        $i->billing_total,
                        $i->sale_note_id ?? '-',
                        $i->sale_note_total,
                        $i->ledger_revenue_total ?? 0,
                        ((float)$i->installment_amount !== (float)$i->linked_voucher_total) ? 'Monto Cuota <> CPE' : 'CPE <> Mayor',
                    ])->toArray()
                );
            }
            if ($duplicateInstallmentLinks > 0) {
                $this->error("  ✗ ALERTA CRÍTICA: Existen {$duplicateInstallmentLinks} cuotas con doble comprobante vinculado (Factura Y Nota de Venta simultáneamente).");
            }
            $summaryTable[] = ['6. Convenios Track B (Cuota = CPE = Mayor)', 'DESCUADRE', "Diferencias: {$agreementIssues->count()} cuotas | Dobles vínculos: {$duplicateInstallmentLinks}"];
        }

        // -------------------------------------------------------------------------------------------------
        // RESUMEN GENERAL DE AUDITORÍA
        // -------------------------------------------------------------------------------------------------
        $this->newLine();
        $this->info("================================================================================");
        $this->info("                         RESUMEN DE AUDITORÍA DE CONCILIACIÓN                   ");
        $this->info("================================================================================");
        $this->table(['Invariante de Conciliación', 'Estado', 'Detalle'], $summaryTable);

        if ($totalIssues === 0) {
            $this->info("\n  ✓ ESTADO FINAL: SISTEMA 100% RECONCILIADO. Cero descuadres detectados en base de pruebas.\n");
            return Command::SUCCESS;
        }

        $this->error("\n  ✗ ESTADO FINAL: SE ENCONTRARON {$totalIssues} DISCREPANCIAS QUE REQUIEREN ACCIÓN CORRECTIVA.\n");
        return Command::FAILURE;
    }
}
