<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Módulo 1 — Gestión de Pacientes Pediátricos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tutors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->string('dni', 8)->unique();
            $table->string('nombres');
            $table->string('apellidos');
            $table->string('telefono', 15);
            $table->string('email')->nullable();
            $table->string('direccion')->nullable();
            $table->timestamps();
        });

        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('historia_clinica', 12)->unique(); // HC-000001
            $table->string('dni', 8)->nullable()->unique();
            $table->string('nombres');
            $table->string('apellidos');
            $table->date('fecha_nacimiento');
            $table->char('sexo', 1); // M | F
            $table->string('grupo_sanguineo', 5)->nullable();
            $table->text('alergias')->nullable();
            $table->string('seguro', 40)->nullable(); // SIS, EsSalud, privado, ninguno
            $table->string('direccion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['apellidos', 'nombres']);
        });

        Schema::create('patient_tutor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tutor_id')->constrained()->cascadeOnDelete();
            $table->string('parentesco', 30); // madre, padre, abuelo/a, tío/a, tutor legal...
            $table->boolean('es_principal')->default(false);
            $table->boolean('puede_retirar_medicamentos')->default(true);
            $table->timestamps();
            $table->unique(['patient_id', 'tutor_id']);
        });

        Schema::create('consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tutor_id')->constrained()->cascadeOnDelete();
            $table->string('version', 10)->default('1.0');
            $table->boolean('acepta_tratamiento_datos')->default(false);
            $table->boolean('acepta_atencion_medica')->default(false);
            $table->boolean('acepta_notificaciones')->default(false);
            $table->timestamp('aceptado_at')->nullable();
            $table->timestamp('revocado_at')->nullable();
            $table->string('ip', 45)->nullable();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Ficha clínica pediátrica específica (antecedentes)
        Schema::create('pediatric_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->unique()->constrained()->cascadeOnDelete();
            // Prenatales
            $table->unsignedTinyInteger('numero_controles_prenatales');
            $table->unsignedTinyInteger('edad_materna');
            $table->text('patologias_embarazo');
            $table->boolean('suplemento_hierro_acido_folico');
            // Natales
            $table->unsignedTinyInteger('edad_gestacional_semanas');
            $table->string('tipo_parto', 20); // eutocico | cesarea | instrumentado
            $table->decimal('peso_nacer_kg', 4, 2);
            $table->decimal('talla_nacer_cm', 4, 1);
            $table->decimal('perimetro_cefalico_nacer_cm', 4, 1)->nullable();
            $table->unsignedTinyInteger('apgar_1');
            $table->unsignedTinyInteger('apgar_5');
            $table->text('complicaciones_parto');
            // Posnatales
            $table->string('lactancia', 30); // exclusiva | mixta | formula
            $table->boolean('tamizaje_neonatal');
            $table->text('hospitalizaciones');
            $table->text('enfermedades_previas');
            $table->text('antecedentes_familiares');
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('anthropometric_measurements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('consultation_id')->nullable()->index();
            $table->date('fecha');
            $table->unsignedInteger('edad_dias');
            $table->decimal('peso_kg', 5, 2);
            $table->decimal('talla_cm', 5, 1);
            $table->decimal('perimetro_cefalico_cm', 4, 1)->nullable();
            $table->decimal('imc', 5, 2)->nullable();
            $table->decimal('z_peso_edad', 5, 2)->nullable();
            $table->decimal('p_peso_edad', 5, 1)->nullable();
            $table->decimal('z_talla_edad', 5, 2)->nullable();
            $table->decimal('p_talla_edad', 5, 1)->nullable();
            $table->decimal('z_pc_edad', 5, 2)->nullable();
            $table->decimal('p_pc_edad', 5, 1)->nullable();
            $table->decimal('z_imc_edad', 5, 2)->nullable();
            $table->decimal('p_imc_edad', 5, 1)->nullable();
            $table->json('alertas')->nullable();
            $table->boolean('tiene_alerta')->default(false)->index();
            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('milestones', function (Blueprint $table) {
            $table->id();
            $table->string('dominio', 20); // motricidad_gruesa | motricidad_fina | lenguaje | social
            $table->string('descripcion');
            $table->unsignedTinyInteger('edad_esperada_meses');
            $table->unsignedTinyInteger('edad_limite_meses');
            $table->timestamps();
        });

        Schema::create('milestone_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->foreignId('milestone_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('consultation_id')->nullable();
            $table->date('fecha');
            $table->boolean('logrado');
            $table->boolean('alerta')->default(false);
            $table->string('observacion')->nullable();
            $table->foreignId('evaluado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('psc35_screenings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained()->cascadeOnDelete();
            $table->date('fecha');
            $table->json('respuestas'); // 35 ítems con valores 0,1,2
            $table->unsignedTinyInteger('puntaje_total');
            $table->unsignedTinyInteger('puntaje_atencion');
            $table->unsignedTinyInteger('puntaje_internalizacion');
            $table->unsignedTinyInteger('puntaje_externalizacion');
            $table->boolean('positivo');
            $table->text('interpretacion');
            $table->foreignId('aplicado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('psc35_screenings');
        Schema::dropIfExists('milestone_assessments');
        Schema::dropIfExists('milestones');
        Schema::dropIfExists('anthropometric_measurements');
        Schema::dropIfExists('pediatric_records');
        Schema::dropIfExists('consents');
        Schema::dropIfExists('patient_tutor');
        Schema::dropIfExists('patients');
        Schema::dropIfExists('tutors');
    }
};
