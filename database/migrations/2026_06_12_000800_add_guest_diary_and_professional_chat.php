<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE `users` MODIFY `rol` ENUM('invitado', 'paciente', 'psicologo', 'psiquiatra', 'admin') NOT NULL DEFAULT 'paciente'");
        }

        Schema::table('diary_entries', function (Blueprint $table) {
            if (! Schema::hasColumn('diary_entries', 'notes')) {
                $table->longText('notes')->nullable()->after('content');
            }
            if (! Schema::hasColumn('diary_entries', 'authorized_professional_id')) {
                $table->foreignId('authorized_professional_id')
                    ->nullable()
                    ->after('entry_date')
                    ->constrained('users')
                    ->nullOnDelete();
            }
            if (! Schema::hasColumn('diary_entries', 'authorized_at')) {
                $table->timestamp('authorized_at')->nullable()->after('authorized_professional_id');
            }
        });

        Schema::create('professional_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->longText('message');
            $table->json('tags')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('professional_chat_messages');

        Schema::table('diary_entries', function (Blueprint $table) {
            if (Schema::hasColumn('diary_entries', 'authorized_professional_id')) {
                $table->dropConstrainedForeignId('authorized_professional_id');
            }
            foreach (['authorized_at', 'notes'] as $column) {
                if (Schema::hasColumn('diary_entries', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        $driver = DB::connection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::table('users')->where('rol', 'invitado')->update(['rol' => 'paciente']);
            DB::statement("ALTER TABLE `users` MODIFY `rol` ENUM('paciente', 'psicologo', 'psiquiatra', 'admin') NOT NULL DEFAULT 'paciente'");
        }
    }
};
