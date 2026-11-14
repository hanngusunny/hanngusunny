<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('course_session_quiz_answer_drafts')) {
            Schema::create('course_session_quiz_answer_drafts', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('course_session_id');
                $table->unsignedInteger('user_id');
                $table->unsignedInteger('question_id');
                $table->text('selected_answer')->nullable();
                $table->timestamps();
                $table->unique(['course_session_id', 'user_id', 'question_id'], 'csq_draft_session_user_question');
                $table->index(['course_session_id', 'user_id'], 'csq_draft_session_user');
            });
        }

        if (! Schema::hasTable('course_knowledge_review_answer_drafts')) {
            Schema::create('course_knowledge_review_answer_drafts', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('course_knowledge_review_id');
                $table->unsignedInteger('user_id');
                $table->unsignedInteger('question_id');
                $table->text('selected_answer')->nullable();
                $table->timestamps();
                $table->unique(['course_knowledge_review_id', 'user_id', 'question_id'], 'ckr_draft_review_user_question');
                $table->index(['course_knowledge_review_id', 'user_id'], 'ckr_draft_review_user');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('course_knowledge_review_answer_drafts');
        Schema::dropIfExists('course_session_quiz_answer_drafts');
    }
};
