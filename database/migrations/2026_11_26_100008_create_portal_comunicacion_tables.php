<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Módulo 5 — Comunicación y Portal de Tutores.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->string('canal', 10); // email | sms
            $table->string('tipo', 30);  // recordatorio_cita | vacuna_vencida | receta_lista | resultado_listo
            $table->foreignId('tutor_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained()->nullOnDelete();
            $table->string('destinatario');
            $table->string('asunto')->nullable();
            $table->text('mensaje');
            $table->string('estado', 15)->default('pendiente'); // pendiente | enviado | fallido | abierto
            $table->string('proveedor', 20)->nullable(); // log | android | smtp
            $table->string('proveedor_id')->nullable();
            $table->string('token_seguimiento', 64)->nullable()->unique();
            $table->text('error')->nullable();
            $table->timestamp('enviado_at')->nullable();
            $table->timestamp('abierto_at')->nullable();
            $table->nullableMorphs('referencia');
            $table->timestamps();
            $table->index(['canal', 'estado']);
        });

        Schema::create('document_accesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tutor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->string('documento_tipo', 20); // receta | resultado
            $table->unsignedBigInteger('documento_id');
            $table->string('accion', 15); // visualizacion | descarga
            $table->string('ip', 45)->nullable();
            $table->timestamps();
            $table->index(['documento_tipo', 'documento_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_accesses');
        Schema::dropIfExists('notification_logs');
    }
};
