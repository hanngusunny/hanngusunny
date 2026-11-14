<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_programs', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('teacher_id');
            $table->enum('status', ['active', 'archived'])->default('active');
            $table->timestamps();

            $table->foreign('teacher_id')->references('id')->on('users');
        });

        Schema::create('study_program_levels', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('program_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('program_id')->references('id')->on('study_programs')->cascadeOnDelete();
        });

        Schema::create('study_program_sessions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('program_id');
            $table->unsignedInteger('level_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('pre_session_materials')->nullable();
            $table->text('pre_session_videos')->nullable();
            $table->text('pre_session_video_content')->nullable();
            $table->string('pre_session_video_url', 512)->nullable();
            $table->text('materials')->nullable();
            $table->text('session_summary')->nullable();
            $table->unsignedInteger('program_knowledge_review_id')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('program_id')->references('id')->on('study_programs')->cascadeOnDelete();
            $table->foreign('level_id')->references('id')->on('study_program_levels')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_program_sessions');
        Schema::dropIfExists('study_program_levels');
        Schema::dropIfExists('study_programs');
    }
};
