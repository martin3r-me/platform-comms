<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comms_activities', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('team_id')->nullable()->index();

            // z. B. "email:8" (generisch für alle Channels)
            $table->string('channel_id')->index();

            // Kontext (Ticket/Task/…)
            $table->string('context_type')->index();
            $table->unsignedBigInteger('context_id')->index();

            // Optional: Thread-Key/ID innerhalb des Channels (z. B. email-thread-id)
            $table->string('thread_ref')->nullable()->index();

            // inbound/outbound/system
            $table->string('direction')->default('inbound')->index();

            // Zeitpunkt des Events (z. B. received_at)
            $table->timestamp('occurred_at')->index();

            // kurze Zusammenfassung für Inbox/Badges
            $table->string('summary', 255)->nullable();

            // beliebige Channel-spezifische Daten
            $table->json('payload')->nullable();

            $table->timestamps();

            $table->index(['context_type', 'context_id', 'channel_id'], 'comms_activities_context_channel_idx');
            $table->index(['team_id', 'occurred_at'], 'comms_activities_team_occurred_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comms_activities');
    }
};


