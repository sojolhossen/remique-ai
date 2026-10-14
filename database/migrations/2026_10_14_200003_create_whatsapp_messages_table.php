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
        Schema::create('whatsapp_messages', function (Blueprint $table) {
            $table->id();
            $table->string('wamid', 128)->nullable()->unique();
            $table->foreignId('whatsapp_identity_id')->nullable()->constrained('whatsapp_identities')->nullOnDelete();
            $table->string('direction', 16)->index(); // inbound, outbound
            $table->string('from_number', 32)->index();
            $table->string('to_number', 32)->index();
            $table->string('message_type', 32)->default('text'); // text, image, document, audio, interactive, template
            $table->text('body')->nullable();
            $table->string('media_id', 128)->nullable();
            $table->string('mime_type', 128)->nullable();
            $table->string('status', 32)->default('received')->index(); // received, accepted, sent, delivered, read, failed, unknown
            $table->string('error_code', 64)->nullable();
            $table->text('error_message')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->index(['direction', 'created_at']);
            $table->index(['whatsapp_identity_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_messages');
    }
};
