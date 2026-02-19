<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('wa_messages', function (Blueprint $table) {
            // Records the exact timestamp WhatsApp reported the last status change.
            // Populated from the 'timestamp' field in the status webhook payload.
            $table->timestamp('status_updated_at')->nullable();
        });

        // Extend the status enum to include 'deleted'
        // (fired when a recipient deletes a sent message for everyone).
        DB::statement(
            "ALTER TABLE wa_messages MODIFY COLUMN
             status ENUM('pending','sent','delivered','read','failed','deleted')
             NOT NULL DEFAULT 'pending'"
        );
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert enum before dropping column to avoid constraint issues
        DB::statement(
            "ALTER TABLE wa_messages MODIFY COLUMN
             status ENUM('pending','sent','delivered','read','failed')
             NOT NULL DEFAULT 'pending'"
        );

        Schema::table('wa_messages', function (Blueprint $table) {
            $table->dropColumn('status_updated_at');
        });
    }
};
