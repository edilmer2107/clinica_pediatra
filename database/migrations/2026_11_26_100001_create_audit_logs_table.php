<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bitácora de auditoría inmutable (RF Módulo 4).
 * - Solo admite INSERT: un trigger de PostgreSQL rechaza UPDATE y DELETE.
 * - Cada registro guarda el hash SHA-256 del anterior (cadena de hashes),
 *   lo que permite detectar manipulaciones (no repudio / integridad).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_name')->nullable();
            $table->string('accion', 60)->index();
            $table->string('modulo', 40)->index();
            $table->string('entidad_tipo')->nullable();
            $table->unsignedBigInteger('entidad_id')->nullable();
            $table->text('descripcion');
            $table->json('datos_anteriores')->nullable();
            $table->json('datos_nuevos')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('hash_anterior', 64)->nullable();
            $table->string('hash', 64);
            $table->timestamp('created_at')->useCurrent()->index();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared(<<<'SQL'
                CREATE OR REPLACE FUNCTION audit_logs_inmutable() RETURNS trigger AS $$
                BEGIN
                    RAISE EXCEPTION 'La bitácora de auditoría es inmutable: operación % no permitida', TG_OP;
                END;
                $$ LANGUAGE plpgsql;

                CREATE TRIGGER trg_audit_logs_inmutable
                BEFORE UPDATE OR DELETE ON audit_logs
                FOR EACH ROW EXECUTE FUNCTION audit_logs_inmutable();

                CREATE TRIGGER trg_audit_logs_no_truncate
                BEFORE TRUNCATE ON audit_logs
                FOR EACH STATEMENT EXECUTE FUNCTION audit_logs_inmutable();
            SQL);
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::unprepared('DROP TRIGGER IF EXISTS trg_audit_logs_inmutable ON audit_logs;');
            DB::unprepared('DROP TRIGGER IF EXISTS trg_audit_logs_no_truncate ON audit_logs;');
            DB::unprepared('DROP FUNCTION IF EXISTS audit_logs_inmutable();');
        }
        Schema::dropIfExists('audit_logs');
    }
};
