<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('online_room_participants')) {
            return;
        }

        Schema::create('online_room_participants', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('room_id');
            $table->unsignedInteger('user_id');
            $table->enum('participant_role', ['host', 'moderator', 'participant'])->default('participant');
            $table->dateTime('joined_at')->nullable();
            $table->dateTime('left_at')->nullable();
            $table->dateTime('last_seen_at')->nullable();
            $table->boolean('is_online')->default(false);
            $table->dateTime('break_out_at')->nullable();
            $table->unsignedInteger('breakout_room_index')->nullable();
            $table->timestamps();
            $table->index(['room_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('online_room_participants');
    }
};
