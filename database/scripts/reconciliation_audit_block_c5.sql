-- =====================================================================================
-- ERP-FVC: SCRIPT DE AUDITORÍA Y CONCILIACIÓN INTEGRAL DE DATOS (BLOCK C5 / TRACK B)
-- Motor: MySQL 8.0+
-- Base de Datos: erp_fvc (o base de datos de pruebas activa)
-- Propósito: Verificar 6 invariantes críticas contables, comerciales, de existencias,
--           tesorería y convenios institucionales.
-- =====================================================================================

USE `erp_fvc`;

-- -------------------------------------------------------------------------------------
-- 1. SUM OF DEBITS = SUM OF CREDITS (PARTIDA DOBLE)
-- -------------------------------------------------------------------------------------

-- [1.1] Verificación por Asiento Individual: Débito vs Crédito en Líneas
-- Propósito: Detectar asientos contables donde la suma de débitos sea distinta a la de créditos.
-- Resultado Esperado: 0 filas retornadas (empty set).
-- Acción si falla: Si diff <> 0, revisar el mapeador contable (AccountingEventMapper), 
--                 reversar el asiento desbalanceado con JournalPostingService::postVoid()
--                 o regenerar líneas cuadradas.
SELECT 
    je.id AS journal_entry_id,
    je.entry_number,
    je.entry_date,
    je.status,
    ROUND(SUM(jel.debit), 2) AS total_debit_lines,
    ROUND(SUM(jel.credit), 2) AS total_credit_lines,
    ROUND(ABS(SUM(jel.debit) - SUM(jel.credit)), 2) AS imbalance
FROM journal_entries je
JOIN journal_entry_lines jel ON je.id = jel.journal_entry_id
GROUP BY je.id, je.entry_number, je.entry_date, je.status
HAVING imbalance > 0.00;

-- [1.2] Verificación de Consistencia Cabecera vs Líneas
-- Propósito: Asegurar que los campos total_debit y total_credit de journal_entries coincidan con sus líneas.
-- Resultado Esperado: 0 filas retornadas.
-- Acción si falla: Ejecutar UPDATE journal_entries je JOIN (...) SET total_debit = lines_debit, total_credit = lines_credit.
SELECT 
    je.id AS journal_entry_id,
    je.entry_number,
    je.total_debit AS header_debit,
    ROUND(SUM(jel.debit), 2) AS lines_debit,
    je.total_credit AS header_credit,
    ROUND(SUM(jel.credit), 2) AS lines_credit
FROM journal_entries je
JOIN journal_entry_lines jel ON je.id = jel.journal_entry_id
GROUP BY je.id, je.entry_number, je.total_debit, je.total_credit
HAVING header_debit <> lines_debit OR header_credit <> lines_credit;

-- [1.3] Verificación por Período Contable
-- Propósito: Verificar que la suma acumulada de débitos y créditos en cada período contable sea idéntica.
-- Resultado Esperado: 0 filas retornadas.
-- Acción si falla: No permitir el cierre del período (AccountingPeriodClosure) hasta conciliar el asiento descuadrado.
SELECT 
    ap.id AS period_id,
    ap.period_code,
    ap.fiscal_year,
    ap.month,
    ap.status AS period_status,
    ROUND(SUM(jel.debit), 2) AS period_debit,
    ROUND(SUM(jel.credit), 2) AS period_credit,
    ROUND(ABS(SUM(jel.debit) - SUM(jel.credit)), 2) AS period_imbalance
FROM accounting_periods ap
JOIN journal_entries je ON ap.id = je.accounting_period_id
JOIN journal_entry_lines jel ON je.id = jel.journal_entry_id
WHERE je.status = 'POSTED'
GROUP BY ap.id, ap.period_code, ap.fiscal_year, ap.month, ap.status
HAVING period_imbalance > 0.00;

-- [1.4] Verificación Global de Partida Doble
-- Propósito: Constatar que en todo el libro mayor histórico la suma global de débitos iguala a créditos.
-- Resultado Esperado: global_imbalance = 0.00.
-- Acción si falla: Correr `php artisan accounting:check-integrity --fix`.
SELECT 
    COUNT(DISTINCT je.id) AS total_posted_entries,
    ROUND(COALESCE(SUM(jel.debit), 0), 2) AS global_debit,
    ROUND(COALESCE(SUM(jel.credit), 0), 2) AS global_credit,
    ROUND(ABS(COALESCE(SUM(jel.debit), 0) - COALESCE(SUM(jel.credit), 0)), 2) AS global_imbalance
FROM journal_entries je
JOIN journal_entry_lines jel ON je.id = jel.journal_entry_id
WHERE je.status = 'POSTED';


