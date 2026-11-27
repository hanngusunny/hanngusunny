<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseAssessment;
use App\Models\CourseAssessmentAnswer;
use App\Models\CourseAssessmentSubmission;
use App\Models\CourseKnowledgeReview;
use App\Models\CourseKnowledgeReviewQuestion;
use App\Models\CourseSession;
use App\Models\CourseSessionQuestion;
use App\Support\HtmlContent;
use App\Support\Questions\QuestionParser;
use App\Support\StudentQuizGrader;
use Illuminate\Support\Facades\DB;

class StudentResultsService
{
    public const VALID_TYPES = ['pre', 'post', 'kr', 'exam'];

    public const FILTER_KEYS = ['all', 'pre', 'post', 'kr', 'exam'];

    private const FRIENDLY_TYPE_LABELS = [
        'choice' => 'Một đáp án',
        'multi_select' => 'Nhiều đáp án',
        'fill' => 'Tự luận',
        'essay' => 'Tự luận',
        'text' => 'Tự luận',
        'fill_blanks' => 'Điền chỗ trống',
        'word_select' => 'Chọn từ điền trống',
        'word_link' => 'Nối từ – từ',
        'word_order' => 'Sắp xếp câu',
        'category_sort' => 'Phân loại từ',
        'word_translate' => 'Dịch từ',
        'memory_match' => 'Lật thẻ ghép cặp',
        'phat_am' => 'Đọc và ghi âm',
        'listening' => 'Nghe và trả lời',
        'image_hotspot' => 'Chọn điểm trên ảnh',
        'image_label_drop' => 'Gắn nhãn hình ảnh',
    ];

    public function __construct(
        private StudentAssessmentService $assessments,
        private AssessmentSubmissionService $submissions,
        private StudentQuizGrader $grader,
        private QuestionParser $parser,
    ) {
    }

