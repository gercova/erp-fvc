<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('billings', function (Blueprint $table) {
            if (! Schema::hasColumn('billings', 'sale_note_id')) {
                $table->foreignId('sale_note_id')
                    ->nullable()
                    ->after('nticket')
                    ->constrained('sale_notes')
                    ->nullOnDelete();
            }
        });

        Schema::table('sale_notes', function (Blueprint $table) {
            if (! Schema::hasColumn('sale_notes', 'billing_id')) {
                $table->foreignId('billing_id')
                    ->nullable()
                    ->after('idfactura_anular')
                    ->constrained('billings')
                    ->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_notes', function (Blueprint $table) {
            if (Schema::hasColumn('sale_notes', 'billing_id')) {
                $table->dropConstrainedForeignId('billing_id');
            }
        });

        Schema::table('billings', function (Blueprint $table) {
            if (Schema::hasColumn('billings', 'sale_note_id')) {
                $table->dropConstrainedForeignId('sale_note_id');
            }
        });
    }
};
