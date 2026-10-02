<?php

namespace Database\Seeders;

use App\Enums\AccountNature;
use App\Enums\AccountType;
use App\Models\ChartOfAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ChartOfAccountsSeeder extends Seeder
{
    /**
     * Parameterized configuration for tax accounts.
     */
    public string $igvAccountCode = '40111';
    public string $irAccountCode = '4017';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $accounts = [
            // ==========================================
            // CLASE 1: ELEMENTO 1 - ACTIVO DISPONIBLE Y EXIGIBLE
            // ==========================================
            ['code' => '1', 'name' => 'ELEMENTO 1: ACTIVO DISPONIBLE Y EXIGIBLE', 'element' => 1, 'level' => 1, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::ACTIVO, 'accepts' => false],
            ['code' => '10', 'name' => 'Efectivo y equivalentes de efectivo', 'element' => 1, 'level' => 2, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::ACTIVO, 'accepts' => false, 'parent' => '1'],
            ['code' => '101', 'name' => 'Caja', 'element' => 1, 'level' => 3, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::ACTIVO, 'accepts' => false, 'parent' => '10'],
            ['code' => '1011', 'name' => 'Caja Central', 'element' => 1, 'level' => 4, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::ACTIVO, 'accepts' => true, 'parent' => '101'],
            ['code' => '1012', 'name' => 'Caja Chica / Mostrador POS', 'element' => 1, 'level' => 4, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::ACTIVO, 'accepts' => true, 'parent' => '101'],
            ['code' => '102', 'name' => 'Fondos fijos de caja', 'element' => 1, 'level' => 3, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::ACTIVO, 'accepts' => true, 'parent' => '10'],
            ['code' => '103', 'name' => 'Efectivo en tránsito', 'element' => 1, 'level' => 3, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::ACTIVO, 'accepts' => false, 'parent' => '10'],
            ['code' => '1031', 'name' => 'Remesas en tránsito / POS Tarjetas', 'element' => 1, 'level' => 4, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::ACTIVO, 'accepts' => true, 'parent' => '103'],
            ['code' => '104', 'name' => 'Cuentas corrientes en instituciones financieras', 'element' => 1, 'level' => 3, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::ACTIVO, 'accepts' => false, 'parent' => '10'],
            ['code' => '1041', 'name' => 'Cuentas corrientes operativas', 'element' => 1, 'level' => 4, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::ACTIVO, 'accepts' => false, 'parent' => '104'],
            ['code' => '10411', 'name' => 'Banco de la Nación - Cta. Recaudadora RDR', 'element' => 1, 'level' => 5, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::ACTIVO, 'accepts' => true, 'parent' => '1041', 'req_fund' => true],
            ['code' => '10412', 'name' => 'Cooperativa de Ahorro y Crédito Tocache', 'element' => 1, 'level' => 5, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::ACTIVO, 'accepts' => true, 'parent' => '1041', 'req_fund' => true],
            ['code' => '107', 'name' => 'Fondos sujetos a restricción', 'element' => 1, 'level' => 3, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::ACTIVO, 'accepts' => false, 'parent' => '10'],
            ['code' => '1071', 'name' => 'Cuenta Única del Tesoro (CUT)', 'element' => 1, 'level' => 4, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::ACTIVO, 'accepts' => true, 'parent' => '107', 'req_fund' => true],

            ['code' => '12', 'name' => 'Cuentas por cobrar comerciales - Terceros', 'element' => 1, 'level' => 2, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::ACTIVO, 'accepts' => false, 'parent' => '1'],
            ['code' => '121', 'name' => 'Facturas, boletas y otros comprobantes por cobrar', 'element' => 1, 'level' => 3, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::ACTIVO, 'accepts' => false, 'parent' => '12'],
            ['code' => '1212', 'name' => 'Emitidas en cartera', 'element' => 1, 'level' => 4, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::ACTIVO, 'accepts' => true, 'parent' => '121', 'req_third' => true],
            ['code' => '1213', 'name' => 'En cobranza', 'element' => 1, 'level' => 4, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::ACTIVO, 'accepts' => true, 'parent' => '121', 'req_third' => true],
            ['code' => '1219', 'name' => 'Otras cuentas por cobrar (Notas de Venta)', 'element' => 1, 'level' => 4, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::ACTIVO, 'accepts' => true, 'parent' => '121', 'req_third' => true],

            ['code' => '14', 'name' => 'Cuentas por cobrar al personal y directores', 'element' => 1, 'level' => 2, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::ACTIVO, 'accepts' => false, 'parent' => '1'],
            ['code' => '141', 'name' => 'Personal', 'element' => 1, 'level' => 3, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::ACTIVO, 'accepts' => false, 'parent' => '14'],
            ['code' => '1413', 'name' => 'Préstamos al personal y viáticos de campo', 'element' => 1, 'level' => 4, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::ACTIVO, 'accepts' => true, 'parent' => '141', 'req_third' => true, 'req_cost' => true],

            ['code' => '16', 'name' => 'Cuentas por cobrar diversas - Terceros', 'element' => 1, 'level' => 2, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::ACTIVO, 'accepts' => false, 'parent' => '1'],
            ['code' => '162', 'name' => 'Reclamos a terceros', 'element' => 1, 'level' => 3, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::ACTIVO, 'accepts' => false, 'parent' => '16'],
            ['code' => '1629', 'name' => 'Faltantes de caja sujetos a regularización', 'element' => 1, 'level' => 4, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::ACTIVO, 'accepts' => true, 'parent' => '162', 'req_third' => true],

            // ==========================================
            // CLASE 2: ELEMENTO 2 - ACTIVO REALIZABLE
            // ==========================================
            ['code' => '2', 'name' => 'ELEMENTO 2: ACTIVO REALIZABLE', 'element' => 2, 'level' => 1, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::ACTIVO, 'accepts' => false],
            ['code' => '20', 'name' => 'Mercaderías', 'element' => 2, 'level' => 2, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::ACTIVO, 'accepts' => false, 'parent' => '2'],
            ['code' => '201', 'name' => 'Mercaderías', 'element' => 2, 'level' => 3, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::ACTIVO, 'accepts' => false, 'parent' => '20'],
            ['code' => '2011', 'name' => 'Mercaderías manufacturadas y comerciales', 'element' => 2, 'level' => 4, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::ACTIVO, 'accepts' => false, 'parent' => '201'],
            ['code' => '20111', 'name' => 'Mercaderías generales en almacén', 'element' => 2, 'level' => 5, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::ACTIVO, 'accepts' => true, 'parent' => '2011'],

            ['code' => '21', 'name' => 'Productos terminados', 'element' => 2, 'level' => 2, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::ACTIVO, 'accepts' => false, 'parent' => '2'],
            ['code' => '211', 'name' => 'Productos agropecuarios y piscícolas terminados', 'element' => 2, 'level' => 3, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::ACTIVO, 'accepts' => true, 'parent' => '21', 'req_cost' => true],
            ['code' => '212', 'name' => 'Productos transformados agroindustriales', 'element' => 2, 'level' => 3, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::ACTIVO, 'accepts' => true, 'parent' => '21', 'req_cost' => true],

            ['code' => '23', 'name' => 'Productos en proceso', 'element' => 2, 'level' => 2, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::ACTIVO, 'accepts' => false, 'parent' => '2'],
            ['code' => '231', 'name' => 'Cultivos agrícolas y forestales en desarrollo', 'element' => 2, 'level' => 3, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::ACTIVO, 'accepts' => true, 'parent' => '23', 'req_cost' => true],

            ['code' => '24', 'name' => 'Materias primas', 'element' => 2, 'level' => 2, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::ACTIVO, 'accepts' => false, 'parent' => '2'],
            ['code' => '241', 'name' => 'Materias primas e insumos de campo y producción', 'element' => 2, 'level' => 3, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::ACTIVO, 'accepts' => true, 'parent' => '24'],

            // ==========================================
            // CLASE 3: ELEMENTO 3 - ACTIVO INMOVILIZADO
            // ==========================================
            ['code' => '3', 'name' => 'ELEMENTO 3: ACTIVO INMOVILIZADO', 'element' => 3, 'level' => 1, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::ACTIVO, 'accepts' => false],
            ['code' => '33', 'name' => 'Propiedad, planta y equipo', 'element' => 3, 'level' => 2, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::ACTIVO, 'accepts' => false, 'parent' => '3'],
            ['code' => '333', 'name' => 'Maquinaria y equipos de explotación', 'element' => 3, 'level' => 3, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::ACTIVO, 'accepts' => true, 'parent' => '33'],
            ['code' => '336', 'name' => 'Equipos diversos', 'element' => 3, 'level' => 3, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::ACTIVO, 'accepts' => true, 'parent' => '33'],

            ['code' => '35', 'name' => 'Activos biológicos', 'element' => 3, 'level' => 2, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::ACTIVO, 'accepts' => false, 'parent' => '3'],
            ['code' => '351', 'name' => 'Activos biológicos en producción (plantaciones / ganado)', 'element' => 3, 'level' => 3, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::ACTIVO, 'accepts' => true, 'parent' => '35', 'req_cost' => true],

            ['code' => '39', 'name' => 'Depreciación y amortización acumulados', 'element' => 3, 'level' => 2, 'nature' => AccountNature::CREDIT, 'classification' => AccountType::ACTIVO, 'accepts' => false, 'parent' => '3'],
            ['code' => '391', 'name' => 'Depreciación acumulada', 'element' => 3, 'level' => 3, 'nature' => AccountNature::CREDIT, 'classification' => AccountType::ACTIVO, 'accepts' => true, 'parent' => '39'],

            // ==========================================
            // CLASE 4: ELEMENTO 4 - PASIVO
            // ==========================================
            ['code' => '4', 'name' => 'ELEMENTO 4: PASIVO', 'element' => 4, 'level' => 1, 'nature' => AccountNature::CREDIT, 'classification' => AccountType::PASIVO, 'accepts' => false],
            ['code' => '40', 'name' => 'Tributos, contraprestaciones y aportes por pagar', 'element' => 4, 'level' => 2, 'nature' => AccountNature::CREDIT, 'classification' => AccountType::PASIVO, 'accepts' => false, 'parent' => '4'],
            ['code' => '401', 'name' => 'Gobierno central', 'element' => 4, 'level' => 3, 'nature' => AccountNature::CREDIT, 'classification' => AccountType::PASIVO, 'accepts' => false, 'parent' => '40'],
            ['code' => '4011', 'name' => 'Impuesto General a las Ventas (IGV)', 'element' => 4, 'level' => 4, 'nature' => AccountNature::CREDIT, 'classification' => AccountType::PASIVO, 'accepts' => false, 'parent' => '401'],
            ['code' => '40111', 'name' => 'IGV - Cuenta propia (Débito Fiscal)', 'element' => 4, 'level' => 5, 'nature' => AccountNature::CREDIT, 'classification' => AccountType::PASIVO, 'accepts' => true, 'parent' => '4011'],
            ['code' => '40112', 'name' => 'IGV - Crédito fiscal por compras', 'element' => 4, 'level' => 5, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::PASIVO, 'accepts' => true, 'parent' => '4011'],
            ['code' => '4017', 'name' => 'Impuesto a la Renta (IR)', 'element' => 4, 'level' => 4, 'nature' => AccountNature::CREDIT, 'classification' => AccountType::PASIVO, 'accepts' => false, 'parent' => '401'],
            ['code' => '40171', 'name' => 'Renta Tercera Categoría', 'element' => 4, 'level' => 5, 'nature' => AccountNature::CREDIT, 'classification' => AccountType::PASIVO, 'accepts' => true, 'parent' => '4017'],
            ['code' => '40172', 'name' => 'Renta Cuarta Categoría (Retenciones recibos)', 'element' => 4, 'level' => 5, 'nature' => AccountNature::CREDIT, 'classification' => AccountType::PASIVO, 'accepts' => true, 'parent' => '4017'],

            ['code' => '41', 'name' => 'Remuneraciones y participaciones por pagar', 'element' => 4, 'level' => 2, 'nature' => AccountNature::CREDIT, 'classification' => AccountType::PASIVO, 'accepts' => false, 'parent' => '4'],
            ['code' => '411', 'name' => 'Remuneraciones y jornales por pagar', 'element' => 4, 'level' => 3, 'nature' => AccountNature::CREDIT, 'classification' => AccountType::PASIVO, 'accepts' => true, 'parent' => '41', 'req_third' => true],

            ['code' => '42', 'name' => 'Cuentas por pagar comerciales - Terceros', 'element' => 4, 'level' => 2, 'nature' => AccountNature::CREDIT, 'classification' => AccountType::PASIVO, 'accepts' => false, 'parent' => '4'],
            ['code' => '421', 'name' => 'Facturas, boletas y otros comprobantes por pagar', 'element' => 4, 'level' => 3, 'nature' => AccountNature::CREDIT, 'classification' => AccountType::PASIVO, 'accepts' => false, 'parent' => '42'],
            ['code' => '4212', 'name' => 'Emitidas por proveedores', 'element' => 4, 'level' => 4, 'nature' => AccountNature::CREDIT, 'classification' => AccountType::PASIVO, 'accepts' => true, 'parent' => '421', 'req_third' => true],

            // ==========================================
            // CLASE 5: ELEMENTO 5 - PATRIMONIO NETO
            // ==========================================
            ['code' => '5', 'name' => 'ELEMENTO 5: PATRIMONIO NETO', 'element' => 5, 'level' => 1, 'nature' => AccountNature::CREDIT, 'classification' => AccountType::PATRIMONIO, 'accepts' => false],
            ['code' => '50', 'name' => 'Capital / Fondo Institucional', 'element' => 5, 'level' => 2, 'nature' => AccountNature::CREDIT, 'classification' => AccountType::PATRIMONIO, 'accepts' => false, 'parent' => '5'],
            ['code' => '501', 'name' => 'Hacienda Nacional y Fondo Institucional', 'element' => 5, 'level' => 3, 'nature' => AccountNature::CREDIT, 'classification' => AccountType::PATRIMONIO, 'accepts' => true, 'parent' => '50'],
            ['code' => '59', 'name' => 'Resultados acumulados', 'element' => 5, 'level' => 2, 'nature' => AccountNature::CREDIT, 'classification' => AccountType::PATRIMONIO, 'accepts' => false, 'parent' => '5'],
            ['code' => '591', 'name' => 'Utilidades no distribuidas', 'element' => 5, 'level' => 3, 'nature' => AccountNature::CREDIT, 'classification' => AccountType::PATRIMONIO, 'accepts' => true, 'parent' => '59'],

            // ==========================================
            // CLASE 6: ELEMENTO 6 - GASTOS POR NATURALEZA
            // ==========================================
            ['code' => '6', 'name' => 'ELEMENTO 6: GASTOS POR NATURALEZA', 'element' => 6, 'level' => 1, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::GASTOS_NATURALEZA, 'accepts' => false],
            ['code' => '60', 'name' => 'Compras', 'element' => 6, 'level' => 2, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::GASTOS_NATURALEZA, 'accepts' => false, 'parent' => '6'],
            ['code' => '601', 'name' => 'Mercaderías', 'element' => 6, 'level' => 3, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::GASTOS_NATURALEZA, 'accepts' => false, 'parent' => '60'],
            ['code' => '6011', 'name' => 'Mercaderías para reventa', 'element' => 6, 'level' => 4, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::GASTOS_NATURALEZA, 'accepts' => true, 'parent' => '601'],
            ['code' => '602', 'name' => 'Materias primas e insumos agropecuarios', 'element' => 6, 'level' => 3, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::GASTOS_NATURALEZA, 'accepts' => true, 'parent' => '60'],

            ['code' => '61', 'name' => 'Variación de inventarios', 'element' => 6, 'level' => 2, 'nature' => AccountNature::CREDIT, 'classification' => AccountType::GASTOS_NATURALEZA, 'accepts' => false, 'parent' => '6'],
            ['code' => '611', 'name' => 'Mercaderías', 'element' => 6, 'level' => 3, 'nature' => AccountNature::CREDIT, 'classification' => AccountType::GASTOS_NATURALEZA, 'accepts' => false, 'parent' => '61'],
            ['code' => '6111', 'name' => 'Variación de existencias - Mercaderías', 'element' => 6, 'level' => 4, 'nature' => AccountNature::CREDIT, 'classification' => AccountType::GASTOS_NATURALEZA, 'accepts' => true, 'parent' => '611'],

            ['code' => '62', 'name' => 'Gastos de personal y directores', 'element' => 6, 'level' => 2, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::GASTOS_NATURALEZA, 'accepts' => false, 'parent' => '6'],
            ['code' => '621', 'name' => 'Remuneraciones y jornales de campo', 'element' => 6, 'level' => 3, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::GASTOS_NATURALEZA, 'accepts' => true, 'parent' => '62', 'req_cost' => true],

            ['code' => '63', 'name' => 'Gastos de servicios prestados por terceros', 'element' => 6, 'level' => 2, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::GASTOS_NATURALEZA, 'accepts' => false, 'parent' => '6'],
            ['code' => '631', 'name' => 'Transporte, correos y gastos de viaje (viáticos)', 'element' => 6, 'level' => 3, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::GASTOS_NATURALEZA, 'accepts' => true, 'parent' => '63', 'req_cost' => true],
            ['code' => '634', 'name' => 'Mantenimiento y reparaciones de maquinaria', 'element' => 6, 'level' => 3, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::GASTOS_NATURALEZA, 'accepts' => true, 'parent' => '63', 'req_cost' => true],
            ['code' => '636', 'name' => 'Servicios básicos (luz, agua, internet)', 'element' => 6, 'level' => 3, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::GASTOS_NATURALEZA, 'accepts' => true, 'parent' => '63'],

            ['code' => '65', 'name' => 'Otros gastos de gestión', 'element' => 6, 'level' => 2, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::GASTOS_NATURALEZA, 'accepts' => false, 'parent' => '6'],
            ['code' => '659', 'name' => 'Gastos de gestión de campo (Declaraciones Juradas)', 'element' => 6, 'level' => 3, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::GASTOS_NATURALEZA, 'accepts' => true, 'parent' => '65', 'req_cost' => true],

            ['code' => '67', 'name' => 'Gastos financieros', 'element' => 6, 'level' => 2, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::GASTOS_NATURALEZA, 'accepts' => false, 'parent' => '6'],
            ['code' => '676', 'name' => 'Pérdida por diferencia de cambio', 'element' => 6, 'level' => 3, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::GASTOS_NATURALEZA, 'accepts' => true, 'parent' => '67'],

            ['code' => '69', 'name' => 'Costo de ventas', 'element' => 6, 'level' => 2, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::GASTOS_NATURALEZA, 'accepts' => false, 'parent' => '6'],
            ['code' => '691', 'name' => 'Costo de ventas - Mercaderías', 'element' => 6, 'level' => 3, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::GASTOS_NATURALEZA, 'accepts' => true, 'parent' => '69'],
            ['code' => '692', 'name' => 'Costo de ventas - Productos agropecuarios', 'element' => 6, 'level' => 3, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::GASTOS_NATURALEZA, 'accepts' => true, 'parent' => '69', 'req_cost' => true],

            // ==========================================
            // CLASE 7: ELEMENTO 7 - INGRESOS
            // ==========================================
            ['code' => '7', 'name' => 'ELEMENTO 7: INGRESOS', 'element' => 7, 'level' => 1, 'nature' => AccountNature::CREDIT, 'classification' => AccountType::INGRESOS, 'accepts' => false],
            ['code' => '70', 'name' => 'Ventas', 'element' => 7, 'level' => 2, 'nature' => AccountNature::CREDIT, 'classification' => AccountType::INGRESOS, 'accepts' => false, 'parent' => '7'],
            ['code' => '701', 'name' => 'Mercaderías', 'element' => 7, 'level' => 3, 'nature' => AccountNature::CREDIT, 'classification' => AccountType::INGRESOS, 'accepts' => false, 'parent' => '70'],
            ['code' => '7011', 'name' => 'Mercaderías manufacturadas', 'element' => 7, 'level' => 4, 'nature' => AccountNature::CREDIT, 'classification' => AccountType::INGRESOS, 'accepts' => false, 'parent' => '701'],
            ['code' => '70111', 'name' => 'Venta de mercaderías comerciales', 'element' => 7, 'level' => 5, 'nature' => AccountNature::CREDIT, 'classification' => AccountType::INGRESOS, 'accepts' => true, 'parent' => '7011'],
            ['code' => '702', 'name' => 'Venta de productos agropecuarios e industriales', 'element' => 7, 'level' => 3, 'nature' => AccountNature::CREDIT, 'classification' => AccountType::INGRESOS, 'accepts' => true, 'parent' => '70', 'req_cost' => true],
            ['code' => '704', 'name' => 'Prestación de servicios institucionales', 'element' => 7, 'level' => 3, 'nature' => AccountNature::CREDIT, 'classification' => AccountType::INGRESOS, 'accepts' => true, 'parent' => '70', 'req_cost' => true],

            ['code' => '75', 'name' => 'Otros ingresos de gestión', 'element' => 7, 'level' => 2, 'nature' => AccountNature::CREDIT, 'classification' => AccountType::INGRESOS, 'accepts' => false, 'parent' => '7'],
            ['code' => '759', 'name' => 'Otros ingresos (sobrantes de caja, ingresos directos APE)', 'element' => 7, 'level' => 3, 'nature' => AccountNature::CREDIT, 'classification' => AccountType::INGRESOS, 'accepts' => true, 'parent' => '75', 'req_cost' => true],

            ['code' => '77', 'name' => 'Ingresos financieros', 'element' => 7, 'level' => 2, 'nature' => AccountNature::CREDIT, 'classification' => AccountType::INGRESOS, 'accepts' => false, 'parent' => '7'],
            ['code' => '776', 'name' => 'Ganancia por diferencia de cambio', 'element' => 7, 'level' => 3, 'nature' => AccountNature::CREDIT, 'classification' => AccountType::INGRESOS, 'accepts' => true, 'parent' => '77'],

            ['code' => '79', 'name' => 'Cargas imputables a cuentas de costos y gastos', 'element' => 7, 'level' => 2, 'nature' => AccountNature::CREDIT, 'classification' => AccountType::INGRESOS, 'accepts' => false, 'parent' => '7'],
            ['code' => '791', 'name' => 'Cargas imputables a cuentas de costos y gastos (contrapartida elemento 9)', 'element' => 7, 'level' => 3, 'nature' => AccountNature::CREDIT, 'classification' => AccountType::INGRESOS, 'accepts' => true, 'parent' => '79'],

            // ==========================================
            // CLASE 8: ELEMENTO 8 - SALDOS INTERMEDIARIOS DE GESTIÓN
            // ==========================================
            ['code' => '8', 'name' => 'ELEMENTO 8: SALDOS INTERMEDIARIOS DE GESTIÓN', 'element' => 8, 'level' => 1, 'nature' => AccountNature::CREDIT, 'classification' => AccountType::GASTOS_FUNCION, 'accepts' => false],
            ['code' => '80', 'name' => 'Margen comercial', 'element' => 8, 'level' => 2, 'nature' => AccountNature::CREDIT, 'classification' => AccountType::GASTOS_FUNCION, 'accepts' => true, 'parent' => '8'],
            ['code' => '89', 'name' => 'Determinación del resultado del ejercicio', 'element' => 8, 'level' => 2, 'nature' => AccountNature::CREDIT, 'classification' => AccountType::GASTOS_FUNCION, 'accepts' => false, 'parent' => '8'],
            ['code' => '891', 'name' => 'Utilidad del ejercicio', 'element' => 8, 'level' => 3, 'nature' => AccountNature::CREDIT, 'classification' => AccountType::GASTOS_FUNCION, 'accepts' => true, 'parent' => '89'],

            // ==========================================
            // CLASE 9: ELEMENTO 9 - COSTOS DE PRODUCCIÓN Y GASTOS POR FUNCIÓN
            // ==========================================
            ['code' => '9', 'name' => 'ELEMENTO 9: COSTOS DE PRODUCCIÓN Y GASTOS POR FUNCIÓN', 'element' => 9, 'level' => 1, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::COSTOS_PRODUCCION, 'accepts' => false],
            ['code' => '90', 'name' => 'Costos de producción agropecuaria y forestal', 'element' => 9, 'level' => 2, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::COSTOS_PRODUCCION, 'accepts' => true, 'parent' => '9', 'req_cost' => true],
            ['code' => '91', 'name' => 'Costos de producción agroindustrial y transformación', 'element' => 9, 'level' => 2, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::COSTOS_PRODUCCION, 'accepts' => true, 'parent' => '9', 'req_cost' => true],
            ['code' => '92', 'name' => 'Costos de talleres y servicios institucionales', 'element' => 9, 'level' => 2, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::COSTOS_PRODUCCION, 'accepts' => true, 'parent' => '9', 'req_cost' => true],
            ['code' => '94', 'name' => 'Gastos de administración', 'element' => 9, 'level' => 2, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::GASTOS_FUNCION, 'accepts' => true, 'parent' => '9'],
            ['code' => '95', 'name' => 'Gastos de ventas', 'element' => 9, 'level' => 2, 'nature' => AccountNature::DEBIT, 'classification' => AccountType::GASTOS_FUNCION, 'accepts' => true, 'parent' => '9'],
        ];

        // First pass: insert accounts without parent_id
        $codeToId = [];
        foreach ($accounts as $data) {
            $account = ChartOfAccount::updateOrCreate(
                ['code' => $data['code']],
                [
                    'name'                 => $data['name'],
                    'element'              => $data['element'],
                    'level'                => $data['level'],
                    'nature'               => $data['nature'],
                    'classification'       => $data['classification'],
                    'accepts_movements'    => $data['accepts'] ?? false,
                    'allows_movement'      => $data['accepts'] ?? false,
                    'requires_third_party' => $data['req_third'] ?? false,
                    'requires_cost_center' => $data['req_cost'] ?? false,
                    'requires_fund_source' => $data['req_fund'] ?? false,
                    'currency'             => 'PEN',
                    'active'               => true,
                    'is_active'            => true,
                ]
            );
            $codeToId[$data['code']] = $account->id;
        }

        // Second pass: set parent_id relationships
        foreach ($accounts as $data) {
            if (!empty($data['parent']) && isset($codeToId[$data['parent']])) {
                ChartOfAccount::where('code', $data['code'])->update([
                    'parent_id' => $codeToId[$data['parent']],
                ]);
            }
        }
    }
}
