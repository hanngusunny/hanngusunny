<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseKnowledgeReviewAnswer;
use App\Models\CourseSessionQuizAnswer;
use App\Support\HtmlContent;
use App\Support\MediaUrl;
use App\Support\Questions\QuestionParser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TeacherGradingService
{
    public const TAB_PRE = 'truoc-buoi';

    public const TAB_POST = 'sau-buoi';

    public const TAB_KNOWLEDGE_REVIEW = 'on-tap-kien-thuc';

    public function __construct(private readonly QuestionParser $parser)
    {
    }

    /**
     * @return array<string, mixed>|null
     */
    public function build(int $teacherId, int $courseId, string $activeTab, string $selectedKey): ?array
    {
        if ($teacherId <= 0 || ! $this->tablesReady()) {
            return null;
        }

        $courses = Course::activeForTeacher($teacherId);

        if ($courses->isEmpty()) {
            return null;
        }

        $courses->loadCount([
            'activeMembers as member_count',
            'sessions as session_count',
        ]);

        if ($courseId <= 0 || ! $courses->contains('id', $courseId)) {
            $courseId = (int) $courses->first()?->id;
        }

        $course = $courses->firstWhere('id', $courseId);

        if ($course === null) {
            return null;
        }

        $activeTab = $this->normalizeTab($activeTab);
        $preItems = $this->getPendingSessionQuizItems($teacherId, 0, 'pre');
        $postItems = $this->getPendingSessionQuizItems($teacherId, 0, 'post_practice');
        $krItems = $this->getPendingKnowledgeReviewItems($teacherId, 0);

        $coursePre = $this->itemsForCourse($preItems, $courseId);
        $coursePost = $this->itemsForCourse($postItems, $courseId);
        $courseKr = $this->itemsForCourse($krItems, $courseId);

        $tabItems = match ($activeTab) {
            self::TAB_POST => $coursePost,
            self::TAB_KNOWLEDGE_REVIEW => $courseKr,
            default => $coursePre,
        };

        $selectedItem = $this->findItemByKey($tabItems, $selectedKey);

        if ($selectedItem === null && $tabItems !== []) {
            $selectedItem = $tabItems[0];
            $selectedKey = (string) ($selectedItem['key'] ?? '');
        }

        $courseSummaries = [];

        foreach ($courses as $row) {
            $id = (int) $row->id;
            $pendingPre = count($this->itemsForCourse($preItems, $id));
            $pendingPost = count($this->itemsForCourse($postItems, $id));
            $pendingKr = count($this->itemsForCourse($krItems, $id));

            $courseSummaries[] = [
                'course' => $row,
                'member_count' => (int) ($row->member_count ?? 0),
                'session_count' => (int) ($row->session_count ?? 0),
                'pending_pre' => $pendingPre,
                'pending_post' => $pendingPost,
                'pending_kr' => $pendingKr,
                'pending_total' => $pendingPre + $pendingPost + $pendingKr,
            ];
        }

        $tabCounts = [
            self::TAB_PRE => count($coursePre),
            self::TAB_POST => count($coursePost),
            self::TAB_KNOWLEDGE_REVIEW => count($courseKr),
        ];

        return [
            'courses' => $courses,
            'courseSummaries' => $courseSummaries,
            'course' => $course,
            'courseId' => $courseId,
            'activeTab' => $activeTab,
            'selectedKey' => $selectedKey,
            'selectedItem' => $selectedItem,
            'preItems' => $coursePre,
            'postItems' => $coursePost,
            'krItems' => $courseKr,
            'tabItems' => $tabItems,
            'tabCounts' => $tabCounts,
            'pendingTotal' => array_sum($tabCounts),
        ];
    }

    public function normalizeTab(string $tab): string
    {
        return match ($tab) {
            self::TAB_PRE, self::TAB_POST, self::TAB_KNOWLEDGE_REVIEW => $tab,
            default => self::TAB_PRE,
        };
    }

    public function tabLabel(string $tab): string
    {
        return match ($this->normalizeTab($tab)) {
            self::TAB_POST => 'Sau buổi học',
            self::TAB_KNOWLEDGE_REVIEW => 'Ôn tập kiến thức bài',
            default => 'Trước buổi học',
        };
    }

    public function pageUrl(int $courseId, string $tab, string $selectedKey = ''): string
    {
        return route('teacher.grading', array_filter([
            'khoa' => $courseId > 0 ? $courseId : null,
            'muc' => $this->normalizeTab($tab),
            'bai' => $selectedKey !== '' ? $selectedKey : null,
        ], static fn ($value) => $value !== null && $value !== ''));
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  list<array{is_correct: bool, comment?: string}>  $itemGrades
     */
    public function saveWordTranslateGrade(array $item, array $itemGrades): bool
    {
        $itemCount = $this->wordTranslateItemCount($item);

        if ($itemCount <= 0 || ! $this->parser->isWordTranslateItemReviewComplete($itemGrades, $itemCount)) {
            return false;
        }

        $row = $this->findAnswerRow($item);

        if ($row === null) {
            return false;
        }

        return $row->saveTeacherWordTranslateReview($itemGrades, $itemCount);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    public function savePhatAmGrade(array $item, bool $isCorrect, string $text, string $recordingPath): bool
    {
        $row = $this->findAnswerRow($item);

        return $row !== null && $row->saveTeacherPhatAmReview($isCorrect, $text, $recordingPath);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    public function saveGrade(array $item, bool $isCorrect, string $comment): bool
    {
        $row = $this->findAnswerRow($item);

        return $row !== null && $row->saveTeacherEssayReview($isCorrect, $comment);
    }

    /**
     * @param  array<string, mixed>  $item
     * @param  array<int, array{is_correct: bool, comment?: string}>  $itemGrades
     */
    public function saveCompositeFillGrade(array $item, array $itemGrades, string $comment, string $recordingPath = ''): bool
    {
        $row = $this->findAnswerRow($item);

        if ($row === null) {
            return false;
        }

        return $row->saveTeacherCompositeFillReview(
            $this->questionFromGradingItem($item),
            (string) ($item['selected_answer'] ?? ''),
            $itemGrades,
            $comment,
            $recordingPath
        );
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    public function questionFromGradingItem(array $item): array
    {
        return $this->parser->normalizeQuestionRowForEdit([
            'question_type' => (string) ($item['question_type'] ?? ''),
            'options_json' => (string) ($item['options_json'] ?? ''),
            'question_text' => (string) ($item['question_html'] ?? $item['question_text'] ?? ''),
            'correct_answer' => (string) ($item['correct_answer'] ?? ''),
        ]);
    }

    /**
     * @param  list<int>  $fillIndexes
     * @return array<int, array{is_correct: bool, comment: string}>|null
     */
    public function parseCompositeFillItemGrades(mixed $rawGrades, array $fillIndexes): ?array
    {
        if ($fillIndexes === [] || ! is_array($rawGrades)) {
            return null;
        }

        $result = [];

        foreach ($fillIndexes as $index) {
            $index = (int) $index;
            $grade = $rawGrades[$index] ?? $rawGrades[(string) $index] ?? null;

            if (! is_array($grade)) {
                return null;
            }

            $isCorrectRaw = (string) ($grade['is_correct'] ?? '');

            if (! in_array($isCorrectRaw, ['0', '1'], true)) {
                return null;
            }

            $result[$index] = [
                'is_correct' => $isCorrectRaw === '1',
                'comment' => trim((string) ($grade['comment'] ?? '')),
            ];
        }

        return $result;
    }

    /**
     * @param  mixed  $rawGrades
     * @return list<array{is_correct: bool, comment: string}>|null
     */
    public function parseWordTranslateItemGrades(mixed $rawGrades, int $itemCount): ?array
    {
        if ($itemCount <= 0 || ! is_array($rawGrades) || count($rawGrades) !== $itemCount) {
            return null;
        }

        $result = [];

        foreach ($rawGrades as $grade) {
            if (! is_array($grade)) {
                return null;
            }

            $isCorrectRaw = (string) ($grade['is_correct'] ?? '');

            if (! in_array($isCorrectRaw, ['0', '1'], true)) {
                return null;
            }

            $result[] = [
                'is_correct' => $isCorrectRaw === '1',
                'comment' => trim((string) ($grade['comment'] ?? '')),
            ];
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    public function wordTranslateItemCount(array $item): int
    {
        return count($this->parser->decodeWordTranslateData([
            'question_type' => (string) ($item['question_type'] ?? ''),
            'options_json' => (string) ($item['options_json'] ?? ''),
        ])['items'] ?? []);
    }

    /**
     * @return list<array{is_correct: bool, comment: string}>
     */
    public function parseFillBlanksItemGrades(mixed $rawGrades, int $blankCount): array
    {
        $result = [];

        if ($blankCount <= 0) {
            return $result;
        }

        $rawGrades = is_array($rawGrades) ? $rawGrades : [];

        for ($index = 0; $index < $blankCount; $index++) {
            $grade = $rawGrades[$index] ?? $rawGrades[(string) $index] ?? null;
            $raw = is_array($grade) ? ($grade['is_correct'] ?? '') : '';

            if (is_array($raw)) {
                $raw = end($raw);
            }

            $result[$index] = [
                'is_correct' => (string) $raw === '1',
                'comment' => '',
            ];
        }

        return $result;
    }

    /**
     * @param  array<int, float>  $maxByIndex
     * @return array<int, float>
     */
    public function parseCompositeItemPointGrades(mixed $rawGrades, array $maxByIndex): array
    {
        return $this->parseCompositeItemGradePayload($rawGrades, $maxByIndex)['earned'];
    }

    /**
     * @param  array<int, float>  $maxByIndex
     * @param  array<int, float>  $existingEarned
     * @param  array<int, array<int, float>>  $existingParts
     * @param  array<int, array<int, float>>  $partWeights
     * @return array{earned: array<int, float>, parts: array<int, array<int, float>>}
     */
    public function parseCompositeItemGradePayload(
        mixed $rawGrades,
        array $maxByIndex,
        array $existingEarned = [],
        array $existingParts = [],
        array $partWeights = []
    ): array {
        $rawGrades = is_array($rawGrades) ? $rawGrades : [];
        $earned = [];
        $parts = [];

        foreach ($maxByIndex as $index => $max) {
            $index = (int) $index;
            $max = max(0.0, (float) $max);
            $grade = $rawGrades[$index] ?? $rawGrades[(string) $index] ?? null;
            $itemPartWeights = is_array($partWeights[$index] ?? null) ? $partWeights[$index] : [];
            $postedParts = is_array($grade) && is_array($grade['parts'] ?? null) ? $grade['parts'] : null;
            $hasPostedPart = false;

            if ($postedParts !== null) {
                $nextParts = [];

                foreach ($itemPartWeights as $partIndex => $partMax) {
                    $partGrade = $postedParts[$partIndex] ?? $postedParts[(string) $partIndex] ?? null;
                    $raw = is_array($partGrade) ? ($partGrade['points'] ?? '') : $partGrade;
                    $raw = str_replace(',', '.', trim((string) $raw));

                    if ($raw !== '' && is_numeric($raw)) {
                        $hasPostedPart = true;
                        $nextParts[(int) $partIndex] = round(max(0, min(max(0, (float) $partMax), (float) $raw)), 2);
                        continue;
                    }

                    $existing = $existingParts[$index][$partIndex] ?? $existingParts[$index][(string) $partIndex] ?? null;
                    $nextParts[(int) $partIndex] = $existing !== null
                        ? round(max(0, min(max(0, (float) $partMax), (float) $existing)), 2)
                        : 0.0;
                }

                if ($hasPostedPart) {
                    $parts[$index] = $nextParts;
                    $earned[$index] = round(max(0, min($max, array_sum($nextParts))), 2);
                    continue;
                }
            }

            $raw = is_array($grade) ? ($grade['points'] ?? '') : $grade;
            $raw = str_replace(',', '.', trim((string) $raw));

            if ($raw !== '' && is_numeric($raw)) {
                $earned[$index] = round(max(0, min($max, (float) $raw)), 2);

                if (isset($existingParts[$index])) {
                    $parts[$index] = $this->clampCompositePartGrades($existingParts[$index], $itemPartWeights, $max);
                }

                continue;
            }

            if (isset($existingEarned[$index])) {
                $earned[$index] = round(max(0, min($max, (float) $existingEarned[$index])), 2);
            } else {
                $earned[$index] = 0.0;
            }

            if (isset($existingParts[$index])) {
                $parts[$index] = $this->clampCompositePartGrades($existingParts[$index], $itemPartWeights, $max);
            }
        }

        return [
            'earned' => $earned,
            'parts' => $parts,
        ];
    }

    /**
     * @param  array<int|string, mixed>  $parts
     * @param  array<int|string, float|int|string>  $partWeights
     * @return array<int, float>
     */
    private function clampCompositePartGrades(array $parts, array $partWeights, float $itemMax): array
    {
        $clamped = [];

        foreach ($parts as $partIndex => $partValue) {
            $partMax = (float) ($partWeights[$partIndex] ?? $partWeights[(string) $partIndex] ?? $itemMax);
            $clamped[(int) $partIndex] = round(max(0, min(max(0, $partMax), (float) $partValue)), 2);
        }

        return $clamped;
    }

    /**
     * @param  array<int, array{is_correct: bool, comment?: string}>  $existing
     * @return array<int, array{is_correct: bool, comment: string}>
     */
    public function mergeWordTranslateItemGrades(mixed $rawGrades, int $itemCount, array $existing = []): array
    {
        $merged = [];

        foreach ($existing as $index => $grade) {
            if (! is_array($grade) || ! array_key_exists('is_correct', $grade)) {
                continue;
            }

            $merged[(int) $index] = [
                'is_correct' => ! empty($grade['is_correct']),
                'comment' => trim((string) ($grade['comment'] ?? '')),
            ];
        }

        if ($itemCount <= 0 || ! is_array($rawGrades)) {
            return $merged;
        }

        for ($index = 0; $index < $itemCount; $index++) {
            $grade = $rawGrades[$index] ?? $rawGrades[(string) $index] ?? null;

            if (! is_array($grade)) {
                continue;
            }

            $isCorrectRaw = (string) ($grade['is_correct'] ?? '');
            $comment = trim((string) ($grade['comment'] ?? ''));

            if (in_array($isCorrectRaw, ['0', '1'], true)) {
                $merged[$index] = [
                    'is_correct' => $isCorrectRaw === '1',
                    'comment' => $comment,
                ];
                continue;
            }

            if ($comment !== '' && isset($merged[$index])) {
                $merged[$index]['comment'] = $comment;
            }
        }

        ksort($merged);

        return $merged;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return array<string, mixed>|null
     */
    public function findItemByKey(array $items, string $key): ?array
    {
        if ($key === '') {
            return null;
        }

        foreach ($items as $item) {
            if (($item['key'] ?? '') === $key) {
                return $item;
            }
        }

        return null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getPendingSessionQuizItems(int $teacherId, int $courseId, string $questionGroup): array
    {
        if ($teacherId <= 0 || ! Schema::hasTable('course_session_quiz_answers')) {
            return [];
        }

        $query = DB::table('course_session_quiz_answers as a')
            ->join('course_session_questions as q', 'q.id', '=', 'a.question_id')
            ->join('course_sessions as s', 's.id', '=', 'a.course_session_id')
            ->join('courses as c', 'c.id', '=', 's.course_id')
            ->join('users as u', 'u.id', '=', 'a.user_id')
            ->where('c.teacher_id', $teacherId)
            ->where('c.status', Course::STATUS_ACTIVE)
            ->where('q.question_group', $questionGroup)
            ->where(function ($inner) {
                $inner->whereIn('q.question_type', ['fill', 'listening', 'word_translate', 'phat_am'])
                    ->orWhere('q.options_json', 'like', '%"phat_am"%')
                    ->orWhere('q.options_json', 'like', '%"word_translate"%')
                    ->orWhere('q.options_json', 'like', '%"listening"%')
                    ->orWhere('a.selected_answer', 'like', '%"recording":%');
            })
            ->whereNotNull('a.selected_answer')
            ->whereRaw('TRIM(a.selected_answer) != ?', [''])
            ->where(function ($inner) {
                $inner->whereNull('a.teacher_graded_at')
                    ->orWhere('a.teacher_graded_at', '');
            })
            ->orderByDesc('a.updated_at')
            ->orderByDesc('a.id')
            ->select([
                'a.id as answer_id',
                'a.course_session_id',
                'a.user_id',
                'a.question_id',
                'a.selected_answer',
                'a.is_correct',
                'a.teacher_comment',
                'a.teacher_graded_at',
                'a.updated_at as submitted_at',
                'q.question_text',
                'q.question_type',
                'q.options_json',
                'q.correct_answer',
                'q.question_group',
                's.title as session_title',
                's.sort_order as session_sort_order',
                'c.id as course_id',
                'c.title as course_title',
                'u.full_name',
                'u.username',
                'u.avatar',
            ]);

        if ($courseId > 0) {
            $query->where('c.id', $courseId);
        }

        $rows = array_values(array_filter(
            $query->get()->map(static fn ($row) => (array) $row)->all(),
            fn (array $row): bool => $this->isTeacherGradedQuestion($row)
        ));

        return $this->formatSessionQuizItems($rows, $questionGroup);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function formatSessionQuizItems(array $rows, string $questionGroup): array
    {
        $items = [];

        foreach ($rows as $row) {
            $answerId = (int) ($row['answer_id'] ?? 0);

            if ($answerId <= 0) {
                continue;
            }

            $normalizedQuestion = $this->parser->normalizeQuestionRowForEdit([
                'question_type' => (string) ($row['question_type'] ?? ''),
                'options_json' => (string) ($row['options_json'] ?? ''),
            ]);

            $studentName = trim((string) (($row['full_name'] ?? '') ?: ($row['username'] ?? '')));
            $sessionTitle = trim((string) ($row['session_title'] ?? ''));

            if ($sessionTitle === '') {
                $order = (int) ($row['session_sort_order'] ?? 0);
                $sessionTitle = $order > 0 ? 'Buổi '.$order : 'Buổi học';
            }

            $items[] = [
                'key' => 'q-'.$answerId,
                'source' => 'session',
                'answer_id' => $answerId,
                'course_id' => (int) ($row['course_id'] ?? 0),
                'course_title' => (string) ($row['course_title'] ?? ''),
                'session_id' => (int) ($row['course_session_id'] ?? 0),
                'session_title' => $sessionTitle,
                'user_id' => (int) ($row['user_id'] ?? 0),
                'student_name' => $studentName !== '' ? $studentName : 'Học viên',
                'student_avatar' => $this->studentAvatarUrl($row['avatar'] ?? null),
                'question_id' => (int) ($row['question_id'] ?? 0),
                'question_type' => (string) ($normalizedQuestion['question_type'] ?? ''),
                'options_json' => (string) ($row['options_json'] ?? ''),
                'question_text' => $this->buildQuestionPreviewText($normalizedQuestion, (string) ($row['question_text'] ?? '')),
                'question_html' => (string) ($row['question_text'] ?? ''),
                'correct_answer' => (string) ($row['correct_answer'] ?? ''),
                'selected_answer' => (string) ($row['selected_answer'] ?? ''),
                'answer_row' => [
                    'is_correct' => (int) ($row['is_correct'] ?? 0),
                    'teacher_comment' => (string) ($row['teacher_comment'] ?? ''),
                    'teacher_graded_at' => $this->gradedAtString($row['teacher_graded_at'] ?? ''),
                ],
                'submitted_at' => (string) ($row['submitted_at'] ?? ''),
                'tab' => $questionGroup === 'post_practice' ? self::TAB_POST : self::TAB_PRE,
                'tab_label' => $questionGroup === 'post_practice' ? 'Sau buổi học' : 'Trước buổi học',
                'assignment_label' => $sessionTitle,
            ];
        }

        return $items;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getPendingKnowledgeReviewItems(int $teacherId, int $courseId): array
    {
        if (
            $teacherId <= 0
            || ! Schema::hasTable('course_knowledge_review_answers')
            || ! Schema::hasColumn('course_knowledge_review_answers', 'teacher_graded_at')
        ) {
            return [];
        }

        $query = DB::table('course_knowledge_review_answers as a')
            ->join('course_knowledge_review_questions as q', 'q.id', '=', 'a.question_id')
            ->join('course_knowledge_reviews as kr', 'kr.id', '=', 'a.course_knowledge_review_id')
            ->join('courses as c', 'c.id', '=', 'kr.course_id')
            ->join('users as u', 'u.id', '=', 'a.user_id')
            ->leftJoin('course_sessions as s', function ($join) {
                $join->whereRaw('s.id = (
                    SELECT MIN(s2.id)
                    FROM course_sessions s2
                    WHERE s2.course_id = c.id
                      AND s2.program_knowledge_review_id = kr.program_knowledge_review_id
                )');
            })
            ->where('c.teacher_id', $teacherId)
            ->where('c.status', Course::STATUS_ACTIVE)
            ->where(function ($inner) {
                $inner->whereIn('q.question_type', ['fill', 'essay', 'listening', 'word_translate', 'phat_am'])
                    ->orWhere('q.options_json', 'like', '%"phat_am"%')
                    ->orWhere('q.options_json', 'like', '%"word_translate"%')
                    ->orWhere('q.options_json', 'like', '%"listening"%')
                    ->orWhere('a.selected_answer', 'like', '%"recording":%');
            })
            ->whereNotNull('a.selected_answer')
            ->whereRaw('TRIM(a.selected_answer) != ?', [''])
            ->where(function ($inner) {
                $inner->whereNull('a.teacher_graded_at')
                    ->orWhere('a.teacher_graded_at', '');
            })
            ->orderByDesc('a.updated_at')
            ->orderByDesc('a.id')
            ->select([
                'a.id as answer_id',
                'a.course_knowledge_review_id',
                'a.user_id',
                'a.question_id',
                'a.selected_answer',
                'a.is_correct',
                'a.teacher_comment',
                'a.teacher_graded_at',
                'a.updated_at as submitted_at',
                'q.question_text',
                'q.question_type',
                'q.options_json',
                'q.correct_answer',
                'kr.title as review_title',
                'kr.program_knowledge_review_id',
                'c.id as course_id',
                'c.title as course_title',
                's.id as session_id',
                's.title as session_title',
                's.sort_order as session_sort_order',
                'u.full_name',
                'u.username',
                'u.avatar',
            ]);

        if ($courseId > 0) {
            $query->where('c.id', $courseId);
        }

        $rows = array_values(array_filter(
            $query->get()->map(static fn ($row) => (array) $row)->all(),
            fn (array $row): bool => $this->isTeacherGradedQuestion($row)
        ));

        $items = [];

        foreach ($rows as $row) {
            $answerId = (int) ($row['answer_id'] ?? 0);

            if ($answerId <= 0) {
                continue;
            }

            $normalizedQuestion = $this->parser->normalizeQuestionRowForEdit([
                'question_type' => (string) ($row['question_type'] ?? ''),
                'options_json' => (string) ($row['options_json'] ?? ''),
            ]);

            $studentName = trim((string) (($row['full_name'] ?? '') ?: ($row['username'] ?? '')));
            $reviewTitle = trim((string) ($row['review_title'] ?? ''));

            if ($reviewTitle === '') {
                $reviewTitle = 'Ôn tập kiến thức';
            }

            $sessionTitle = trim((string) ($row['session_title'] ?? ''));

            if ($sessionTitle === '') {
                $order = (int) ($row['session_sort_order'] ?? 0);
                $sessionTitle = $order > 0 ? 'Buổi '.$order : '';
            }

            $assignmentLabel = $sessionTitle !== ''
                ? $sessionTitle.' · '.$reviewTitle
                : $reviewTitle;

            $items[] = [
                'key' => 'k-'.$answerId,
                'source' => 'knowledge',
                'answer_id' => $answerId,
                'course_id' => (int) ($row['course_id'] ?? 0),
                'course_title' => (string) ($row['course_title'] ?? ''),
                'session_id' => (int) ($row['session_id'] ?? 0),
                'session_title' => $sessionTitle,
                'review_id' => (int) ($row['course_knowledge_review_id'] ?? 0),
                'review_title' => $reviewTitle,
                'user_id' => (int) ($row['user_id'] ?? 0),
                'student_name' => $studentName !== '' ? $studentName : 'Học viên',
                'student_avatar' => $this->studentAvatarUrl($row['avatar'] ?? null),
                'question_id' => (int) ($row['question_id'] ?? 0),
                'question_type' => (string) ($normalizedQuestion['question_type'] ?? ''),
                'options_json' => (string) ($row['options_json'] ?? ''),
                'question_text' => $this->buildQuestionPreviewText($normalizedQuestion, (string) ($row['question_text'] ?? '')),
                'question_html' => (string) ($row['question_text'] ?? ''),
                'correct_answer' => (string) ($row['correct_answer'] ?? ''),
                'selected_answer' => (string) ($row['selected_answer'] ?? ''),
                'answer_row' => [
                    'is_correct' => (int) ($row['is_correct'] ?? 0),
                    'teacher_comment' => (string) ($row['teacher_comment'] ?? ''),
                    'teacher_graded_at' => $this->gradedAtString($row['teacher_graded_at'] ?? ''),
                ],
                'submitted_at' => (string) ($row['submitted_at'] ?? ''),
                'tab' => self::TAB_KNOWLEDGE_REVIEW,
                'tab_label' => 'Ôn tập kiến thức bài',
                'assignment_label' => $assignmentLabel,
            ];
        }

        return $items;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function findAnswerRow(array $item): CourseSessionQuizAnswer|CourseKnowledgeReviewAnswer|null
    {
        $source = (string) ($item['source'] ?? '');
        $userId = (int) ($item['user_id'] ?? 0);
        $questionId = (int) ($item['question_id'] ?? 0);
        $answerId = (int) ($item['answer_id'] ?? 0);

        if ($source === 'knowledge') {
            return CourseKnowledgeReviewAnswer::findForTeacherReview(
                (int) ($item['review_id'] ?? 0),
                $userId,
                $questionId,
                $answerId
            );
        }

        return CourseSessionQuizAnswer::findForTeacherReview(
            (int) ($item['session_id'] ?? 0),
            $userId,
            $questionId,
            $answerId
        );
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    private function itemsForCourse(array $items, int $courseId): array
    {
        if ($courseId <= 0) {
            return $items;
        }

        return array_values(array_filter(
            $items,
            static fn (array $item): bool => (int) ($item['course_id'] ?? 0) === $courseId
        ));
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function isTeacherGradedQuestion(array $row): bool
    {
        $question = $this->parser->normalizeQuestionRowForEdit([
            'question_type' => (string) ($row['question_type'] ?? ''),
            'options_json' => (string) ($row['options_json'] ?? ''),
            'correct_answer' => (string) ($row['correct_answer'] ?? ''),
        ]);

        if ($this->parser->requiresTeacherGrading($question)) {
            return true;
        }

        if (strtolower(trim((string) ($row['question_type'] ?? ''))) === 'phat_am') {
            return $this->parser->hasPhatAmRecordingInSubmission((string) ($row['selected_answer'] ?? ''));
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $question
     */
    private function buildQuestionPreviewText(array $question, string $questionHtml): string
    {
        $plain = HtmlContent::plain($questionHtml);

        if ($plain !== '') {
            return $plain;
        }

        $type = strtolower(trim((string) ($question['question_type'] ?? '')));
        $type = $type === 'text' || $type === 'essay' ? 'fill' : $type;

        if ($type === 'listening') {
            foreach ($this->parser->decodeListeningQuestions($question) as $subQuestion) {
                $subText = trim(strip_tags((string) ($subQuestion['choice_question'] ?? '')));

                if ($subText !== '') {
                    return $subText;
                }

                $legacyText = trim(strip_tags((string) ($this->parser->decodeListeningConfig($question)['text'] ?? '')));

                if ($legacyText !== '') {
                    return mb_strlen($legacyText) > 120 ? mb_substr($legacyText, 0, 117).'...' : $legacyText;
                }
            }
        }

        if ($type === 'phat_am') {
            $prompt = trim(strip_tags((string) ($this->parser->decodePhatAmBundle($question)['prompt'] ?? '')));

            if ($prompt !== '') {
                return mb_strlen($prompt) > 120 ? mb_substr($prompt, 0, 117).'...' : $prompt;
            }
        }

        return match ($type) {
            'listening' => 'Nghe đáp',
            'fill' => 'Tự luận',
            'word_translate' => 'Dịch từ',
            'phat_am' => 'Phát âm',
            default => 'Câu hỏi',
        };
    }

    private function studentAvatarUrl(mixed $avatar): ?string
    {
        $path = trim(str_replace('\\', '/', (string) $avatar));

        if ($path === '') {
            return null;
        }

        $url = MediaUrl::to($path);

        return $url !== '' ? $url : null;
    }

    private function gradedAtString(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        return trim((string) $value);
    }

    private function tablesReady(): bool
    {
        return Schema::hasTable('courses')
            && Schema::hasTable('course_session_quiz_answers')
            && Schema::hasTable('course_session_questions');
    }
}
