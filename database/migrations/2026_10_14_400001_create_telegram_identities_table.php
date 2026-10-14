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
        Schema::create('telegram_identities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('chat_id', 64)->unique()->index();
            $table->string('username', 128)->nullable()->index();
            $table->string('first_name', 128)->nullable();
            $table->string('status', 32)->default('pending_verification')->index(); // active, pending_verification, blocked
            $table->string('verification_token', 64)->nullable()->index();
            $table->dateTime('verification_expires_at')->nullable();
            $table->dateTime('verified_at')->nullable();
            $table->dateTime('last_inbound_at')->nullable()->index();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });

        // Add telegram support column to users if desired for preferred channel
        Schema::table('users', function (Blueprint $table) {
            $table->string('preferred_channel', 32)->default('all')->after('preferred_language'); // whatsapp, telegram, all
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_identities');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('preferred_channel');
        });
    }
};
