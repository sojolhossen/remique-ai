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
        Schema::table('reminders', function (Blueprint $table) {
            $table->index(['status', 'due_at'], 'reminders_status_due_at_idx');
        });

        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->index(['from_number', 'created_at'], 'whatsapp_messages_from_created_idx');
            $table->index(['status', 'created_at'], 'whatsapp_messages_status_created_idx');
        });

        Schema::table('reminder_delivery_attempts', function (Blueprint $table) {
            $table->index(['whatsapp_identity_id', 'status'], 'delivery_attempts_identity_status_idx');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->index(['action', 'created_at'], 'audit_logs_action_created_idx');
            $table->index(['user_id', 'created_at'], 'audit_logs_user_created_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reminders', function (Blueprint $table) {
            $table->dropIndex('reminders_status_due_at_idx');
        });

        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->dropIndex('whatsapp_messages_from_created_idx');
            $table->dropIndex('whatsapp_messages_status_created_idx');
        });

        Schema::table('reminder_delivery_attempts', function (Blueprint $table) {
            $table->dropIndex('delivery_attempts_identity_status_idx');
        });

        Schema::table('audit_logs', function (Blueprint $table) {
            $table->dropIndex('audit_logs_action_created_idx');
            $table->dropIndex('audit_logs_user_created_idx');
        });
    }
};
