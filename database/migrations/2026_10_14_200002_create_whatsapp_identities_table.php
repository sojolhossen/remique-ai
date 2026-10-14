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
        Schema::create('whatsapp_identities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('phone_number', 32)->unique();
            $table->string('display_name')->nullable();
            $table->string('status', 32)->default('pending_verification')->index();
            $table->string('verification_token', 64)->nullable()->index();
            $table->dateTime('verification_expires_at')->nullable();
            $table->dateTime('verified_at')->nullable();
            $table->foreignId('verified_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('manual_verification_reason')->nullable();
            $table->dateTime('consent_recorded_at')->nullable();
            $table->dateTime('last_inbound_at')->nullable()->index();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_identities');
    }
};
