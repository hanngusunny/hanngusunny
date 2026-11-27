<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseAssessment;
use App\Models\CourseAssessmentRetake;
use App\Models\CourseAssessmentSubmission;
use App\Models\CourseKnowledgeReviewAnswer;
use App\Models\CourseMember;
use App\Models\CourseSession;
use App\Models\CourseSessionQuizAnswer;
use App\Support\HtmlContent;
use App\Support\MediaUrl;
use App\Support\Questions\QuestionParser;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TeacherGradingService
{
    public const TAB_ALL = 'tat-ca';

    public const TAB_PRE = 'truoc-buoi';

    public const TAB_POST = 'sau-buoi';

    public const TAB_KNOWLEDGE_REVIEW = 'on-tap-kien-thuc';

    public const TAB_ASSESSMENT = 'bai-thi';

    public const PER_PAGE = 8;

    /** @var list<string> */
    private const AVATAR_TONES = ['orange', 'blue', 'green', 'purple', 'pink'];

    private readonly AssessmentSubmissionService $assessmentScoring;

    private readonly AssessmentDraftService $assessmentDrafts;

    public function __construct(
        private readonly QuestionParser $parser,
        ?AssessmentSubmissionService $assessmentScoring = null,
        ?AssessmentDraftService $assessmentDrafts = null,
    ) {
        $this->assessmentScoring = $assessmentScoring ?? app(AssessmentSubmissionService::class);
        $this->assessmentDrafts = $assessmentDrafts ?? app(AssessmentDraftService::class);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function build(
        int $teacherId,
        int $courseId,
        string $activeTab = self::TAB_ALL,
        string $selectedKey = '',
        string $search = '',
        string $statusFilter = 'all',
        string $sort = 'priority',
        int $page = 1,
        bool $forceEdit = false,
        int $focusQuestionId = 0,
    ): ?array {
        if ($teacherId <= 0 || ! $this->tablesReady()) {
            return null;
        }

        $courses = Course::activeForTeacher($teacherId);

        if ($courses->isEmpty()) {
            return null;
        }

        $courses->load([
            'program.levels' => static fn ($q) => $q->orderBy('sort_order')->orderBy('id'),
        ]);
        $courses->loadCount([
            'activeMembers as member_count',
            'sessions as session_count',
        ]);

        // Nếu mở bằng link cũ dạng ?bai=q-123 hoặc ?bai=k-456, tự xác định khóa & bài nộp chứa câu hỏi đó
        $resolvedLegacy = $this->resolveLegacyAnswerKey($teacherId, $selectedKey);
        if ($resolvedLegacy !== null) {
            if ($courseId <= 0 && $resolvedLegacy['course_id'] > 0 && $courses->contains('id', $resolvedLegacy['course_id'])) {
                $courseId = $resolvedLegacy['course_id'];
            }
            $selectedKey = $resolvedLegacy['submission_key'];
            if ($focusQuestionId <= 0) {
                $focusQuestionId = $resolvedLegacy['question_id'];
            }
        }

        if ($courseId <= 0 || ! $courses->contains('id', $courseId)) {
            $courseId = (int) $courses->first()?->id;
        }

        $course = $courses->firstWhere('id', $courseId);

        if ($course === null) {
            return null;
        }

        $activeTab = $this->normalizeTab($activeTab);
        $statusFilter = $this->normalizeStatusFilter($statusFilter);
        $sort = $this->normalizeSort($sort);
        $search = trim($search);
        $page = max(1, $page);

        $courseIds = $courses->pluck('id')->map(static fn ($id): int => (int) $id)->all();
        $courseLevelsById = $this->resolveCourseLevels($courses);
        $selectedCourseLevel = $courseLevelsById[$courseId] ?? '';

        $filterQuery = array_filter([
            'q' => $search !== '' ? $search : null,
            'trang_thai' => $statusFilter !== 'all' ? $statusFilter : null,
            'sap_xep' => $sort !== 'priority' ? $sort : null,
        ], static fn ($v) => $v !== null && $v !== '');

        // Nếu đang mở chi tiết một bài nộp cụ thể, tải ngay chi tiết bài nộp đó
        $submissionDetail = null;
        if ($selectedKey !== '') {
            $submissionDetail = $this->buildSubmissionDetail(
                $teacherId,
                $course,
                $selectedCourseLevel,
                $selectedKey,
                $activeTab,
                $filterQuery,
                $forceEdit,
                $focusQuestionId
            );
        }

        // Tải danh sách bài nộp gom theo (Học viên × Bài làm) cho khóa đang chọn
        $coursePre = $this->getSessionSubmissions($teacherId, $courseId, 'pre', $selectedCourseLevel);
        $coursePost = $this->getSessionSubmissions($teacherId, $courseId, 'post_practice', $selectedCourseLevel);
        $courseKr = $this->getKnowledgeReviewSubmissions($teacherId, $courseId, $selectedCourseLevel);
        $courseAssessments = $this->getAssessmentSubmissions($course, $selectedCourseLevel);

        $allCourseItems = array_merge($coursePre, $coursePost, $courseKr, $courseAssessments);

        $pendingCountsByCourse = $this->countPendingByCourse($courseIds, $courseId, [
            'pre' => $this->countStatus($coursePre, 'pending'),
            'post' => $this->countStatus($coursePost, 'pending'),
            'kr' => $this->countStatus($courseKr, 'pending'),
            'assessment' => $this->countStatus($courseAssessments, 'pending'),
        ]);

        $courseSummaries = [];
        foreach ($courses as $row) {
            $id = (int) $row->id;
            $counts = $pendingCountsByCourse[$id] ?? ['pre' => 0, 'post' => 0, 'kr' => 0, 'assessment' => 0];
            $courseSummaries[] = [
                'course' => $row,
                'level_title' => $courseLevelsById[$id] ?? '',
                'member_count' => (int) ($row->member_count ?? 0),
                'session_count' => (int) ($row->session_count ?? 0),
                'pending_pre' => (int) ($counts['pre'] ?? 0),
                'pending_post' => (int) ($counts['post'] ?? 0),
                'pending_kr' => (int) ($counts['kr'] ?? 0),
                'pending_assessment' => (int) ($counts['assessment'] ?? 0),
                'pending_total' => (int) ($counts['pre'] ?? 0)
                    + (int) ($counts['post'] ?? 0)
                    + (int) ($counts['kr'] ?? 0)
                    + (int) ($counts['assessment'] ?? 0),
            ];
        }

        $tabItems = match ($activeTab) {
            self::TAB_PRE => $coursePre,
            self::TAB_POST => $coursePost,
            self::TAB_KNOWLEDGE_REVIEW => $courseKr,
            self::TAB_ASSESSMENT => $courseAssessments,
            default => $allCourseItems,
        };

        $filteredItems = $this->filterItems($tabItems, $search, $statusFilter);
        $filteredItems = $this->sortItems($filteredItems, $sort);

        foreach ($filteredItems as &$item) {
            $item['detail_url'] = $this->pageUrl(
                $courseId,
                $activeTab,
                (string) ($item['key'] ?? ''),
                $filterQuery
            );
        }
        unset($item);

        $totalFiltered = count($filteredItems);
        $lastPage = max(1, (int) ceil($totalFiltered / self::PER_PAGE));
        $currentPage = min($page, $lastPage);
        $offset = ($currentPage - 1) * self::PER_PAGE;
        $pageItems = array_slice($filteredItems, $offset, self::PER_PAGE);

        $pagination = [
            'total' => $totalFiltered,
            'per_page' => self::PER_PAGE,
            'current_page' => $currentPage,
            'last_page' => $lastPage,
            'from' => $totalFiltered > 0 ? $offset + 1 : 0,
            'to' => $totalFiltered > 0 ? min($totalFiltered, $offset + count($pageItems)) : 0,
            'pages' => $this->buildPaginationWindow($currentPage, $lastPage),
        ];

        $pendingPreCount = $this->countStatus($coursePre, 'pending');
        $pendingPostCount = $this->countStatus($coursePost, 'pending');
        $pendingKrCount = $this->countStatus($courseKr, 'pending');
        $pendingAssCount = $this->countStatus($courseAssessments, 'pending');

        $tabCounts = [
            self::TAB_ALL => $pendingPreCount + $pendingPostCount + $pendingKrCount + $pendingAssCount,
            self::TAB_PRE => $pendingPreCount,
            self::TAB_POST => $pendingPostCount,
            self::TAB_KNOWLEDGE_REVIEW => $pendingKrCount,
            self::TAB_ASSESSMENT => $pendingAssCount,
        ];

        return [
            'courses' => $courses,
            'courseSummaries' => $courseSummaries,
            'course' => $course,
            'courseId' => $courseId,
            'courseLevelTitle' => $selectedCourseLevel,
            'activeTab' => $activeTab,
            'selectedKey' => $submissionDetail !== null ? $selectedKey : '',
            'selectedItem' => null,
            'submissionDetail' => $submissionDetail,
            'preItems' => $coursePre,
            'postItems' => $coursePost,
            'krItems' => $courseKr,
            'assessmentItems' => $courseAssessments,
            'tabItems' => $tabItems,
            'filteredItems' => $filteredItems,
            'pageItems' => $pageItems,
            'pagination' => $pagination,
            'tabCounts' => $tabCounts,
            'pendingTotal' => $tabCounts[self::TAB_ALL],
            'search' => $search,
            'statusFilter' => $statusFilter,
            'sort' => $sort,
            'filterQuery' => $filterQuery,
        ];
    }

    /**
     * Tìm trực tiếp 1 bản ghi câu trả lời đơn lẻ (q-{answerId} hoặc k-{answerId}) để lưu chấm bài nhanh.
     *
     * @return array<string, mixed>|null
     */
    public function findSingleAnswerItem(int $teacherId, int $courseId, string $answerKey): ?array
    {
        if ($teacherId <= 0 || $courseId <= 0 || $answerKey === '') {
            return null;
        }

        if (preg_match('/^q-(\d+)$/', $answerKey, $m) === 1) {
            $answerId = (int) $m[1];
            $row = DB::table('course_session_quiz_answers as a')
                ->join('course_session_questions as q', 'q.id', '=', 'a.question_id')
                ->join('course_sessions as s', 's.id', '=', 'a.course_session_id')
                ->join('courses as c', 'c.id', '=', 's.course_id')
                ->where('a.id', $answerId)
                ->where('c.id', $courseId)
                ->where('c.teacher_id', $teacherId)
                ->where('c.status', Course::STATUS_ACTIVE)
                ->select([
                    'a.id as answer_id',
                    'a.course_session_id as session_id',
                    'a.user_id',
                    'a.question_id',
                    'a.selected_answer',
                    'a.is_correct',
                    'a.teacher_comment',
                    'a.teacher_graded_at',
                    'q.question_text',
                    'q.question_type',
                    'q.options_json',
                    'q.correct_answer',
                    'q.question_group',
                    'c.id as course_id',
                ])
                ->first();

            if ($row === null) {
                return null;
            }

            $normalized = $this->parser->normalizeQuestionRowForEdit([
                'question_type' => (string) ($row->question_type ?? ''),
                'options_json' => (string) ($row->options_json ?? ''),
                'correct_answer' => (string) ($row->correct_answer ?? ''),
            ]);
            $group = (string) ($row->question_group ?? 'pre');

            return [
                'key' => 'q-'.$answerId,
                'submission_key' => ($group === 'post_practice' ? 'post-' : 'pre-').(int) $row->session_id.'-'.(int) $row->user_id,
                'source' => 'session',
                'answer_id' => $answerId,
                'course_id' => (int) $row->course_id,
                'session_id' => (int) $row->session_id,
                'user_id' => (int) $row->user_id,
                'question_id' => (int) $row->question_id,
                'question_type' => (string) ($normalized['question_type'] ?? ''),
                'options_json' => (string) ($row->options_json ?? ''),
                'question_html' => (string) ($row->question_text ?? ''),
                'question_text' => HtmlContent::plain((string) ($row->question_text ?? '')),
                'correct_answer' => (string) ($row->correct_answer ?? ''),
                'selected_answer' => (string) ($row->selected_answer ?? ''),
                'answer_row' => [
                    'is_correct' => (int) ($row->is_correct ?? 0),
                    'teacher_comment' => (string) ($row->teacher_comment ?? ''),
                    'teacher_graded_at' => $this->gradedAtString($row->teacher_graded_at ?? ''),
                ],
            ];
        }

        if (preg_match('/^k-(\d+)$/', $answerKey, $m) === 1 && Schema::hasTable('course_knowledge_review_answers')) {
            $answerId = (int) $m[1];
            $row = DB::table('course_knowledge_review_answers as a')
                ->join('course_knowledge_review_questions as q', 'q.id', '=', 'a.question_id')
                ->join('course_knowledge_reviews as kr', 'kr.id', '=', 'a.course_knowledge_review_id')
                ->join('courses as c', 'c.id', '=', 'kr.course_id')
                ->where('a.id', $answerId)
                ->where('c.id', $courseId)
                ->where('c.teacher_id', $teacherId)
                ->where('c.status', Course::STATUS_ACTIVE)
                ->select([
                    'a.id as answer_id',
                    'a.course_knowledge_review_id as review_id',
                    'a.user_id',
                    'a.question_id',
                    'a.selected_answer',
                    'a.is_correct',
                    'a.teacher_comment',
                    'a.teacher_graded_at',
                    'q.question_text',
                    'q.question_type',
                    'q.options_json',
                    'q.correct_answer',
                    'c.id as course_id',
                ])
                ->first();

            if ($row === null) {
                return null;
            }

            $normalized = $this->parser->normalizeQuestionRowForEdit([
                'question_type' => (string) ($row->question_type ?? ''),
                'options_json' => (string) ($row->options_json ?? ''),
                'correct_answer' => (string) ($row->correct_answer ?? ''),
            ]);

            return [
                'key' => 'k-'.$answerId,
                'submission_key' => 'kr-'.(int) $row->review_id.'-'.(int) $row->user_id,
                'source' => 'knowledge',
                'answer_id' => $answerId,
                'course_id' => (int) $row->course_id,
                'review_id' => (int) $row->review_id,
                'user_id' => (int) $row->user_id,
                'question_id' => (int) $row->question_id,
                'question_type' => (string) ($normalized['question_type'] ?? ''),
                'options_json' => (string) ($row->options_json ?? ''),
                'question_html' => (string) ($row->question_text ?? ''),
                'question_text' => HtmlContent::plain((string) ($row->question_text ?? '')),
                'correct_answer' => (string) ($row->correct_answer ?? ''),
                'selected_answer' => (string) ($row->selected_answer ?? ''),
                'answer_row' => [
                    'is_correct' => (int) ($row->is_correct ?? 0),
                    'teacher_comment' => (string) ($row->teacher_comment ?? ''),
                    'teacher_graded_at' => $this->gradedAtString($row->teacher_graded_at ?? ''),
                ],
            ];
        }

        return null;
    }

    /**
     * Xây dựng payload chi tiết 1 bài nộp thống nhất (tách rõ 2 nhóm Chấm tay & Tự động).
     *
     * @param  array<string, mixed>  $filterQuery
     * @return array<string, mixed>|null
     */
    public function buildSubmissionDetail(
        int $teacherId,
        Course $course,
        string $courseLevelTitle,
        string $submissionKey,
        string $activeTab,
        array $filterQuery = [],
        bool $forceEdit = false,
        int $focusQuestionId = 0,
    ): ?array {
        $courseId = (int) $course->id;

        if (preg_match('/^(pre|post)-(\d+)-(\d+)$/', $submissionKey, $m) === 1) {
            return $this->buildSessionSubmissionDetail(
                $course,
                $courseLevelTitle,
                $m[1] === 'post' ? 'post_practice' : 'pre',
                (int) $m[2],
                (int) $m[3],
                $activeTab,
                $filterQuery,
                $forceEdit,
                $focusQuestionId
            );
        }

        if (preg_match('/^kr-(\d+)-(\d+)$/', $submissionKey, $m) === 1) {
            return $this->buildKnowledgeSubmissionDetail(
                $course,
                $courseLevelTitle,
                (int) $m[1],
                (int) $m[2],
                $activeTab,
                $filterQuery,
                $forceEdit,
                $focusQuestionId
            );
        }

        if (preg_match('/^(?:ass|a)-(\d+)-(\d+)$/', $submissionKey, $m) === 1) {
            return $this->buildAssessmentSubmissionDetail(
                $course,
                $courseLevelTitle,
                (int) $m[1],
                (int) $m[2],
                $activeTab,
                $filterQuery,
                $forceEdit,
                $focusQuestionId
            );
        }

        return null;
    }

    public function normalizeTab(string $tab): string
    {
        return match ($tab) {
            self::TAB_ALL,
            self::TAB_PRE,
            self::TAB_POST,
            self::TAB_KNOWLEDGE_REVIEW,
            self::TAB_ASSESSMENT => $tab,
            default => self::TAB_ALL,
        };
    }

    public function normalizeStatusFilter(string $status): string
    {
        return match ($status) {
            'pending', 'draft', 'graded' => $status,
            default => 'all',
        };
    }

    public function normalizeSort(string $sort): string
    {
        return match ($sort) {
            'newest', 'oldest', 'name' => $sort,
            default => 'priority',
        };
    }

    public function tabLabel(string $tab): string
    {
        return match ($this->normalizeTab($tab)) {
            self::TAB_PRE => 'Trước buổi',
            self::TAB_POST => 'Sau buổi',
            self::TAB_KNOWLEDGE_REVIEW => 'Bài kiểm tra',
            self::TAB_ASSESSMENT => 'Bài thi',
            default => 'Tất cả',
        };
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    public function pageUrl(int $courseId, string $tab, string $selectedKey = '', array $extra = []): string
    {
        $normalizedTab = $this->normalizeTab($tab);
        $params = array_merge([
            'khoa' => $courseId > 0 ? $courseId : null,
            'muc' => $normalizedTab !== self::TAB_ALL ? $normalizedTab : null,
            'bai' => $selectedKey !== '' ? $selectedKey : null,
        ], $extra);

        if (isset($params['page']) && (int) $params['page'] <= 1) {
            unset($params['page']);
        }
        if (($params['trang_thai'] ?? '') === 'all') {
            unset($params['trang_thai']);
        }
        if (($params['sap_xep'] ?? '') === 'priority') {
            unset($params['sap_xep']);
        }

        return route('teacher.grading', array_filter(
            $params,
            static fn ($value) => $value !== null && $value !== ''
        ));
    }

    public function formatScoreVi(float $value): string
    {
        $rounded = round($value, 2);
        if (abs($rounded - round($rounded)) < 0.001) {
            return (string) ((int) round($rounded));
        }

        $formatted = rtrim(rtrim(number_format($rounded, 2, ',', ''), '0'), ',');

        return $formatted !== '' ? $formatted : '0';
    }

    public function questionTypeLabel(string $type): string
    {
        return match ($type) {
            'choice' => 'Một đáp án',
            'multi_select' => 'Nhiều đáp án',
            'fill_blanks' => 'Điền chỗ trống',
            'word_select' => 'Chọn từ',
            'word_translate' => 'Dịch từ',
            'word_link' => 'Nối từ',
            'memory_match' => 'Lật thẻ ghép từ – ảnh',
            'word_order' => 'Sắp xếp câu',
            'category_sort' => 'Phân loại',
            'image_hotspot' => 'Click ảnh',
            'image_label_drop' => 'Kéo nhãn ảnh',
            'fill', 'essay', 'text' => 'Tự luận',
            'phat_am' => 'Đọc và ghi âm',
            'listening' => 'Nghe đáp',
            default => 'Câu hỏi',
        };
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
     * @param  list<array<string, mixed>>  $items
     */
    public function findNextPendingKey(array $items): string
    {
        foreach ($items as $item) {
            if (($item['status'] ?? '') === 'pending') {
                return (string) ($item['key'] ?? '');
            }
        }

        return '';
    }

    /* ======================================================================
       Private: Grouped Submissions Builders (1 row = 1 student × 1 assignment)
       ====================================================================== */

    /**
     * @return array{course_id: int, submission_key: string, question_id: int}|null
     */
    private function resolveLegacyAnswerKey(int $teacherId, string $key): ?array
    {
        if ($key === '') {
            return null;
        }

        if (preg_match('/^q-(\d+)$/', $key, $m) === 1) {
            $row = DB::table('course_session_quiz_answers as a')
                ->join('course_session_questions as q', 'q.id', '=', 'a.question_id')
                ->join('course_sessions as s', 's.id', '=', 'a.course_session_id')
                ->join('courses as c', 'c.id', '=', 's.course_id')
                ->where('a.id', (int) $m[1])
                ->where('c.teacher_id', $teacherId)
                ->select(['s.course_id', 'a.course_session_id', 'a.user_id', 'a.question_id', 'q.question_group'])
                ->first();

            if ($row !== null) {
                $prefix = (string) ($row->question_group ?? '') === 'post_practice' ? 'post' : 'pre';

                return [
                    'course_id' => (int) $row->course_id,
                    'submission_key' => $prefix.'-'.(int) $row->course_session_id.'-'.(int) $row->user_id,
                    'question_id' => (int) $row->question_id,
                ];
            }
        }

        if (preg_match('/^k-(\d+)$/', $key, $m) === 1 && Schema::hasTable('course_knowledge_review_answers')) {
            $row = DB::table('course_knowledge_review_answers as a')
                ->join('course_knowledge_reviews as kr', 'kr.id', '=', 'a.course_knowledge_review_id')
                ->join('courses as c', 'c.id', '=', 'kr.course_id')
                ->where('a.id', (int) $m[1])
                ->where('c.teacher_id', $teacherId)
                ->select(['kr.course_id', 'a.course_knowledge_review_id', 'a.user_id', 'a.question_id'])
                ->first();

            if ($row !== null) {
                return [
                    'course_id' => (int) $row->course_id,
                    'submission_key' => 'kr-'.(int) $row->course_knowledge_review_id.'-'.(int) $row->user_id,
                    'question_id' => (int) $row->question_id,
                ];
            }
        }

        return null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getSessionSubmissions(
        int $teacherId,
        int $courseId,
        string $questionGroup,
        string $courseLevelTitle
    ): array {
        if ($teacherId <= 0 || $courseId <= 0 || ! Schema::hasTable('course_session_quiz_answers')) {
            return [];
        }

        // Đếm tổng số câu hỏi của từng buổi trong khóa (dùng tính thang điểm 100)
        $questionCountsBySession = DB::table('course_session_questions as q')
            ->join('course_sessions as s', 's.id', '=', 'q.course_session_id')
            ->where('s.course_id', $courseId)
            ->where('q.question_group', $questionGroup)
            ->groupBy('q.course_session_id')
            ->selectRaw('q.course_session_id, COUNT(*) as total')
            ->pluck('total', 'course_session_id')
            ->map(static fn ($v): int => (int) $v)
            ->all();

        $rows = DB::table('course_session_quiz_answers as a')
            ->join('course_session_questions as q', 'q.id', '=', 'a.question_id')
            ->join('course_sessions as s', 's.id', '=', 'a.course_session_id')
            ->join('courses as c', 'c.id', '=', 's.course_id')
            ->join('users as u', 'u.id', '=', 'a.user_id')
            ->where('c.id', $courseId)
            ->where('c.teacher_id', $teacherId)
            ->where('c.status', Course::STATUS_ACTIVE)
            ->where('q.question_group', $questionGroup)
            ->whereNotNull('a.selected_answer')
            ->whereRaw('TRIM(a.selected_answer) != ?', [''])
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
                'q.question_type',
                'q.options_json',
                'q.correct_answer',
                's.title as session_title',
                's.sort_order as session_sort_order',
                'c.title as course_title',
                'u.full_name',
                'u.username',
                'u.student_code',
                'u.avatar',
            ])
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $grouped = [];
        foreach ($rows as $row) {
            $sid = (int) $row->course_session_id;
            $uid = (int) $row->user_id;
            $gKey = $sid.'-'.$uid;
            if (! isset($grouped[$gKey])) {
                $grouped[$gKey] = [
                    'session_id' => $sid,
                    'user_id' => $uid,
                    'session_title' => trim((string) ($row->session_title ?? '')),
                    'session_sort_order' => (int) ($row->session_sort_order ?? 0),
                    'course_title' => (string) ($row->course_title ?? ''),
                    'full_name' => (string) ($row->full_name ?? ''),
                    'username' => (string) ($row->username ?? ''),
                    'student_code' => (string) ($row->student_code ?? ''),
                    'avatar' => $row->avatar,
                    'latest_submitted_at' => '',
                    'latest_graded_at' => '',
                    'answers' => [],
                ];
            }
            $subAt = (string) ($row->submitted_at ?? '');
            if ($subAt !== '' && strcmp($subAt, $grouped[$gKey]['latest_submitted_at']) > 0) {
                $grouped[$gKey]['latest_submitted_at'] = $subAt;
            }
            $grdAt = $this->gradedAtString($row->teacher_graded_at ?? '');
            if ($grdAt !== '' && strcmp($grdAt, $grouped[$gKey]['latest_graded_at']) > 0) {
                $grouped[$gKey]['latest_graded_at'] = $grdAt;
            }
            $grouped[$gKey]['answers'][] = (array) $row;
        }

        $prefix = $questionGroup === 'post_practice' ? 'post' : 'pre';
        $tabKey = $questionGroup === 'post_practice' ? self::TAB_POST : self::TAB_PRE;
        $contentLabel = $questionGroup === 'post_practice' ? 'Sau buổi' : 'Trước buổi';

        $items = [];
        foreach ($grouped as $g) {
            $sid = $g['session_id'];
            $uid = $g['user_id'];
            $totalQ = max(count($g['answers']), $questionCountsBySession[$sid] ?? 0, 1);
            $weight = 100.0 / $totalQ;

            $manualCount = 0;
            $pendingManual = 0;
            $earnedTotal = 0.0;

            foreach ($g['answers'] as $ans) {
                $qNorm = $this->parser->normalizeQuestionRowForEdit([
                    'question_type' => (string) ($ans['question_type'] ?? ''),
                    'options_json' => (string) ($ans['options_json'] ?? ''),
                    'correct_answer' => (string) ($ans['correct_answer'] ?? ''),
                ]);
                $selected = (string) ($ans['selected_answer'] ?? '');
                $isManual = $this->isManualQuestion($qNorm, $selected);
                $gradedAt = $this->gradedAtString($ans['teacher_graded_at'] ?? '');

                if ($isManual) {
                    $manualCount++;
                    if ($gradedAt === '') {
                        $pendingManual++;
                        continue;
                    }
                }

                $earnedTotal += $this->computeSessionQuestionEarned($qNorm, $ans, $weight);
            }

            $earnedTotal = round(min(100.0, max(0.0, $earnedTotal)), 2);
            $status = $pendingManual > 0 ? 'pending' : 'graded';
            $sessionTitle = $g['session_title'] !== ''
                ? $g['session_title']
                : ($g['session_sort_order'] > 0 ? 'Buổi '.$g['session_sort_order'] : 'Buổi học');

            $studentName = trim($g['full_name'] !== '' ? $g['full_name'] : $g['username']);
            $studentName = $studentName !== '' ? $studentName : 'Học viên';
            $subtitleParts = array_filter([
                $g['course_title'],
                $courseLevelTitle,
                $this->compactSessionLabel($sessionTitle),
            ]);

            $items[] = [
                'key' => $prefix.'-'.$sid.'-'.$uid,
                'source' => 'session',
                'course_id' => $courseId,
                'course_title' => $g['course_title'],
                'level_title' => $courseLevelTitle,
                'session_id' => $sid,
                'user_id' => $uid,
                'student_name' => $studentName,
                'student_code' => $g['student_code'] !== '' ? $g['student_code'] : $g['username'],
                'student_avatar' => $this->studentAvatarUrl($g['avatar']),
                'student_initials' => $this->buildInitials($studentName),
                'avatar_tone' => self::AVATAR_TONES[$uid % count(self::AVATAR_TONES)],
                'question_text' => $sessionTitle,
                'assignment_label' => $sessionTitle,
                'subtitle' => implode(' · ', $subtitleParts),
                'submitted_at' => $g['latest_submitted_at'],
                'graded_at' => $g['latest_graded_at'],
                'tab' => $tabKey,
                'tab_label' => $contentLabel,
                'content_type_label' => $contentLabel,
                'status' => $status,
                'status_label' => $this->statusLabel($status),
                'pending_manual' => $pendingManual,
                'manual_count' => $manualCount,
                'total_questions' => $totalQ,
                'score_display' => $status === 'graded' ? $this->formatScoreVi($earnedTotal).' / 100' : '—',
                'detail_url' => '',
            ];
        }

        return $items;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getKnowledgeReviewSubmissions(
        int $teacherId,
        int $courseId,
        string $courseLevelTitle
    ): array {
        if (
            $teacherId <= 0
            || $courseId <= 0
            || ! Schema::hasTable('course_knowledge_review_answers')
            || ! Schema::hasColumn('course_knowledge_review_answers', 'teacher_graded_at')
        ) {
            return [];
        }

        $questionCountsByReview = DB::table('course_knowledge_review_questions as q')
            ->join('course_knowledge_reviews as kr', 'kr.id', '=', 'q.course_knowledge_review_id')
            ->where('kr.course_id', $courseId)
            ->groupBy('q.course_knowledge_review_id')
            ->selectRaw('q.course_knowledge_review_id, COUNT(*) as total')
            ->pluck('total', 'course_knowledge_review_id')
            ->map(static fn ($v): int => (int) $v)
            ->all();

        $rows = DB::table('course_knowledge_review_answers as a')
            ->join('course_knowledge_review_questions as q', 'q.id', '=', 'a.question_id')
            ->join('course_knowledge_reviews as kr', 'kr.id', '=', 'a.course_knowledge_review_id')
            ->join('courses as c', 'c.id', '=', 'kr.course_id')
            ->join('users as u', 'u.id', '=', 'a.user_id')
            ->where('c.id', $courseId)
            ->where('c.teacher_id', $teacherId)
            ->where('c.status', Course::STATUS_ACTIVE)
            ->whereNotNull('a.selected_answer')
            ->whereRaw('TRIM(a.selected_answer) != ?', [''])
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
                'q.question_type',
                'q.options_json',
                'q.correct_answer',
                'kr.title as review_title',
                'c.title as course_title',
                'u.full_name',
                'u.username',
                'u.student_code',
                'u.avatar',
            ])
            ->get();

        if ($rows->isEmpty()) {
            return [];
        }

        $grouped = [];
        foreach ($rows as $row) {
            $krId = (int) $row->course_knowledge_review_id;
            $uid = (int) $row->user_id;
            $gKey = $krId.'-'.$uid;
            if (! isset($grouped[$gKey])) {
                $grouped[$gKey] = [
                    'review_id' => $krId,
                    'user_id' => $uid,
                    'review_title' => trim((string) ($row->review_title ?? '')) ?: 'Ôn tập kiến thức',
                    'course_title' => (string) ($row->course_title ?? ''),
                    'full_name' => (string) ($row->full_name ?? ''),
                    'username' => (string) ($row->username ?? ''),
                    'student_code' => (string) ($row->student_code ?? ''),
                    'avatar' => $row->avatar,
                    'latest_submitted_at' => '',
                    'latest_graded_at' => '',
                    'answers' => [],
                ];
            }
            $subAt = (string) ($row->submitted_at ?? '');
            if ($subAt !== '' && strcmp($subAt, $grouped[$gKey]['latest_submitted_at']) > 0) {
                $grouped[$gKey]['latest_submitted_at'] = $subAt;
            }
            $grdAt = $this->gradedAtString($row->teacher_graded_at ?? '');
            if ($grdAt !== '' && strcmp($grdAt, $grouped[$gKey]['latest_graded_at']) > 0) {
                $grouped[$gKey]['latest_graded_at'] = $grdAt;
            }
            $grouped[$gKey]['answers'][] = (array) $row;
        }

        $items = [];
        foreach ($grouped as $g) {
            $krId = $g['review_id'];
            $uid = $g['user_id'];
            $totalQ = max(count($g['answers']), $questionCountsByReview[$krId] ?? 0, 1);
            $weight = 100.0 / $totalQ;

            $manualCount = 0;
            $pendingManual = 0;
            $earnedTotal = 0.0;

            foreach ($g['answers'] as $ans) {
                $qNorm = $this->parser->normalizeQuestionRowForEdit([
                    'question_type' => (string) ($ans['question_type'] ?? ''),
                    'options_json' => (string) ($ans['options_json'] ?? ''),
                    'correct_answer' => (string) ($ans['correct_answer'] ?? ''),
                ]);
                $selected = (string) ($ans['selected_answer'] ?? '');
                $isManual = $this->isManualQuestion($qNorm, $selected);
                $gradedAt = $this->gradedAtString($ans['teacher_graded_at'] ?? '');

                if ($isManual) {
                    $manualCount++;
                    if ($gradedAt === '') {
                        $pendingManual++;
                        continue;
                    }
                }

                $earnedTotal += $this->computeSessionQuestionEarned($qNorm, $ans, $weight);
            }

            $earnedTotal = round(min(100.0, max(0.0, $earnedTotal)), 2);
            $status = $pendingManual > 0 ? 'pending' : 'graded';
            $studentName = trim($g['full_name'] !== '' ? $g['full_name'] : $g['username']);
            $studentName = $studentName !== '' ? $studentName : 'Học viên';
            $subtitleParts = array_filter([
                $g['course_title'],
                $courseLevelTitle,
                $g['review_title'],
            ]);

            $items[] = [
                'key' => 'kr-'.$krId.'-'.$uid,
                'source' => 'knowledge',
                'course_id' => $courseId,
                'course_title' => $g['course_title'],
                'level_title' => $courseLevelTitle,
                'review_id' => $krId,
                'user_id' => $uid,
                'student_name' => $studentName,
                'student_code' => $g['student_code'] !== '' ? $g['student_code'] : $g['username'],
                'student_avatar' => $this->studentAvatarUrl($g['avatar']),
                'student_initials' => $this->buildInitials($studentName),
                'avatar_tone' => self::AVATAR_TONES[$uid % count(self::AVATAR_TONES)],
                'question_text' => $g['review_title'],
                'assignment_label' => $g['review_title'],
                'subtitle' => implode(' · ', $subtitleParts),
                'submitted_at' => $g['latest_submitted_at'],
                'graded_at' => $g['latest_graded_at'],
                'tab' => self::TAB_KNOWLEDGE_REVIEW,
                'tab_label' => 'Bài kiểm tra',
                'content_type_label' => 'Bài kiểm tra',
                'status' => $status,
                'status_label' => $this->statusLabel($status),
                'pending_manual' => $pendingManual,
                'manual_count' => $manualCount,
                'total_questions' => $totalQ,
                'score_display' => $status === 'graded' ? $this->formatScoreVi($earnedTotal).' / 100' : '—',
                'detail_url' => '',
            ];
        }

        return $items;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getAssessmentSubmissions(Course $course, string $courseLevelTitle): array
    {
        if (! Schema::hasTable('course_assessments') || ! Schema::hasTable('course_assessment_answers')) {
            return [];
        }

        $courseId = (int) $course->id;
        $courseTitle = (string) $course->title;

        $assessments = CourseAssessment::query()
            ->where('course_id', $courseId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($assessments->isEmpty()) {
            return [];
        }

        $membersByUserId = [];
        foreach (CourseMember::activeMembersForCourse($courseId) as $member) {
            if ($member->user !== null) {
                $membersByUserId[(int) $member->user_id] = $member->user;
            }
        }

        if ($membersByUserId === []) {
            return [];
        }

        $items = [];

        foreach ($assessments as $assessment) {
            $assessmentId = (int) $assessment->id;
            $assessmentTitle = trim((string) ($assessment->title ?: 'Bài thi'));

            $submissionMap = $this->assessmentScoring->submissionSummariesForAssessment($assessmentId);
            if (Schema::hasTable('course_assessment_submissions')) {
                $submissionMap = CourseAssessmentSubmission::mergeIntoSummaries($assessmentId, $submissionMap);
            }

            $draftUserIds = Schema::hasTable('course_assessment_answer_drafts')
                ? array_fill_keys($this->assessmentDrafts->userIdsForAssessment($assessmentId), true)
                : [];
            $draftProgress = $draftUserIds !== []
                ? $this->assessmentDrafts->getProgressByUser($assessmentId)
                : [];
            $retakeUserIds = Schema::hasTable('course_assessment_retakes')
                ? array_fill_keys(
                    CourseAssessmentRetake::query()
                        ->where('course_assessment_id', $assessmentId)
                        ->pluck('user_id')
                        ->map(static fn ($id): int => (int) $id)
                        ->all(),
                    true
                )
                : [];

            foreach ($membersByUserId as $uid => $user) {
                $summary = $submissionMap[$uid] ?? [];
                $hasSubmitted = ! empty($summary['submitted']);
                $allowRetake = isset($retakeUserIds[$uid]);
                $isRetaking = $allowRetake && $hasSubmitted;
                $isDraft = ! $hasSubmitted && isset($draftUserIds[$uid]);

                if (! $hasSubmitted && ! $isDraft) {
                    continue;
                }

                $isGraded = ! $isDraft && ! $isRetaking && ! empty($summary['is_graded']);
                $status = $isDraft ? 'draft' : ($isGraded ? 'graded' : 'pending');

                $studentName = trim((string) ($user->full_name ?: $user->username ?: $user->name ?: ''));
                $studentName = $studentName !== '' ? $studentName : 'Học viên';

                $submittedAt = $isDraft
                    ? (string) ($draftProgress[$uid]['updated_at'] ?? '')
                    : (string) ($summary['submitted_at'] ?? '');

                $scoreDisplay = '—';
                if ($isGraded) {
                    $earned = (float) ($summary['points_earned'] ?? 0);
                    $possible = (float) ($summary['points_possible'] ?? 0);
                    $scoreDisplay = $possible > 0
                        ? $this->formatScoreVi($earned).' / '.$this->formatScoreVi($possible)
                        : (string) ((int) ($summary['score'] ?? 0));
                }

                $subtitleParts = array_filter([$courseTitle, $courseLevelTitle, $assessmentTitle]);

                $items[] = [
                    'key' => 'ass-'.$assessmentId.'-'.$uid,
                    'source' => 'assessment',
                    'assessment_id' => $assessmentId,
                    'course_id' => $courseId,
                    'course_title' => $courseTitle,
                    'level_title' => $courseLevelTitle,
                    'user_id' => $uid,
                    'student_name' => $studentName,
                    'student_code' => trim((string) ($user->student_code ?: $user->username)),
                    'student_avatar' => $user->avatarUrl(),
                    'student_initials' => $this->buildInitials($studentName),
                    'avatar_tone' => self::AVATAR_TONES[$uid % count(self::AVATAR_TONES)],
                    'question_text' => $assessmentTitle,
                    'assignment_label' => $assessmentTitle,
                    'subtitle' => implode(' · ', $subtitleParts),
                    'submitted_at' => $submittedAt,
                    'graded_at' => (string) ($summary['graded_at'] ?? ''),
                    'tab' => self::TAB_ASSESSMENT,
                    'tab_label' => 'Bài thi',
                    'content_type_label' => 'Bài thi',
                    'status' => $status,
                    'status_label' => $this->statusLabel($status),
                    'pending_manual' => (int) ($summary['pending_manual'] ?? 0),
                    'score_display' => $scoreDisplay,
                    'detail_url' => '',
                ];
            }
        }

        return $items;
    }

    /* ======================================================================
       Private: Submission Detail Builders (Chấm tay / Tự động / Xem chi tiết)
       ====================================================================== */

    /**
     * @param  array<string, mixed>  $filterQuery
     * @return array<string, mixed>|null
     */
    private function buildSessionSubmissionDetail(
        Course $course,
        string $courseLevelTitle,
        string $questionGroup,
        int $sessionId,
        int $userId,
        string $activeTab,
        array $filterQuery,
        bool $forceEdit,
        int $focusQuestionId
    ): ?array {
        $courseId = (int) $course->id;
        $session = DB::table('course_sessions')
            ->where('id', $sessionId)
            ->where('course_id', $courseId)
            ->first();
        $user = DB::table('users')
            ->where('id', $userId)
            ->first(['id', 'full_name', 'username', 'student_code', 'avatar']);

        if ($session === null || $user === null) {
            return null;
        }

        $rawQuestions = DB::table('course_session_questions')
            ->where('course_session_id', $sessionId)
            ->where('question_group', $questionGroup)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($rawQuestions->isEmpty()) {
            return null;
        }

        $rawAnswers = DB::table('course_session_quiz_answers')
            ->where('course_session_id', $sessionId)
            ->where('user_id', $userId)
            ->get()
            ->keyBy('question_id');

        $sessionTitle = trim((string) ($session->title ?? ''));
        if ($sessionTitle === '') {
            $order = (int) ($session->sort_order ?? 0);
            $sessionTitle = $order > 0 ? 'Buổi '.$order : 'Buổi học';
        }
        $prefix = $questionGroup === 'post_practice' ? 'post' : 'pre';
        $submissionKey = $prefix.'-'.$sessionId.'-'.$userId;
        $typeLabel = $questionGroup === 'post_practice' ? 'Sau buổi' : 'Trước buổi';
        $typeFullLabel = $questionGroup === 'post_practice' ? 'Bài tập sau buổi' : 'Bài tập trước buổi';

        return $this->assembleQuizSubmissionDetail(
            'session',
            $submissionKey,
            $course,
            $courseLevelTitle,
            $sessionTitle,
            $this->compactSessionLabel($sessionTitle),
            $typeLabel,
            $typeFullLabel,
            $user,
            $rawQuestions,
            $rawAnswers,
            'q-',
            $activeTab,
            $filterQuery,
            $forceEdit,
            $focusQuestionId
        );
    }

    /**
     * @param  array<string, mixed>  $filterQuery
     * @return array<string, mixed>|null
     */
    private function buildKnowledgeSubmissionDetail(
        Course $course,
        string $courseLevelTitle,
        int $reviewId,
        int $userId,
        string $activeTab,
        array $filterQuery,
        bool $forceEdit,
        int $focusQuestionId
    ): ?array {
        $courseId = (int) $course->id;
        $review = DB::table('course_knowledge_reviews')
            ->where('id', $reviewId)
            ->where('course_id', $courseId)
            ->first();
        $user = DB::table('users')
            ->where('id', $userId)
            ->first(['id', 'full_name', 'username', 'student_code', 'avatar']);

        if ($review === null || $user === null) {
            return null;
        }

        $rawQuestions = DB::table('course_knowledge_review_questions')
            ->where('course_knowledge_review_id', $reviewId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($rawQuestions->isEmpty()) {
            return null;
        }

        $rawAnswers = DB::table('course_knowledge_review_answers')
            ->where('course_knowledge_review_id', $reviewId)
            ->where('user_id', $userId)
            ->get()
            ->keyBy('question_id');

        $reviewTitle = trim((string) ($review->title ?? '')) ?: 'Ôn tập kiến thức';
        $submissionKey = 'kr-'.$reviewId.'-'.$userId;

        return $this->assembleQuizSubmissionDetail(
            'knowledge',
            $submissionKey,
            $course,
            $courseLevelTitle,
            $reviewTitle,
            $reviewTitle,
            'Bài kiểm tra',
            'Ôn tập kiến thức',
            $user,
            $rawQuestions,
            $rawAnswers,
            'k-',
            $activeTab,
            $filterQuery,
            $forceEdit,
            $focusQuestionId
        );
    }

    /**
     * @param  Collection<int, object>  $rawQuestions
     * @param  Collection<int, object>  $rawAnswers
     * @param  array<string, mixed>  $filterQuery
     * @return array<string, mixed>
     */
    private function assembleQuizSubmissionDetail(
        string $source,
        string $submissionKey,
        Course $course,
        string $courseLevelTitle,
        string $assignmentTitle,
        string $contextShortLabel,
        string $contentTypeLabel,
        string $contextTypeLabel,
        object $user,
        Collection $rawQuestions,
        Collection $rawAnswers,
        string $answerPrefix,
        string $activeTab,
        array $filterQuery,
        bool $forceEdit,
        int $focusQuestionId
    ): array {
        $courseId = (int) $course->id;
        $totalQ = max(1, $rawQuestions->count());
        $weight = round(100.0 / $totalQ, 2);

        $allQuestions = [];
        $manualQuestions = [];
        $autoQuestions = [];
        $pendingManualLabels = [];
        $totalEarned = 0.0;
        $latestSubmittedAt = '';
        $latestGradedAt = '';

        foreach ($rawQuestions->values() as $idx => $qObj) {
            $qRow = (array) $qObj;
            $qid = (int) ($qRow['id'] ?? 0);
            $qNorm = $this->parser->normalizeQuestionRowForEdit($qRow);
            $qType = (string) ($qNorm['question_type'] ?? 'choice');
            $qType = in_array($qType, ['text', 'essay'], true) ? 'fill' : $qType;
            $qNorm['question_type'] = $qType;

            $ansObj = $rawAnswers->get($qid);
            $ansRow = $ansObj !== null ? (array) $ansObj : [];
            $answerId = (int) ($ansRow['id'] ?? 0);
            $selected = trim((string) ($ansRow['selected_answer'] ?? ''));
            $gradedAt = $this->gradedAtString($ansRow['teacher_graded_at'] ?? '');
            $subAt = (string) ($ansRow['updated_at'] ?? '');
            if ($subAt !== '' && strcmp($subAt, $latestSubmittedAt) > 0) {
                $latestSubmittedAt = $subAt;
            }
            if ($gradedAt !== '' && strcmp($gradedAt, $latestGradedAt) > 0) {
                $latestGradedAt = $gradedAt;
            }

            $isManual = $this->isManualQuestion($qNorm, $selected);
            $hasAnswer = $answerId > 0 && $selected !== '';
            $isGraded = $isManual ? ($gradedAt !== '' || ! $hasAnswer) : true;

            $earned = null;
            if (! $isManual) {
                $earned = $hasAnswer ? $this->computeSessionQuestionEarned($qNorm, $ansRow, $weight) : 0.0;
                $totalEarned += $earned;
            } elseif ($gradedAt !== '') {
                $earned = $this->computeSessionQuestionEarned($qNorm, $ansRow, $weight);
                $totalEarned += $earned;
            } elseif (! $hasAnswer) {
                $earned = 0.0;
            }

            if ($isManual && ! $isGraded) {
                $pendingManualLabels[] = (string) ($idx + 1);
            }

            $scoreState = $this->resolveScoreState($earned, $weight, $isManual, $isGraded);
            $gradeMode = $this->resolveSessionGradeMode($qNorm, $selected);

            $entry = [
                'index' => $idx + 1,
                'label' => (string) ($idx + 1),
                'question_id' => $qid,
                'answer_id' => $answerId,
                'answer_key' => $answerId > 0 ? $answerPrefix.$answerId : '',
                'source' => $source,
                'question' => $qNorm,
                'question_type' => $qType,
                'question_type_label' => $this->questionTypeLabel($qType),
                'answer_row' => $ansRow,
                'selected_answer' => $selected,
                'has_answer' => $hasAnswer,
                'is_manual' => $isManual,
                'is_graded' => $isGraded,
                'max_points' => $weight,
                'earned_points' => $earned,
                'max_points_display' => $this->formatScoreVi($weight),
                'earned_points_display' => $earned !== null ? $this->formatScoreVi($earned) : '—',
                'score_state' => $scoreState,
                'grade_mode' => $gradeMode,
                'teacher_comment' => (string) ($ansRow['teacher_comment'] ?? ''),
                'teacher_graded_at' => $gradedAt,
            ];

            $allQuestions[] = $entry;
            if ($isManual) {
                $manualQuestions[] = $entry;
            } else {
                $autoQuestions[] = $entry;
            }
        }

        $totalEarned = round(min(100.0, max(0.0, $totalEarned)), 2);
        $pendingCount = count($pendingManualLabels);
        $isGraded = $pendingCount === 0;
        $isReadOnly = $isGraded && ! $forceEdit;

        $studentName = trim((string) (($user->full_name ?? '') ?: ($user->username ?? '')));
        $studentName = $studentName !== '' ? $studentName : 'Học viên';
        $uid = (int) ($user->id ?? 0);
        $studentCode = trim((string) (($user->student_code ?? '') ?: ($user->username ?? '')));

        $coursePill = trim($course->title.($courseLevelTitle !== '' ? ' · '.$courseLevelTitle : ''));
        $contextPills = array_values(array_filter([$coursePill, $contextShortLabel, $contextTypeLabel]));

        $defaultMode = count($manualQuestions) > 0 ? 'manual' : 'auto';
        if ($focusQuestionId > 0) {
            foreach ($autoQuestions as $aq) {
                if ((int) $aq['question_id'] === $focusQuestionId) {
                    $defaultMode = 'auto';
                    break;
                }
            }
        }

        return [
            'source' => $source,
            'submission_key' => $submissionKey,
            'course_id' => $courseId,
            'course_title' => (string) $course->title,
            'level_title' => $courseLevelTitle,
            'assignment_title' => $assignmentTitle,
            'content_type_label' => $contentTypeLabel,
            'context_pills' => $contextPills,
            'student' => [
                'id' => $uid,
                'name' => $studentName,
                'code' => $studentCode,
                'avatar' => $this->studentAvatarUrl($user->avatar ?? null),
                'initials' => $this->buildInitials($studentName),
                'avatar_tone' => self::AVATAR_TONES[$uid % count(self::AVATAR_TONES)],
            ],
            'all_questions' => $allQuestions,
            'manual_questions' => $manualQuestions,
            'auto_questions' => $autoQuestions,
            'total_earned' => $totalEarned,
            'total_possible' => 100.0,
            'total_earned_display' => $this->formatScoreVi($totalEarned),
            'total_possible_display' => '100',
            'pending_manual_count' => $pendingCount,
            'pending_manual_labels' => $pendingManualLabels,
            'is_graded' => $isGraded,
            'is_draft' => false,
            'is_retaking' => false,
            'allow_retake' => false,
            'is_read_only' => $isReadOnly,
            'default_mode' => $defaultMode,
            'focus_question_id' => $focusQuestionId,
            'submitted_at' => $latestSubmittedAt,
            'graded_at' => $latestGradedAt,
            'save_url' => route('teacher.grading.save'),
            'upload_url' => route('teacher.grading.upload'),
            'publish_url' => '',
            'retake_grant_url' => '',
            'retake_revoke_url' => '',
            'assessment_id' => 0,
            'back_url' => $this->pageUrl($courseId, $activeTab, '', $filterQuery),
            'edit_url' => $this->pageUrl($courseId, $activeTab, $submissionKey, array_merge($filterQuery, ['sua' => 1])),
            'view_url' => $this->pageUrl($courseId, $activeTab, $submissionKey, $filterQuery),
        ];
    }

    /**
     * @param  array<string, mixed>  $filterQuery
     * @return array<string, mixed>|null
     */
    private function buildAssessmentSubmissionDetail(
        Course $course,
        string $courseLevelTitle,
        int $assessmentId,
        int $userId,
        string $activeTab,
        array $filterQuery,
        bool $forceEdit,
        int $focusQuestionId
    ): ?array {
        $courseId = (int) $course->id;
        /** @var TeacherAssessmentGradeService $gradeService */
        $gradeService = app(TeacherAssessmentGradeService::class);
        $detail = $gradeService->buildStudentDetail($courseId, $assessmentId, $userId);

        if ($detail === null) {
            return null;
        }

        $assessment = $detail['assessment'] ?? null;
        $member = is_array($detail['member'] ?? null) ? $detail['member'] : [];
        $questions = is_array($detail['questions'] ?? null) ? $detail['questions'] : [];
        $answerMap = is_array($detail['answerMap'] ?? null) ? $detail['answerMap'] : [];
        $hasSubmitted = ! empty($detail['hasSubmitted']);
        $isDraftPreview = ! empty($detail['isDraftPreview']);
        $isRetaking = ! empty($detail['isRetaking']);
        $allowRetake = ! empty($detail['allowRetake']);
        $result = is_array($detail['result'] ?? null) ? $detail['result'] : null;
        $submission = is_array($detail['submission'] ?? null) ? $detail['submission'] : [];
        $isPublished = ! empty($detail['isGraded']);

        $userRow = DB::table('users')->where('id', $userId)->first(['student_code', 'username']);
        $studentCode = trim((string) (($userRow->student_code ?? '') ?: ($member['username'] ?? '')));
        $studentName = trim((string) (($member['full_name'] ?? '') ?: ($member['username'] ?? '')));
        $studentName = $studentName !== '' ? $studentName : 'Học viên';

        $allQuestions = [];
        $manualQuestions = [];
        $autoQuestions = [];
        $pendingManualLabels = [];

        foreach ($questions as $idx => $rawQ) {
            $qNorm = $this->parser->normalizeQuestionRowForEdit(is_array($rawQ) ? $rawQ : []);
            $qid = (int) ($qNorm['id'] ?? 0);
            $qType = (string) ($qNorm['question_type'] ?? 'choice');
            $qType = in_array($qType, ['text', 'essay'], true) ? 'fill' : $qType;
            $qNorm['question_type'] = $qType;

            $ansRow = is_array($answerMap[$qid] ?? null) ? $answerMap[$qid] : [];
            $selected = (string) ($ansRow['selected_answer'] ?? '');
            if ($selected === '' && trim((string) ($ansRow['answer_text'] ?? '')) !== '') {
                $selected = (string) ($ansRow['answer_text'] ?? '');
            }

            $maxPoints = $this->assessmentScoring->questionPoints($qNorm);
            $isManual = $this->assessmentScoring->questionNeedsTeacherReview($qNorm, $selected);
            $gradedAt = $this->gradedAtString($ansRow['teacher_graded_at'] ?? '');
            $earned = $this->assessmentScoring->resolveEarnedPoints($qNorm, $ansRow);

            $isGraded = $isManual
                ? ($gradedAt !== '' || $earned !== null)
                : true;

            if (! $isManual && $earned === null) {
                $earned = ! empty($ansRow['is_correct']) ? $maxPoints : 0.0;
            }

            if ($isManual && ! $isGraded && ! $isDraftPreview) {
                $pendingManualLabels[] = (string) ($idx + 1);
            }

            $scoreState = $this->resolveScoreState($earned, $maxPoints, $isManual, $isGraded);
            $gradeMode = $this->resolveAssessmentGradeMode($qNorm);

            $entry = [
                'index' => $idx + 1,
                'label' => (string) ($idx + 1),
                'question_id' => $qid,
                'answer_id' => (int) ($ansRow['id'] ?? 0),
                'answer_key' => (string) $qid,
                'source' => 'assessment',
                'question' => $qNorm,
                'question_type' => $qType,
                'question_type_label' => $this->questionTypeLabel($qType),
                'answer_row' => $ansRow,
                'selected_answer' => $selected,
                'has_answer' => trim($selected) !== '',
                'is_manual' => $isManual,
                'is_graded' => $isGraded,
                'max_points' => $maxPoints,
                'earned_points' => $earned,
                'max_points_display' => $this->formatScoreVi($maxPoints),
                'earned_points_display' => $earned !== null ? $this->formatScoreVi($earned) : '—',
                'score_state' => $scoreState,
                'grade_mode' => $gradeMode,
                'teacher_comment' => (string) ($ansRow['teacher_comment'] ?? ''),
                'teacher_graded_at' => $gradedAt,
            ];

            $allQuestions[] = $entry;
            if ($isManual) {
                $manualQuestions[] = $entry;
            } else {
                $autoQuestions[] = $entry;
            }
        }

        $totalEarned = ($hasSubmitted && $result !== null) ? (float) ($result['points_earned'] ?? 0) : 0.0;
        $totalPossible = ($hasSubmitted && $result !== null) ? (float) ($result['points_possible'] ?? 100) : 100.0;
        if ($totalPossible <= 0) {
            $totalPossible = 100.0;
        }
        $pendingCount = ($hasSubmitted && ! $isDraftPreview && $result !== null)
            ? (int) ($result['pending_manual'] ?? count($pendingManualLabels))
            : count($pendingManualLabels);

        $isReadOnly = ($isPublished || $isDraftPreview) && ! $forceEdit;
        $assessmentTitle = trim((string) ($assessment?->title ?? 'Bài thi'));
        $submissionKey = 'ass-'.$assessmentId.'-'.$userId;
        $coursePill = trim($course->title.($courseLevelTitle !== '' ? ' · '.$courseLevelTitle : ''));
        $contextPills = array_values(array_filter([$coursePill, $assessmentTitle, 'Bài thi']));

        $defaultMode = count($manualQuestions) > 0 ? 'manual' : 'auto';
        if ($focusQuestionId > 0) {
            foreach ($autoQuestions as $aq) {
                if ((int) $aq['question_id'] === $focusQuestionId) {
                    $defaultMode = 'auto';
                    break;
                }
            }
        }

        return [
            'source' => 'assessment',
            'submission_key' => $submissionKey,
            'course_id' => $courseId,
            'course_title' => (string) $course->title,
            'level_title' => $courseLevelTitle,
            'assignment_title' => $assessmentTitle,
            'content_type_label' => 'Bài thi',
            'context_pills' => $contextPills,
            'student' => [
                'id' => $userId,
                'name' => $studentName,
                'code' => $studentCode,
                'avatar' => $member['avatar'] ?? null,
                'initials' => $this->buildInitials($studentName),
                'avatar_tone' => self::AVATAR_TONES[$userId % count(self::AVATAR_TONES)],
            ],
            'all_questions' => $allQuestions,
            'manual_questions' => $manualQuestions,
            'auto_questions' => $autoQuestions,
            'total_earned' => $totalEarned,
            'total_possible' => $totalPossible,
            'total_earned_display' => $this->formatScoreVi($totalEarned),
            'total_possible_display' => $this->formatScoreVi($totalPossible),
            'pending_manual_count' => $pendingCount,
            'pending_manual_labels' => $pendingManualLabels,
            'is_graded' => $isPublished,
            'is_draft' => $isDraftPreview,
            'is_retaking' => $isRetaking,
            'allow_retake' => $allowRetake,
            'is_read_only' => $isReadOnly,
            'default_mode' => $defaultMode,
            'focus_question_id' => $focusQuestionId,
            'submitted_at' => (string) ($submission['submitted_at'] ?? ''),
            'graded_at' => (string) ($submission['graded_at'] ?? ''),
            'save_url' => route('teacher.assessments.grade'),
            'upload_url' => route('teacher.grading.upload'),
            'publish_url' => route('teacher.assessments.publish'),
            'retake_grant_url' => route('teacher.assessments.retake.grant'),
            'retake_revoke_url' => route('teacher.assessments.retake.revoke'),
            'assessment_id' => $assessmentId,
            'back_url' => $this->pageUrl($courseId, $activeTab, '', $filterQuery),
            'edit_url' => $this->pageUrl($courseId, $activeTab, $submissionKey, array_merge($filterQuery, ['sua' => 1])),
            'view_url' => $this->pageUrl($courseId, $activeTab, $submissionKey, $filterQuery),
        ];
    }

    /**
     * @param  array<string, mixed>  $qNorm
     */
    private function isManualQuestion(array $qNorm, string $selectedAnswer): bool
    {
        if ($this->parser->requiresTeacherGrading($qNorm)) {
            return true;
        }

        if (strtolower(trim((string) ($qNorm['question_type'] ?? ''))) === 'phat_am') {
            return $this->parser->hasPhatAmRecordingInSubmission($selectedAnswer);
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $qNorm
     * @param  array<string, mixed>  $ansRow
     */
    private function computeSessionQuestionEarned(array $qNorm, array $ansRow, float $weight): float
    {
        $type = (string) ($qNorm['question_type'] ?? 'choice');
        $comment = (string) ($ansRow['teacher_comment'] ?? '');

        if ($type === 'word_translate') {
            $total = count($this->parser->decodeWordTranslateData($qNorm)['items'] ?? []);
            if ($total > 0) {
                $reviews = $this->parser->decodeWordTranslateItemReview($comment);
                $correct = $this->parser->countWordTranslateCorrectItems($reviews);

                return round($weight * ($correct / $total), 2);
            }
        }

        if ($this->parser->hasCompositeFillItems($qNorm)) {
            $fillIndexes = $this->parser->compositeFillItemIndexes($qNorm);
            $total = count($fillIndexes);
            if ($total > 0) {
                $reviews = $this->parser->decodeCompositeFillItemReviews($comment);
                $correct = 0;
                foreach ($reviews as $review) {
                    if (! empty($review['is_correct'])) {
                        $correct++;
                    }
                }

                return round($weight * ($correct / $total), 2);
            }
        }

        return ! empty($ansRow['is_correct']) ? $weight : 0.0;
    }

    private function resolveScoreState(?float $earned, float $max, bool $isManual, bool $isGraded): string
    {
        if ($isManual && ! $isGraded) {
            return 'pending';
        }
        if ($earned === null) {
            return 'pending';
        }
        if ($earned <= 0.001) {
            return 'zero';
        }
        if ($max > 0 && $earned >= ($max - 0.01)) {
            return 'full';
        }

        return 'partial';
    }

    /**
     * @param  array<string, mixed>  $qNorm
     */
    private function resolveSessionGradeMode(array $qNorm, string $selected): string
    {
        $type = (string) ($qNorm['question_type'] ?? 'fill');
        if ($type === 'word_translate') {
            return 'word_translate';
        }
        if ($this->parser->hasCompositeFillItems($qNorm)) {
            return 'composite_fill';
        }
        if ($type === 'phat_am' && $this->parser->hasPhatAmRecordingInSubmission($selected)) {
            return 'phat_am';
        }

        return 'essay';
    }

    /**
     * @param  array<string, mixed>  $qNorm
     */
    private function resolveAssessmentGradeMode(array $qNorm): string
    {
        $type = (string) ($qNorm['question_type'] ?? 'choice');
        if ($type === 'word_translate') {
            return 'word_translate';
        }
        if ($type === 'fill_blanks') {
            return 'fill_blanks';
        }
        if (in_array($type, ['listening', 'phat_am'], true)) {
            $bundle = $type === 'listening'
                ? $this->parser->decodeListeningBundle($qNorm)
                : $this->parser->decodePhatAmBundle($qNorm);
            if (($bundle['questions'] ?? []) !== []) {
                return 'composite_items';
            }
        }

        return 'points';
    }

    /**
     * @param  Collection<int, Course>  $courses
     * @return array<int, string>
     */
    private function resolveCourseLevels(Collection $courses): array
    {
        $levels = [];
        $courseIds = $courses->pluck('id')->map(static fn ($id): int => (int) $id)->all();

        if ($courseIds !== []) {
            $upcomingSessions = CourseSession::query()
                ->whereIn('course_sessions.course_id', $courseIds)
                ->where('course_sessions.status', '!=', 'cancelled')
                ->with(['programSession.level'])
                ->inProgramOrder()
                ->get()
                ->groupBy('course_id');

            foreach ($courses as $course) {
                $cid = (int) $course->id;
                $levelTitle = '';
                foreach ($upcomingSessions->get($cid, collect()) as $session) {
                    $phase = $session->livePhase();
                    if ($phase === 'live' || $phase === 'upcoming') {
                        $levelTitle = trim((string) ($session->programSession?->level?->title ?? ''));
                        if ($levelTitle !== '') {
                            break;
                        }
                    }
                }
                if ($levelTitle === '') {
                    $levelTitle = trim((string) ($course->program?->levels?->first()?->title ?? ''));
                }
                $levels[$cid] = $levelTitle;
            }
        }

        return $levels;
    }

    /**
     * @param  list<int>  $courseIds
     * @param  array{pre: int, post: int, kr: int, assessment: int}  $selectedCourseCounts
     * @return array<int, array{pre: int, post: int, kr: int, assessment: int}>
     */
    private function countPendingByCourse(
        array $courseIds,
        int $selectedCourseId,
        array $selectedCourseCounts
    ): array {
        $result = [];
        foreach ($courseIds as $cid) {
            $result[$cid] = ['pre' => 0, 'post' => 0, 'kr' => 0, 'assessment' => 0];
        }
        $result[$selectedCourseId] = $selectedCourseCounts;

        $otherIds = array_values(array_filter(
            $courseIds,
            static fn (int $cid): bool => $cid !== $selectedCourseId
        ));

        if ($otherIds === []) {
            return $result;
        }

        $quizRows = DB::table('course_session_quiz_answers as a')
            ->join('course_session_questions as q', 'q.id', '=', 'a.question_id')
            ->join('course_sessions as s', 's.id', '=', 'a.course_session_id')
            ->whereIn('s.course_id', $otherIds)
            ->whereIn('q.question_group', ['pre', 'post_practice'])
            ->where(function ($inner): void {
                $inner->whereIn('q.question_type', ['fill', 'listening', 'word_translate', 'phat_am'])
                    ->orWhere('q.options_json', 'like', '%"phat_am"%')
                    ->orWhere('q.options_json', 'like', '%"word_translate"%')
                    ->orWhere('q.options_json', 'like', '%"listening"%')
                    ->orWhere('a.selected_answer', 'like', '%"recording":%');
            })
            ->whereNotNull('a.selected_answer')
            ->whereRaw('TRIM(a.selected_answer) != ?', [''])
            ->whereNull('a.teacher_graded_at')
            ->groupBy('s.course_id', 'q.question_group')
            ->selectRaw('s.course_id, q.question_group, COUNT(DISTINCT CONCAT(a.course_session_id, "-", a.user_id)) as total')
            ->get();

        foreach ($quizRows as $row) {
            $cid = (int) $row->course_id;
            $bucket = (string) $row->question_group === 'post_practice' ? 'post' : 'pre';
            if (isset($result[$cid])) {
                $result[$cid][$bucket] = (int) $row->total;
            }
        }

        if (Schema::hasTable('course_knowledge_review_answers') && Schema::hasColumn('course_knowledge_review_answers', 'teacher_graded_at')) {
            $krRows = DB::table('course_knowledge_review_answers as a')
                ->join('course_knowledge_review_questions as q', 'q.id', '=', 'a.question_id')
                ->join('course_knowledge_reviews as kr', 'kr.id', '=', 'a.course_knowledge_review_id')
                ->whereIn('kr.course_id', $otherIds)
                ->where(function ($inner): void {
                    $inner->whereIn('q.question_type', ['fill', 'essay', 'listening', 'word_translate', 'phat_am'])
                        ->orWhere('q.options_json', 'like', '%"phat_am"%')
                        ->orWhere('q.options_json', 'like', '%"word_translate"%')
                        ->orWhere('q.options_json', 'like', '%"listening"%')
                        ->orWhere('a.selected_answer', 'like', '%"recording":%');
                })
                ->whereNotNull('a.selected_answer')
                ->whereRaw('TRIM(a.selected_answer) != ?', [''])
                ->whereNull('a.teacher_graded_at')
                ->groupBy('kr.course_id')
                ->selectRaw('kr.course_id, COUNT(DISTINCT CONCAT(a.course_knowledge_review_id, "-", a.user_id)) as total')
                ->get();

            foreach ($krRows as $row) {
                $cid = (int) $row->course_id;
                if (isset($result[$cid])) {
                    $result[$cid]['kr'] = (int) $row->total;
                }
            }
        }

        if (Schema::hasTable('course_assessment_submissions') && Schema::hasTable('course_assessments')) {
            $assRows = DB::table('course_assessment_submissions as sub')
                ->join('course_assessments as ca', 'ca.id', '=', 'sub.course_assessment_id')
                ->whereIn('ca.course_id', $otherIds)
                ->where('sub.status', '!=', 'graded')
                ->groupBy('ca.course_id')
                ->selectRaw('ca.course_id, COUNT(*) as total')
                ->get();

            foreach ($assRows as $row) {
                $cid = (int) $row->course_id;
                if (isset($result[$cid])) {
                    $result[$cid]['assessment'] = (int) $row->total;
                }
            }
        }

        return $result;
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    private function filterItems(array $items, string $search, string $statusFilter): array
    {
        $needle = mb_strtolower($search, 'UTF-8');

        return array_values(array_filter($items, static function (array $item) use ($needle, $statusFilter): bool {
            if ($statusFilter !== 'all' && ($item['status'] ?? '') !== $statusFilter) {
                return false;
            }

            if ($needle !== '') {
                $haystack = mb_strtolower(
                    implode(' ', [
                        (string) ($item['student_name'] ?? ''),
                        (string) ($item['student_code'] ?? ''),
                        (string) ($item['course_title'] ?? ''),
                        (string) ($item['subtitle'] ?? ''),
                        (string) ($item['assignment_label'] ?? ''),
                        (string) ($item['content_type_label'] ?? ''),
                    ]),
                    'UTF-8'
                );

                if (! str_contains($haystack, $needle)) {
                    return false;
                }
            }

            return true;
        }));
    }

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    private function sortItems(array $items, string $sort): array
    {
        $rankOf = static fn (string $status): int => match ($status) {
            'draft' => 0,
            'pending' => 1,
            default => 2,
        };

        usort($items, static function (array $a, array $b) use ($sort, $rankOf): int {
            if ($sort === 'name') {
                $cmp = strcmp(
                    mb_strtolower((string) ($a['student_name'] ?? ''), 'UTF-8'),
                    mb_strtolower((string) ($b['student_name'] ?? ''), 'UTF-8')
                );
                if ($cmp !== 0) {
                    return $cmp;
                }
            } elseif ($sort === 'oldest') {
                $cmp = strcmp((string) ($a['submitted_at'] ?? ''), (string) ($b['submitted_at'] ?? ''));
                if ($cmp !== 0) {
                    return $cmp;
                }
            } elseif ($sort === 'newest') {
                $cmp = strcmp((string) ($b['submitted_at'] ?? ''), (string) ($a['submitted_at'] ?? ''));
                if ($cmp !== 0) {
                    return $cmp;
                }
            } else {
                $cmp = $rankOf((string) ($a['status'] ?? 'graded')) <=> $rankOf((string) ($b['status'] ?? 'graded'));
                if ($cmp !== 0) {
                    return $cmp;
                }
                $cmp = strcmp((string) ($b['submitted_at'] ?? ''), (string) ($a['submitted_at'] ?? ''));
                if ($cmp !== 0) {
                    return $cmp;
                }
            }

            return strcmp((string) ($b['key'] ?? ''), (string) ($a['key'] ?? ''));
        });

        return $items;
    }

    /**
     * @return list<int|string>
     */
    private function buildPaginationWindow(int $current, int $last): array
    {
        if ($last <= 5) {
            return range(1, $last);
        }
        if ($current <= 3) {
            return [1, 2, 3, '...', $last];
        }
        if ($current >= $last - 2) {
            return [1, '...', $last - 2, $last - 1, $last];
        }

        return [1, '...', $current - 1, $current, $current + 1, '...', $last];
    }

    /**
     * @param  list<array<string, mixed>>  $items
     */
    private function countStatus(array $items, string $status): int
    {
        $count = 0;
        foreach ($items as $item) {
            if (($item['status'] ?? '') === $status) {
                $count++;
            }
        }

        return $count;
    }

    private function compactSessionLabel(string $sessionTitle): string
    {
        if (preg_match('/^(Buổi\s+(?:\d+|bù))\b/iu', $sessionTitle, $matches) === 1) {
            return $matches[1];
        }

        return mb_strlen($sessionTitle) > 36 ? mb_substr($sessionTitle, 0, 33).'…' : $sessionTitle;
    }

    private function buildInitials(string $name): string
    {
        $clean = trim((string) preg_replace('/\s+/u', ' ', $name));
        if ($clean === '') {
            return 'HV';
        }
        $parts = explode(' ', $clean);
        if (count($parts) === 1) {
            return mb_strtoupper(mb_substr($parts[0], 0, 2, 'UTF-8'), 'UTF-8');
        }

        $first = mb_substr($parts[0], 0, 1, 'UTF-8');
        $last = mb_substr($parts[count($parts) - 1], 0, 1, 'UTF-8');

        return mb_strtoupper($first.$last, 'UTF-8');
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'draft' => 'Bản nháp',
            'pending' => 'Chờ chấm',
            default => 'Đã gửi kết quả',
        };
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
