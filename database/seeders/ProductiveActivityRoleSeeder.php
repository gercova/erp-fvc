<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ProductiveActivityRoleSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Roles existentes y nuevo rol específico del módulo
        $superAdmin         = Role::firstOrCreate(['name' => 'SUPERADMIN']);
        $admin              = Role::firstOrCreate(['name' => 'ADMIN']);
        $directorGeneral    = Role::firstOrCreate(['name' => 'DIRECTOR_GENERAL']);
        $administracion     = Role::firstOrCreate(['name' => 'ADMINISTRACION']);
        $contabilidad       = Role::firstOrCreate(['name' => 'CONTABILIDAD']);
        $jefeArea           = Role::firstOrCreate(['name' => 'JEFE_AREA']);
        $responsableAct     = Role::firstOrCreate(['name' => 'RESPONSABLE_ACTIVIDAD']);

        // 2. Definición exhaustiva de Permisos del Módulo APE, Producción y Agro
        $permissions = [
            // Catálogo APE
            'productive_activities.index'               => 'Listar y consultar catálogo de actividades productivas',
            'productive_activities.create'              => 'Crear nuevas actividades productivas institucionales',
            'productive_activities.edit'                => 'Modificar datos de actividades productivas',
            'productive_activities.delete'              => 'Anular o desactivar actividades productivas',
            'productive_activities.approve'             => 'Aprobar apertura y cierre de actividades productivas',

            // Tablero de Centro de Costos
            'productive_activities.cost_center.index'   => 'Ver tablero de control de centros de costo (Matriz Actividad × Mes y Gráficos)',
            'productive_activities.cost_center.export'  => 'Exportar consolidado económico a Excel (Resumen y Multi-Hoja)',
            'productive_activities.cost_center.import'  => 'Importar formatos históricos del informe económico en Excel',

            // Ingresos y Egresos (Movimientos)
            'productive_activities.transactions.index'  => 'Listar movimientos de ingresos y egresos de actividades',
            'productive_activities.transactions.create' => 'Registrar ingresos y egresos por actividad productiva',
            'productive_activities.transactions.edit'   => 'Modificar registros de ingresos y egresos',
            'productive_activities.transactions.delete' => 'Anular transacciones de ingresos o egresos',

            // Conciliación RDR y Tesorería
            'productive_activities.rdr.index'           => 'Acceder al módulo de conciliación RDR y tesorería',
            'productive_activities.rdr.reconcile'       => 'Registrar extractos bancarios y conciliar cuentas recaudadoras',
            'productive_activities.rdr.cut_transfers'   => 'Registrar y tramitar transferencias a la Cuenta Única del Tesoro (CUT)',
            'productive_activities.rdr.internal_loans'  => 'Registrar habilitaciones y préstamos internos para viáticos/gastos',
            'productive_activities.rdr.closures'        => 'Generar balances y cierres mensuales de período',
            'productive_activities.rdr.approve_closures'=> 'Suscribir y aprobar cierres mensuales en la cadena de firmas',

            // Producción y Comercialización
            'production.plans.index'                    => 'Gestionar planes de producción y campañas agrícolas/pecuarias',
            'production.raw_materials.index'            => 'Gestionar catálogo abierto de insumos y materias primas',
            'production.input_movements.index'          => 'Registrar compras y consumos de insumos por actividad/lote',
            'production.produced_items.index'           => 'Registrar productos obtenidos de producción y lotes',
            'production.harvests.index'                 => 'Registrar partes de cosecha, pesaje de balanza y rendimiento',
            'production.profitability.index'            => 'Consultar reportes de rentabilidad, margen neto y ROI por producto',
            'commercialization.orders.index'            => 'Gestionar preventas, pedidos y distribución de producción',

            // Agropecuario y Forestal
            'agrolivestock.plots.index'                 => 'Gestionar parcelas, lotes y plantaciones de cultivos',
            'agrolivestock.nurseries.index'             => 'Gestionar viveros forestales, especies y almacigado',
            'agrolivestock.livestock.index'             => 'Gestionar unidades pecuarias, razas y eventos zootécnicos',
            'agrolivestock.harvests.quick_entry'        => 'Acceso al registro rápido de cosechas y rendimientos de campo',
            'agrolivestock.reports.index'               => 'Consultar reportes técnicos interanuales y conciliación balanza/factura',
        ];

        foreach ($permissions as $permName => $permDesc) {
            Permission::firstOrCreate(['name' => $permName]);
        }

        // 3. Asignación a SUPERADMIN y ADMIN (Acceso Total Irrestricto)
        $allPermissions = array_keys($permissions);
        $superAdmin->givePermissionTo($allPermissions);
        $admin->givePermissionTo($allPermissions);

        // 4. Asignación a DIRECTOR_GENERAL (Supervisión Ejecutiva Consolidada + Firmas)
        $directorPermissions = [
            'admin.home',
            'productive_activities.index',
            'productive_activities.cost_center.index',
            'productive_activities.cost_center.export',
            'productive_activities.transactions.index',
            'productive_activities.rdr.index',
            'productive_activities.approve',
            'productive_activities.rdr.approve_closures',
            'production.plans.index',
            'production.produced_items.index',
            'production.harvests.index',
            'production.profitability.index',
            'commercialization.orders.index',
            'agrolivestock.plots.index',
            'agrolivestock.nurseries.index',
            'agrolivestock.livestock.index',
            'agrolivestock.reports.index',
        ];
        $directorGeneral->givePermissionTo($directorPermissions);

        // 5. Asignación a ADMINISTRACION (Gestión Financiera, Operativa y Aprobaciones)
        $adminstracionPermissions = [
            'admin.home',
            'productive_activities.index',
            'productive_activities.create',
            'productive_activities.edit',
            'productive_activities.cost_center.index',
            'productive_activities.cost_center.export',
            'productive_activities.cost_center.import',
            'productive_activities.transactions.index',
            'productive_activities.transactions.create',
            'productive_activities.transactions.edit',
            'productive_activities.rdr.index',
            'productive_activities.rdr.reconcile',
            'productive_activities.rdr.cut_transfers',
            'productive_activities.rdr.internal_loans',
            'productive_activities.rdr.closures',
            'productive_activities.rdr.approve_closures',
            'production.plans.index',
            'production.profitability.index',
            'commercialization.orders.index',
            'agrolivestock.reports.index',
        ];
        $administracion->givePermissionTo($adminstracionPermissions);

        // 6. Asignación a CONTABILIDAD (Acceso Consolidado de Solo Lectura + Conciliación RDR)
        // Confirmado: Igual que en reportes de ventas y facturación, Contabilidad accede
        // a todo el módulo de manera consolidada para auditoría, cruce y estados financieros.
        $contabilidadPermissions = [
            'admin.home',
            'productive_activities.index',
            'productive_activities.cost_center.index',
            'productive_activities.cost_center.export',
            'productive_activities.transactions.index',
            'productive_activities.rdr.index',
            'productive_activities.rdr.reconcile',
            'production.profitability.index',
            'agrolivestock.reports.index',
        ];
        $contabilidad->givePermissionTo($contabilidadPermissions);

        // 7. Asignación a JEFE_AREA (Gestión de Actividades bajo su Departamento)
        $jefeAreaPermissions = [
            'admin.home',
            'productive_activities.index',
            'productive_activities.cost_center.index',
            'productive_activities.transactions.index',
            'productive_activities.transactions.create',
            'production.plans.index',
            'production.raw_materials.index',
            'production.input_movements.index',
            'production.produced_items.index',
            'production.harvests.index',
            'production.profitability.index',
            'commercialization.orders.index',
            'agrolivestock.plots.index',
            'agrolivestock.nurseries.index',
            'agrolivestock.livestock.index',
            'agrolivestock.harvests.quick_entry',
            'agrolivestock.reports.index',
        ];
        $jefeArea->givePermissionTo($jefeAreaPermissions);

        // 8. Asignación a RESPONSABLE_ACTIVIDAD (Rol Operativo por Actividad APE)
        // Equivalente a JEFE_AREA pero acotado a la actividad productiva donde está asignado.
        $responsablePermissions = [
            'admin.home',
            'productive_activities.index',
            'productive_activities.cost_center.index',
            'productive_activities.transactions.index',
            'productive_activities.transactions.create',
            'productive_activities.transactions.edit',
            'productive_activities.rdr.closures',
            'production.plans.index',
            'production.raw_materials.index',
            'production.input_movements.index',
            'production.produced_items.index',
            'production.harvests.index',
            'production.profitability.index',
            'commercialization.orders.index',
            'agrolivestock.plots.index',
            'agrolivestock.nurseries.index',
            'agrolivestock.livestock.index',
            'agrolivestock.harvests.quick_entry',
            'agrolivestock.reports.index',
        ];
        $responsableAct->givePermissionTo($responsablePermissions);
    }
}
