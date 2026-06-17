<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            if (! Schema::hasColumn('appointments', 'zoom_meeting_id')) {
                $table->string('zoom_meeting_id', 100)->nullable()->after('room_link');
            }
            if (! Schema::hasColumn('appointments', 'zoom_join_url')) {
                $table->text('zoom_join_url')->nullable()->after('zoom_meeting_id');
            }
            if (! Schema::hasColumn('appointments', 'zoom_start_url')) {
                $table->text('zoom_start_url')->nullable()->after('zoom_join_url');
            }
            if (! Schema::hasColumn('appointments', 'zoom_password')) {
                $table->string('zoom_password', 100)->nullable()->after('zoom_start_url');
            }
            if (! Schema::hasColumn('appointments', 'zoom_created_at')) {
                $table->timestamp('zoom_created_at')->nullable()->after('zoom_password');
            }
            if (! Schema::hasColumn('appointments', 'zoom_payload')) {
                $table->json('zoom_payload')->nullable()->after('zoom_created_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            foreach (['zoom_payload', 'zoom_created_at', 'zoom_password', 'zoom_start_url', 'zoom_join_url', 'zoom_meeting_id'] as $column) {
                if (Schema::hasColumn('appointments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
