<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comms_activity_reads', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('team_id')->nullable()->index();
            $table->unsignedBigInteger('user_id')->index();

            $table->string('channel_id')->index();

            $table->string('context_type')->index();
            $table->unsignedBigInteger('context_id')->index();

            $table->timestamp('last_seen_at')->nullable();

            $table->timestamps();

            $table->unique(
                ['user_id', 'channel_id', 'context_type', 'context_id'],
                'comms_activity_reads_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comms_activity_reads');
    }
};


