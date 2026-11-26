<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Módulo 1 — Inmunizaciones (esquema regular, aplicación con descuento de stock, ESAVI).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vaccines', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique();
            $table->string('nombre');
            $table->string('enfermedades');
            $table->string('via', 20); // IM, SC, ID, oral
            $table->string('codigo_cvx', 10)->nullable(); // código para HL7 FHIR
            $table->foreignId('medication_id')->nullable()->constrained()->nullOnDelete(); // producto en farmacia
            $table->decimal('precio', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('vaccine_schedule_doses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vaccine_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('numero_dosis');
            $table->string('nombre_dosis'); // 1ra dosis, refuerzo...
            $table->unsignedSmallInteger('edad_dias');      // edad recomendada
            $table->unsignedSmallInteger('tolerancia_dias'); // a partir de edad_dias + tolerancia => vencida
            $table->string('grupo_edad', 30); // RN, < 1 año, 1 año, 2-4 años, 5+ años
            $table->unsignedSmallInteger('orden');
            $table->timestamps();
        });

        Schema::create('immunizations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vaccine_id')->constrained();
            $table->foreignId('vaccine_schedule_dose_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('lot_id')->constrained();
            $table->date('fecha_aplicacion');
            $table->string('sitio_aplicacion', 40)->nullable();
            $table->string('observaciones')->nullable();
            $table->foreignId('aplicado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['patient_id', 'vaccine_schedule_dose_id']);
        });

        Schema::create('esavi_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('immunization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->dateTime('fecha_inicio_sintomas');
            $table->text('sintomas');
            $table->string('gravedad', 10); // leve | moderado | grave
            $table->text('descripcion')->nullable();
            $table->text('tratamiento')->nullable();
            $table->string('desenlace', 30)->nullable(); // recuperado, en_seguimiento, hospitalizado, secuelas, fallecido
            $table->timestamp('notificado_at')->nullable();
            $table->foreignId('reportado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('vaccination_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vaccine_id')->constrained()->cascadeOnDelete();
            $table->string('grupo_edad', 30);
            $table->unsignedSmallInteger('anio');
            $table->unsignedInteger('meta');
            $table->timestamps();
            $table->unique(['vaccine_id', 'grupo_edad', 'anio']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vaccination_goals');
        Schema::dropIfExists('esavi_reports');
        Schema::dropIfExists('immunizations');
        Schema::dropIfExists('vaccine_schedule_doses');
        Schema::dropIfExists('vaccines');
    }
};