-- -------------------------------------------------------------------------------------
-- 2. TOTAL SALES = INVOICES + STANDALONE SALE NOTES = GENERAL LEDGER (70x + 40111)
-- -------------------------------------------------------------------------------------

-- [2.1] Comparativa Global de Ventas Comerciales vs Libro Mayor
-- Propósito: Asegurar que la facturación comercial neta (Facturas + Boletas + Notas Venta no facturadas - NC)
--           coincida con el devengo registrado en las cuentas contables de ingreso (70x) e IGV (40111).
-- Resultado Esperado: sales_diff <= 0.05.
-- Acción si falla: Identificar comprobantes no contabilizados mediante [2.2] y ejecutar `php artisan accounting:backfill`.
WITH commercial_sales AS (
    SELECT 
        -- Facturas y Boletas activas
        ROUND(COALESCE(SUM(CASE WHEN b.idtipo_comprobante IN (1, 2) AND b.anulado = 0 THEN b.total ELSE 0 END), 0), 2) AS billings_total,
        -- Notas de Crédito activas (restan de ventas)
        ROUND(COALESCE(SUM(CASE WHEN b.idtipo_comprobante = 3 AND b.anulado = 0 THEN b.total ELSE 0 END), 0), 2) AS credit_notes_total,
        -- Notas de Venta independientes (no facturadas)
        (
            SELECT ROUND(COALESCE(SUM(sn.total), 0), 2)
            FROM sale_notes sn
            WHERE sn.estado = 1 AND sn.billing_id IS NULL
        ) AS standalone_sale_notes_total
    FROM billings b
),
ledger_sales AS (
    SELECT 
        ROUND(COALESCE(SUM(CASE WHEN coa.code LIKE '70%' THEN (jel.credit - jel.debit) ELSE 0 END), 0), 2) AS revenue_70_net,
        ROUND(COALESCE(SUM(CASE WHEN coa.code LIKE '40111%' THEN (jel.credit - jel.debit) ELSE 0 END), 0), 2) AS igv_40111_net,
        ROUND(COALESCE(SUM(CASE WHEN coa.code LIKE '70%' OR coa.code LIKE '40111%' THEN (jel.credit - jel.debit) ELSE 0 END), 0), 2) AS total_ledger_sales
    FROM journal_entry_lines jel
    JOIN journal_entries je ON jel.journal_entry_id = je.id
    JOIN chart_of_accounts coa ON jel.account_id = coa.id
    WHERE je.status = 'POSTED'
)
SELECT 
    cs.billings_total,
    cs.credit_notes_total,
    cs.standalone_sale_notes_total,
    ROUND((cs.billings_total + cs.standalone_sale_notes_total - cs.credit_notes_total), 2) AS net_commercial_sales,
    ls.revenue_70_net,
    ls.igv_40111_net,
    ls.total_ledger_sales,
    ROUND(ABS((cs.billings_total + cs.standalone_sale_notes_total - cs.credit_notes_total) - ls.total_ledger_sales), 2) AS sales_diff
FROM commercial_sales cs
CROSS JOIN ledger_sales ls;

-- [2.2] Comprobantes Comerciales Sin Asiento Contable Asociado
-- Propósito: Listar facturas o notas de venta activas que no generaron asiento en journal_entries.
-- Resultado Esperado: 0 filas retornadas.
-- Acción si falla: Ejecutar JournalPostingService::post($voucher) para cada registro huérfano.
SELECT 
    'BILLING' AS voucher_type,
    b.id,
    CONCAT(b.serie, '-', b.correlativo) AS document_number,
    b.fecha_emision,
    b.total
FROM billings b
LEFT JOIN journal_entries je ON je.source_type = 'App\\Models\\Billing' AND je.source_id = b.id AND je.status = 'POSTED'
WHERE b.anulado = 0 AND je.id IS NULL
UNION ALL
SELECT 
    'SALE_NOTE' AS voucher_type,
    sn.id,
    CONCAT(sn.serie, '-', sn.correlativo) AS document_number,
    sn.fecha_emision,
    sn.total
FROM sale_notes sn
LEFT JOIN journal_entries je ON je.source_type = 'App\\Models\\SaleNote' AND je.source_id = sn.id AND je.status = 'POSTED'
WHERE sn.estado = 1 AND sn.billing_id IS NULL AND je.id IS NULL;


-- -------------------------------------------------------------------------------------
-- 3. PHYSICAL STOCK IN stock_products = KARDEX BALANCE PER PRODUCT AND WAREHOUSE
-- -------------------------------------------------------------------------------------

