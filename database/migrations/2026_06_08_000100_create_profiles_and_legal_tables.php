<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('emergency_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('nombre', 180);
            $table->string('relacion', 80);
            $table->string('telefono', 30);
            $table->timestamps();
        });

        Schema::create('legal_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('acepta_terminos')->default(false);
            $table->boolean('acepta_privacidad')->default(false);
            $table->boolean('acepta_datos_sensibles')->default(false);
            $table->boolean('acepta_comunicaciones')->default(false);
            $table->boolean('acepta_condiciones_profesionales')->default(false);
            $table->boolean('declara_veracidad_profesional')->default(false);
            $table->timestamp('accepted_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
        });

        Schema::create('patient_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('terapia_previa', 80)->nullable();
            $table->text('medicacion_actual')->nullable();
            $table->text('motivo_consulta')->nullable();
            $table->text('objetivos')->nullable();
            $table->string('ocupacion', 150)->nullable();
            $table->text('domicilio')->nullable();
            $table->string('estado_civil', 60)->nullable();
            $table->text('antecedentes')->nullable();
            $table->text('alergias')->nullable();
            $table->longText('clinical_history')->nullable();
            $table->longText('clinical_attachments')->nullable();
            $table->timestamps();
        });

        Schema::create('professional_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->enum('tipo_profesional', ['psicologo', 'psiquiatra']);
            $table->string('titulo_profesional', 180)->nullable();
            $table->string('cedula_profesional', 60)->nullable()->index();
            $table->string('cedula_especialidad', 60)->nullable();
            $table->string('institucion', 180)->nullable();
            $table->string('posgrado', 180)->nullable();
            $table->string('especialidad_principal', 180)->nullable();
            $table->unsignedSmallInteger('experiencia_anios')->nullable();
            $table->string('asociaciones', 255)->nullable();
            $table->json('enfoques')->nullable();
            $table->json('poblaciones')->nullable();
            $table->json('areas')->nullable();
            $table->string('modalidad', 60)->nullable();
            $table->string('ubicacion', 180)->nullable();
            $table->string('idiomas', 120)->nullable();
            $table->text('biografia')->nullable();
            $table->text('servicios')->nullable();
            $table->text('presentacion')->nullable();
            $table->json('formacion_academica')->nullable();
            $table->json('especialidades')->nullable();
            $table->json('dias_atencion')->nullable();
            $table->string('proximo_espacio', 180)->nullable();
            $table->decimal('costo_min', 10, 2)->nullable();
            $table->decimal('costo_max', 10, 2)->nullable();
            $table->unsignedSmallInteger('duracion_sesion')->nullable();
            $table->json('disponibilidad')->nullable();
            $table->json('documentos')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('professional_profiles');
        Schema::dropIfExists('patient_profiles');
        Schema::dropIfExists('legal_consents');
        Schema::dropIfExists('emergency_contacts');
    }
};
