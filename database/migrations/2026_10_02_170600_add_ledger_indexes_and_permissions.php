<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            // Index by entry_date for date-filtered ledger queries
            $table->index('entry_date', 'idx_journal_entry_date');
        });

        // Ensure permissions exist
        $viewPerm = Permission::firstOrCreate(
            ['name' => 'accounting.view'],
            ['descripcion' => 'Ver libros contables y reportes contables']
        );
        $exportPerm = Permission::firstOrCreate(
            ['name' => 'accounting.export'],
            ['descripcion' => 'Exportar libros contables a PDF y Excel']
        );

        $superAdmin = Role::where('name', 'SUPERADMIN')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo([$viewPerm, $exportPerm]);
        }

        $admin = Role::where('name', 'ADMIN')->first();
        if ($admin) {
            $admin->givePermissionTo([$viewPerm, $exportPerm]);
        }

        $contabilidad = Role::where('name', 'CONTABILIDAD')->first();
        if ($contabilidad) {
            $contabilidad->givePermissionTo([$viewPerm, $exportPerm]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropIndex('idx_journal_entry_date');
        });

        Permission::whereIn('name', ['accounting.view', 'accounting.export'])->delete();
    }
};
