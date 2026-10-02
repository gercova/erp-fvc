<?php

namespace App\Services;

use App\Exports\BillingReportExport;
use App\Models\ArchingCash;
use App\Models\Billing;
use App\Models\Business;
use App\Models\Buy;
use App\Models\Category;
use App\Models\Client;
use App\Models\DetailBilling;
use App\Models\DetailPayment;
use App\Models\DetailSaleNote;
use App\Models\PayMode;
use App\Models\Product;
use App\Models\Provider;
use App\Models\SaleNote;
use App\Models\StockProduct;
use App\Models\TypeDocument;
use App\Models\Warehouse;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * ReportService
 *
 * Centralized reporting service for ERP-FVC.
 *
 * DOCUMENTED EXCLUSION & RECONCILIATION RULES:
 * 1. Single Base Query: Sales from `billings` (electronic receipts) and `sale_notes`
 *    are unified into a single result set with an explicit `origin` column ('billing' vs 'sale_note').
 * 2. Converted Sales Notes (Canjeadas): If a sales note was converted to a billing
 *    (receipt/invoice), it is linked via `billings.sale_note_id` or `sale_notes.billing_id`
 *    or marked with `estado = 2`. Converted sales notes are strictly EXCLUDED from the sales
 *    totals to prevent double-counting.
 * 3. Voided Documents (Anulados): Documents with `anulado = 1` (billings) or `estado = 0` (sale_notes)
 *    are cancelled operations with zero revenue. They are EXCLUDED from all revenue and sales
 *    performance metrics (and only visible in voucher audit/status listings).
 * 4. Credit Notes (Notas de Crédito - tipo 07): Credit notes offset or adjust previous invoices.
 *    By default, they are excluded from positive sales volume and can be consulted in credit note reports
 *    or applied as adjustments to net margin.
 * 5. Inventory: Services (`opcion = 2`) do not carry physical stock and are excluded from stock reports.
 */
class ReportService
{
    /**
     * Resolve consistent date range from request or defaults.
     */
    public function resolveDateRange(?Request $request): array
    {
        $startDate = $request?->input('start_date') ?: $request?->input('fecha_inicio');
        $endDate   = $request?->input('end_date') ?: $request?->input('fecha_fin');

        $start = $startDate
            ? Carbon::parse($startDate)->startOfDay()->toDateString()
            : Carbon::now()->startOfMonth()->toDateString();

        $end = $endDate
            ? Carbon::parse($endDate)->endOfDay()->toDateString()
            : Carbon::now()->endOfMonth()->toDateString();

        return [$start, $end];
    }

    /**
     * Currency symbol from Business / Country.
     */
    public function currencySymbol(): string
    {
        $business = Business::find(1);
        if ($business && $business->country) {
            return $business->country->signo ?: 'S/';
        }

        return 'S/';
    }

