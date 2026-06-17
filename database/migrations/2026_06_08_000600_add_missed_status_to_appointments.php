<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (! Schema::hasColumn('appointments', 'missed_at')) {
                $table->timestamp('missed_at')->nullable()->after('ends_at');
            }
        });

        $driver = DB::connection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE `appointments` MODIFY `status` ENUM('pending_payment', 'pending', 'accepted', 'rescheduled', 'rejected', 'cancelled', 'completed', 'missed') NOT NULL DEFAULT 'pending'");
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('appointments', 'missed_at')) {
            Schema::table('appointments', function (Blueprint $table) {
                $table->dropColumn('missed_at');
            });
        }

        $driver = DB::connection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::table('appointments')->where('status', 'missed')->update(['status' => 'cancelled']);
            DB::statement("ALTER TABLE `appointments` MODIFY `status` ENUM('pending_payment', 'pending', 'accepted', 'rescheduled', 'rejected', 'cancelled', 'completed') NOT NULL DEFAULT 'pending'");
        }
    }
};
