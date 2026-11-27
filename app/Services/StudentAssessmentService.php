<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseAssessment;
use App\Models\CourseAssessmentAnswer;
use App\Models\CourseAssessmentQuestion;
use App\Models\CourseAssessmentRetake;
use App\Models\CourseAssessmentSubmission;
use App\Models\CourseMember;

class StudentAssessmentService
{
    public function __construct(
        private AssessmentAttemptService $attempts,
    ) {
    }

    /**
     * @return array{assessment: CourseAssessment, course: Course}|null
     */
    public function resolveForStudent(int $assessmentId, int $userId): ?array
    {
        $assessment = CourseAssessment::query()->with(['course.teacher'])->find($assessmentId);

        if ($assessment === null) {
            return null;
        }

        $course = $assessment->course;

        if ($course === null || ! CourseMember::isMemberOf((int) $course->id, $userId)) {
            return null;
        }

        $active = CourseMember::activeCourseFor($userId);

        if ($active === null || (int) $active->id !== (int) $course->id) {
            return null;
        }

        return [
            'assessment' => $assessment,
            'course' => $course,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listForStudentCourse(int $courseId, int $userId): array
    {
        if ($courseId <= 0 || $userId <= 0) {
            return [];
        }

        CourseAssessmentSubmission::reconcileForCourse($courseId);

        $rows = [];

        $assessments = CourseAssessment::query()
            ->with('slots')
            ->where('course_id', $courseId)
            ->where('status', '!=', 'cancelled')
            ->orderBy('sort_order')
            ->orderBy('assessment_date')
            ->orderBy('start_time')
            ->orderBy('id')
            ->get();

        $counts = CourseAssessmentQuestion::query()
            ->whereIn('course_assessment_id', $assessments->pluck('id'))
            ->selectRaw('course_assessment_id, COUNT(*) AS aggregate')
            ->groupBy('course_assessment_id')
            ->pluck('aggregate', 'course_assessment_id');

        foreach ($assessments as $assessment) {
            $questionCount = (int) ($counts[$assessment->id] ?? 0);

            if ($questionCount === 0) {
                continue;
            }

            $row = $this->enrichForStudent($assessment, $userId, null, $questionCount);
            $row['display'] = $this->studentDisplay($row, $assessment);
            $row['can_open'] = $this->canStudentOpenPage($row, $assessment);
            $row['take_url'] = route('student.assessment.show', $assessment->id);
            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>|null  $questions
     * @param  int|null  $questionCount  Số câu đã biết trước (bỏ qua việc đọc & chuẩn hoá câu hỏi).
     * @return array<string, mixed>
     */
    public function enrichForStudent(CourseAssessment $assessment, int $userId, ?array $questions = null, ?int $questionCount = null): array
    {
        $assessmentId = (int) $assessment->id;

        if ($questionCount === null) {
            $questions ??= $this->readyQuestions($assessmentId);
            $questionCount = count($questions);
        }

        $hasSubmitted = CourseAssessmentAnswer::hasSubmitted($assessmentId, $userId);
        $allowRetake = CourseAssessmentRetake::isAllowed($assessmentId, $userId);
        $isGraded = ! $hasSubmitted || CourseAssessmentSubmission::isGraded($assessmentId, $userId);

        $row = $assessment->toStudentArray();
        $row['has_submitted'] = $hasSubmitted;
        $row['allow_retake'] = $allowRetake;
        $row['is_graded'] = $isGraded;
        $row['results_visible'] = $hasSubmitted && $isGraded && ! $allowRetake;
        $row['phase'] = $assessment->studentPhase($hasSubmitted, $allowRetake);
        $row['is_takeable'] = $this->isTakeableWith($assessment, $hasSubmitted, $allowRetake, $questionCount > 0);
        $row['question_count'] = $questionCount;

        return $this->attempts->enrichAssessmentTimer($row, $userId, $hasSubmitted, $allowRetake);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function readyQuestions(int $assessmentId): array
    {
        if ($assessmentId <= 0) {
            return [];
        }

        $parser = app(\App\Support\Questions\QuestionParser::class);
        $rows = [];

        foreach (
            CourseAssessmentQuestion::query()
                ->where('course_assessment_id', $assessmentId)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get() as $question
        ) {
            $row = $parser->normalizeQuestionRowForEdit($question->toArray());

            if ((int) ($row['id'] ?? 0) <= 0) {
                continue;
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * @param  list<array<string, mixed>>  $questions
     */
    public function isTakeable(CourseAssessment $assessment, bool $hasSubmitted, bool $allowRetake, array $questions): bool
    {
        return $this->isTakeableWith($assessment, $hasSubmitted, $allowRetake, $questions !== []);
    }

    private function isTakeableWith(CourseAssessment $assessment, bool $hasSubmitted, bool $allowRetake, bool $hasQuestions): bool
    {
        if (! $hasQuestions) {
            return false;
        }

        if ($hasSubmitted) {
            return $allowRetake && $assessment->isScheduleOpen();
        }

        return $assessment->isScheduleOpen();
    }

    /**
     * @param  list<array<string, mixed>>  $questions
     */
    public function canStartAssessment(CourseAssessment $assessment, bool $hasSubmitted, bool $allowRetake, array $questions): bool
    {
        if (! $assessment->isScheduleOpen() || $questions === []) {
            return false;
        }

        if ($hasSubmitted) {
            return $allowRetake;
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $assessment
     */
    public function canStudentOpenPage(array $assessment, ?CourseAssessment $model = null): bool
    {
        if (($assessment['status'] ?? '') === 'cancelled') {
            return false;
        }

        $phase = (string) ($assessment['phase'] ?? '');

        if ($phase === 'completed' || $phase === 'closed') {
            return true;
        }

        if ($phase === 'upcoming') {
            return false;
        }

        $model ??= CourseAssessment::query()->find((int) ($assessment['id'] ?? 0));

        return $model?->isScheduleOpen() ?? false;
    }

    /**
     * @param  array<string, mixed>  $assessment
     * @return array<string, mixed>
     */
    public function studentDisplay(array $assessment, ?CourseAssessment $model = null): array
    {
        $phase = (string) ($assessment['phase'] ?? 'upcoming');
        $slots = is_array($assessment['slots'] ?? null) ? $assessment['slots'] : [];
        $pick = $this->displaySlot($assessment, $slots);
        $startTime = substr((string) ($pick['start_time'] ?? $assessment['start_time'] ?? ''), 0, 5);
        $endTime = substr((string) ($pick['end_time'] ?? $assessment['end_time'] ?? ''), 0, 5);
        $dateValue = (string) ($pick['date'] ?? $assessment['assessment_date'] ?? '');
        $hasSchedule = $dateValue !== '' && $startTime !== '';
        $timeLabel = $startTime;
        $slotCount = count($slots);

        if ($startTime !== '' && $endTime !== '') {
            $timeLabel = $startTime.' – '.$endTime;
        }

        $display = [
            'phase' => $phase,
            'hasSchedule' => $hasSchedule,
            'timeLabel' => $timeLabel,
            'countdownLabel' => $this->countdownLabel($assessment, $hasSchedule, $model),
            'dateDay' => '',
            'dateMonth' => '',
            'weekdayFull' => '',
            'weekdayShort' => '',
            'dateLabel' => 'Chưa lên lịch',
        ];

        if (! $hasSchedule) {
            return $display;
        }

        $dateTs = strtotime($dateValue) ?: time();
        $weekdaysFull = ['Chủ nhật', 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7'];
        $weekdaysShort = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'];
        $weekdayIndex = (int) date('w', $dateTs);

        $display['dateDay'] = date('d', $dateTs);
        $display['dateMonth'] = date('m/Y', $dateTs);
        $display['weekdayFull'] = $weekdaysFull[$weekdayIndex] ?? '';
        $display['weekdayShort'] = $weekdaysShort[$weekdayIndex] ?? '';
        $display['dateLabel'] = date('d/m/Y', $dateTs);
        if ($slotCount > 1) {
            $display['dateLabel'] .= ' · '.$slotCount.' khung giờ';
        }

        return $display;
    }

    /**
     * @param  array<string, mixed>  $assessment
     */
    private function countdownLabel(array $assessment, bool $hasSchedule, ?CourseAssessment $model = null): string
    {
        if (($assessment['status'] ?? '') === 'cancelled') {
            return 'Đã hủy';
        }

        $phase = (string) ($assessment['phase'] ?? '');

        if ($phase === 'completed') {
            if (! empty($assessment['is_graded'])) {
                return 'Đã có kết quả';
            }

            if (! empty($assessment['has_submitted'])) {
                return 'Chờ giáo viên chấm';
            }

            return 'Đã nộp bài';
        }

        if ($phase === 'open') {
            return 'Đang mở — làm bài ngay';
        }

        if ($phase === 'retake') {
            return 'Giáo viên cho phép làm lại';
        }

        if ($phase === 'closed') {
            return 'Đã kết thúc';
        }

        if (! $hasSchedule) {
            return 'Chưa lên lịch';
        }

        $model ??= CourseAssessment::query()->find((int) ($assessment['id'] ?? 0));
        $window = $model?->window() ?? ['start' => 0];
        $diff = ($window['start'] ?? 0) - time();

        if ($diff <= 0) {
            return 'Sắp mở';
        }

        if ($diff < 3600) {
            return max(1, (int) ceil($diff / 60)).' phút nữa';
        }

        if (date('Y-m-d', $window['start']) === date('Y-m-d')) {
            if ($diff < 6 * 3600) {
                return max(1, (int) ceil($diff / 3600)).' giờ nữa';
            }

            return 'Hôm nay';
        }

        return date('d/m', $window['start']);
    }

    /**
     * @param  array<string, mixed>  $assessment
     * @param  list<array<string, mixed>>  $slots
     * @return array<string, mixed>
     */
    private function displaySlot(array $assessment, array $slots): array
    {
        if ($slots === []) {
            return [
                'date' => (string) ($assessment['assessment_date'] ?? ''),
                'start_time' => (string) ($assessment['start_time'] ?? ''),
                'end_time' => (string) ($assessment['end_time'] ?? ''),
            ];
        }

        $now = time();
        $open = null;
        $upcoming = null;
        foreach ($slots as $slot) {
            $date = (string) ($slot['date'] ?? '');
            $startTime = substr((string) ($slot['start_time'] ?? ''), 0, 5);
            if ($date === '' || $startTime === '') {
                continue;
            }
            $endTime = substr((string) ($slot['end_time'] ?? ''), 0, 5);
            $start = strtotime($date.' '.$startTime.':00') ?: 0;
            $end = $endTime !== ''
                ? (strtotime($date.' '.$endTime.':00') ?: $start)
                : $start + 7200;
            if ($end <= $start) {
                $end = $start + 7200;
            }
            if ($now >= $start && $now <= $end) {
                $open = $slot;
                break;
            }
            if ($upcoming === null && $start > $now) {
                $upcoming = $slot;
            }
        }

        $pick = $open ?? $upcoming ?? $slots[count($slots) - 1];

        return is_array($pick) ? $pick : [];
    }
}
