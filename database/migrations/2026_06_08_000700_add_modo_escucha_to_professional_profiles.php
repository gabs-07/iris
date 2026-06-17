<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('professional_profiles', function (Blueprint $table) {
            if (! Schema::hasColumn('professional_profiles', 'modo_escucha_activo')) {
                $table->boolean('modo_escucha_activo')->default(false)->after('disponibilidad');
            }
            if (! Schema::hasColumn('professional_profiles', 'modo_escucha_activado_at')) {
                $table->timestamp('modo_escucha_activado_at')->nullable()->after('modo_escucha_activo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('professional_profiles', function (Blueprint $table) {
            foreach (['modo_escucha_activado_at', 'modo_escucha_activo'] as $column) {
                if (Schema::hasColumn('professional_profiles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