-- [3.1] Comparación de Existencias Físicas vs Saldo Teórico del Kardex
-- Propósito: Comprobar que stock_products.stock_actual sea igual a:
--           (Stock Inicial + Compras + Traslados Receptor) - (Ventas Facturadas + Ventas Notas + Traslados Despacho).
-- Resultado Esperado: 0 filas retornadas con stock_diff > 0.
-- Acción si falla: Investigar desajustes de inventario físico, anular documentos duplicados o registrar ajuste formal.
WITH kardex_balance AS (
    SELECT 
        sp.idalmacen,
        w.descripcion AS warehouse_name,
        sp.idproducto,
        p.descripcion AS product_name,
        sp.stock_actual AS physical_stock,
        COALESCE(sp.stock_entrada, 0) AS initial_stock,
        (
            SELECT COALESCE(SUM(db.cantidad), 0)
            FROM detail_buys db
            JOIN buys b ON b.id = db.idcompra
            WHERE b.estado = 1 AND db.idalmacen = sp.idalmacen AND db.idproducto = sp.idproducto
        ) AS buys_qty,
        (
            SELECT COALESCE(SUM(dto.cantidad), 0)
            FROM detail_transfer_orders dto
            JOIN transfer_orders tor ON tor.id = dto.idorden_traslado
            WHERE tor.estado = 1 AND tor.idalmacen_receptor = sp.idalmacen AND dto.idproducto = sp.idproducto
        ) AS transfer_in_qty,
        (
            SELECT COALESCE(SUM(dbi.cantidad), 0)
            FROM detail_billings dbi
            JOIN billings bi ON bi.id = dbi.idfacturacion
            WHERE bi.anulado = 0 AND bi.idalmacen = sp.idalmacen AND dbi.idproducto = sp.idproducto
        ) AS billing_sales_qty,
        (
            SELECT COALESCE(SUM(dsn.cantidad), 0)
            FROM detail_sale_notes dsn
            JOIN sale_notes sn ON sn.id = dsn.idnotaventa
            WHERE sn.estado = 1 AND sn.billing_id IS NULL AND dsn.idalmacen = sp.idalmacen AND dsn.idproducto = sp.idproducto
        ) AS sale_note_sales_qty,
        (
            SELECT COALESCE(SUM(dto2.cantidad), 0)
            FROM detail_transfer_orders dto2
            JOIN transfer_orders tor2 ON tor2.id = dto2.idorden_traslado
            WHERE tor2.estado = 1 AND tor2.idalmacen_despacho = sp.idalmacen AND dto2.idproducto = sp.idproducto
        ) AS transfer_out_qty
    FROM stock_products sp
    JOIN products p ON sp.idproducto = p.id
    JOIN warehouses w ON sp.idalmacen = w.id
    WHERE p.opcion = 1 -- Solo bienes físicos tangibles
)
SELECT 
    kb.idalmacen,
    kb.warehouse_name,
    kb.idproducto,
    kb.product_name,
    kb.physical_stock,
    ROUND((kb.initial_stock + kb.buys_qty + kb.transfer_in_qty - kb.billing_sales_qty - kb.sale_note_sales_qty - kb.transfer_out_qty), 2) AS calculated_kardex_stock,
    ROUND(ABS(kb.physical_stock - (kb.initial_stock + kb.buys_qty + kb.transfer_in_qty - kb.billing_sales_qty - kb.sale_note_sales_qty - kb.transfer_out_qty)), 2) AS stock_diff
FROM kardex_balance kb
HAVING stock_diff > 0.001;

-- [3.2] Invariante de Exclusión de Servicios en Almacén
-- Propósito: Garantizar que ningún servicio (opcion = 2) posea existencias o registros en stock_products.
-- Resultado Esperado: 0 filas retornadas.
-- Acción si falla: Ejecutar DELETE FROM stock_products WHERE idproducto IN (SELECT id FROM products WHERE opcion = 2).
SELECT 
    sp.id,
    sp.idalmacen,
    sp.idproducto,
    p.descripcion AS service_name,
    sp.stock_actual
FROM stock_products sp
JOIN products p ON sp.idproducto = p.id
WHERE p.opcion = 2;


-- -------------------------------------------------------------------------------------
-- 4. CASH: EXPECTED COUNT RESULT = RECORDED PAYMENTS BY PAYMENT METHOD
-- -------------------------------------------------------------------------------------

