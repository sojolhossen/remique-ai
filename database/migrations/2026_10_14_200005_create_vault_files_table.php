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
        Schema::create('vault_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('whatsapp_message_id')->nullable()->constrained('whatsapp_messages')->nullOnDelete();
            $table->string('storage_disk', 32)->default('local');
            $table->string('storage_path'); // Opaque internal key in private storage
            $table->string('original_filename');
            $table->string('mime_type', 128);
            $table->unsignedBigInteger('file_size');
            $table->string('checksum_sha256', 64)->index();
            $table->text('caption')->nullable();
            $table->string('category', 64)->nullable()->index();
            $table->json('tags')->nullable();
            $table->longText('search_text')->nullable();
            $table->string('status', 32)->default('active')->index(); // active, processing, failed, deleted
            $table->dateTime('deleted_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vault_files');
    }
};
