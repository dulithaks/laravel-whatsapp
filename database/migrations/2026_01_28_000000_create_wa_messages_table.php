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
        Schema::create('wa_messages', function (Blueprint $table) {
            $table->id();
            $table->string('wa_message_id')->nullable()->index();
            $table->string('from_phone')->nullable()->index();
            $table->string('to_phone')->nullable()->index();
            $table->enum('direction', ['incoming', 'outgoing'])->index();
            $table->string('message_type')->nullable(); // text, image, audio, video, document, location, etc.
            $table->text('body')->nullable(); // text content or JSON for complex types
            $table->enum('status', ['pending', 'sent', 'delivered', 'read', 'failed'])->default('pending')->index();
            $table->json('payload')->nullable(); // full request/response payload
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wa_messages');
    }
};