-- [4.1] Cuadre Teórico de Sesiones de Caja Cerradas
-- Propósito: Verificar que el monto_estimado en arqueos de caja cerrados coincida con:
--           monto_inicial + Pagos en Efectivo + Movimientos de Ingreso - Movimientos de Egreso.
-- Resultado Esperado: 0 filas retornadas con diff > 0.05.
-- Acción si falla: Recalcular `monto_estimado` = monto_inicial + vtas_efectivo + ingresos - egresos.
WITH cash_audit AS (
    SELECT 
        ac.id AS arching_id,
        ac.fecha_inicio,
        ac.fecha_fin,
        COALESCE(ac.monto_inicial, 0) AS opening_balance,
        COALESCE(ac.monto_final, 0) AS physical_count_closing,
        COALESCE(ac.monto_estimado, 0) AS recorded_system_estimate,
        (
            SELECT COALESCE(SUM(dp.monto), 0)
            FROM detail_payments dp
            WHERE dp.idarqueocaja = ac.id AND dp.idpago = 1 AND dp.estado = 1
        ) AS cash_payments_total,
        (
            SELECT COALESCE(SUM(dp2.monto), 0)
            FROM detail_payments dp2
            WHERE dp2.idarqueocaja = ac.id AND dp2.idpago <> 1 AND dp2.estado = 1
        ) AS electronic_payments_total,
        (
            SELECT COALESCE(SUM(cm.monto), 0)
            FROM cash_movements cm
            WHERE cm.idarqueocaja = ac.id AND cm.tipo = 'ingreso' AND cm.estado = 1
        ) AS external_cash_inflow,
        (
            SELECT COALESCE(SUM(cm2.monto), 0)
            FROM cash_movements cm2
            WHERE cm2.idarqueocaja = ac.id AND cm2.tipo = 'egreso' AND cm2.estado = 1
        ) AS external_cash_outflow
    FROM arching_cashes ac
    WHERE ac.monto_final IS NOT NULL -- Arqueos cerrados
)
SELECT 
    ca.arching_id,
    ca.fecha_inicio,
    ca.opening_balance,
    ca.cash_payments_total,
    ca.external_cash_inflow,
    ca.external_cash_outflow,
    ROUND((ca.opening_balance + ca.cash_payments_total + ca.external_cash_inflow - ca.external_cash_outflow), 2) AS calculated_expected_cash,
    ca.recorded_system_estimate,
    ca.physical_count_closing,
    ROUND(ABS((ca.opening_balance + ca.cash_payments_total + ca.external_cash_inflow - ca.external_cash_outflow) - ca.recorded_system_estimate), 2) AS diff
FROM cash_audit ca
HAVING diff > 0.05;

-- [4.2] Resumen de Cobranzas por Medio de Pago en Caja
-- Propósito: Desglosar las ventas cobradas por canal de pago para conciliar con depósitos bancarios o billeteras.
SELECT 
    pm.id AS pay_mode_id,
    pm.descripcion AS payment_method,
    COUNT(dp.id) AS transaction_count,
    ROUND(COALESCE(SUM(dp.monto), 0), 2) AS total_collected
FROM pay_modes pm
LEFT JOIN detail_payments dp ON pm.id = dp.idpago AND dp.estado = 1
GROUP BY pm.id, pm.descripcion
ORDER BY total_collected DESC;


-- -------------------------------------------------------------------------------------
-- 5. RECONCILED BANK BALANCE = ACCOUNTING BALANCE (ACCOUNT 104x)
-- -------------------------------------------------------------------------------------

-- [5.1] Conciliación de Cuentas Bancarias vs Saldo en Libro Mayor
-- Propósito: Comprobar que el saldo actual registrado en bank_accounts (o extracto conciliado)
--           sea idéntico al saldo neto acumulado (Débitos - Créditos) de su cuenta contable 104x.
-- Resultado Esperado: balance_diff <= 0.05 en todas las cuentas activas.
-- Acción si falla: Revisar transacciones bancarias no contabilizadas en bank_movements o partidas en tránsito.
SELECT 
    ba.id AS bank_account_id,
    ba.bank_name,
    ba.account_number,
    coa.code AS account_code,
    coa.name AS account_name,
    ROUND(COALESCE(ba.current_balance, 0), 2) AS bank_current_balance,
    ROUND(COALESCE(SUM(jel.debit - jel.credit), 0), 2) AS general_ledger_balance,
    ROUND(ABS(COALESCE(ba.current_balance, 0) - COALESCE(SUM(jel.debit - jel.credit), 0)), 2) AS balance_diff
