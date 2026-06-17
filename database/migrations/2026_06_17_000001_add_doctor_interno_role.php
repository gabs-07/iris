<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE users MODIFY rol ENUM('invitado', 'paciente', 'psicologo', 'psiquiatra', 'doctor_interno', 'admin') NOT NULL DEFAULT 'paciente'");

        if (Schema::hasTable('professional_profiles')) {
            DB::statement("ALTER TABLE professional_profiles MODIFY tipo_profesional ENUM('psicologo', 'psiquiatra', 'doctor_interno') NOT NULL");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('professional_profiles')) {
            DB::statement("ALTER TABLE professional_profiles MODIFY tipo_profesional ENUM('psicologo', 'psiquiatra') NOT NULL");
        }

        DB::statement("ALTER TABLE users MODIFY rol ENUM('invitado', 'paciente', 'psicologo', 'psiquiatra', 'admin') NOT NULL DEFAULT 'paciente'");
    }
};
