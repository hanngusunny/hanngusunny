<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseAssessment;
use App\Models\CourseAssessmentQuestion;
use App\Models\CourseKnowledgeReview;
use App\Models\CourseKnowledgeReviewQuestion;
use App\Models\CourseSession;
use App\Models\CourseSessionQuestion;
use App\Models\StudyProgramAssessment;
use App\Models\StudyProgramAssessmentQuestion;
use App\Models\StudyProgramKnowledgeReview;
use App\Models\StudyProgramKnowledgeReviewQuestion;
use App\Models\StudyProgramLevel;
use App\Models\StudyProgramSession;
use App\Models\StudyProgramSessionQuestion;
use App\Support\Questions\QuestionParser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProgramCourseSyncService
{
    public function syncProgramToCourse(int $programId): void
    {
        $this->reconcileSessions($programId);
        $this->reconcileAssessments($programId);
        $this->reconcileKnowledgeReviews($programId);
    }

    /**
     * Dọn buổi học mất trình độ (xóa trình độ cũ) và đồng bộ lại khóa học gắn chương trình.
     *
     * @return array{removed_program_sessions:int, removed_course_sessions:int, levels:int, sessions:int}
     */
    public function refreshProgramStructure(int $programId): array
    {
        return DB::transaction(function () use ($programId) {
            $levelIds = StudyProgramLevel::query()
                ->where('program_id', $programId)
                ->pluck('id')
                ->map(static fn ($id): int => (int) $id)
                ->all();
            $levelMap = array_fill_keys($levelIds, true);

            $orphanIds = StudyProgramSession::query()
                ->where('program_id', $programId)
                ->where(function ($query) use ($levelMap) {
                    $query->whereNull('level_id');
                    if ($levelMap === []) {
                        $query->orWhereNotNull('level_id');
                    } else {
                        $query->orWhereNotIn('level_id', array_keys($levelMap));
                    }
                })
                ->pluck('id')
                ->map(static fn ($id): int => (int) $id)
                ->all();

            $removedProgram = 0;
            if ($orphanIds !== []) {
                CourseSession::query()->whereIn('program_session_id', $orphanIds)->delete();
                $removedProgram = StudyProgramSession::query()->whereIn('id', $orphanIds)->delete();
            }

            $validSessionIds = StudyProgramSession::query()
                ->where('program_id', $programId)
                ->pluck('id')
                ->map(static fn ($id): int => (int) $id)
                ->all();

            $courseIds = Course::query()
                ->where('program_id', $programId)
                ->pluck('id')
                ->map(static fn ($id): int => (int) $id)
                ->all();

            $removedCourse = 0;
            if ($courseIds !== []) {
                $staleQuery = CourseSession::query()->whereIn('course_id', $courseIds);
                if ($validSessionIds === []) {
                    $removedCourse = (clone $staleQuery)->delete();
                } else {
                    $removedCourse = (clone $staleQuery)
                        ->where(function ($query) use ($validSessionIds) {
                            $query->whereNull('program_session_id')
                                ->orWhereNotIn('program_session_id', $validSessionIds);
                        })
                        ->delete();
                }
            }

            foreach (StudyProgramLevel::query()->where('program_id', $programId)->orderBy('sort_order')->orderBy('id')->get() as $index => $level) {
                $level->update(['sort_order' => $index + 1]);
                $order = 1;
                foreach ($level->sessions()->orderBy('sort_order')->orderBy('id')->get() as $session) {
                    if ((int) $session->sort_order !== $order) {
                        $session->update(['sort_order' => $order]);
                    }
                    $order++;
                }
            }

            $this->reconcileSessions($programId);

            return [
                'removed_program_sessions' => (int) $removedProgram,
                'removed_course_sessions' => (int) $removedCourse,
                'levels' => count($levelIds),
                'sessions' => count($validSessionIds),
            ];
        });
    }

    public function ensureMissingSessionsForCourse(int $courseId, int $programId): int
    {
        if ($courseId <= 0 || $programId <= 0) {
            return 0;
        }

        $existing = CourseSession::query()
            ->where('course_id', $courseId)
            ->whereNotNull('program_session_id')
            ->pluck('program_session_id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
        $existingMap = array_fill_keys($existing, true);

        $created = 0;

        foreach (StudyProgramSession::query()->where('program_id', $programId)->orderBy('id')->get() as $session) {
            if (isset($existingMap[(int) $session->id])) {
                continue;
            }

            $createdRow = CourseSession::query()->create([
                'public_code' => $this->uniqueSessionCode(),
                'course_id' => $courseId,
                'program_session_id' => $session->id,
                'title' => $session->title,
                'description' => $session->description,
                'pre_session_materials' => $session->pre_session_materials,
                'pre_session_videos' => $session->pre_session_videos,
                'pre_session_video_content' => $session->pre_session_video_content,
                'pre_session_video_url' => $session->pre_session_video_url,
                'materials' => $session->materials,
                'session_summary' => $session->session_summary,
                'program_knowledge_review_id' => $session->program_knowledge_review_id,
                'sort_order' => $session->sort_order,
                'status' => 'scheduled',
            ]);

            $this->replaceSessionQuestions($session->id, (int) $createdRow->id);
            $created++;
        }

        return $created;
    }

    public function reconcileSessions(int $programId): void
    {
        $courses = Course::query()->where('program_id', $programId)->get();
        $sessions = StudyProgramSession::query()
            ->where('program_id', $programId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        foreach ($courses as $course) {
            foreach ($sessions as $session) {
                $existing = CourseSession::query()
                    ->where('course_id', $course->id)
                    ->where('program_session_id', $session->id)
                    ->first();

                if ($existing) {
                    $this->applyProgramSessionFields($existing, $session);
                    $this->syncCourseSessionQuestions((int) $session->id, (int) $existing->id);
                    continue;
                }

                $created = CourseSession::query()->create([
                    'public_code' => $this->uniqueSessionCode(),
                    'course_id' => $course->id,
                    'program_session_id' => $session->id,
                    'title' => $session->title,
                    'description' => $session->description,
                    'pre_session_materials' => $session->pre_session_materials,
                    'pre_session_videos' => $session->pre_session_videos,
                    'pre_session_video_content' => $session->pre_session_video_content,
                    'pre_session_video_url' => $session->pre_session_video_url,
                    'materials' => $session->materials,
                    'session_summary' => $session->session_summary,
                    'program_knowledge_review_id' => $session->program_knowledge_review_id,
                    'sort_order' => $session->sort_order,
                    'status' => 'scheduled',
                ]);

                $this->replaceSessionQuestions($session->id, $created->id);
            }
        }
    }

    private function uniqueSessionCode(): string
    {
        do {
            $code = substr(str_replace(['+', '/', '='], '', base64_encode(random_bytes(9))), 0, 12);
        } while (CourseSession::query()->where('public_code', $code)->exists());

        return $code;
    }

    public function syncSession(StudyProgramSession $session): void
    {
        $linked = CourseSession::query()
            ->where('program_session_id', $session->id)
            ->get();

        foreach ($linked as $courseSession) {
            $this->applyProgramSessionFields($courseSession, $session);
            $this->syncCourseSessionQuestions((int) $session->id, (int) $courseSession->id);
        }
    }

    private function applyProgramSessionFields(CourseSession $courseSession, StudyProgramSession $session): void
    {
        $courseSession->update([
            'title' => $session->title,
            'description' => $session->description,
            'pre_session_materials' => $session->pre_session_materials,
            'pre_session_videos' => $session->pre_session_videos,
            'pre_session_video_content' => $session->pre_session_video_content,
            'pre_session_video_url' => $session->pre_session_video_url,
            'materials' => $session->materials,
            'session_summary' => $session->session_summary,
            'program_knowledge_review_id' => $session->program_knowledge_review_id,
            'sort_order' => $session->sort_order,
        ]);
    }

    public function syncSessionQuestions(int $programSessionId): void
    {
        $ids = CourseSession::query()
            ->where('program_session_id', $programSessionId)
            ->pluck('id');

        foreach ($ids as $courseSessionId) {
            $this->syncCourseSessionQuestions($programSessionId, (int) $courseSessionId);
        }
    }

    public function syncCourseSessionQuestions(int $programSessionId, int $courseSessionId): void
    {
        $sourceRows = StudyProgramSessionQuestion::query()
            ->where('session_id', $programSessionId)
            ->orderBy('question_group')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $destRows = CourseSessionQuestion::query()
            ->where('course_session_id', $courseSessionId)
            ->orderBy('question_group')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if (! $this->sessionQuestionsNeedSync($sourceRows->all(), $destRows->all())) {
            return;
        }

        if ($this->sessionHasAnswers($courseSessionId)) {
            $this->updateSessionQuestionsInPlace($sourceRows->all(), $destRows->all(), $courseSessionId);

            return;
        }

        $this->replaceSessionQuestions($programSessionId, $courseSessionId);
    }

    /**
     * @param  list<StudyProgramSessionQuestion>  $sourceRows
     * @param  list<CourseSessionQuestion>  $destRows
     */
    private function sessionQuestionsNeedSync(array $sourceRows, array $destRows): bool
    {
        if ($destRows === [] && $sourceRows === []) {
            return false;
        }

        if (count($sourceRows) !== count($destRows)) {
            return true;
        }

        $parser = app(QuestionParser::class);

        foreach ($sourceRows as $index => $source) {
            $dest = $destRows[$index] ?? null;

            if (! $dest instanceof CourseSessionQuestion) {
                return true;
            }

            $sourceType = $this->syncedQuestionType($parser, $source->toArray());

            if (
                (string) $source->question_group !== (string) $dest->question_group
                || trim((string) $source->question_text) !== trim((string) $dest->question_text)
                || (string) ($source->options_json ?? '') !== (string) ($dest->options_json ?? '')
                || $sourceType !== (string) $dest->question_type
                || (string) ($source->correct_answer ?? '') !== (string) ($dest->correct_answer ?? '')
                || (string) ($source->answer_explanation ?? '') !== (string) ($dest->answer_explanation ?? '')
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<StudyProgramSessionQuestion>  $sourceRows
     * @param  list<CourseSessionQuestion>  $destRows
     */
    private function updateSessionQuestionsInPlace(array $sourceRows, array $destRows, int $courseSessionId): void
    {
        $parser = app(QuestionParser::class);

        foreach ($sourceRows as $index => $source) {
            $payload = $this->sessionQuestionPayload($source, $parser);

            $dest = $destRows[$index] ?? null;
            if ($dest instanceof CourseSessionQuestion) {
                $dest->update($payload);
                continue;
            }

            CourseSessionQuestion::query()->create(array_merge($payload, [
                'course_session_id' => $courseSessionId,
            ]));
        }

        for ($i = count($sourceRows); $i < count($destRows); $i++) {
            $extra = $destRows[$i];
            $hasAnswer = Schema::hasTable('course_session_quiz_answers')
                && DB::table('course_session_quiz_answers')->where('question_id', $extra->id)->exists();

            if (! $hasAnswer) {
                $extra->delete();
            }
        }
    }

    private function sessionHasAnswers(int $courseSessionId): bool
    {
        return Schema::hasTable('course_session_quiz_answers')
            && DB::table('course_session_quiz_answers')->where('course_session_id', $courseSessionId)->exists();
    }

    public function replaceSessionQuestions(int $programSessionId, int $courseSessionId): void
    {
        if ($this->sessionHasAnswers($courseSessionId)) {
            $sourceRows = StudyProgramSessionQuestion::query()
                ->where('session_id', $programSessionId)
                ->orderBy('question_group')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->all();
            $destRows = CourseSessionQuestion::query()
                ->where('course_session_id', $courseSessionId)
                ->orderBy('question_group')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->all();
            $this->updateSessionQuestionsInPlace($sourceRows, $destRows, $courseSessionId);

            return;
        }

        $rows = StudyProgramSessionQuestion::query()
            ->where('session_id', $programSessionId)
            ->orderBy('question_group')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if (Schema::hasTable('course_session_quiz_answers')) {
            DB::table('course_session_quiz_answers')->where('course_session_id', $courseSessionId)->delete();
        }

        CourseSessionQuestion::query()->where('course_session_id', $courseSessionId)->delete();

        $parser = app(QuestionParser::class);

        foreach ($rows as $row) {
            CourseSessionQuestion::query()->create(array_merge(
                $this->sessionQuestionPayload($row, $parser),
                ['course_session_id' => $courseSessionId]
            ));
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function sessionQuestionPayload(StudyProgramSessionQuestion $row, QuestionParser $parser): array
    {
        return [
            'question_group' => $row->question_group,
            'question_text' => $row->question_text,
            'question_type' => $this->syncedQuestionType($parser, $row->toArray()),
            'options_json' => $row->options_json,
            'correct_answer' => $row->correct_answer,
            'answer_explanation' => $row->answer_explanation,
            'sort_order' => $row->sort_order,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function syncedQuestionType(QuestionParser $parser, array $row): string
    {
        $normalized = $parser->normalizeQuestionRowForEdit($row);
        $type = strtolower(trim((string) ($normalized['question_type'] ?? '')));

        if ($type === 'text') {
            $type = 'fill';
        }

        if ($type === '' || ! in_array($type, QuestionParser::TYPES, true)) {
            return 'choice';
        }

        return $type;
    }

    public function syncAssessment(StudyProgramAssessment $assessment): void
    {
        $linked = CourseAssessment::query()
            ->where('program_assessment_id', $assessment->id)
            ->where('status', '!=', 'cancelled')
            ->get();

        foreach ($linked as $courseAssessment) {
            $courseAssessment->update([
                'title' => $assessment->title,
                'description' => $assessment->description,
                'duration_minutes' => $assessment->duration_minutes,
                'sort_order' => $assessment->sort_order,
            ]);

            $this->syncAssessmentQuestions($assessment->id, $courseAssessment->id);
        }

        $this->reconcileAssessments((int) $assessment->program_id);
    }

    public function reconcileAssessments(int $programId): void
    {
        $courses = Course::query()->where('program_id', $programId)->get();
        $items = StudyProgramAssessment::query()
            ->where('program_id', $programId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        foreach ($courses as $course) {
            foreach ($items as $index => $item) {
                $existing = CourseAssessment::query()
                    ->where('course_id', $course->id)
                    ->where('program_assessment_id', $item->id)
                    ->where('status', '!=', 'cancelled')
                    ->first();

                if ($existing) {
                    $existing->update([
                        'title' => $item->title,
                        'description' => $item->description,
                        'duration_minutes' => $item->duration_minutes,
                        'sort_order' => $index,
                    ]);
                    $this->syncAssessmentQuestions($item->id, $existing->id);
                    continue;
                }

                $created = CourseAssessment::query()->create([
                    'course_id' => $course->id,
                    'program_assessment_id' => $item->id,
                    'title' => $item->title,
                    'description' => $item->description,
                    'duration_minutes' => $item->duration_minutes,
                    'sort_order' => $index,
                    'status' => 'active',
                ]);

                $this->replaceAssessmentQuestions($item->id, $created->id);
            }
        }
    }

    public function syncAssessmentQuestions(int $programAssessmentId, int $courseAssessmentId): void
    {
        $sourceRows = StudyProgramAssessmentQuestion::query()
            ->where('assessment_id', $programAssessmentId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $destRows = CourseAssessmentQuestion::query()
            ->where('course_assessment_id', $courseAssessmentId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($destRows->isEmpty()) {
            $this->replaceAssessmentQuestions($programAssessmentId, $courseAssessmentId);

            return;
        }

        if ($this->assessmentHasResponses($courseAssessmentId)) {
            $this->updateAssessmentQuestionsInPlace($sourceRows->all(), $destRows->all(), $courseAssessmentId);

            return;
        }

        $this->replaceAssessmentQuestions($programAssessmentId, $courseAssessmentId);
    }

    /**
     * @param  list<StudyProgramAssessmentQuestion>  $sourceRows
     * @param  list<CourseAssessmentQuestion>  $destRows
     */
    private function updateAssessmentQuestionsInPlace(array $sourceRows, array $destRows, int $courseAssessmentId): void
    {
        $parser = app(QuestionParser::class);

        foreach ($sourceRows as $index => $source) {
            $dest = $destRows[$index] ?? null;
            $payload = $this->assessmentQuestionPayload($source, $parser);

            if ($dest instanceof CourseAssessmentQuestion) {
                $dest->update($payload);
                continue;
            }

            CourseAssessmentQuestion::query()->create(array_merge($payload, [
                'course_assessment_id' => $courseAssessmentId,
            ]));
        }
    }

    public function replaceAssessmentQuestions(int $programAssessmentId, int $courseAssessmentId): void
    {
        if ($this->assessmentHasResponses($courseAssessmentId)) {
            $sourceRows = StudyProgramAssessmentQuestion::query()
                ->where('assessment_id', $programAssessmentId)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->all();
            $destRows = CourseAssessmentQuestion::query()
                ->where('course_assessment_id', $courseAssessmentId)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->all();
            $this->updateAssessmentQuestionsInPlace($sourceRows, $destRows, $courseAssessmentId);

            return;
        }

        foreach (['course_assessment_answers', 'course_assessment_submissions', 'course_assessment_answer_drafts'] as $table) {
            if (Schema::hasTable($table)) {
                DB::table($table)->where('course_assessment_id', $courseAssessmentId)->delete();
            }
        }

        CourseAssessmentQuestion::query()->where('course_assessment_id', $courseAssessmentId)->delete();

        $parser = app(QuestionParser::class);
        $rows = StudyProgramAssessmentQuestion::query()
            ->where('assessment_id', $programAssessmentId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        foreach ($rows as $row) {
            CourseAssessmentQuestion::query()->create(array_merge(
                $this->assessmentQuestionPayload($row, $parser),
                ['course_assessment_id' => $courseAssessmentId]
            ));
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function assessmentQuestionPayload(StudyProgramAssessmentQuestion $row, QuestionParser $parser): array
    {
        $normalized = $parser->normalizeQuestionRowForEdit($row->toArray());

        return [
            'question_text' => $row->question_text,
            'question_type' => $this->syncedQuestionType($parser, $row->toArray()),
            'options_json' => $row->options_json,
            'correct_answer' => $row->correct_answer,
            'answer_explanation' => $row->answer_explanation,
            'points' => $row->pointsFromParsed($row->toArray(), $normalized),
            'sort_order' => $row->sort_order,
        ];
    }

    private function assessmentHasResponses(int $courseAssessmentId): bool
    {
        foreach (['course_assessment_answers', 'course_assessment_answer_drafts', 'course_assessment_submissions', 'course_assessment_attempts'] as $table) {
            if (Schema::hasTable($table) && DB::table($table)->where('course_assessment_id', $courseAssessmentId)->exists()) {
                return true;
            }
        }

        return false;
    }

    public function syncKnowledgeReview(StudyProgramKnowledgeReview $review): void
    {
        $linked = CourseKnowledgeReview::query()
            ->where('program_knowledge_review_id', $review->id)
            ->where('status', '!=', 'cancelled')
            ->get();

        foreach ($linked as $courseReview) {
            $courseReview->update([
                'title' => $review->title,
                'description' => $review->description,
                'duration_minutes' => $review->duration_minutes,
                'sort_order' => $review->sort_order,
            ]);

            $this->syncKnowledgeReviewQuestions($review->id, $courseReview->id);
        }

        $this->reconcileKnowledgeReviews((int) $review->program_id);
    }

    public function reconcileKnowledgeReviews(int $programId): void
    {
        $courses = Course::query()->where('program_id', $programId)->get();
        $items = StudyProgramKnowledgeReview::query()
            ->where('program_id', $programId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        foreach ($courses as $course) {
            foreach ($items as $index => $item) {
                $existing = CourseKnowledgeReview::query()
                    ->where('course_id', $course->id)
                    ->where('program_knowledge_review_id', $item->id)
                    ->where('status', '!=', 'cancelled')
                    ->first();

                if ($existing) {
                    $existing->update([
                        'title' => $item->title,
                        'description' => $item->description,
                        'duration_minutes' => $item->duration_minutes,
                        'sort_order' => $index,
                    ]);
                    $this->syncKnowledgeReviewQuestions($item->id, $existing->id);
                    continue;
                }

                $created = CourseKnowledgeReview::query()->create([
                    'course_id' => $course->id,
                    'program_knowledge_review_id' => $item->id,
                    'title' => $item->title,
                    'description' => $item->description,
                    'duration_minutes' => $item->duration_minutes,
                    'sort_order' => $index,
                    'status' => 'active',
                ]);

                $this->replaceKnowledgeReviewQuestions($item->id, $created->id);
            }
        }
    }

    public function syncKnowledgeReviewQuestions(int $programReviewId, int $courseReviewId): void
    {
        if ($this->knowledgeReviewHasAnswers($courseReviewId)) {
            $this->updateKnowledgeReviewQuestionsInPlace($programReviewId, $courseReviewId);

            return;
        }

        $this->replaceKnowledgeReviewQuestions($programReviewId, $courseReviewId);
    }

    public function replaceKnowledgeReviewQuestions(int $programReviewId, int $courseReviewId): void
    {
        if ($this->knowledgeReviewHasAnswers($courseReviewId)) {
            $this->updateKnowledgeReviewQuestionsInPlace($programReviewId, $courseReviewId);

            return;
        }

        CourseKnowledgeReviewQuestion::query()->where('course_knowledge_review_id', $courseReviewId)->delete();

        $parser = app(QuestionParser::class);
        $rows = StudyProgramKnowledgeReviewQuestion::query()
            ->where('knowledge_review_id', $programReviewId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        foreach ($rows as $row) {
            CourseKnowledgeReviewQuestion::query()->create([
                'course_knowledge_review_id' => $courseReviewId,
                'question_text' => $row->question_text,
                'question_type' => $this->syncedQuestionType($parser, $row->toArray()),
                'options_json' => $row->options_json,
                'correct_answer' => $row->correct_answer,
                'answer_explanation' => $row->answer_explanation,
                'sort_order' => $row->sort_order,
            ]);
        }
    }

    private function updateKnowledgeReviewQuestionsInPlace(int $programReviewId, int $courseReviewId): void
    {
        $parser = app(QuestionParser::class);
        $programRows = StudyProgramKnowledgeReviewQuestion::query()
            ->where('knowledge_review_id', $programReviewId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        $courseRows = CourseKnowledgeReviewQuestion::query()
            ->where('course_knowledge_review_id', $courseReviewId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        foreach ($programRows as $index => $programRow) {
            $payload = [
                'question_text' => $programRow->question_text,
                'question_type' => $this->syncedQuestionType($parser, $programRow->toArray()),
                'options_json' => $programRow->options_json,
                'correct_answer' => $programRow->correct_answer,
                'answer_explanation' => $programRow->answer_explanation,
                'sort_order' => $programRow->sort_order,
            ];

            $courseRow = $courseRows[$index] ?? null;
            if ($courseRow instanceof CourseKnowledgeReviewQuestion) {
                $courseRow->update($payload);
                continue;
            }

            CourseKnowledgeReviewQuestion::query()->create(array_merge($payload, [
                'course_knowledge_review_id' => $courseReviewId,
            ]));
        }

        for ($i = $programRows->count(); $i < $courseRows->count(); $i++) {
            $extra = $courseRows[$i];
            $hasAnswer = Schema::hasTable('course_knowledge_review_answers')
                && DB::table('course_knowledge_review_answers')->where('question_id', $extra->id)->exists();

            if (! $hasAnswer) {
                $extra->delete();
            }
        }
    }

    private function knowledgeReviewHasAnswers(int $courseReviewId): bool
    {
        return Schema::hasTable('course_knowledge_review_answers')
            && DB::table('course_knowledge_review_answers')->where('course_knowledge_review_id', $courseReviewId)->exists();
    }
}
