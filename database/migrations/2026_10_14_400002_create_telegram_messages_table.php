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
        Schema::create('telegram_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('telegram_identity_id')->constrained('telegram_identities')->cascadeOnDelete();
            $table->string('chat_id', 64)->index();
            $table->string('direction', 16)->index(); // inbound, outbound
            $table->string('role', 16)->default('user'); // user, assistant
            $table->string('message_id', 64)->nullable();
            $table->text('body')->nullable();
            $table->timestamps();

            $table->index(['telegram_identity_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('telegram_messages');
    }
};
