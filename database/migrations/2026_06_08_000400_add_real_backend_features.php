<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->string('provider', 40)->nullable()->after('method');
            $table->string('currency', 10)->default('MXN')->after('amount');
            $table->string('provider_order_id', 180)->nullable()->unique()->after('reference');
            $table->string('provider_capture_id', 180)->nullable()->index()->after('provider_order_id');
            $table->json('provider_payload')->nullable()->after('provider_capture_id');
        });

        Schema::create('community_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['community_post_id', 'user_id']);
        });

        Schema::create('community_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reason', 120);
            $table->text('details')->nullable();
            $table->string('status', 40)->default('pending')->index();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('auditable_type')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->string('action', 120)->index();
            $table->text('description')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['auditable_type', 'auditable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('community_reports');
        Schema::dropIfExists('community_likes');
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['provider', 'currency', 'provider_order_id', 'provider_capture_id', 'provider_payload']);
        });
        Schema::dropIfExists('notifications');
    }
};
