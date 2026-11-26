<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Módulo 4 — Facturación y Administración (cuenta única por paciente).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('estado', 15)->default('abierta'); // abierta | facturada
            $table->timestamp('abierta_at');
            $table->timestamp('cerrada_at')->nullable();
            $table->timestamps();
            $table->index(['patient_id', 'estado']);
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 20)->unique(); // B001-000001
            $table->foreignId('patient_account_id')->constrained();
            $table->foreignId('patient_id')->constrained();
            $table->foreignId('tutor_id')->nullable()->constrained()->nullOnDelete();
            $table->date('fecha_emision');
            $table->date('fecha_vencimiento');
            $table->decimal('subtotal_servicios', 12, 2)->default(0);
            $table->decimal('subtotal_medicamentos', 12, 2)->default(0);
            $table->decimal('subtotal', 12, 2);
            $table->decimal('igv', 12, 2);
            $table->decimal('total', 12, 2);
            $table->decimal('monto_pagado', 12, 2)->default(0);
            $table->string('estado', 15)->default('pendiente'); // pendiente | parcial | pagada | anulada
            $table->string('motivo_anulacion')->nullable();
            $table->foreignId('anulada_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('anulada_at')->nullable();
            $table->foreignId('emitida_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_account_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained();
            $table->string('tipo', 20); // consulta | procedimiento | vacuna | laboratorio | medicamento
            $table->string('descripcion');
            $table->decimal('cantidad', 10, 2);
            $table->decimal('precio_unitario', 10, 2);
            $table->decimal('subtotal', 12, 2);
            $table->nullableMorphs('origen');
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $table->decimal('monto', 12, 2);
            $table->string('metodo', 20); // efectivo | tarjeta | yape | plin | transferencia
            $table->string('referencia')->nullable();
            $table->dateTime('fecha');
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::dropIfExists('charges');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('patient_accounts');
    }
};
