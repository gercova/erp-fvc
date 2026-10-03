<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AccountingSecurityRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Idempotent permission and role seeder for Accounting, Treasury, and Budget modules.
     * Does not overwrite or wipe out existing assignments.
     */
    public function run(): void
    {
        // 1. Spatie Permissions Definition
        $permissions = [
            'accounting.view'      => 'Visualizar libros contables, estados financieros y reportes de tesorería y presupuesto',
            'accounting.post'      => 'Registrar, contabilizar y extornar asientos contables en libros oficiales',
            'accounting.close'     => 'Ejecutar y autorizar cierres de período contable mensuales y anuales',
            'accounting.reopen'    => 'Reabrir períodos contables cerrados y registrar operaciones extemporáneas',
            'accounting.export'    => 'Exportar reportes, libros y estados contables a formatos PDF y Excel',
            'treasury.manage'      => 'Gestionar cuentas bancarias, importar extractos y registrar transferencias internas',
            'treasury.reconcile'   => 'Elaborar, ajustar, conciliar y cerrar conciliaciones bancarias oficiales',
            'budget.manage'        => 'Formular, registrar líneas y registrar modificaciones presupuestales (PIM/PIA)',
            'budget.approve'       => 'Revisar y otorgar aprobación institucional a presupuestos y modificaciones',
        ];

        foreach ($permissions as $name => $description) {
            $permission = Permission::firstOrCreate(
                ['name' => $name],
                ['descripcion' => $description]
            );

            if (empty($permission->descripcion)) {
                $permission->descripcion = $description;
                $permission->save();
            }
        }

        // 2. Roles Retrieval or Creation
        $superAdmin      = Role::firstOrCreate(['name' => 'SUPERADMIN']);
        $admin           = Role::firstOrCreate(['name' => 'ADMIN']);
        $contabilidad    = Role::firstOrCreate(['name' => 'CONTABILIDAD']);
        $administracion  = Role::firstOrCreate(['name' => 'ADMINISTRACION']);
        $directorGeneral = Role::firstOrCreate(['name' => 'DIRECTOR_GENERAL']);
        
        // Open decision: new TREASURER role
        $tesorero        = Role::firstOrCreate(['name' => 'TESORERO']);
        $treasurerAlias  = Role::firstOrCreate(['name' => 'TREASURER']);

        // 3. Default Assignment (Idempotent via givePermissionTo, preserving existing permissions)
        // SUPERADMIN / ADMIN: All module permissions
        $allAccountingPermissions = array_keys($permissions);
        $superAdmin->givePermissionTo($allAccountingPermissions);
        $admin->givePermissionTo($allAccountingPermissions);

        // CONTABILIDAD (ACCOUNTING): view, post, close, export, reconcile
        $contabilidad->givePermissionTo([
            'accounting.view',
            'accounting.post',
            'accounting.close',
            'accounting.export',
            'treasury.reconcile',
        ]);

        // ADMINISTRACION (ADMINISTRATION): view, budget.*, treasury.manage
        $administracion->givePermissionTo([
            'accounting.view',
            'budget.manage',
            'budget.approve',
            'treasury.manage',
        ]);

        // DIRECTOR_GENERAL (CEO): view, budget.approve, close approval (accounting.close)
        $directorGeneral->givePermissionTo([
            'accounting.view',
            'budget.approve',
            'accounting.close',
        ]);

        // TESORERO / TREASURER (open decision): view, treasury.manage, treasury.reconcile
        $treasurerPermissions = [
            'accounting.view',
            'treasury.manage',
            'treasury.reconcile',
        ];
        $tesorero->givePermissionTo($treasurerPermissions);
        $treasurerAlias->givePermissionTo($treasurerPermissions);
    }
}
