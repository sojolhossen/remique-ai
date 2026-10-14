<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reminder_delivery_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reminder_id')->constrained('reminders')->cascadeOnDelete();
            $table->foreignId('whatsapp_identity_id')->nullable()->constrained('whatsapp_identities')->nullOnDelete();
            $table->string('idempotency_key', 128)->unique();
            $table->string('channel', 32)->default('whatsapp');
            $table->string('status', 32)->default('pending')->index(); // pending, processing, sent, delivered, read, failed, exhausted
            $table->boolean('is_template')->default(false);
            $table->string('template_name', 64)->nullable();
            $table->foreignId('whatsapp_message_id')->nullable()->constrained('whatsapp_messages')->nullOnDelete();
            $table->unsignedInteger('attempt_number')->default(1);
            $table->string('error_code', 64)->nullable();
            $table->text('error_message')->nullable();
            $table->dateTime('scheduled_for');
            $table->dateTime('processing_started_at')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->dateTime('read_at')->nullable();
            $table->timestamps();

            $table->index(['reminder_id', 'status']);
            $table->index(['status', 'scheduled_for']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reminder_delivery_attempts');
    }
};
