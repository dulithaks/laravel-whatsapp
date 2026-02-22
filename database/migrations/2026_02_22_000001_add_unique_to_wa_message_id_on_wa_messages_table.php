<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add a UNIQUE constraint to wa_messages.wa_message_id.
 *
 * Before adding the constraint we remove any duplicate rows that were created
 * by WhatsApp webhook re-deliveries (a defect fixed in the same release).
 * For each duplicated wa_message_id we keep the row with the lowest id
 * (first insert), which is the most complete record.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Deduplicate — keep the earliest row per wa_message_id
        $keep = DB::table('wa_messages')
            ->whereNotNull('wa_message_id')
            ->groupBy('wa_message_id')
            ->pluck(DB::raw('MIN(id)'));

        if ($keep->isNotEmpty()) {
            DB::table('wa_messages')
                ->whereNotNull('wa_message_id')
                ->whereNotIn('id', $keep)
                ->delete();
        }

        // 2. Replace the plain index with a unique index
        Schema::table('wa_messages', function (Blueprint $table) {
            $table->dropIndex(['wa_message_id']);   // wa_messages_wa_message_id_index
            $table->unique('wa_message_id');
        });
    }

    public function down(): void
    {
        Schema::table('wa_messages', function (Blueprint $table) {
            $table->dropUnique(['wa_message_id']);
            $table->index('wa_message_id');
        });
    }
};
