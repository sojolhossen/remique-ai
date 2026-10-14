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
        Schema::create('whatsapp_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 64)->unique();
            $table->string('category', 32)->default('UTILITY'); // UTILITY, MARKETING, AUTHENTICATION
            $table->string('language', 16)->default('en_US');
            $table->string('status', 32)->default('APPROVED')->index(); // APPROVED, PENDING, REJECTED, PAUSED
            $table->text('body_text');
            $table->json('parameters')->nullable(); // description of variables e.g. ["title", "due_time"]
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_templates');
    }
};
