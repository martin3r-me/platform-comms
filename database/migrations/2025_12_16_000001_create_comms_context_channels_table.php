<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comms_context_channels', function (Blueprint $table) {
            $table->id();

            // Polymorpher Kontext (z. B. HelpdeskTicket, PlannerTask, HelpdeskBoard, PlannerProject)
            $table->string('context_type');
            $table->unsignedBigInteger('context_id');

            // Channel-ID aus Registry (z. B. "email:8")
            $table->string('channel_id');

            // Scope/Ownership (optional, aber hilfreich fürs Filtern/Debuggen)
            $table->unsignedBigInteger('team_id')->nullable()->index();
            $table->unsignedBigInteger('created_by_user_id')->nullable()->index();

            $table->timestamps();

            $table->unique(['context_type', 'context_id'], 'comms_context_channels_context_unique');
            $table->index(['channel_id'], 'comms_context_channels_channel_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comms_context_channels');
    }
};


