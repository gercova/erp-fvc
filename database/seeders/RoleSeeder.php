<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin         = Role::firstOrCreate(['name' => 'SUPERADMIN']);
        $admin              = Role::firstOrCreate(['name' => 'ADMIN']);
        $vendedor           = Role::firstOrCreate(['name' => 'VENDEDOR']);
        $cajero             = Role::firstOrCreate(['name' => 'CAJERO']);
        $contabilidad       = Role::firstOrCreate(['name' => 'CONTABILIDAD']);
        $directorGeneral    = Role::firstOrCreate(['name' => 'DIRECTOR_GENERAL']);
        $administracion     = Role::firstOrCreate(['name' => 'ADMINISTRACION']);
        $jefeArea           = Role::firstOrCreate(['name' => 'JEFE_AREA']);
        $coordinador        = Role::firstOrCreate(['name' => 'COORDINADOR']);
        $coordPe            = Role::firstOrCreate(['name' => 'COORD_PE']);
        $jefeInmediato      = Role::firstOrCreate(['name' => 'JEFE_INMEDIATO']);
        $tramiteDoc         = Role::firstOrCreate(['name' => 'TRAMITE_DOCUMENTARIO']);
        $unidadAcademica    = Role::firstOrCreate(['name' => 'UNIDAD_ACADEMICA']);
        $chofer             = Role::firstOrCreate(['name' => 'CHOFER']);
        $docente            = Role::firstOrCreate(['name' => 'DOCENTE']);
        $abastecimiento     = Role::firstOrCreate(['name' => 'ABASTECIMIENTO']);

        $permissionsByModule = [
            'Dashboard' => [
                'admin.home' => 'Acceder al dashboard principal',
            ],
            'Requerimientos' => [
                'requisitions.index' => 'Listar y ver requerimientos',
                'requisitions.create' => 'Crear requerimientos',
                'requisitions.edit' => 'Editar requerimientos propios',
                'requisitions.delete' => 'Anular/Eliminar requerimientos',
                'requisitions.pdf' => 'Descargar e imprimir PDF de requerimiento',

                'expense_declarations.index' => 'Listar declaraciones juradas de gastos',
                'expense_declarations.create' => 'Crear declaraciones juradas de gastos',
                'expense_declarations.edit' => 'Editar declaraciones juradas de gastos',
                'expense_declarations.delete' => 'Anular/Eliminar declaraciones juradas de gastos',
                'expense_declarations.pdf' => 'Descargar e imprimir PDF de declaración jurada',

                'exit_slips.index' => 'Listar papeletas de salida',
                'exit_slips.create' => 'Crear papeletas de salida',
                'exit_slips.edit' => 'Editar papeletas de salida',
                'exit_slips.delete' => 'Anular/Eliminar papeletas de salida',
                'exit_slips.pdf' => 'Descargar e imprimir PDF de papeleta de salida',

                'vehicle_exit_slips.index' => 'Listar papeletas de salida de vehículo',
                'vehicle_exit_slips.create' => 'Crear papeletas de salida de vehículo',
                'vehicle_exit_slips.edit' => 'Editar papeletas de salida de vehículo',
                'vehicle_exit_slips.delete' => 'Anular/Eliminar papeletas de salida de vehículo',
                'vehicle_exit_slips.pdf' => 'Descargar e imprimir PDF de papeleta de vehículo',

                'vacation_exit_slips.index' => 'Listar papeletas de vacaciones',
                'vacation_exit_slips.create' => 'Crear papeletas de vacaciones',
                'vacation_exit_slips.edit' => 'Editar papeletas de vacaciones',
                'vacation_exit_slips.delete' => 'Anular/Eliminar papeletas de vacaciones',
                'vacation_exit_slips.pdf' => 'Descargar e imprimir PDF de papeleta de vacaciones',

                'fuel_control_slips.index' => 'Listar vales de control de combustible',
                'fuel_control_slips.create' => 'Crear vales de control de combustible',
                'fuel_control_slips.edit' => 'Editar vales de control de combustible',
                'fuel_control_slips.delete' => 'Anular/Eliminar vales de control de combustible',
                'fuel_control_slips.pdf' => 'Descargar e imprimir PDF de vale de control',

                'approvals.index' => 'Acceder a la bandeja de aprobaciones',
                'approvals.action' => 'Firmar, aprobar, observar o rechazar documentos',
            ],
            'Ventas' => [
                'admin.clients' => 'Gestionar clientes',
                'admin.quotes' => 'Gestionar cotizaciones',
                'admin.pos' => 'Usar punto de venta',
                'admin.sale_notes' => 'Gestionar notas de venta',
                'admin.billings' => 'Gestionar comprobantes',
                'admin.shipment_guides' => 'Gestionar guias de remision',
            ],
            'Compras' => [
                'admin.providers' => 'Gestionar proveedores',
                'admin.buys' => 'Gestionar compras',
            ],
            'Inventario' => [
                'admin.products' => 'Gestionar productos',
                'admin.categories' => 'Gestionar categorias',
                'admin.warehouses' => 'Gestionar almacenes',
                'admin.transfer_orders' => 'Gestionar ordenes de traslado',
            ],
            'Operaciones' => [
                'admin.arching_cashes' => 'Gestionar arqueo de cajas',
            ],
            'Configuracion' => [
                'admin.business' => 'Gestionar empresa',
                'admin.paymodes' => 'Gestionar metodos de pago',
                'admin.cashes' => 'Gestionar cajas',
                'admin.series' => 'Gestionar series',
                'admin.users' => 'Gestionar usuarios',
                'admin.roles' => 'Gestionar roles y permisos',
                'admin.areas' => 'Gestionar áreas',
            ],
            'Reportes' => [
                'report.sales.index' => 'Ver reporte de ventas',
                'report.sales.by_product.index' => 'Ver reporte de ventas por producto',
                'report.payments.index' => 'Ver reporte de pagos',
                'report.billings.sales_register' => 'Ver registro de ventas',
                'report.billings.billing_documents' => 'Ver reporte de documentos emitidos',
                'report.billings.credit_notes' => 'Ver reporte de notas de credito',
            ],
        ];

        $allPermissions = [];

        foreach ($permissionsByModule as $module => $permissions) {
            foreach ($permissions as $name => $description) {
                $permission = Permission::firstOrCreate(
                    ['name' => $name],
                    ['descripcion' => $description]
                );

                if (empty($permission->descripcion)) {
                    $permission->descripcion = $description;
                    $permission->save();
                }

                $allPermissions[] = $permission->name;
            }
        }

        $superAdmin->syncPermissions($allPermissions);
        $admin->syncPermissions($allPermissions);

        $vendedor->syncPermissions([
            'admin.home',
            'admin.clients',
            'admin.quotes',
            'admin.pos',
            'admin.sale_notes',
            'admin.billings',
            'admin.shipment_guides',
            'admin.arching_cashes',
        ]);

        $cajero->syncPermissions([
            'admin.home',
            'admin.pos',
            'admin.sale_notes',
            'admin.billings',
            'admin.arching_cashes',
        ]);

        $contabilidad->syncPermissions([
            'admin.home',
            'report.billings.sales_register',
            'report.billings.billing_documents',
            'report.billings.credit_notes',
        ]);
    }
}