    /**
     * Tổng hợp danh sách 4 loại bài làm (Trước buổi, Sau buổi, Bài kiểm tra, Bài thi) bằng query gom nhóm.
     *
     * @return array{items: list<array<string, mixed>>, counts: array<string, int>}
     */
    public function listForStudent(int $courseId, int $userId): array
    {
        $counts = ['all' => 0, 'pre' => 0, 'post' => 0, 'kr' => 0, 'exam' => 0];

        if ($courseId <= 0 || $userId <= 0) {
            return ['items' => [], 'counts' => $counts];
        }

        $items = [];
        $now = time();
        $recentThreshold = $now - (7 * 86400);

        // 1 & 2. Bài tập Trước buổi (pre) & Sau buổi (post_practice)
        $quizRows = DB::table('course_session_quiz_answers as a')
            ->join('course_session_questions as q', 'q.id', '=', 'a.question_id')
            ->join('course_sessions as s', 's.id', '=', 'a.course_session_id')
            ->where('a.user_id', $userId)
            ->where('s.course_id', $courseId)
            ->whereIn('q.question_group', ['pre', 'post_practice'])
            ->selectRaw('a.course_session_id, q.question_group, s.title, s.sort_order, COUNT(*) as answered_count, SUM(CASE WHEN a.is_correct = 1 THEN 1 ELSE 0 END) as correct_count, MAX(a.updated_at) as last_at, MAX(a.teacher_graded_at) as graded_at')
            ->groupBy('a.course_session_id', 'q.question_group', 's.title', 's.sort_order')
            ->get();

        if ($quizRows->isNotEmpty()) {
            $sessionIds = $quizRows->pluck('course_session_id')->unique()->values()->all();
            $questionTotals = DB::table('course_session_questions')
                ->whereIn('course_session_id', $sessionIds)
                ->whereIn('question_group', ['pre', 'post_practice'])
                ->selectRaw('course_session_id, question_group, COUNT(*) as total_count')
                ->groupBy('course_session_id', 'question_group')
                ->get()
                ->mapWithKeys(fn ($r) => [$r->course_session_id.':'.$r->question_group => (int) $r->total_count]);

            foreach ($quizRows as $row) {
                $group = (string) $row->question_group;
                $type = $group === 'pre' ? 'pre' : 'post';
                $typeLabel = $type === 'pre' ? 'Trước buổi' : 'Sau buổi';
                $sessionId = (int) $row->course_session_id;
                $total = max((int) ($questionTotals[$sessionId.':'.$group] ?? 0), (int) $row->answered_count);
                $correct = (int) $row->correct_count;
                $score = $total > 0 ? (int) round(($correct / $total) * 100) : 0;

                $submittedTs = $this->toTimestamp($row->last_at);
                $gradedTs = $this->toTimestamp($row->graded_at);
                $resultTs = $gradedTs ?: $submittedTs;
                $title = trim((string) ($row->title ?? '')) ?: ('Buổi #'.$sessionId);
                $sessionNo = (int) ($row->sort_order ?? 0);

                $subParts = [$typeLabel];
                if ($sessionNo > 0) {
                    $subParts[] = 'Buổi '.$sessionNo;
                }
                if ($submittedTs > 0) {
                    $subParts[] = 'Nộp '.$this->formatDateTime($submittedTs);
                }

                $items[] = [
                    'type' => $type,
                    'id' => $sessionId,
                    'title' => $title,
                    'type_label' => $typeLabel,
                    'subtitle' => implode(' · ', $subParts),
                    'is_pending' => false,
                    'status_key' => 'graded',
                    'status_label' => 'Đã có kết quả',
                    'status_sub' => $resultTs > 0 ? 'Có kết quả '.$this->formatDateTime($resultTs) : 'Đã chấm điểm',
                    'score' => $score,
                    'score_display' => $score.' / 100',
                    'is_new' => $resultTs > 0 && $resultTs >= $recentThreshold,
                    'sort_ts' => $resultTs,
                    'detail_url' => route('student.evaluations', [
                        'tab' => 'ket-qua',
                        'loai' => $type,
                        'id' => $sessionId,
                    ]),
                ];
            }
        }

        // 3. Bài kiểm tra / Ôn tập kiến thức (kr)
        $krRows = DB::table('course_knowledge_review_answers as a')
            ->join('course_knowledge_reviews as kr', 'kr.id', '=', 'a.course_knowledge_review_id')
            ->where('a.user_id', $userId)
            ->where('kr.course_id', $courseId)
            ->selectRaw('kr.id, kr.title, COUNT(*) as answered_count, SUM(CASE WHEN a.is_correct = 1 THEN 1 ELSE 0 END) as correct_count, MAX(a.updated_at) as last_at, MAX(a.teacher_graded_at) as graded_at')
            ->groupBy('kr.id', 'kr.title')
            ->get();

        if ($krRows->isNotEmpty()) {
            $krIds = $krRows->pluck('id')->all();
            $krTotals = DB::table('course_knowledge_review_questions')
                ->whereIn('course_knowledge_review_id', $krIds)
                ->selectRaw('course_knowledge_review_id, COUNT(*) as total_count')
                ->groupBy('course_knowledge_review_id')
                ->pluck('total_count', 'course_knowledge_review_id');

            foreach ($krRows as $row) {
                $krId = (int) $row->id;
                $total = max((int) ($krTotals[$krId] ?? 0), (int) $row->answered_count);
                $correct = (int) $row->correct_count;
                $score = $total > 0 ? (int) round(($correct / $total) * 100) : 0;
                $submittedTs = $this->toTimestamp($row->last_at);
                $gradedTs = $this->toTimestamp($row->graded_at);
                $resultTs = $gradedTs ?: $submittedTs;
                $title = trim((string) ($row->title ?? '')) ?: ('Bài kiểm tra #'.$krId);

                $subParts = ['Bài kiểm tra', $total.' câu'];
                if ($submittedTs > 0) {
                    $subParts[] = 'Nộp '.$this->formatDateTime($submittedTs);
                }

                $items[] = [
                    'type' => 'kr',
                    'id' => $krId,
                    'title' => $title,
                    'type_label' => 'Bài kiểm tra',
                    'subtitle' => implode(' · ', $subParts),
                    'is_pending' => false,
                    'status_key' => 'graded',
                    'status_label' => 'Đã có kết quả',
                    'status_sub' => $resultTs > 0 ? 'Có kết quả '.$this->formatDateTime($resultTs) : 'Đã chấm điểm',
                    'score' => $score,
                    'score_display' => $score.' / 100',
                    'is_new' => $resultTs > 0 && $resultTs >= $recentThreshold,
                    'sort_ts' => $resultTs,
                    'detail_url' => route('student.evaluations', [
                        'tab' => 'ket-qua',
                        'loai' => 'kr',
                        'id' => $krId,
                    ]),
                ];
            }
        }

        // 4. Bài thi định kỳ (exam) — gom nhóm trực tiếp, không N+1
        $examAnswerRows = DB::table('course_assessment_answers as a')
            ->join('course_assessments as ca', 'ca.id', '=', 'a.course_assessment_id')
            ->where('a.user_id', $userId)
            ->where('ca.course_id', $courseId)
            ->where('ca.status', '!=', 'cancelled')
            ->selectRaw('ca.id, ca.title, SUM(COALESCE(a.points_earned, 0)) as earned_sum, SUM(CASE WHEN a.is_correct = 1 THEN 1 ELSE 0 END) as correct_sum, COUNT(*) as answered_sum, MAX(a.updated_at) as last_at')
            ->groupBy('ca.id', 'ca.title')
            ->get();

        if ($examAnswerRows->isNotEmpty()) {
            $examIds = $examAnswerRows->pluck('id')->all();

            $subMetaByExam = CourseAssessmentSubmission::query()
                ->whereIn('course_assessment_id', $examIds)
                ->where('user_id', $userId)
                ->get()
                ->keyBy('course_assessment_id');

            $retakeExamIds = DB::table('course_assessment_retakes')
                ->whereIn('course_assessment_id', $examIds)
                ->where('user_id', $userId)
                ->pluck('course_assessment_id')
                ->flip();

            $possibleByExam = DB::table('course_assessment_questions')
                ->whereIn('course_assessment_id', $examIds)
                ->selectRaw('course_assessment_id, SUM(CASE WHEN COALESCE(points, 0) > 0 THEN points ELSE 1 END) as possible_sum, COUNT(*) as total_q')
                ->groupBy('course_assessment_id')
                ->get()
                ->keyBy('course_assessment_id');

            foreach ($examAnswerRows as $row) {
                $examId = (int) $row->id;
                $meta = $subMetaByExam[$examId] ?? null;
                $isGraded = $meta !== null && $meta->status === 'graded';
                $allowRetake = isset($retakeExamIds[$examId]);
                $visible = $isGraded && ! $allowRetake;

                $possibleRow = $possibleByExam[$examId] ?? null;
                $qCount = (int) ($possibleRow->total_q ?? $row->answered_sum ?? 0);
                if ($qCount <= 0) {
                    continue;
                }

                $submittedTs = $this->toTimestamp($meta?->submitted_at ?? $meta?->created_at ?? $row->last_at);
                $gradedTs = $this->toTimestamp($meta?->graded_at ?? null);
                $sortTs = $visible ? ($gradedTs ?: $submittedTs) : $submittedTs;

                $subParts = ['Bài thi', $qCount.' câu'];
                if ($submittedTs > 0) {
                    $subParts[] = 'Nộp '.$this->formatDateTime($submittedTs);
                }

                $score = null;
                if ($visible) {
                    $possible = (float) ($possibleRow->possible_sum ?? 0);
                    $earned = (float) ($row->earned_sum ?? 0);
                    $score = $possible > 0
                        ? (int) round(min(100, max(0, ($earned / $possible) * 100)))
                        : (int) round(((int) $row->correct_sum / $qCount) * 100);
                }

                $items[] = [
                    'type' => 'exam',
                    'id' => $examId,
                    'title' => trim((string) ($row->title ?? 'Bài thi')),
                    'type_label' => 'Bài thi',
                    'subtitle' => implode(' · ', $subParts),
                    'is_pending' => ! $visible,
                    'status_key' => $visible ? 'graded' : 'pending',
                    'status_label' => $visible ? 'Đã có kết quả' : 'Chờ giáo viên chấm',
                    'status_sub' => $visible
                        ? ($gradedTs > 0 ? 'Có kết quả '.$this->formatDateTime($gradedTs) : 'Đã công bố kết quả')
                        : 'Điểm hiển thị sau khi giáo viên gửi kết quả',
                    'score' => $score,
                    'score_display' => $visible ? ($score.' / 100') : '—',
                    'is_new' => $visible && $gradedTs > 0 && $gradedTs >= $recentThreshold,
                    'sort_ts' => $sortTs,
                    'detail_url' => route('student.evaluations', [
                        'tab' => 'ket-qua',
                        'loai' => 'exam',
                        'id' => $examId,
                    ]),
                ];
            }
        }

        usort($items, static fn (array $a, array $b): int => ($b['sort_ts'] <=> $a['sort_ts']) ?: ($b['id'] <=> $a['id']));

        foreach ($items as $item) {
            $counts['all']++;
            if (isset($counts[$item['type']])) {
                $counts[$item['type']]++;
            }
        }

        return [
            'items' => $items,
            'counts' => $counts,
        ];
    }