FROM bank_accounts ba
JOIN chart_of_accounts coa ON ba.accounting_account_id = coa.id
LEFT JOIN journal_entry_lines jel ON jel.account_id = coa.id
LEFT JOIN journal_entries je ON jel.journal_entry_id = je.id AND je.status = 'POSTED'
WHERE ba.is_active = 1
GROUP BY ba.id, ba.bank_name, ba.account_number, coa.code, coa.name, ba.current_balance
HAVING balance_diff > 0.05;


-- -------------------------------------------------------------------------------------
-- 6. AGREEMENT-BASED REVENUE = LINKED INVOICES = GENERAL LEDGER (TRACK B)
-- -------------------------------------------------------------------------------------

-- [6.1] Conciliación Cuota de Convenio = Comprobante Emitido = Asiento Mayor
-- Propósito: Garantizar la regla del Track B:
--           1. Monto de la cuota facturada = Total del comprobante vinculado (Factura, Boleta o Nota de Venta).
--           2. Total del comprobante = Devengo en cuentas 70x + 40111 en Libro Mayor.
--           3. Nunca existe doble vinculación (Factura Y Nota de Venta simultáneas).
-- Resultado Esperado: 0 filas retornadas con discrepancy_type <> 'OK'.
-- Acción si falla: Si installment_voucher_diff > 0, corregir monto de cuota o ajustar comprobante;
--                 si voucher_ledger_diff > 0, postear asiento del comprobante con JournalPostingService.
SELECT 
    ai.id AS installment_id,
    a.code AS agreement_code,
    a.title AS agreement_title,
    ai.installment_number,
    ai.status AS installment_status,
    ROUND(ai.amount, 2) AS installment_amount,
    ai.billing_id,
    ROUND(COALESCE(b.total, 0), 2) AS billing_total,
    ai.sale_note_id,
    ROUND(COALESCE(sn.total, 0), 2) AS sale_note_total,
    ROUND(COALESCE(b.total, sn.total, 0), 2) AS linked_voucher_total,
    (
        SELECT ROUND(COALESCE(SUM(jel.credit), 0), 2)
        FROM journal_entry_lines jel
        JOIN journal_entries je ON jel.journal_entry_id = je.id
        JOIN chart_of_accounts coa ON jel.account_id = coa.id
        WHERE je.status = 'POSTED'
          AND (coa.code LIKE '70%' OR coa.code LIKE '40111%')
          AND (
              (je.source_type = 'App\\Models\\Billing' AND je.source_id = ai.billing_id) OR
              (je.source_type = 'App\\Models\\SaleNote' AND je.source_id = ai.sale_note_id)
          )
    ) AS ledger_revenue_total,
    CASE 
        WHEN ai.billing_id IS NOT NULL AND ai.sale_note_id IS NOT NULL THEN 'ERROR_DUAL_VOUCHER_LINK'
        WHEN ABS(ai.amount - COALESCE(b.total, sn.total, 0)) > 0.05 THEN 'ERROR_INSTALLMENT_VOUCHER_MISMATCH'
        WHEN (ai.billing_id IS NOT NULL OR ai.sale_note_id IS NOT NULL) AND 
             (SELECT COUNT(*) FROM journal_entries WHERE status = 'POSTED' AND ((source_type = 'App\\Models\\Billing' AND source_id = ai.billing_id) OR (source_type = 'App\\Models\\SaleNote' AND source_id = ai.sale_note_id))) = 0 THEN 'WARNING_UNPOSTED_LEDGER'
        ELSE 'OK'
    END AS reconciliation_status
FROM agreement_installments ai
JOIN agreements a ON ai.agreement_id = a.id
LEFT JOIN billings b ON ai.billing_id = b.id
LEFT JOIN sale_notes sn ON ai.sale_note_id = sn.id
WHERE ai.billing_id IS NOT NULL OR ai.sale_note_id IS NOT NULL OR ai.status IN ('INVOICED', 'COLLECTED')
HAVING reconciliation_status <> 'OK';

-- [6.2] Verificación de Inexistencia de Asientos Directos Duplicados de Convenios
-- Propósito: Confirmar que NO existan asientos directos a 70x con source_type = 'App\Models\Agreement'
--           o 'App\Models\AgreementInstallment', cumpliendo la regla de que el devengo transita
--           exclusivamente a través del comprobante comercial (Track B).
-- Resultado Esperado: 0 filas retornadas.
-- Acción si falla: Anular asientos directos huérfanos que duplican el ingreso de la facturación.
SELECT 
    je.id,
    je.entry_number,
    je.entry_date,
    je.source_type,
    je.source_id,
    je.concept,
    je.total_credit
FROM journal_entries je
WHERE je.source_type IN (
    'App\\Models\\Agreement',
    'App\\Models\\AgreementInstallment',
    'Agreement',
    'AgreementInstallment'
);
