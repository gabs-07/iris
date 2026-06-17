<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('professional_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('folio', 40)->unique();
            $table->text('reason')->nullable();
            $table->string('modality', 80)->nullable();
            $table->date('appointment_date')->nullable()->index();
            $table->string('appointment_time', 20)->nullable();
            $table->timestamp('starts_at')->nullable()->index();
            $table->timestamp('ends_at')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['pending_payment', 'pending', 'accepted', 'rescheduled', 'rejected', 'cancelled', 'completed', 'missed'])->default('pending')->index();
            $table->enum('payment_status', ['pending', 'paid', 'refunded', 'waived'])->default('pending');
            $table->decimal('amount', 10, 2)->nullable();
            $table->string('room_link', 500)->nullable();
            $table->string('requested_by', 30)->nullable();
            $table->text('reschedule_proposal')->nullable();
            $table->date('reschedule_date')->nullable();
            $table->string('reschedule_time', 20)->nullable();
            $table->text('cancel_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('diary_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
            $table->string('title', 180)->nullable();
            $table->longText('content');
            $table->string('mood', 80)->nullable();
            $table->string('emoji', 16)->nullable();
            $table->date('entry_date')->index();
            $table->timestamps();
        });

        Schema::create('patient_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('professional_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 180);
            $table->text('description')->nullable();
            $table->date('due_date')->nullable();
            $table->string('status', 60)->default('pendiente');
            $table->string('repeat', 80)->nullable();
            $table->text('evidence')->nullable();
            $table->string('evidence_file_path', 500)->nullable();
            $table->string('evidence_file_name', 255)->nullable();
            $table->string('evidence_file_disk', 40)->nullable()->default('local');
            $table->string('evidence_file_mime', 120)->nullable();
            $table->unsignedInteger('evidence_file_size')->nullable();
            $table->text('follow_up')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->string('review_status', 60)->default('pendiente');
            $table->text('review_feedback')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('patient_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('professional_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 180);
            $table->date('note_date')->nullable();
            $table->string('type', 80)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('prescriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('professional_id')->constrained('users')->cascadeOnDelete();
            $table->string('folio', 50)->unique();
            $table->text('patient_name')->nullable();
            $table->text('diagnosis')->nullable();
            $table->text('medication')->nullable();
            $table->text('dose')->nullable();
            $table->text('frequency')->nullable();
            $table->text('duration')->nullable();
            $table->text('instructions')->nullable();
            $table->string('status', 60)->default('emitida');
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();
        });

        Schema::create('session_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('professional_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('patient_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note_type', 40)->default('session');
            $table->longText('content');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('session_notes');
        Schema::dropIfExists('prescriptions');
        Schema::dropIfExists('patient_notes');
        Schema::dropIfExists('patient_tasks');
        Schema::dropIfExists('diary_entries');
        Schema::dropIfExists('appointments');
    }
};
