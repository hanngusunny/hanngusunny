<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('course_monthly_evaluations')) {
            return;
        }

        Schema::create('course_monthly_evaluations', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('course_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('teacher_id');
            $table->char('eval_month', 7);
            $table->unsignedTinyInteger('score_attitude')->nullable();
            $table->unsignedTinyInteger('score_progress')->nullable();
            $table->unsignedTinyInteger('score_participation')->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->unique(['course_id', 'user_id', 'eval_month'], 'course_monthly_evaluations_unique');
            $table->index(['course_id', 'eval_month'], 'course_monthly_evaluations_course_month');
            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('teacher_id')->references('id')->on('users')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_monthly_evaluations');
    }
};
