<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Módulo 3 — Operaciones de farmacia: dispensación, controlados, retiros,
 * conciliación de inventario y errores de medicación.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dispensations', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique(); // DS-000001
            $table->foreignId('prescription_id')->constrained();
            $table->foreignId('patient_id')->constrained();
            $table->foreignId('pharmacist_id')->constrained('users');
            $table->foreignId('tutor_id')->nullable()->constrained()->nullOnDelete(); // quien retira
            $table->dateTime('fecha');
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });

        Schema::create('dispensation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dispensation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('prescription_item_id')->constrained();
            $table->foreignId('medication_id')->constrained();
            $table->foreignId('lot_id')->constrained();
            $table->unsignedInteger('cantidad');
            $table->decimal('precio_unitario', 10, 2);
            $table->timestamps();
        });

        Schema::create('controlled_drug_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medication_id')->constrained();
            $table->foreignId('lot_id')->constrained();
            $table->dateTime('fecha');
            $table->string('tipo', 10); // entrada | salida
            $table->unsignedInteger('cantidad');
            $table->integer('saldo'); // saldo acumulado del medicamento
            $table->string('documento')->nullable(); // guía, factura, receta
            $table->foreignId('pharmacist_id')->constrained('users');
            $table->foreignId('tutor_id')->nullable()->constrained()->nullOnDelete();
            $table->string('tutor_dni', 8)->nullable();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('prescription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('observaciones')->nullable();
            $table->timestamps();
        });

        Schema::create('lot_recalls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lot_id')->constrained();
            $table->string('alerta_sanitaria'); // N° de alerta DIGEMID
            $table->text('motivo');
            $table->date('fecha');
            $table->unsignedInteger('cantidad_retirada');
            $table->string('disposicion_final', 30); // devolucion_proveedor | destruccion | cuarentena
            $table->text('detalle_disposicion')->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('inventory_counts', function (Blueprint $table) {
            $table->id();
            $table->date('fecha');
            $table->string('estado', 15)->default('borrador'); // borrador | cerrado
            $table->unsignedInteger('stock_teorico_total')->default(0);
            $table->unsignedInteger('stock_fisico_total')->default(0);
            $table->decimal('consistencia', 6, 2)->nullable(); // %
            $table->text('observaciones')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('inventory_count_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_count_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lot_id')->constrained();
            $table->integer('stock_teorico');
            $table->integer('stock_fisico')->nullable();
            $table->integer('diferencia')->nullable();
            $table->timestamps();
        });

        Schema::create('medication_errors', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 20); // prescripcion | dispensacion
            $table->string('categoria', 40); // dosis incorrecta, medicamento equivocado...
            $table->foreignId('prescription_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('dispensation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->date('fecha');
            $table->string('gravedad', 15); // sin_dano | leve | moderado | grave
            $table->text('descripcion');
            $table->text('medida_correctiva');
            $table->string('evidencia_path')->nullable();
            $table->string('evidencia_nombre')->nullable();
            $table->foreignId('reportado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medication_errors');
        Schema::dropIfExists('inventory_count_items');
        Schema::dropIfExists('inventory_counts');
        Schema::dropIfExists('lot_recalls');
        Schema::dropIfExists('controlled_drug_entries');
        Schema::dropIfExists('dispensation_items');
        Schema::dropIfExists('dispensations');
    }
};
