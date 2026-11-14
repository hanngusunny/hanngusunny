<?php

namespace App\Services;

use App\Models\StudyProgram;
use App\Models\StudyProgramAssessment;
use App\Models\StudyProgramAssessmentQuestion;
use App\Models\StudyProgramKnowledgeReview;
use App\Models\StudyProgramKnowledgeReviewQuestion;
use App\Models\StudyProgramLevel;
use App\Models\StudyProgramSession;
use App\Models\StudyProgramSessionQuestion;
use App\Support\Questions\QuestionParser;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StudyProgramCopyService
{
    public function __construct(private QuestionParser $parser)
    {
    }

    public function copy(StudyProgram $source, int $teacherId): StudyProgram
    {
        if ($teacherId <= 0) {
            $teacherId = (int) $source->teacher_id;
        }

        set_time_limit(180);

        return DB::transaction(function () use ($source, $teacherId) {
            $copy = StudyProgram::query()->create([
                'title' => $this->copyTitle((string) $source->title),
                'description' => $source->description,
                'teacher_id' => $teacherId,
                'status' => 'active',
            ]);

            $levelMap = $this->copyLevels($source, $copy);
            $reviewMap = $this->copyKnowledgeReviews($source, $copy);
            $this->copyAssessments($source, $copy);
            $this->copySessions($source, $copy, $levelMap, $reviewMap);

            return $copy;
        });
    }

    /**
     * @return array<int, int>
     */
    private function copyLevels(StudyProgram $source, StudyProgram $copy): array
    {
        $map = [];

        foreach ($source->levels()->orderBy('sort_order')->orderBy('id')->get() as $level) {
            $created = StudyProgramLevel::query()->create([
                'program_id' => $copy->id,
                'title' => $level->title,
                'description' => $level->description,
                'sort_order' => $level->sort_order,
            ]);
            $map[(int) $level->id] = (int) $created->id;
        }

        return $map;
    }

    /**
     * @return array<int, int>
     */
    private function copyKnowledgeReviews(StudyProgram $source, StudyProgram $copy): array
    {
        $map = [];

        foreach ($source->knowledgeReviews()->orderBy('sort_order')->orderBy('id')->get() as $review) {
            $created = StudyProgramKnowledgeReview::query()->create([
                'program_id' => $copy->id,
                'title' => $review->title,
                'description' => $review->description,
                'practice_date' => $review->practice_date,
                'start_time' => $review->start_time,
                'end_time' => $review->end_time,
                'duration_minutes' => $review->duration_minutes,
                'sort_order' => $review->sort_order,
            ]);
            $map[(int) $review->id] = (int) $created->id;
            $this->copyKnowledgeReviewQuestions((int) $review->id, (int) $created->id);
        }

        return $map;
    }

    private function copyKnowledgeReviewQuestions(int $sourceId, int $copyId): void
    {
        $now = Carbon::now();
        $batch = [];

        foreach (StudyProgramKnowledgeReviewQuestion::query()->where('knowledge_review_id', $sourceId)->orderBy('sort_order')->orderBy('id')->get() as $row) {
            $batch[] = [
                'knowledge_review_id' => $copyId,
                'question_text' => $row->question_text,
                'question_type' => $this->safeQuestionType($row->toArray()),
                'options_json' => $row->options_json,
                'correct_answer' => $row->correct_answer,
                'answer_explanation' => $row->answer_explanation,
                'sort_order' => $row->sort_order,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $batch = $this->flushQuestionBatch(StudyProgramKnowledgeReviewQuestion::class, $batch);
        }

        $this->flushQuestionBatch(StudyProgramKnowledgeReviewQuestion::class, $batch, true);
    }

    private function copyAssessments(StudyProgram $source, StudyProgram $copy): void
    {
        foreach ($source->assessments()->orderBy('sort_order')->orderBy('id')->get() as $assessment) {
            $created = StudyProgramAssessment::query()->create([
                'program_id' => $copy->id,
                'title' => $assessment->title,
                'description' => $assessment->description,
                'duration_minutes' => $assessment->duration_minutes,
                'sort_order' => $assessment->sort_order,
            ]);
            $this->copyAssessmentQuestions((int) $assessment->id, (int) $created->id);
        }
    }

    private function copyAssessmentQuestions(int $sourceId, int $copyId): void
    {
        $now = Carbon::now();
        $batch = [];

        foreach (StudyProgramAssessmentQuestion::query()->where('assessment_id', $sourceId)->orderBy('sort_order')->orderBy('id')->get() as $row) {
            $batch[] = [
                'assessment_id' => $copyId,
                'question_text' => $row->question_text,
                'question_type' => $this->safeQuestionType($row->toArray()),
                'options_json' => $row->options_json,
                'correct_answer' => $row->correct_answer,
                'answer_explanation' => $row->answer_explanation,
                'points' => $row->points ?? 1,
                'sort_order' => $row->sort_order,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $batch = $this->flushQuestionBatch(StudyProgramAssessmentQuestion::class, $batch);
        }

        $this->flushQuestionBatch(StudyProgramAssessmentQuestion::class, $batch, true);
    }

    /**
     * @param  array<int, int>  $levelMap
     * @param  array<int, int>  $reviewMap
     */
    private function copySessions(StudyProgram $source, StudyProgram $copy, array $levelMap, array $reviewMap): void
    {
        foreach ($source->sessions()->orderBy('sort_order')->orderBy('id')->get() as $session) {
            $reviewId = (int) ($session->program_knowledge_review_id ?: 0);
            $levelId = (int) ($session->level_id ?: 0);

            $created = StudyProgramSession::query()->create([
                'program_id' => $copy->id,
                'level_id' => $levelId > 0 ? ($levelMap[$levelId] ?? null) : null,
                'title' => $session->title,
                'description' => $session->description,
                'pre_session_materials' => $session->pre_session_materials,
                'pre_session_videos' => $session->pre_session_videos,
                'pre_session_video_content' => $session->pre_session_video_content,
                'pre_session_video_url' => $session->pre_session_video_url,
                'materials' => $session->materials,
                'session_summary' => $session->session_summary,
                'program_knowledge_review_id' => $reviewId > 0 ? ($reviewMap[$reviewId] ?? null) : null,
                'sort_order' => $session->sort_order,
            ]);

            $this->copySessionQuestions((int) $session->id, (int) $created->id);
        }
    }

    private function copySessionQuestions(int $sourceId, int $copyId): void
    {
        $now = Carbon::now();
        $batch = [];

        foreach (StudyProgramSessionQuestion::query()->where('session_id', $sourceId)->orderBy('question_group')->orderBy('sort_order')->orderBy('id')->get() as $row) {
            $batch[] = [
                'session_id' => $copyId,
                'question_group' => $row->question_group,
                'question_text' => $row->question_text,
                'question_type' => $this->safeQuestionType($row->toArray()),
                'options_json' => $row->options_json,
                'correct_answer' => $row->correct_answer,
                'answer_explanation' => $row->answer_explanation,
                'sort_order' => $row->sort_order,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $batch = $this->flushQuestionBatch(StudyProgramSessionQuestion::class, $batch);
        }

        $this->flushQuestionBatch(StudyProgramSessionQuestion::class, $batch, true);
    }

    /**
     * @param  class-string  $model
     * @param  list<array<string, mixed>>  $batch
     * @return list<array<string, mixed>>
     */
    private function flushQuestionBatch(string $model, array $batch, bool $force = false): array
    {
        if ($batch === [] || (! $force && count($batch) < 100)) {
            return $batch;
        }

        $model::query()->insert($batch);

        return [];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function safeQuestionType(array $row): string
    {
        $normalized = $this->parser->normalizeQuestionRowForEdit($row);
        $type = strtolower(trim((string) ($normalized['question_type'] ?? '')));

        if ($type === 'text') {
            $type = 'fill';
        }

        if ($type === '' || ! in_array($type, QuestionParser::TYPES, true)) {
            return 'choice';
        }

        return $type;
    }

    private function copyTitle(string $sourceTitle): string
    {
        $base = trim($sourceTitle) !== '' ? trim($sourceTitle) : 'Chương trình';
        $max = 255;
        $suffix = ' (bản sao)';

        if (mb_strlen($base.$suffix) > $max) {
            $base = mb_substr($base, 0, $max - mb_strlen($suffix));
        }

        $title = $base.$suffix;
        $n = 2;

        while (StudyProgram::query()->where('title', $title)->exists()) {
            $suffix = ' (bản sao '.$n.')';
            if (mb_strlen($base.$suffix) > $max) {
                $base = mb_substr(trim($sourceTitle) !== '' ? trim($sourceTitle) : 'Chương trình', 0, $max - mb_strlen($suffix));
            }
            $title = $base.$suffix;
            $n++;
        }

        return $title;
    }
}