    /**
     * 1. UNIFIED SALES BASE QUERY
     *
     * Produces a single query combining `billings` and `sale_notes` with `origin` column.
     * Excludes converted sales notes, voided documents, and credit notes according to documented rules.
     */
    public function salesBaseQuery(?Request $request = null, array $options = [])
    {
        [$startDate, $endDate] = $this->resolveDateRange($request);

        $warehouseId    = (int) ($request?->input('idalmacen') ?: $request?->input('id_almacen') ?: 0);
        $clientId       = (int) ($request?->input('idcliente') ?: $request?->input('id_cliente') ?: 0);
        $userId         = (int) ($request?->input('idusuario') ?: $request?->input('id_usuario') ?: 0);
        $docTypeCode    = $request?->input('tipo_comprobante') ?: $request?->input('filter_document_type');
        $payModeId      = (int) ($request?->input('idpago') ?: $request?->input('filter_pay_mode') ?: 0);
        $statusFilter   = $request?->input('status') ?: $request?->input('filter_status');

        $includeVoided      = $options['include_voided'] ?? false;
        $includeCreditNotes = $options['include_credit_notes'] ?? false;

        // --- SUBQUERY 1: BILLINGS ---
        $billingsQuery = DB::table('billings as b')
            ->join('type_documents as td', 'b.idtipo_comprobante', '=', 'td.id')
            ->join('clients as c', 'b.idcliente', '=', 'c.id')
            ->leftJoin('warehouses as w', 'b.idalmacen', '=', 'w.id')
            ->leftJoin('users as u', 'b.idusuario', '=', 'u.id')
            ->leftJoin('pay_modes as pm', 'b.idpago', '=', 'pm.id')
            ->selectRaw("
                'billing' as origin,
                b.id,
                b.idtipo_comprobante,
                td.codigo as tipo_comprobante_codigo,
                td.descripcion as tipo_comprobante,
                b.serie,
                b.correlativo,
                b.fecha_emision,
                b.hora,
                b.fecha_vencimiento,
                b.idcliente,
                c.nombres as cliente,
                c.nro_documento as cliente_documento,
                b.idalmacen,
                COALESCE(w.descripcion, 'Principal') as almacen,
                b.idusuario,
                COALESCE(u.nombres, 'Sistema') as usuario,
                b.idarqueocaja,
                b.idpago,
                b.modo_pago,
                COALESCE(pm.descripcion, 'Efectivo') as metodo_pago,
                CAST(b.gravada AS DECIMAL(16,2)) as subtotal,
                CAST(b.exonerada AS DECIMAL(16,2)) as exonerada,
                CAST(b.inafecta AS DECIMAL(16,2)) as inafecta,
                CAST(b.igv AS DECIMAL(16,2)) as igv,
                CAST(b.total AS DECIMAL(16,2)) as total,
                (CASE WHEN b.anulado = 1 THEN 1 ELSE 0 END) as anulado,
                b.sale_note_id,
                NULL as billing_id
            ")
            ->whereBetween('b.fecha_emision', [$startDate, $endDate]);

        if (! $includeVoided) {
            $billingsQuery->where('b.anulado', 0);
        }

        if (! $includeCreditNotes) {
            $billingsQuery->where('td.codigo', '<>', '07');
        }

        if ($warehouseId > 0) {
            $billingsQuery->where('b.idalmacen', $warehouseId);
        }

        if ($clientId > 0) {
            $billingsQuery->where('b.idcliente', $clientId);
        }

        if ($userId > 0) {
            $billingsQuery->where('b.idusuario', $userId);
        }

        if ($docTypeCode) {
            $billingsQuery->where('td.codigo', $docTypeCode);
        }

        if ($payModeId > 0) {
            $billingsQuery->where('b.idpago', $payModeId);
        }

        if ($statusFilter === 'vigente') {
            $billingsQuery->where('b.anulado', 0);
        } elseif ($statusFilter === 'anulado') {
            $billingsQuery->where('b.anulado', 1);
        }

        // --- SUBQUERY 2: SALE NOTES ---
        $saleNotesQuery = DB::table('sale_notes as sn')
            ->join('type_documents as td', 'sn.idtipo_comprobante', '=', 'td.id')
            ->join('clients as c', 'sn.idcliente', '=', 'c.id')
            ->leftJoin('users as u', 'sn.idusuario', '=', 'u.id')
            ->selectRaw("
                'sale_note' as origin,
                sn.id,
                sn.idtipo_comprobante,
                td.codigo as tipo_comprobante_codigo,
                td.descripcion as tipo_comprobante,
                sn.serie,
                sn.correlativo,
                sn.fecha_emision,
                sn.hora,
                sn.fecha_vencimiento,
                sn.idcliente,
                c.nombres as cliente,
                c.nro_documento as cliente_documento,
                (SELECT COALESCE(dsn.idalmacen, 1) FROM detail_sale_notes dsn WHERE dsn.idnotaventa = sn.id LIMIT 1) as idalmacen,
                COALESCE((SELECT w.descripcion FROM detail_sale_notes dsn JOIN warehouses w ON dsn.idalmacen = w.id WHERE dsn.idnotaventa = sn.id LIMIT 1), 'Principal') as almacen,
                sn.idusuario,
                COALESCE(u.nombres, 'Sistema') as usuario,
                sn.idarqueocaja,
                COALESCE((SELECT dp.idpago FROM detail_payments dp WHERE dp.idfactura = sn.id AND dp.idtipo_comprobante = sn.idtipo_comprobante AND dp.estado = 1 LIMIT 1), 1) as idpago,
                sn.modo_pago,
                COALESCE((SELECT pm.descripcion FROM detail_payments dp JOIN pay_modes pm ON dp.idpago = pm.id WHERE dp.idfactura = sn.id AND dp.idtipo_comprobante = sn.idtipo_comprobante AND dp.estado = 1 LIMIT 1), 'Efectivo') as metodo_pago,
                CAST(sn.subtotal AS DECIMAL(16,2)) as subtotal,
                0.00 as exonerada,
                0.00 as inafecta,
                CAST(sn.igv AS DECIMAL(16,2)) as igv,
                CAST(sn.total AS DECIMAL(16,2)) as total,
                (CASE WHEN sn.estado = 0 THEN 1 ELSE 0 END) as anulado,
                NULL as sale_note_id,
                sn.billing_id
            ")
            ->whereBetween('sn.fecha_emision', [$startDate, $endDate]);

        // Exclude converted sales notes to eliminate duplicates (sale note converted to receipt)
        $saleNotesQuery->whereNull('sn.billing_id')
            ->where('sn.estado', '<>', 2)
            ->whereNotExists(function ($sub) {
                $sub->selectRaw('1')
                    ->from('billings as b_conv')
                    ->whereColumn('b_conv.sale_note_id', 'sn.id')
                    ->where('b_conv.anulado', 0);
            });

        if (! $includeVoided) {
            $saleNotesQuery->where('sn.estado', '<>', 0);
        }

        if ($warehouseId > 0) {
            $saleNotesQuery->whereExists(function ($sub) use ($warehouseId) {
                $sub->selectRaw('1')
                    ->from('detail_sale_notes as dsn2')
                    ->whereColumn('dsn2.idnotaventa', 'sn.id')
                    ->where('dsn2.idalmacen', $warehouseId);
            });
        }

        if ($clientId > 0) {
            $saleNotesQuery->where('sn.idcliente', $clientId);
        }

        if ($userId > 0) {
            $saleNotesQuery->where('sn.idusuario', $userId);
        }

        if ($docTypeCode) {
            $saleNotesQuery->where('td.codigo', $docTypeCode);
        }

        if ($statusFilter === 'vigente') {
            $saleNotesQuery->where('sn.estado', 1);
        } elseif ($statusFilter === 'anulado') {
            $saleNotesQuery->where('sn.estado', 0);
        }

        // UNIFIED QUERY via UNION ALL
        return $billingsQuery->unionAll($saleNotesQuery);
    }

    /**
     * Retrieve all unified sales records as a collection with computed columns.
     */
    public function getUnifiedSalesList(?Request $request = null, array $options = []): Collection
    {
        $rows = $this->salesBaseQuery($request, $options)->get();

        return $rows->map(function ($row) {
            $row->numero_documento = $row->serie . '-' . $row->correlativo;
            $row->subtotal = (float) $row->subtotal;
            $row->igv      = (float) $row->igv;
            $row->total    = (float) $row->total;
            $row->anulado  = (bool) $row->anulado;
            return $row;
        });
    }

    /**
     * 1.1 Report: Sales by Date
     */
    public function getSalesByDate(?Request $request = null, array $filters = []): array
    {
        $sales = $this->getUnifiedSalesList($request, $filters);

        $grouped = $sales->groupBy('fecha_emision')->map(function ($group, $fecha) {
            $count = $group->count();
            $subtotal = round($group->sum('subtotal'), 2);
            $igv = round($group->sum('igv'), 2);
            $total = round($group->sum('total'), 2);
            $ticketPromedio = $count > 0 ? round($total / $count, 2) : 0.00;

            return [
                'fecha'           => $fecha,
                'cantidad_ventas' => $count,
                'subtotal'        => $subtotal,
                'total_impuestos' => $igv,
                'total'           => $total,
                'ticket_promedio' => $ticketPromedio,
            ];
        })->values()->sortBy('fecha')->values();

        $totalSales = round($grouped->sum('total'), 2);
        $totalOrders = $grouped->sum('cantidad_ventas');
        $averageTicket = $totalOrders > 0 ? round($totalSales / $totalOrders, 2) : 0.00;

        return [
            'sales'          => $grouped->toArray(),
            'total_ventas'   => $totalSales,
            'total_ordenes'  => $totalOrders,
            'ticket_promedio'=> $averageTicket,
            'signo'          => $this->currencySymbol(),
        ];
    }

    /**
     * 1.2 Report: Sales by Product
     *
     * Joins items from detail_billings and detail_sale_notes.
     * Excludes converted sales notes, voided documents, and credit notes.
     */
    public function getSalesByProduct(?Request $request = null, array $filters = []): array
    {
        [$startDate, $endDate] = $this->resolveDateRange($request);
        $warehouseId = (int) ($request?->input('idalmacen') ?: $request?->input('id_almacen') ?: 0);
        $categoryId  = (int) ($request?->input('idcategoria') ?: 0);

        // Billing items
        $billingItems = DB::table('detail_billings as db')
            ->join('billings as b', 'db.idfacturacion', '=', 'b.id')
            ->join('type_documents as td', 'b.idtipo_comprobante', '=', 'td.id')
            ->join('products as p', 'db.idproducto', '=', 'p.id')
            ->leftJoin('categories as cat', 'p.idcategoria', '=', 'cat.id')
            ->leftJoin('units as u', 'p.idunidad', '=', 'u.id')
            ->selectRaw("
                'billing' as origin,
                p.id as producto_id,
                p.descripcion as producto,
                COALESCE(p.codigo_interno, p.codigo_barras, CONCAT('P-', p.id)) as codigo,
                COALESCE(cat.descripcion, 'General') as categoria,
                COALESCE(u.descripcion, 'NIU') as unidad,
                CAST(db.cantidad AS DECIMAL(16,2)) as cantidad,
                CAST(db.precio_total AS DECIMAL(16,2)) as total
            ")
            ->whereBetween('b.fecha_emision', [$startDate, $endDate])
            ->where('b.anulado', 0)
            ->where('td.codigo', '<>', '07');

        if ($warehouseId > 0) {
            $billingItems->where('b.idalmacen', $warehouseId);
        }
        if ($categoryId > 0) {
            $billingItems->where('p.idcategoria', $categoryId);
        }

        // Sale Note items
        $saleNoteItems = DB::table('detail_sale_notes as dsn')
            ->join('sale_notes as sn', 'dsn.idnotaventa', '=', 'sn.id')
            ->join('type_documents as td', 'sn.idtipo_comprobante', '=', 'td.id')
            ->join('products as p', 'dsn.idproducto', '=', 'p.id')
            ->leftJoin('categories as cat', 'p.idcategoria', '=', 'cat.id')
            ->leftJoin('units as u', 'p.idunidad', '=', 'u.id')
            ->selectRaw("
                'sale_note' as origin,
                p.id as producto_id,
                p.descripcion as producto,
                COALESCE(p.codigo_interno, p.codigo_barras, CONCAT('P-', p.id)) as codigo,
                COALESCE(cat.descripcion, 'General') as categoria,
                COALESCE(u.descripcion, 'NIU') as unidad,
                CAST(dsn.cantidad AS DECIMAL(16,2)) as cantidad,
                CAST(dsn.precio_total AS DECIMAL(16,2)) as total
            ")
            ->whereBetween('sn.fecha_emision', [$startDate, $endDate])
            ->where('sn.estado', 1)
            ->whereNull('sn.billing_id')
            ->whereNotExists(function ($sub) {
                $sub->selectRaw('1')
                    ->from('billings as b_conv')
                    ->whereColumn('b_conv.sale_note_id', 'sn.id')
                    ->where('b_conv.anulado', 0);
            });

        if ($warehouseId > 0) {
            $saleNoteItems->where('dsn.idalmacen', $warehouseId);
        }
        if ($categoryId > 0) {
            $saleNoteItems->where('p.idcategoria', $categoryId);
        }

        $allUnifiedItems = $billingItems->unionAll($saleNoteItems)->get();

        $grouped = $allUnifiedItems->groupBy('producto_id')->map(function ($group) {
            $first = $group->first();
            $qty = round($group->sum('cantidad'), 2);
            $total = round($group->sum('total'), 2);
            $avgPrice = $qty > 0 ? round($total / $qty, 2) : 0.00;

            return [
                'producto_id'      => $first->producto_id,
                'codigo'           => $first->codigo,
                'producto'         => $first->producto,
                'categoria'        => $first->categoria,
                'unidad'           => $first->unidad,
                'cantidad_vendida' => $qty,
                'total_ventas'     => $total,
                'precio_promedio'  => $avgPrice,
            ];
        })->values()->sortByDesc('total_ventas')->values();

        $totalSales = round($grouped->sum('total_ventas'), 2);
        $totalQty   = round($grouped->sum('cantidad_vendida'), 2);

        return [
            'sales'          => $grouped->toArray(),
            'total_ventas'   => $totalSales,
            'total_cantidad' => $totalQty,
            'signo'          => $this->currencySymbol(),
        ];
    }

    /**
     * 1.3 Report: Sales by Customer
     */
    public function getSalesByCustomer(?Request $request = null, array $filters = []): array
    {
        $sales = $this->getUnifiedSalesList($request, $filters);

        $grouped = $sales->groupBy('idcliente')->map(function ($group, $clienteId) {
            $first = $group->first();
            $count = $group->count();
            $subtotal = round($group->sum('subtotal'), 2);
            $igv = round($group->sum('igv'), 2);
            $total = round($group->sum('total'), 2);
            $ticket = $count > 0 ? round($total / $count, 2) : 0.00;

            return [
                'idcliente'        => (int) $clienteId,
                'cliente'          => $first->cliente,
                'cliente_documento'=> $first->cliente_documento,
                'cantidad_compras' => $count,
                'subtotal'         => $subtotal,
                'igv'              => $igv,
                'total_ventas'     => $total,
                'ticket_promedio'  => $ticket,
            ];
        })->values()->sortByDesc('total_ventas')->values();

        $totalSales = round($grouped->sum('total_ventas'), 2);
        $totalTransactions = $grouped->sum('cantidad_compras');

        return [
            'sales'              => $grouped->toArray(),
            'total_ventas'       => $totalSales,
            'total_transacciones'=> $totalTransactions,
            'signo'              => $this->currencySymbol(),
        ];
    }

    /**
     * 1.4 Report: Sales by Type of Document / Receipt
     */
    public function getSalesByTypeDocument(?Request $request = null, array $filters = []): array
    {
        $sales = $this->getUnifiedSalesList($request, $filters);

        $grouped = $sales->groupBy('tipo_comprobante_codigo')->map(function ($group, $code) {
            $first = $group->first();
            $count = $group->count();
            $subtotal = round($group->sum('subtotal'), 2);
            $igv = round($group->sum('igv'), 2);
            $total = round($group->sum('total'), 2);

            return [
                'codigo'              => (string) $code,
                'tipo_comprobante'    => $first->tipo_comprobante,
                'cantidad_documentos' => $count,
                'subtotal'            => $subtotal,
                'igv'                 => $igv,
                'total_ventas'        => $total,
            ];
        })->values()->sortByDesc('total_ventas')->values();

        $totalSales = round($grouped->sum('total_ventas'), 2);
        $totalDocs  = $grouped->sum('cantidad_documentos');

        return [
            'sales'              => $grouped->toArray(),
            'total_ventas'       => $totalSales,
            'total_documentos'   => $totalDocs,
            'signo'              => $this->currencySymbol(),
        ];
    }

    /**
     * 1.5 Report: Sales by Means of Payment
     *
     * Sourced from detail_payments tied to valid sales (billings and sale_notes).
     * Excludes voided sales and converted sale notes.
     */
    public function getSalesByPaymentMethod(?Request $request = null, array $filters = []): array
    {
        [$startDate, $endDate] = $this->resolveDateRange($request);
        $warehouseId = (int) ($request?->input('idalmacen') ?: $request?->input('id_almacen') ?: 0);

        // Payments from billings
        $billingPayments = DB::table('detail_payments as dp')
            ->join('billings as b', function ($j) {
                $j->on('dp.idfactura', '=', 'b.id')
                  ->on('dp.idtipo_comprobante', '=', 'b.idtipo_comprobante');
            })
            ->join('pay_modes as pm', 'dp.idpago', '=', 'pm.id')
            ->selectRaw("
                'billing' as origin,
                pm.id as idpago,
                pm.descripcion as metodo_pago,
                CAST(dp.monto AS DECIMAL(16,2)) as monto
            ")
            ->where('dp.estado', 1)
            ->where('b.anulado', 0)
            ->whereBetween('b.fecha_emision', [$startDate, $endDate]);

        if ($warehouseId > 0) {
            $billingPayments->where('b.idalmacen', $warehouseId);
        }

        // Payments from sale_notes
        $saleNotePayments = DB::table('detail_payments as dp')
            ->join('sale_notes as sn', function ($j) {
                $j->on('dp.idfactura', '=', 'sn.id')
                  ->on('dp.idtipo_comprobante', '=', 'sn.idtipo_comprobante');
            })
            ->join('pay_modes as pm', 'dp.idpago', '=', 'pm.id')
            ->selectRaw("
                'sale_note' as origin,
                pm.id as idpago,
                pm.descripcion as metodo_pago,
                CAST(dp.monto AS DECIMAL(16,2)) as monto
            ")
            ->where('dp.estado', 1)
            ->where('sn.estado', 1)
            ->whereNull('sn.billing_id')
            ->whereNotExists(function ($sub) {
                $sub->selectRaw('1')
                    ->from('billings as b_conv')
                    ->whereColumn('b_conv.sale_note_id', 'sn.id')
                    ->where('b_conv.anulado', 0);
            })
            ->whereBetween('sn.fecha_emision', [$startDate, $endDate]);

        if ($warehouseId > 0) {
            $saleNotePayments->whereExists(function ($sub) use ($warehouseId) {
                $sub->selectRaw('1')
                    ->from('detail_sale_notes as dsn')
                    ->whereColumn('dsn.idnotaventa', 'sn.id')
                    ->where('dsn.idalmacen', $warehouseId);
            });
        }

        $allPayments = $billingPayments->unionAll($saleNotePayments)->get();

        $grouped = $allPayments->groupBy('idpago')->map(function ($group, $idpago) {
            $first = $group->first();
            $count = $group->count();
            $total = round($group->sum('monto'), 2);

            return [
                'idpago'                 => (int) $idpago,
                'metodo_pago'            => $first->metodo_pago,
                'cantidad_transacciones' => $count,
                'total_recaudado'        => $total,
            ];
        })->values()->sortByDesc('total_recaudado')->values();

        $totalRecaudado = round($grouped->sum('total_recaudado'), 2);
        $totalTransactions = $grouped->sum('cantidad_transacciones');

        return [
            'sales'                => $grouped->toArray(),
            'total_recaudado'      => $totalRecaudado,
            'total_transacciones'  => $totalTransactions,
            'signo'                => $this->currencySymbol(),
        ];
    }

    /**
     * 2. Report: Stock per Warehouse
     *
     * Physical products only (excludes services opcion = 2).
     */
    public function getStockByWarehouse(?Request $request = null, array $filters = []): array
    {
        $warehouseId = (int) ($request?->input('idalmacen') ?: $request?->input('id_almacen') ?: 0);
        $categoryId  = (int) ($request?->input('idcategoria') ?: 0);
        $search      = trim((string) ($request?->input('search') ?: ''));

        $query = DB::table('stock_products as sp')
            ->join('products as p', 'sp.idproducto', '=', 'p.id')
            ->join('warehouses as w', 'sp.idalmacen', '=', 'w.id')
            ->leftJoin('categories as c', 'p.idcategoria', '=', 'c.id')
            ->leftJoin('units as u', 'p.idunidad', '=', 'u.id')
            ->where('p.opcion', 1) // Strictly physical products; services have no stock
            ->selectRaw("
                sp.id,
                sp.idalmacen,
                w.descripcion as almacen,
                p.id as producto_id,
                COALESCE(p.codigo_interno, p.codigo_barras, CONCAT('P-', p.id)) as codigo,
                p.descripcion as producto,
                COALESCE(c.descripcion, 'General') as categoria,
                COALESCE(u.descripcion, 'NIU') as unidad,
                CAST(sp.stock_actual AS DECIMAL(16,2)) as stock_actual,
                CAST(sp.stock_minimo AS DECIMAL(16,2)) as stock_minimo,
                CAST(COALESCE(sp.precio_compra, p.precio_compra, 0) AS DECIMAL(16,2)) as precio_compra,
                CAST(COALESCE(sp.precio_venta, p.precio_venta, 0) AS DECIMAL(16,2)) as precio_venta,
                CAST((sp.stock_actual * COALESCE(sp.precio_compra, p.precio_compra, 0)) AS DECIMAL(16,2)) as valor_inventario
            ");

        if ($warehouseId > 0) {
            $query->where('sp.idalmacen', $warehouseId);
        }

        if ($categoryId > 0) {
            $query->where('p.idcategoria', $categoryId);
        }

        if ($search !== '') {
            $query->where(function ($w) use ($search) {
                $w->where('p.descripcion', 'like', "%{$search}%")
                  ->orWhere('p.codigo_interno', 'like', "%{$search}%")
                  ->orWhere('p.codigo_barras', 'like', "%{$search}%");
            });
        }

        $items = $query->orderBy('w.descripcion')->orderBy('p.descripcion')->get();

        $totalStock = round($items->sum('stock_actual'), 2);
        $totalValuation = round($items->sum('valor_inventario'), 2);

        return [
            'items'            => $items->map(fn($item) => (array) $item)->toArray(),
            'total_stock'      => $totalStock,
            'total_valoracion' => $totalValuation,
            'total_productos'  => $items->count(),
            'signo'            => $this->currencySymbol(),
        ];
    }

    /**
     * 3. Report: Purchases by Supplier
     *
     * Valid purchases only (buys.estado = 1).
     */
    public function getPurchasesBySupplier(?Request $request = null, array $filters = []): array
    {
        [$startDate, $endDate] = $this->resolveDateRange($request);
        $warehouseId = (int) ($request?->input('idalmacen') ?: $request?->input('id_almacen') ?: 0);
        $supplierId  = (int) ($request?->input('idproveedor') ?: 0);

        $query = DB::table('buys as b')
            ->join('providers as p', 'b.idproveedor', '=', 'p.id')
            ->where('b.estado', 1)
            ->whereBetween('b.fecha_emision', [$startDate, $endDate])
            ->selectRaw("
                p.id as idproveedor,
                p.nro_documento as documento,
                p.nombres as proveedor,
                COUNT(b.id) as cantidad_compras,
                CAST(SUM(b.gravada) AS DECIMAL(16,2)) as gravada,
                CAST(SUM(b.igv) AS DECIMAL(16,2)) as igv,
                CAST(SUM(b.total) AS DECIMAL(16,2)) as total_compras
            ")
            ->groupBy('p.id', 'p.nro_documento', 'p.nombres');

        if ($warehouseId > 0) {
            $query->where('b.idalmacen', $warehouseId);
        }

        if ($supplierId > 0) {
            $query->where('b.idproveedor', $supplierId);
        }

        $items = $query->orderByDesc('total_compras')->get();

        $totalPurchases = round($items->sum('total_compras'), 2);
        $totalCount     = $items->sum('cantidad_compras');

        return [
            'suppliers'       => $items->map(fn($item) => (array) $item)->toArray(),
            'total_compras'   => $totalPurchases,
            'total_ordenes'   => $totalCount,
            'signo'           => $this->currencySymbol(),
        ];
    }

    /**
     * 4. Report: Daily Cash (Caja Diaria / Arqueos)
     */
    public function getDailyCash(?Request $request = null, array $filters = []): array
    {
        [$startDate, $endDate] = $this->resolveDateRange($request);
        $cashId  = (int) ($request?->input('idcaja') ?: 0);
        $userId  = (int) ($request?->input('idusuario') ?: 0);
        $status  = $request?->input('estado');

        $query = DB::table('arching_cashes as ac')
            ->join('cashes as c', 'ac.idcaja', '=', 'c.id')
            ->join('users as u', 'ac.idusuario', '=', 'u.id')
            ->selectRaw("
                ac.id,
                ac.idcaja,
                c.descripcion as caja,
                ac.idusuario,
                u.nombres as usuario,
                ac.fecha_inicio,
                ac.fecha_fin,
                CAST(ac.monto_inicial AS DECIMAL(16,2)) as monto_inicial,
                CAST(COALESCE(ac.total_ventas, 0) AS DECIMAL(16,2)) as total_ventas,
                CAST(COALESCE(ac.total_ingresos, 0) AS DECIMAL(16,2)) as total_ingresos,
                CAST(COALESCE(ac.total_egresos, 0) AS DECIMAL(16,2)) as total_egresos,
                CAST(COALESCE(ac.monto_estimado, 0) AS DECIMAL(16,2)) as monto_estimado,
                CAST(COALESCE(ac.monto_final, 0) AS DECIMAL(16,2)) as monto_final,
                CAST(COALESCE(ac.diferencia, 0) AS DECIMAL(16,2)) as diferencia,
                ac.estado
            ")
            ->whereDate('ac.fecha_inicio', '>=', $startDate)
            ->whereDate('ac.fecha_inicio', '<=', $endDate);

        if ($cashId > 0) {
            $query->where('ac.idcaja', $cashId);
        }

        if ($userId > 0) {
            $query->where('ac.idusuario', $userId);
        }

        if ($status !== null && $status !== '') {
            $query->where('ac.estado', (int) $status);
        }

        $items = $query->orderByDesc('ac.fecha_inicio')->get()->map(function ($row) {
            $row->estado_label = (int) $row->estado === 1 ? 'Abierta' : 'Cerrada';
            $diff = (float) $row->diferencia;
            if ($diff > 0) {
                $row->resultado = 'Sobrante';
            } elseif ($diff < 0) {
                $row->resultado = 'Faltante';
            } else {
                $row->resultado = 'Cuadrado';
            }
            return (array) $row;
        });

        $totalFloat = round($items->sum('monto_inicial'), 2);
        $totalSales = round($items->sum('total_ventas'), 2);
        $totalCounted = round($items->sum('monto_final'), 2);
        $totalDiff = round($items->sum('diferencia'), 2);

        return [
            'sessions'       => $items->toArray(),
            'total_inicial'  => $totalFloat,
            'total_ventas'   => $totalSales,
            'total_contado'  => $totalCounted,
            'total_diferencia'=> $totalDiff,
            'signo'          => $this->currencySymbol(),
        ];
    }

    /**
     * 5. Report: Issued Vouchers and Statuses
     *
     * Comprehensive voucher audit listing billings and sale_notes with SUNAT and internal statuses.
     */
    public function getIssuedVouchers(?Request $request = null, array $filters = []): array
    {
        $sales = $this->getUnifiedSalesList($request, array_merge(['include_voided' => true, 'include_credit_notes' => true], $filters));

        $mapped = $sales->map(function ($row) {
            $statusLabel = 'Vigente';
            if ($row->anulado) {
                $statusLabel = 'Anulado';
            } elseif (! empty($row->sale_note_id) || ! empty($row->billing_id)) {
                $statusLabel = 'Canjeado';
            }

            return [
                'origin'            => $row->origin,
                'id'                => $row->id,
                'tipo_comprobante'  => $row->tipo_comprobante,
                'tipo_codigo'       => $row->tipo_comprobante_codigo,
                'numero_documento'  => $row->numero_documento,
                'fecha_emision'     => $row->fecha_emision,
                'hora'              => $row->hora,
                'cliente'           => $row->cliente,
                'cliente_documento' => $row->cliente_documento,
                'almacen'           => $row->almacen,
                'metodo_pago'       => $row->metodo_pago,
                'subtotal'          => $row->subtotal,
                'igv'               => $row->igv,
                'total'             => $row->total,
                'estado'            => $statusLabel,
                'anulado'           => $row->anulado,
            ];
        });

        $totalIssued = round($mapped->where('anulado', false)->sum('total'), 2);
        $totalVoided = round($mapped->where('anulado', true)->sum('total'), 2);

        return [
            'vouchers'        => $mapped->toArray(),
            'total_emitido'   => $totalIssued,
            'total_anulado'   => $totalVoided,
            'cantidad_total'  => $mapped->count(),
            'signo'           => $this->currencySymbol(),
        ];
    }

    /**
     * Convert / link a sale note into a billing document (canje de nota de venta).
     */
    public function convertSaleNoteToBilling(SaleNote $saleNote, Billing $billing): void
    {
        $saleNote->update([
            'billing_id' => $billing->id,
            'estado'     => 2, // Canjeada a comprobante
        ]);

        $billing->update([
            'sale_note_id' => $saleNote->id,
        ]);
    }

    /**
     * Reusable PDF Exporter
     */
    public function exportPdf(string $title, string $filename, array $headings, array $rows)
    {
        $business = Business::find(1);
        $pdf = Pdf::loadView('admin.reports.billings.pdf', [
            'title'       => $title,
            'headings'    => $headings,
            'rows'        => $rows,
            'business'    => $business,
            'generatedAt' => now(),
        ])->setPaper('a4', count($headings) > 6 ? 'landscape' : 'portrait');

        return $pdf->download($filename);
    }

    /**
     * Reusable Excel Exporter
     */
    public function exportExcel(string $title, string $filename, array $headings, array $rows): BinaryFileResponse
    {
        return Excel::download(new BillingReportExport($title, $headings, $rows), $filename);
    }
}
