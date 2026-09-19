<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expense_declarations', function (Blueprint $table) {
            $table->id();
            $table->string('correlativo')->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->string('servidor_nombres');
            $table->string('dni', 20);
            $table->string('cargo');
            $table->decimal('total', 12, 2)->default(0);
            $table->string('total_letras')->nullable();
            $table->text('conceptos');
            $table->date('fecha');
            $table->string('lugar')->default('Uchiza');
            $table->text('certifico')->nullable();
            $table->string('status', 30)->default('PENDIENTE');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('expense_declaration_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expense_declaration_id')->constrained('expense_declarations')->cascadeOnDelete();
            $table->date('fecha');
            $table->text('detalle_gestion');
            $table->decimal('importe', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_declaration_items');
        Schema::dropIfExists('expense_declarations');
    }
};
