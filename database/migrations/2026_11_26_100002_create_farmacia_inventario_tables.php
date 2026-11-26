<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Módulo 3 — Farmacia e Inventario (catálogo, lotes y movimientos).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medications', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique();
            $table->string('nombre');
            $table->string('principio_activo');
            $table->string('concentracion')->nullable();          // p. ej. 250 mg/5 mL
            $table->string('forma_farmaceutica', 60);             // jarabe, tableta, vial...
            $table->string('presentacion')->nullable();           // frasco 60 mL
            $table->string('unidad', 20)->default('unidad');      // unidad de dispensación
            $table->decimal('mg_por_unidad', 10, 3)->nullable();   // mg contenidos por unidad de dispensación
            $table->decimal('mg_por_ml', 10, 3)->nullable();       // para líquidos orales
            $table->decimal('dosis_min_mg_kg_dia', 10, 3)->nullable();
            $table->decimal('dosis_max_mg_kg_dia', 10, 3)->nullable();
            $table->decimal('dosis_max_diaria_mg', 10, 3)->nullable();
            $table->decimal('dosis_min_mg_m2_dia', 10, 3)->nullable();
            $table->decimal('dosis_max_mg_m2_dia', 10, 3)->nullable();
            $table->boolean('es_controlado')->default(false);
            $table->string('tipo_control', 20)->nullable();       // estupefaciente | psicotropico
            $table->boolean('es_vacuna')->default(false);
            $table->decimal('precio_venta', 10, 2)->default(0);
            $table->unsignedInteger('stock_minimo')->default(0);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('lots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medication_id')->constrained()->cascadeOnDelete();
            $table->string('numero_lote', 40);
            $table->date('fecha_vencimiento')->index();
            $table->unsignedInteger('cantidad_inicial');
            $table->integer('cantidad_actual');
            $table->decimal('costo_unitario', 10, 2)->default(0);
            $table->string('estado', 20)->default('activo')->index(); // activo | cuarentena | retirado | agotado
            $table->string('almacen', 60)->default('Almacén principal');
            $table->string('ubicacion', 60)->nullable();          // estante / anaquel
            $table->string('proveedor')->nullable();
            $table->date('fecha_ingreso');
            $table->timestamps();
            $table->unique(['medication_id', 'numero_lote']);
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medication_id')->constrained();
            $table->foreignId('lot_id')->constrained();
            $table->string('tipo', 30); // entrada | dispensacion | vacunacion | ajuste | retiro
            $table->integer('cantidad'); // positivo entrada, negativo salida
            $table->integer('saldo_lote');
            $table->nullableMorphs('referencia');
            $table->string('motivo')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['tipo', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('lots');
        Schema::dropIfExists('medications');
    }
};
