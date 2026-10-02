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
        Schema::table('buys', function (Blueprint $table) {
            if (! Schema::hasColumn('buys', 'idalmacen')) {
                $table->unsignedBigInteger('idalmacen')->nullable()->after('idproveedor');
                $table->foreign('idalmacen')->references('id')->on('warehouses')->nullOnDelete();
            }

            if (! Schema::hasColumn('buys', 'condicion_pago')) {
                $table->string('condicion_pago', 20)->default('Contado')->after('modo_pago');
            }

            if (! Schema::hasColumn('buys', 'monto_credito')) {
                $table->decimal('monto_credito', 18, 2)->default(0)->after('condicion_pago');
            }

            if (! Schema::hasColumn('buys', 'cuotas')) {
                $table->json('cuotas')->nullable()->after('monto_credito');
            }

            $table->unique(['idproveedor', 'idtipo_comprobante', 'serie', 'correlativo'], 'buys_provider_voucher_unique');
        });

        if (! Schema::hasTable('accounts_payable')) {
            Schema::create('accounts_payable', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('idcompra');
                $table->unsignedBigInteger('idproveedor');
                $table->unsignedBigInteger('idalmacen')->nullable();
                $table->decimal('monto_total', 18, 2);
                $table->decimal('monto_pagado', 18, 2)->default(0);
                $table->decimal('saldo', 18, 2);
                $table->date('fecha_emision');
                $table->date('fecha_vencimiento');
                $table->string('estado', 20)->default('PENDIENTE');
                $table->json('cuotas')->nullable();
                $table->text('observaciones')->nullable();
                $table->unsignedBigInteger('idusuario')->nullable();
                $table->timestamps();

                $table->foreign('idcompra')->references('id')->on('buys')->onDelete('cascade');
                $table->foreign('idproveedor')->references('id')->on('providers');
                $table->foreign('idalmacen')->references('id')->on('warehouses')->nullOnDelete();
                $table->foreign('idusuario')->references('id')->on('users')->nullOnDelete();

                $table->index(['idproveedor', 'estado']);
                $table->index(['fecha_vencimiento', 'estado']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('accounts_payable')) {
            Schema::dropIfExists('accounts_payable');
        }

        Schema::table('buys', function (Blueprint $table) {
            $table->dropUnique('buys_provider_voucher_unique');

            if (Schema::hasColumn('buys', 'idalmacen')) {
                $table->dropForeign(['idalmacen']);
                $table->dropColumn('idalmacen');
            }

            $columns = [];
            foreach (['condicion_pago', 'monto_credito', 'cuotas'] as $column) {
                if (Schema::hasColumn('buys', $column)) {
                    $columns[] = $column;
                }
            }
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