    /**
     * Chi tiết 1 bài làm chuẩn Figma (#719:26030, #719:26243, #719:26596, #719:26850).
     *
     * @return array<string, mixed>|null
     */
    public function detailForStudent(int $courseId, int $userId, string $type, int $targetId, ?Course $course = null): ?array
    {
        if ($courseId <= 0 || $userId <= 0 || $targetId <= 0 || ! in_array($type, self::VALID_TYPES, true)) {
            return null;
        }

        $teacherName = trim((string) ($course?->teacher?->name ?? 'Giáo viên'));
        $teacherAvatar = $course?->teacher?->avatarUrl() ?? '';
        $teacherInitials = $this->initials($teacherName);

        if ($type === 'exam') {
            return $this->detailExam($courseId, $userId, $targetId, $teacherName, $teacherAvatar, $teacherInitials);
        }

        if ($type === 'kr') {
            return $this->detailKnowledgeReview($courseId, $userId, $targetId, $teacherName, $teacherAvatar, $teacherInitials);
        }

        return $this->detailSessionQuiz($courseId, $userId, $type, $targetId, $teacherName, $teacherAvatar, $teacherInitials);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function detailSessionQuiz(
        int $courseId,
        int $userId,
        string $type,
        int $sessionId,
        string $teacherName,
        string $teacherAvatar,
        string $teacherInitials
    ): ?array {
        $session = CourseSession::query()
            ->where('id', $sessionId)
            ->where('course_id', $courseId)
            ->first();

        if ($session === null) {
            return null;
        }

        $group = $type === 'pre' ? 'pre' : 'post_practice';
        $questions = $this->grader->readyQuestions(CourseSessionQuestion::forSessionGroup($sessionId, $group));

        if ($questions === []) {
            return null;
        }

        $qIds = array_map(static fn (array $q): int => (int) ($q['id'] ?? 0), $questions);
        $answerRows = DB::table('course_session_quiz_answers')
            ->where('course_session_id', $sessionId)
            ->where('user_id', $userId)
            ->whereIn('question_id', $qIds)
            ->get();

        if ($answerRows->isEmpty()) {
            return null;
        }

        $answerMap = [];
        $latestSubmitted = 0;
        $latestGraded = 0;

        foreach ($answerRows as $row) {
            $arr = (array) $row;
            $answerMap[(int) $arr['question_id']] = $arr;
            $latestSubmitted = max($latestSubmitted, $this->toTimestamp($arr['updated_at'] ?? null));
            $latestGraded = max($latestGraded, $this->toTimestamp($arr['teacher_graded_at'] ?? null));
        }

        $totalQ = count($questions);
        $unitPts = $totalQ > 0 ? round(100 / $totalQ, 2) : 0.0;
        $correctCount = 0;
        $commentCount = 0;
        $cards = [];

        foreach ($questions as $index => $question) {
            $card = $this->formatQuestionCard($index + 1, $question, $answerMap[(int) ($question['id'] ?? 0)] ?? null, false, $unitPts);
            if ($card['status'] === 'correct') {
                $correctCount++;
            }
            if ($card['teacher_comment'] !== '' || $card['teacher_audio_url'] !== '') {
                $commentCount++;
            }
            $cards[] = $card;
        }

        $scorePct = $totalQ > 0 ? (int) round(($correctCount / $totalQ) * 100) : 0;
        $resultTs = $latestGraded ?: $latestSubmitted;
        $typeLabel = $type === 'pre' ? 'Bài tập trước buổi' : 'Bài tập sau buổi';

        return [
            'type' => $type,
            'type_label' => $typeLabel,
            'title' => trim((string) $session->title) ?: ('Buổi #'.$sessionId),
            'is_pending' => false,
            'time_subtitle' => $resultTs > 0
                ? ($latestGraded > 0 ? 'Có kết quả ' : 'Đã nộp ').$this->formatDateTime($resultTs)
                : $typeLabel,
            'score_display' => $scorePct.' / 100',
            'score_sub' => $correctCount.'/'.$totalQ.' câu đúng',
            'teacher_name' => $teacherName,
            'teacher_avatar' => $teacherAvatar,
            'teacher_initials' => $teacherInitials,
            'overall_note' => $this->buildOverallNote($correctCount, $totalQ, $commentCount, $scorePct),
            'questions' => $cards,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function detailKnowledgeReview(
        int $courseId,
        int $userId,
        int $krId,
        string $teacherName,
        string $teacherAvatar,
        string $teacherInitials
    ): ?array {
        $kr = CourseKnowledgeReview::query()
            ->where('id', $krId)
            ->where('course_id', $courseId)
            ->first();

        if ($kr === null) {
            return null;
        }

        $questions = $this->grader->readyQuestions(
            CourseKnowledgeReviewQuestion::query()
                ->where('course_knowledge_review_id', $krId)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
        );

        if ($questions === []) {
            return null;
        }

        $answerRows = DB::table('course_knowledge_review_answers')
            ->where('course_knowledge_review_id', $krId)
            ->where('user_id', $userId)
            ->get();

        if ($answerRows->isEmpty()) {
            return null;
        }

        $answerMap = [];
        $latestSubmitted = 0;
        $latestGraded = 0;

        foreach ($answerRows as $row) {
            $arr = (array) $row;
            $answerMap[(int) $arr['question_id']] = $arr;
            $latestSubmitted = max($latestSubmitted, $this->toTimestamp($arr['updated_at'] ?? null));
            $latestGraded = max($latestGraded, $this->toTimestamp($arr['teacher_graded_at'] ?? null));
        }

        $totalQ = count($questions);
        $unitPts = $totalQ > 0 ? round(100 / $totalQ, 2) : 0.0;
        $correctCount = 0;
        $commentCount = 0;
        $cards = [];

        foreach ($questions as $index => $question) {
            $card = $this->formatQuestionCard($index + 1, $question, $answerMap[(int) ($question['id'] ?? 0)] ?? null, false, $unitPts);
            if ($card['status'] === 'correct') {
                $correctCount++;
            }
            if ($card['teacher_comment'] !== '' || $card['teacher_audio_url'] !== '') {
                $commentCount++;
            }
            $cards[] = $card;
        }

        $scorePct = $totalQ > 0 ? (int) round(($correctCount / $totalQ) * 100) : 0;
        $resultTs = $latestGraded ?: $latestSubmitted;

        return [
            'type' => 'kr',
            'type_label' => 'Bài kiểm tra',
            'title' => trim((string) $kr->title) ?: ('Bài kiểm tra #'.$krId),
            'is_pending' => false,
            'time_subtitle' => $resultTs > 0
                ? ($latestGraded > 0 ? 'Có kết quả ' : 'Đã nộp ').$this->formatDateTime($resultTs)
                : 'Bài kiểm tra',
            'score_display' => $scorePct.' / 100',
            'score_sub' => $correctCount.'/'.$totalQ.' câu đúng',
            'teacher_name' => $teacherName,
            'teacher_avatar' => $teacherAvatar,
            'teacher_initials' => $teacherInitials,
            'overall_note' => $this->buildOverallNote($correctCount, $totalQ, $commentCount, $scorePct),
            'questions' => $cards,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function detailExam(
        int $courseId,
        int $userId,
        int $assessmentId,
        string $teacherName,
        string $teacherAvatar,
        string $teacherInitials
    ): ?array {
        $model = CourseAssessment::query()
            ->with('slots')
            ->where('id', $assessmentId)
            ->where('course_id', $courseId)
            ->first();

        if ($model === null) {
            return null;
        }

        $questions = $this->assessments->readyQuestions($assessmentId);
        if ($questions === []) {
            return null;
        }

        $enriched = $this->assessments->enrichForStudent($model, $userId, $questions);
        if (empty($enriched['has_submitted'])) {
            return null;
        }

        $submissionMeta = CourseAssessmentSubmission::getForStudent($assessmentId, $userId);
        $submittedTs = $this->toTimestamp($submissionMeta?->submitted_at ?? $submissionMeta?->created_at ?? null);
        $gradedTs = $this->toTimestamp($submissionMeta?->graded_at ?? null);
        $visible = ! empty($enriched['results_visible']);
        $title = trim((string) $model->title) ?: ('Bài thi #'.$assessmentId);

        // Chờ giáo viên chấm → TUYỆT ĐỐI không trả đáp án hay điểm
        if (! $visible) {
            $pendingCards = [];
            foreach ($questions as $index => $question) {
                $qType = strtolower((string) ($question['question_type'] ?? 'choice'));
                $maxPts = $this->submissions->questionPoints($question);
                $pendingCards[] = [
                    'number' => $index + 1,
                    'type' => $qType,
                    'type_label' => self::FRIENDLY_TYPE_LABELS[$qType] ?? 'Câu hỏi',
                    'status' => 'pending',
                    'status_label' => 'Đã nộp',
                    'points_display' => '— / '.$this->submissions->formatPoints($maxPts),
                    'points_is_zero' => false,
                    'prompt_html' => '',
                    'student_text' => '',
                    'correct_text' => '',
                    'is_essay_box' => false,
                    'essay_html' => '',
                    'audio_url' => '',
                    'audio_name' => '',
                    'teacher_comment' => '',
                    'teacher_audio_url' => '',
                ];
            }

            return [
                'type' => 'exam',
                'type_label' => 'Bài thi',
                'title' => $title,
                'is_pending' => true,
                'time_subtitle' => ($submittedTs > 0 ? 'Đã nộp '.$this->formatDateTime($submittedTs).' · ' : '').'Chờ giáo viên chấm',
                'score_display' => '— / 100',
                'score_sub' => '',
                'teacher_name' => $teacherName,
                'teacher_avatar' => $teacherAvatar,
                'teacher_initials' => $teacherInitials,
                'overall_note' => 'Giáo viên đang chấm bài. Điểm và nhận xét sẽ hiện ở đây khi có kết quả.',
                'questions' => $pendingCards,
            ];
        }

        $answerMap = CourseAssessmentAnswer::mapForUser($assessmentId, $userId);
        $result = $this->submissions->buildResult($questions, $answerMap);
        $scorePct = (int) ($result['score'] ?? 0);
        $earnedTotal = (float) ($result['points_earned'] ?? 0);
        $possibleTotal = (float) ($result['points_possible'] ?? 0);

        $cards = [];
        $correctCount = 0;
        $commentCount = 0;

        foreach ($questions as $index => $question) {
            $qId = (int) ($question['id'] ?? 0);
            $maxPts = $this->submissions->questionPoints($question);
            $card = $this->formatQuestionCard($index + 1, $question, $answerMap[$qId] ?? null, true, $maxPts);
            if ($card['status'] === 'correct') {
                $correctCount++;
            }
            if ($card['teacher_comment'] !== '' || $card['teacher_audio_url'] !== '') {
                $commentCount++;
            }
            $cards[] = $card;
        }

        $scoreDisplay = $possibleTotal > 0
            ? $this->submissions->formatPoints($earnedTotal).' / '.$this->submissions->formatPoints($possibleTotal)
            : $scorePct.' / 100';

        return [
            'type' => 'exam',
            'type_label' => 'Bài thi',
            'title' => $title,
            'is_pending' => false,
            'time_subtitle' => $gradedTs > 0
                ? 'Có kết quả '.$this->formatDateTime($gradedTs)
                : ($submittedTs > 0 ? 'Đã nộp '.$this->formatDateTime($submittedTs) : 'Đã có kết quả'),
            'score_display' => $scoreDisplay,
            'score_sub' => $possibleTotal > 0 ? $scorePct.'% · '.$correctCount.'/'.count($questions).' câu đúng' : $correctCount.'/'.count($questions).' câu đúng',
            'teacher_name' => $teacherName,
            'teacher_avatar' => $teacherAvatar,
            'teacher_initials' => $teacherInitials,
            'overall_note' => $this->buildOverallNote($correctCount, count($questions), $commentCount, $scorePct),
            'questions' => $cards,
        ];
    }

    /**
     * Chuẩn hóa 1 câu hỏi cho thẻ Chi tiết kết quả chuẩn Figma.
     *
     * @param  array<string, mixed>  $question
     * @param  array<string, mixed>|null  $answerRow
     * @return array<string, mixed>
     */
    private function formatQuestionCard(
        int $number,
        array $question,
        ?array $answerRow,
        bool $useExamPoints,
        float $maxPoints
    ): array {
        $qType = strtolower((string) ($question['question_type'] ?? 'choice'));
        if ($qType === 'text' || $qType === 'essay') {
            $qType = 'fill';
        }

        $typeLabel = self::FRIENDLY_TYPE_LABELS[$qType] ?? 'Câu hỏi';
        $rawSelected = trim((string) ($answerRow['selected_answer'] ?? ''));
        // Chỉ bảng course_assessment_answers có cột answer_text (bài thi)
        if ($useExamPoints && $rawSelected === '' && trim((string) ($answerRow['answer_text'] ?? '')) !== '') {
            $rawSelected = trim((string) ($answerRow['answer_text'] ?? ''));
        }

        $hasAnswer = $answerRow !== null && $rawSelected !== '';
        $isCorrect = $answerRow !== null && ! empty($answerRow['is_correct']);

        if ($useExamPoints) {
            $earned = ($answerRow !== null && isset($answerRow['points_earned']) && $answerRow['points_earned'] !== null)
                ? (float) $answerRow['points_earned']
                : ($isCorrect ? $maxPoints : 0.0);
        } else {
            $earned = $isCorrect ? $maxPoints : 0.0;
        }

        if (! $hasAnswer) {
            $status = 'unanswered';
            $statusLabel = 'Chưa trả lời';
        } elseif ($isCorrect || ($maxPoints > 0 && $earned >= $maxPoints)) {
            $status = 'correct';
            $statusLabel = 'Đúng';
        } elseif ($earned > 0 && $earned < $maxPoints) {
            $status = 'partial';
            $statusLabel = 'Đạt một phần';
        } else {
            $status = 'wrong';
            $statusLabel = 'Sai';
        }

        $rawTeacherComment = trim((string) ($answerRow['teacher_comment'] ?? ''));
        $teacherComment = '';
        $teacherAudioUrl = '';

        if ($rawTeacherComment !== '') {
            $phatAmRev = $this->parser->decodePhatAmTeacherReview($rawTeacherComment);
            if ($phatAmRev['recording'] !== '') {
                $teacherAudioUrl = $this->parser->resolvePhatAmRecordingUrl($phatAmRev['recording']);
            }
            $teacherComment = $this->parser->decodeCompositeFillReviewComment($rawTeacherComment);
        }

        $promptHtml = $this->resolvePromptHtml($question, $qType);
        $studentText = '';
        $correctText = '';
        $isEssayBox = false;
        $essayHtml = '';
        $audioUrl = '';
        $audioName = '';

        if ($qType === 'choice') {
            $options = $this->decodeChoiceOptionsSafe($question);
            $selKey = strtolower($rawSelected);
            $corKey = strtolower(trim((string) ($question['correct_answer'] ?? '')));

            if ($hasAnswer) {
                $optVal = trim((string) ($options[$selKey] ?? ''));
                $studentText = $optVal !== '' ? $optVal : strtoupper($selKey);
            }
            if ($corKey !== '' && $status !== 'correct') {
                $corVal = trim((string) ($options[$corKey] ?? ''));
                $correctText = $corVal !== '' ? $corVal : strtoupper($corKey);
            }
        } elseif ($qType === 'multi_select') {
            $options = $this->decodeChoiceOptionsSafe($question);
            $selLetters = $this->parser->parseMultiSelectLetters($rawSelected);
            $corLetters = $this->parser->parseMultiSelectLetters((string) ($question['correct_answer'] ?? ''));

            if ($selLetters !== []) {
                $studentText = implode(', ', array_map(
                    static fn (string $k): string => trim((string) ($options[$k] ?? strtoupper($k))),
                    $selLetters
                ));
            }
            if ($corLetters !== [] && $status !== 'correct') {
                $correctText = implode(', ', array_map(
                    static fn (string $k): string => trim((string) ($options[$k] ?? strtoupper($k))),
                    $corLetters
                ));
            }
        } elseif ($qType === 'fill') {
            $isEssayBox = true;
            $essayHtml = $hasAnswer ? HtmlContent::render($rawSelected) : '';
            $modelAns = trim((string) ($question['correct_answer'] ?? ''));
            if ($modelAns !== '' && $status !== 'correct') {
                $correctText = QuestionParser::plainTextFromHtml($modelAns);
            }
        } elseif ($qType === 'phat_am') {
            $isEssayBox = true;
            $recPath = $this->parser->decodePhatAmStudentRecording($rawSelected);
            if ($recPath !== '') {
                $audioUrl = $this->parser->resolvePhatAmRecordingUrl($recPath);
                $audioName = basename($recPath);
            }
            $payload = $this->parser->decodePhatAmStudentPayload($rawSelected);
            $extraAns = trim((string) ($payload['answer'] ?? ''));
            if ($extraAns !== '' && $audioUrl === '') {
                $essayHtml = HtmlContent::render($extraAns);
            }
        } elseif (in_array($qType, ['fill_blanks', 'word_select'], true)) {
            $decoded = json_decode($rawSelected, true);
            $blanks = is_array($decoded) && is_array($decoded['blanks'] ?? null) ? $decoded['blanks'] : [];
            if ($blanks !== []) {
                $studentText = implode(' · ', array_map(static fn ($v): string => trim((string) $v) ?: '___', $blanks));
            }
            $data = $qType === 'word_select'
                ? $this->parser->decodeWordSelectData($question)
                : $this->parser->decodeFillBlanksData($question);
            $expected = is_array($data['answers'] ?? null) ? $data['answers'] : [];
            if ($expected !== [] && $status !== 'correct') {
                $correctText = implode(' · ', array_map(static fn ($v): string => trim((string) $v), $expected));
            }
        } elseif ($qType === 'word_translate') {
            $decoded = json_decode($rawSelected, true);
            $trans = is_array($decoded) && is_array($decoded['translations'] ?? null) ? $decoded['translations'] : [];
            if ($trans !== []) {
                $studentText = implode(' · ', array_filter(array_map(static fn ($v): string => trim((string) $v), $trans)));
            }
        } elseif ($qType === 'word_link') {
            $pairs = $this->parser->decodePlayableWordLinkPairs($question);
            if ($hasAnswer && $pairs !== []) {
                $summary = [];
                foreach (array_slice($pairs, 0, 5) as $pair) {
                    $left = trim((string) ($pair['left_text'] ?? $pair['left'] ?? ''));
                    $right = trim((string) ($pair['right_text'] ?? $pair['right'] ?? ''));
                    if ($left !== '' && $right !== '') {
                        $summary[] = $left.' – '.$right;
                    }
                }
                $studentText = $summary !== [] ? implode(' · ', $summary) : ($isCorrect ? 'Đã nối đúng toàn bộ cặp từ' : 'Đã nộp bài nối từ');
            }
        } elseif ($qType === 'listening') {
            $decoded = json_decode($rawSelected, true);
            if ($hasAnswer) {
                $studentText = $isCorrect ? 'Đã hoàn thành chính xác phần nghe' : 'Đã nộp phần nghe hiểu';
                if (is_array($decoded) && ! empty($decoded['answers']) && is_array($decoded['answers'])) {
                    $flat = [];
                    foreach ($decoded['answers'] as $sub) {
                        if (is_string($sub) && trim($sub) !== '') {
                            $flat[] = trim($sub);
                        }
                    }
                    if ($flat !== []) {
                        $studentText = implode(' · ', array_slice($flat, 0, 6));
                    }
                }
            }
        } elseif ($hasAnswer) {
            $decoded = json_decode($rawSelected, true);
            if (is_array($decoded) && ($decoded['status'] ?? '') === 'completed') {
                $studentText = 'Đã hoàn thành câu hỏi tương tác';
            } else {
                $studentText = QuestionParser::plainTextFromHtml($rawSelected);
            }
        }

        $pointsDisplay = $maxPoints > 0
            ? $this->submissions->formatPoints($earned).' / '.$this->submissions->formatPoints($maxPoints).' điểm'
            : ($status === 'correct' ? 'Đạt' : '0 điểm');

        return [
            'number' => $number,
            'type' => $qType,
            'type_label' => $typeLabel,
            'status' => $status,
            'status_label' => $statusLabel,
            'points_display' => $pointsDisplay,
            'points_is_zero' => $earned <= 0 && $status !== 'pending',
            'prompt_html' => $promptHtml,
            'student_text' => $studentText,
            'correct_text' => $correctText,
            'is_essay_box' => $isEssayBox,
            'essay_html' => $essayHtml,
            'audio_url' => $audioUrl,
            'audio_name' => $audioName !== '' ? $audioName : 'ban-ghi-am.webm',
            'teacher_comment' => $teacherComment,
            'teacher_audio_url' => $teacherAudioUrl,
        ];
    }

    /**
     * @param  array<string, mixed>  $question
     */
    private function resolvePromptHtml(array $question, string $qType): string
    {
        $raw = trim((string) ($question['question_text'] ?? ''));

        if ($raw === '' && $qType === 'phat_am') {
            $raw = $this->parser->decodePhatAmPromptForDisplay($question);
        }

        if ($raw === '') {
            return '';
        }

        $clean = QuestionParser::stripLiveQuestionGroupLabel($raw);

        return str_contains($clean, '<') ? HtmlContent::render($clean) : nl2br(e($clean));
    }

    /**
     * @param  array<string, mixed>  $question
     * @return array<string, string>
     */
    private function decodeChoiceOptionsSafe(array $question): array
    {
        $decoded = json_decode((string) ($question['options_json'] ?? ''), true);
        if (! is_array($decoded)) {
            return [];
        }

        $out = [];
        foreach (['a', 'b', 'c', 'd', 'A', 'B', 'C', 'D'] as $key) {
            if (isset($decoded[$key]) && is_string($decoded[$key]) && trim($decoded[$key]) !== '') {
                $out[strtolower($key)] = QuestionParser::plainTextFromHtml($decoded[$key]);
            }
        }

        return $out;
    }

    private function buildOverallNote(int $correct, int $total, int $commentCount, int $scorePct): string
    {
        if ($total <= 0) {
            return 'Bài làm đã được ghi nhận trên hệ thống.';
        }

        $prefix = match (true) {
            $scorePct >= 90 => 'Kết quả rất tốt!',
            $scorePct >= 75 => 'Bạn làm bài tốt.',
            $scorePct >= 50 => 'Bạn đã hoàn thành khá tốt, hãy ôn kỹ lại các câu chưa đúng.',
            default => 'Hãy xem kỹ lại đáp án và ôn luyện thêm những phần còn nhầm lẫn nhé.',
        };

        $detail = ' Đúng '.$correct.'/'.$total.' câu.';
        if ($commentCount > 0) {
            $detail .= ' Giáo viên đã ghi nhận xét chi tiết ở '.$commentCount.' câu bên dưới.';
        }

        return $prefix.$detail;
    }

    private function initials(string $name): string
    {
        $parts = preg_split('/\s+/u', trim($name)) ?: [];
        $parts = array_values(array_filter($parts));
        if ($parts === []) {
            return 'GV';
        }
        if (count($parts) === 1) {
            return mb_strtoupper(mb_substr($parts[0], 0, 2, 'UTF-8'), 'UTF-8');
        }

        $lastTwo = array_slice($parts, -2);

        return mb_strtoupper(
            mb_substr($lastTwo[0], 0, 1, 'UTF-8').mb_substr($lastTwo[1], 0, 1, 'UTF-8'),
            'UTF-8'
        );
    }

    private function toTimestamp(mixed $value): int
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->getTimestamp();
        }

        $str = trim((string) ($value ?? ''));

        return $str !== '' ? (strtotime($str) ?: 0) : 0;
    }

    private function formatDateTime(int $ts): string
    {
        return date('H:i · d/m/Y', $ts);
    }
}
