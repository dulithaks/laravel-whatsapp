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
        Schema::table('wa_messages', function (Blueprint $table) {
            // Records the exact timestamp WhatsApp reported the last status change,
            // derived from the 'timestamp' field in the status webhook payload.
            $table->timestamp('status_updated_at')->nullable()->after('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wa_messages', function (Blueprint $table) {
            $table->dropColumn('status_updated_at');
        });
    }
};
