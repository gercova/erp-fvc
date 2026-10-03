<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AgreementRoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Idempotent permission and role seeder for Agreements and Technological Services module.
     */
    public function run(): void {
        $permissions = [
            'agreements.view'    => 'Visualizar catálogo de convenios, adendas y compromisos',
            'agreements.create'  => 'Crear nuevos convenios y adendas institucionales',
            'agreements.edit'    => 'Modificar convenios, adendas y cronogramas de facturación',
            'agreements.manage'  => 'Gestionar aprobaciones, estados y liquidaciones de convenios',
            'agreements.approve' => 'Aprobar visaciones y convenios institucionales en cadena de firmas',
            'services.view'      => 'Visualizar catálogo de servicios tecnológicos y órdenes de servicio',
            'services.manage'    => 'Gestionar órdenes de servicio, sesiones, asistencias y entregables',
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

        // Roles Assignment
        $superAdmin      = Role::firstOrCreate(['name' => 'SUPERADMIN']);
        $admin           = Role::firstOrCreate(['name' => 'ADMIN']);
        $directorGeneral = Role::firstOrCreate(['name' => 'DIRECTOR_GENERAL']);
        $administracion  = Role::firstOrCreate(['name' => 'ADMINISTRACION']);
        $contabilidad    = Role::firstOrCreate(['name' => 'CONTABILIDAD']);
        $coordinador     = Role::firstOrCreate(['name' => 'COORDINADOR']);
        $jefeArea        = Role::firstOrCreate(['name' => 'JEFE_AREA']);

        $allPermissions = array_keys($permissions);
        $superAdmin->givePermissionTo($allPermissions);
        $admin->givePermissionTo($allPermissions);
        $directorGeneral->givePermissionTo($allPermissions);

        $administracion->givePermissionTo(['agreements.view', 'agreements.manage', 'agreements.approve', 'services.view', 'services.manage']);
        $contabilidad->givePermissionTo(['agreements.view', 'services.view']);
        $coordinador->givePermissionTo(['agreements.view', 'agreements.create', 'agreements.edit', 'agreements.manage', 'services.view', 'services.manage']);
        $jefeArea->givePermissionTo(['agreements.view', 'agreements.create', 'agreements.edit', 'agreements.manage', 'agreements.approve', 'services.view', 'services.manage']);
    }
}
