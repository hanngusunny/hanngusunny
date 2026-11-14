<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('course_assessment_retakes')) {
            return;
        }

        Schema::create('course_assessment_retakes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('course_assessment_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('granted_by')->nullable();
            $table->timestamps();

            $table->unique(['course_assessment_id', 'user_id'], 'course_assessment_retakes_unique');
            $table->foreign('course_assessment_id', 'course_assessment_retakes_assessment_fk')
                ->references('id')->on('course_assessments')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('user_id', 'course_assessment_retakes_user_fk')
                ->references('id')->on('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('granted_by', 'course_assessment_retakes_granted_fk')
                ->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_assessment_retakes');
    }
};
