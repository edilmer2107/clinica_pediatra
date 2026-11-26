<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Módulo 2 — Gestión Clínica y Consultas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cie10_codes', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 10)->unique();
            $table->string('descripcion');
            $table->timestamps();
        });

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique();
            $table->string('nombre');
            $table->string('tipo', 20); // consulta | procedimiento | laboratorio | imagen
            $table->decimal('precio', 10, 2);
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained('users');
            $table->foreignId('tutor_id')->nullable()->constrained()->nullOnDelete();
            $table->dateTime('fecha_hora')->index();
            $table->unsignedSmallInteger('duracion_minutos')->default(30);
            $table->string('modalidad', 20); // presencial | telemedicina
            $table->string('servicio', 30);  // general | control_nino_sano | telemedicina
            $table->string('estado', 20)->default('programada')->index(); // programada | confirmada | atendida | cancelada | no_asistio | reprogramada
            $table->string('motivo')->nullable();
            $table->string('enlace_telemedicina')->nullable();
            $table->string('motivo_cancelacion')->nullable();
            $table->timestamp('cancelada_at')->nullable();
            $table->foreignId('reprogramada_desde_id')->nullable()->constrained('appointments')->nullOnDelete();
            $table->timestamp('recordatorio_enviado_at')->nullable();
            $table->foreignId('creado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('consultations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained('users');
            $table->dateTime('fecha');
            $table->string('plantilla', 20); // neonato | lactante | preescolar | escolar | adolescente
            $table->string('servicio', 30);
            $table->text('motivo_consulta');
            $table->text('enfermedad_actual')->nullable();
            $table->json('interrogatorio');   // alimentación, sueño, escolaridad... según plantilla
            $table->json('examen_fisico');    // segmentado por sistemas
            $table->decimal('temperatura', 4, 1)->nullable();
            $table->unsignedSmallInteger('frecuencia_cardiaca')->nullable();
            $table->unsignedSmallInteger('frecuencia_respiratoria')->nullable();
            $table->unsignedTinyInteger('saturacion_o2')->nullable();
            $table->string('presion_arterial', 10)->nullable();
            $table->text('plan_tratamiento')->nullable();
            $table->text('indicaciones')->nullable();
            $table->string('estado', 20)->default('abierta'); // abierta | cerrada
            $table->timestamps();
        });

        Schema::create('consultation_diagnoses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('consultation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cie10_code_id')->constrained();
            $table->string('tipo', 15)->default('definitivo'); // presuntivo | definitivo
            $table->boolean('principal')->default(false);
            $table->timestamps();
        });

        Schema::create('drug_interactions', function (Blueprint $table) {
            $table->id();
            $table->string('principio_a');
            $table->string('principio_b');
            $table->string('severidad', 10); // grave | moderada
            $table->text('descripcion');
            $table->timestamps();
            $table->unique(['principio_a', 'principio_b']);
        });

        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique(); // RX-000001
            $table->foreignId('consultation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained('users');
            $table->decimal('peso_kg', 5, 2);
            $table->decimal('talla_cm', 5, 1)->nullable();
            $table->decimal('superficie_corporal', 5, 3)->nullable();
            $table->string('estado', 20)->default('emitida'); // emitida | parcial | dispensada | anulada
            $table->text('observaciones')->nullable();
            $table->timestamp('lista_para_retiro_at')->nullable();
            $table->timestamps();
        });

        Schema::create('prescription_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prescription_id')->constrained()->cascadeOnDelete();
            $table->foreignId('medication_id')->constrained();
            $table->string('metodo_calculo', 10); // peso | sc
            $table->decimal('dosis_indicada', 10, 3);   // mg/kg/día o mg/m²/día
            $table->decimal('dosis_diaria_mg', 10, 3);
            $table->unsignedTinyInteger('frecuencia_horas');
            $table->decimal('dosis_por_toma_mg', 10, 3);
            $table->decimal('volumen_por_toma_ml', 8, 2)->nullable();
            $table->unsignedSmallInteger('duracion_dias');
            $table->string('via', 20);
            $table->unsignedInteger('cantidad');            // unidades a dispensar
            $table->unsignedInteger('cantidad_dispensada')->default(0);
            $table->boolean('alerta_dosis')->default(false);
            $table->boolean('alerta_interaccion')->default(false);
            $table->text('alertas')->nullable();
            $table->text('justificacion')->nullable();      // obligatoria si el médico confirma pese a la alerta
            $table->string('indicaciones')->nullable();
            $table->timestamps();
        });

        Schema::create('lab_tests', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique();
            $table->string('nombre');
            $table->string('tipo', 15); // laboratorio | imagen
            $table->string('unidad', 20)->nullable();
            $table->decimal('ref_min', 10, 3)->nullable();
            $table->decimal('ref_max', 10, 3)->nullable();
            $table->decimal('precio', 10, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('lab_orders', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique(); // OL-000001
            $table->foreignId('consultation_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('doctor_id')->constrained('users');
            $table->string('tipo', 15); // laboratorio | imagen
            $table->string('prioridad', 10)->default('rutina'); // rutina | urgente
            $table->string('estado', 20)->default('solicitada'); // solicitada | completada
            $table->text('indicaciones')->nullable();
            $table->timestamps();
        });

        Schema::create('lab_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lab_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lab_test_id')->constrained();
            $table->decimal('valor', 10, 3)->nullable();
            $table->text('resultado_texto')->nullable();
            $table->decimal('ref_min', 10, 3)->nullable();
            $table->decimal('ref_max', 10, 3)->nullable();
            $table->boolean('es_anormal')->default(false);
            $table->string('archivo_path')->nullable();
            $table->string('archivo_nombre')->nullable();
            $table->timestamp('resultado_at')->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_order_items');
        Schema::dropIfExists('lab_orders');
        Schema::dropIfExists('lab_tests');
        Schema::dropIfExists('prescription_items');
        Schema::dropIfExists('prescriptions');
        Schema::dropIfExists('drug_interactions');
        Schema::dropIfExists('consultation_diagnoses');
        Schema::dropIfExists('consultations');
        Schema::dropIfExists('appointments');
        Schema::dropIfExists('services');
        Schema::dropIfExists('cie10_codes');
    }
};
