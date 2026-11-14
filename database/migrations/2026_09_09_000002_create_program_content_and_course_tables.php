<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** @var list<string> */
    private array $questionTypes = [
        'fill',
        'choice',
        'essay',
        'multi_select',
        'memory_match',
        'word_link',
        'word_order',
        'listening',
        'category_sort',
        'fill_blanks',
        'word_select',
        'word_translate',
        'phat_am',
        'image_hotspot',
        'image_label_drop',
    ];

    public function up(): void
    {
        $types = $this->questionTypes;

        if (! Schema::hasTable('study_program_session_questions')) {
        Schema::create('study_program_session_questions', function (Blueprint $table) use ($types) {
            $table->increments('id');
            $table->unsignedInteger('session_id');
            $table->enum('question_group', ['review', 'quiz', 'post_practice', 'pre']);
            $table->text('question_text');
            $table->enum('question_type', $types)->default('choice');
            $table->text('options_json')->nullable();
            $table->string('correct_answer', 255)->nullable();
            $table->text('answer_explanation')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['session_id', 'question_group', 'sort_order'], 'spsq_session_group_sort');
            $table->foreign('session_id', 'study_program_session_questions_session_id_foreign')
                ->references('id')->on('study_program_sessions')->cascadeOnDelete()->cascadeOnUpdate();
        });
        }

        if (! Schema::hasTable('study_program_assessments')) {
        Schema::create('study_program_assessments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('program_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['program_id', 'sort_order'], 'spa_program_sort');
            $table->foreign('program_id', 'study_program_assessments_program_id_foreign')
                ->references('id')->on('study_programs')->cascadeOnDelete()->cascadeOnUpdate();
        });
        }

        if (! Schema::hasTable('study_program_assessment_questions')) {
        Schema::create('study_program_assessment_questions', function (Blueprint $table) use ($types) {
            $table->increments('id');
            $table->unsignedInteger('assessment_id');
            $table->text('question_text');
            $table->enum('question_type', $types)->default('choice');
            $table->text('options_json')->nullable();
            $table->string('correct_answer', 8)->nullable();
            $table->text('answer_explanation')->nullable();
            $table->decimal('points', 8, 2)->default(1.00);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['assessment_id', 'sort_order'], 'spaq_assessment_sort');
            $table->foreign('assessment_id', 'study_program_assessment_questions_assessment_id_foreign')
                ->references('id')->on('study_program_assessments')->cascadeOnDelete()->cascadeOnUpdate();
        });
        }

        if (! Schema::hasTable('study_program_knowledge_reviews')) {
        Schema::create('study_program_knowledge_reviews', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('program_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('practice_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['program_id', 'sort_order'], 'spkr_program_sort');
            $table->foreign('program_id', 'spkr_program_id_foreign')
                ->references('id')->on('study_programs')->cascadeOnDelete()->cascadeOnUpdate();
        });
        }

        if (! Schema::hasTable('study_program_knowledge_review_questions')) {
        Schema::create('study_program_knowledge_review_questions', function (Blueprint $table) use ($types) {
            $table->increments('id');
            $table->unsignedInteger('knowledge_review_id');
            $table->text('question_text');
            $table->enum('question_type', $types)->default('choice');
            $table->text('options_json')->nullable();
            $table->string('correct_answer', 8)->nullable();
            $table->text('answer_explanation')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['knowledge_review_id', 'sort_order'], 'spkrq_review_sort');
            $table->foreign('knowledge_review_id', 'spkrq_review_id_foreign')
                ->references('id')->on('study_program_knowledge_reviews')->cascadeOnDelete()->cascadeOnUpdate();
        });
        }

        Schema::create('online_rooms', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('room_code', 64);
            $table->unsignedInteger('host_id');
            $table->dateTime('scheduled_start')->nullable();
            $table->dateTime('scheduled_end')->nullable();
            $table->enum('status', ['draft', 'scheduled', 'live', 'ended', 'cancelled'])->default('draft');
            $table->enum('access_type', ['all', 'students', 'teachers'])->default('all');
            $table->unsignedInteger('max_participants')->default(50);
            $table->boolean('waiting_room')->default(false);
            $table->string('room_password', 64)->nullable();
            $table->timestamps();

            $table->unique('room_code');
            $table->index('host_id');
            $table->index('status');
            $table->foreign('host_id', 'online_rooms_host_id_foreign')
                ->references('id')->on('users')->cascadeOnDelete()->cascadeOnUpdate();
        });

        Schema::create('courses', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('teacher_id');
            $table->unsignedInteger('program_id')->nullable();
            $table->date('schedule_start_date')->nullable();
            $table->time('default_start_time')->nullable();
            $table->time('default_end_time')->nullable();
            $table->string('weekly_schedule_days', 32)->nullable();
            $table->unsignedInteger('online_room_id')->nullable();
            $table->enum('status', ['active', 'cancelled'])->default('active');
            $table->timestamps();

            $table->foreign('teacher_id', 'courses_teacher_id_foreign')
                ->references('id')->on('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('program_id', 'courses_program_id_foreign')
                ->references('id')->on('study_programs')->nullOnDelete();
            $table->foreign('online_room_id', 'courses_online_room_id_foreign')
                ->references('id')->on('online_rooms')->nullOnDelete()->cascadeOnUpdate();
        });

        Schema::create('course_sessions', function (Blueprint $table) {
            $table->increments('id');
            $table->char('public_code', 12);
            $table->unsignedInteger('course_id');
            $table->unsignedInteger('program_session_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('pre_session_materials')->nullable();
            $table->text('pre_session_videos')->nullable();
            $table->text('pre_session_video_content')->nullable();
            $table->string('pre_session_video_url', 512)->nullable();
            $table->text('materials')->nullable();
            $table->text('session_summary')->nullable();
            $table->unsignedInteger('program_knowledge_review_id')->nullable();
            $table->date('session_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('record_video_url', 512)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->enum('status', ['scheduled', 'completed', 'cancelled'])->default('scheduled');
            $table->unsignedInteger('online_room_id')->nullable();
            $table->timestamps();

            $table->unique('public_code');
            $table->index(['course_id', 'sort_order'], 'cs_course_sort');
            $table->foreign('course_id', 'course_sessions_course_id_foreign')
                ->references('id')->on('courses')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('program_session_id', 'course_sessions_program_session_fk')
                ->references('id')->on('study_program_sessions')->nullOnDelete();
            $table->foreign('program_knowledge_review_id', 'course_sessions_program_kr_fk')
                ->references('id')->on('study_program_knowledge_reviews')->nullOnDelete();
            $table->foreign('online_room_id', 'course_sessions_online_room_id_foreign')
                ->references('id')->on('online_rooms')->nullOnDelete()->cascadeOnUpdate();
        });

        Schema::create('course_session_questions', function (Blueprint $table) use ($types) {
            $table->increments('id');
            $table->unsignedInteger('course_session_id');
            $table->enum('question_group', ['review', 'quiz', 'post_practice', 'pre']);
            $table->text('question_text');
            $table->enum('question_type', $types)->default('choice');
            $table->text('options_json')->nullable();
            $table->string('correct_answer', 255)->nullable();
            $table->text('answer_explanation')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['course_session_id', 'question_group', 'sort_order'], 'csq_session_group_sort');
            $table->foreign('course_session_id', 'course_session_questions_course_session_id_foreign')
                ->references('id')->on('course_sessions')->cascadeOnDelete()->cascadeOnUpdate();
        });

        Schema::create('course_assessments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('course_id');
            $table->unsignedInteger('program_assessment_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->date('assessment_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->enum('status', ['active', 'cancelled'])->default('active');
            $table->timestamps();

            $table->index(['course_id', 'sort_order'], 'ca_course_sort');
            $table->foreign('course_id', 'course_assessments_course_id_foreign')
                ->references('id')->on('courses')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('program_assessment_id', 'course_assessments_program_assessment_fk')
                ->references('id')->on('study_program_assessments')->cascadeOnDelete();
        });

        Schema::create('course_assessment_questions', function (Blueprint $table) use ($types) {
            $table->increments('id');
            $table->unsignedInteger('course_assessment_id');
            $table->text('question_text');
            $table->enum('question_type', $types)->default('choice');
            $table->text('options_json')->nullable();
            $table->string('correct_answer', 8)->nullable();
            $table->text('answer_explanation')->nullable();
            $table->decimal('points', 8, 2)->default(1.00);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['course_assessment_id', 'sort_order'], 'caq_assessment_sort');
            $table->foreign('course_assessment_id', 'course_assessment_questions_course_assessment_id_foreign')
                ->references('id')->on('course_assessments')->cascadeOnDelete()->cascadeOnUpdate();
        });

        Schema::create('course_knowledge_reviews', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('course_id');
            $table->unsignedInteger('program_knowledge_review_id')->nullable();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('practice_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->enum('status', ['active', 'cancelled'])->default('active');
            $table->timestamps();

            $table->index(['course_id', 'sort_order'], 'ckr_course_sort');
            $table->foreign('course_id', 'course_kr_course_fk')
                ->references('id')->on('courses')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('program_knowledge_review_id', 'course_kr_program_kr_fk')
                ->references('id')->on('study_program_knowledge_reviews')->nullOnDelete()->cascadeOnUpdate();
        });

        Schema::create('course_knowledge_review_questions', function (Blueprint $table) use ($types) {
            $table->increments('id');
            $table->unsignedInteger('course_knowledge_review_id');
            $table->text('question_text');
            $table->enum('question_type', $types)->default('choice');
            $table->text('options_json')->nullable();
            $table->string('correct_answer', 8)->nullable();
            $table->text('answer_explanation')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['course_knowledge_review_id', 'sort_order'], 'ckrq_review_sort');
            $table->foreign('course_knowledge_review_id', 'course_kr_q_parent_fk')
                ->references('id')->on('course_knowledge_reviews')->cascadeOnDelete()->cascadeOnUpdate();
        });

        Schema::create('course_session_quiz_answers', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('course_session_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('question_id');
            $table->text('selected_answer')->nullable();
            $table->unsignedTinyInteger('is_correct')->default(0);
            $table->text('teacher_comment')->nullable();
            $table->dateTime('teacher_graded_at')->nullable();
            $table->timestamps();

            $table->unique(['course_session_id', 'user_id', 'question_id'], 'course_session_id_user_id_question_id');
            $table->foreign('course_session_id', 'course_session_quiz_answers_course_session_id_foreign')
                ->references('id')->on('course_sessions')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('question_id', 'course_session_quiz_answers_question_id_foreign')
                ->references('id')->on('course_session_questions')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('user_id', 'course_session_quiz_answers_user_id_foreign')
                ->references('id')->on('users')->cascadeOnDelete()->cascadeOnUpdate();
        });

        Schema::create('course_assessment_answers', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('course_assessment_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('question_id');
            $table->text('selected_answer')->nullable();
            $table->text('answer_text')->nullable();
            $table->unsignedTinyInteger('is_correct')->default(0);
            $table->decimal('points_earned', 8, 2)->nullable();
            $table->dateTime('teacher_graded_at')->nullable();
            $table->text('teacher_comment')->nullable();
            $table->timestamps();

            $table->unique(['course_assessment_id', 'user_id', 'question_id'], 'course_assessment_id_user_id_question_id');
            $table->index(['course_assessment_id', 'user_id'], 'course_assessment_id_user_id');
            $table->foreign('course_assessment_id', 'course_assessment_answers_course_assessment_id_foreign')
                ->references('id')->on('course_assessments')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('question_id', 'course_assessment_answers_question_id_foreign')
                ->references('id')->on('course_assessment_questions')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('user_id', 'course_assessment_answers_user_id_foreign')
                ->references('id')->on('users')->cascadeOnDelete()->cascadeOnUpdate();
        });

        Schema::create('course_assessment_submissions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('course_assessment_id');
            $table->unsignedInteger('user_id');
            $table->enum('status', ['submitted', 'graded'])->default('submitted');
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('graded_at')->nullable();
            $table->unsignedInteger('graded_by')->nullable();
            $table->timestamps();

            $table->unique(['course_assessment_id', 'user_id'], 'course_assessment_id_user_id');
            $table->foreign('course_assessment_id', 'course_assessment_submissions_course_assessment_id_foreign')
                ->references('id')->on('course_assessments')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('user_id', 'course_assessment_submissions_user_id_foreign')
                ->references('id')->on('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('graded_by', 'course_assessment_submissions_graded_by_foreign')
                ->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('course_assessment_answer_drafts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('course_assessment_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('question_id');
            $table->text('selected_answer')->nullable();
            $table->text('answer_text')->nullable();
            $table->unsignedTinyInteger('is_correct')->default(0);
            $table->decimal('points_earned', 8, 2)->nullable();
            $table->timestamps();

            $table->unique(['course_assessment_id', 'user_id', 'question_id'], 'course_assessment_user_question');
            $table->index(['course_assessment_id', 'user_id'], 'course_assessment_user');
        });

        Schema::create('course_assessment_attempts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('course_assessment_id');
            $table->unsignedInteger('user_id');
            $table->dateTime('started_at');
            $table->dateTime('expires_at');
            $table->timestamps();

            $table->unique(['course_assessment_id', 'user_id'], 'course_assessment_id_user_id');
            $table->foreign('course_assessment_id', 'course_assessment_attempt_assessment_fk')
                ->references('id')->on('course_assessments')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('user_id', 'course_assessment_attempt_user_fk')
                ->references('id')->on('users')->cascadeOnDelete()->cascadeOnUpdate();
        });

        Schema::create('course_knowledge_review_answers', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('course_knowledge_review_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('question_id');
            $table->text('selected_answer')->nullable();
            $table->unsignedTinyInteger('is_correct')->default(0);
            $table->text('teacher_comment')->nullable();
            $table->dateTime('teacher_graded_at')->nullable();
            $table->timestamps();

            $table->unique(['course_knowledge_review_id', 'user_id', 'question_id'], 'course_kr_user_question');
            $table->index(['course_knowledge_review_id', 'user_id'], 'course_knowledge_review_id_user_id');
            $table->foreign('course_knowledge_review_id', 'course_kr_a_review_fk')
                ->references('id')->on('course_knowledge_reviews')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('question_id', 'course_kr_a_question_fk')
                ->references('id')->on('course_knowledge_review_questions')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreign('user_id', 'course_kr_a_user_fk')
                ->references('id')->on('users')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_knowledge_review_answers');
        Schema::dropIfExists('course_assessment_attempts');
        Schema::dropIfExists('course_assessment_answer_drafts');
        Schema::dropIfExists('course_assessment_submissions');
        Schema::dropIfExists('course_assessment_answers');
        Schema::dropIfExists('course_session_quiz_answers');
        Schema::dropIfExists('course_knowledge_review_questions');
        Schema::dropIfExists('course_knowledge_reviews');
        Schema::dropIfExists('course_assessment_questions');
        Schema::dropIfExists('course_assessments');
        Schema::dropIfExists('course_session_questions');
        Schema::dropIfExists('course_sessions');
        Schema::dropIfExists('courses');
        Schema::dropIfExists('online_rooms');
        Schema::dropIfExists('study_program_knowledge_review_questions');
        Schema::dropIfExists('study_program_knowledge_reviews');
        Schema::dropIfExists('study_program_assessment_questions');
        Schema::dropIfExists('study_program_assessments');
        Schema::dropIfExists('study_program_session_questions');
    }
};
