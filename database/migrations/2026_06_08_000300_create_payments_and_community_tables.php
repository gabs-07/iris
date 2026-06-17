<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('plan', 120);
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('cycle', 40)->default('monthly');
            $table->string('status', 40)->default('active');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->text('features')->nullable();
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->string('concept', 180);
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('status', 40)->default('paid');
            $table->string('method', 80)->default('card');
            $table->timestamp('paid_at')->nullable();
            $table->string('reference', 120)->nullable();
            $table->timestamps();
        });

        Schema::create('community_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title', 180)->nullable();
            $table->longText('content');
            $table->string('category', 80)->nullable();
            $table->boolean('anonymous')->default(false);
            $table->string('status', 40)->default('published');
            $table->timestamps();
        });

        Schema::create('community_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_post_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->text('content');
            $table->boolean('anonymous')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_comments');
        Schema::dropIfExists('community_posts');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('subscriptions');
    }
};
