<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('course_session_room_events')) {
            return;
        }

        Schema::create('course_session_room_events', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('session_id');
            $table->unsignedInteger('room_id');
            $table->unsignedInteger('user_id');
            $table->string('event_type', 32);
            $table->dateTime('occurred_at');
            $table->timestamps();

            $table->index(['session_id', 'occurred_at'], 'course_session_room_events_session_time');
            $table->index('room_id', 'course_session_room_events_room');
            $table->index('user_id', 'course_session_room_events_user');
            $table->foreign('session_id', 'course_session_room_events_session_fk')
                ->references('id')->on('course_sessions')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('room_id', 'course_session_room_events_room_fk')
                ->references('id')->on('online_rooms')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('user_id', 'course_session_room_events_user_fk')
                ->references('id')->on('users')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_session_room_events');
    }
};
