<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('course_session_attendance')) {
            Schema::create('course_session_attendance', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('session_id');
                $table->unsignedInteger('user_id');
                $table->dateTime('joined_at')->nullable();
                $table->timestamps();

                $table->unique(['session_id', 'user_id'], 'course_session_attendance_session_user');
                $table->index('user_id', 'course_session_attendance_user');
                $table->foreign('session_id', 'course_session_attendance_session_fk')
                    ->references('id')->on('course_sessions')->cascadeOnDelete()->cascadeOnUpdate();
                $table->foreign('user_id', 'course_session_attendance_user_fk')
                    ->references('id')->on('users')->cascadeOnDelete()->cascadeOnUpdate();
            });
        }

        if (! Schema::hasTable('course_session_reflections')) {
            Schema::create('course_session_reflections', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('course_session_id');
                $table->unsignedInteger('user_id');
                $table->unsignedTinyInteger('session_rating')->nullable();
                $table->text('student_comment')->nullable();
                $table->timestamps();

                $table->unique(['course_session_id', 'user_id'], 'course_session_reflections_session_user');
                $table->foreign('course_session_id', 'course_session_reflections_session_fk')
                    ->references('id')->on('course_sessions')->cascadeOnDelete()->cascadeOnUpdate();
                $table->foreign('user_id', 'course_session_reflections_user_fk')
                    ->references('id')->on('users')->cascadeOnDelete()->cascadeOnUpdate();
            });
        }

        if (! Schema::hasTable('course_knowledge_review_attempts')) {
            Schema::create('course_knowledge_review_attempts', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('course_knowledge_review_id');
                $table->unsignedInteger('user_id');
                $table->unsignedTinyInteger('is_practice')->default(0);
                $table->dateTime('started_at');
                $table->dateTime('expires_at');
                $table->timestamps();

                $table->unique(['course_knowledge_review_id', 'user_id', 'is_practice'], 'course_kr_attempt_unique');
                $table->foreign('course_knowledge_review_id', 'course_kr_attempt_review_fk')
                    ->references('id')->on('course_knowledge_reviews')->cascadeOnDelete()->cascadeOnUpdate();
                $table->foreign('user_id', 'course_kr_attempt_user_fk')
                    ->references('id')->on('users')->cascadeOnDelete()->cascadeOnUpdate();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('course_knowledge_review_attempts');
        Schema::dropIfExists('course_session_reflections');
        Schema::dropIfExists('course_session_attendance');
    }
};
