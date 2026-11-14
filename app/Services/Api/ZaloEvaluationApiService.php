<?php

namespace App\Services\Api;

use App\Models\CourseMonthlyEvaluation;
use App\Models\User;
use App\Services\StudentEvaluationService;

class ZaloEvaluationApiService
{
    public function __construct(
        private ZaloApiSupport $api,
        private StudentEvaluationService $evaluations,
    ) {
    }

    /**
     * @return array{code: string, message: string, status: int}|null
     */
    public function ensureReady(): ?array
    {
        return $this->api->ensureTables(
            ['course_monthly_evaluations'],
            'Module đánh giá chưa được cài database.'
        );
    }

    /**
     * @return array{payload: array<string, mixed>}|array{error: array<string, mixed>}
     */
    public function weeklyEvaluationByPhone(string $phone, ?string $evalWeek): array
    {
        if ($error = $this->ensureReady()) {
            return ['error' => $error];
        }

        $student = User::findStudentByPhone($phone);

        if ($student === null) {
            return ['error' => $this->api->error('STUDENT_NOT_FOUND', 'Không tìm thấy học viên với số điện thoại này.', 404)];
        }

        $payload = $this->evaluations->build((int) $student->id, $evalWeek);

        if ($payload === null) {
            return ['error' => $this->api->error('LOAD_FAILED', 'Không thể tải đánh giá.', 500)];
        }

        $data = $this->formatForApi($payload);
        $data['student'] = $this->api->formatStudentBrief($student);

        if ($data['course'] === null) {
            $data['message'] = 'Học viên chưa tham gia khóa học đang hoạt động.';
        }

        return ['payload' => $data];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function formatForApi(array $payload): array
    {
        $course = $payload['course'] ?? null;
        $evaluation = $payload['evaluation'] ?? null;
        $scoreLabels = is_array($payload['scoreLabels'] ?? null)
            ? $payload['scoreLabels']
            : CourseMonthlyEvaluation::scoreLabels();
        $evalWeek = (string) ($payload['evalWeek'] ?? CourseMonthlyEvaluation::currentWeekKey());
        $weekOptions = is_array($payload['weekOptions'] ?? null) ? $payload['weekOptions'] : [];
        $filledWeeks = is_array($payload['filledWeeks'] ?? null) ? $payload['filledWeeks'] : [];
        $hasEvalForWeek = ! empty($payload['hasEvalForWeek']);
        $currentWeek = CourseMonthlyEvaluation::currentWeekKey();
        $weekOptionsOut = [];

        foreach ($weekOptions as $option) {
            $option = (string) $option;

            if ($option === '') {
                continue;
            }

            $weekOptionsOut[] = [
                'value' => $option,
                'label' => CourseMonthlyEvaluation::formatWeekLabel($option),
                'has_evaluation' => in_array($option, $filledWeeks, true),
            ];
        }

        $courseOut = null;

        if ($course !== null) {
            $course->loadMissing('teacher');
            $courseOut = [
                'id' => (int) $course->id,
                'title' => (string) $course->title,
                'teacher_name' => (string) ($course->teacher?->name ?? ''),
            ];
        }

        return [
            'course' => $courseOut,
            'eval_week' => $evalWeek,
            'week_label' => (string) ($payload['weekLabel'] ?? CourseMonthlyEvaluation::formatWeekLabel($evalWeek)),
            'current_week' => $currentWeek,
            'is_current_week' => $evalWeek === $currentWeek,
            'week_options' => $weekOptionsOut,
            'filled_weeks' => array_values($filledWeeks),
            'has_eval_for_week' => $hasEvalForWeek,
            'score_labels' => $scoreLabels,
            'evaluation' => $this->formatEvaluation($evaluation instanceof CourseMonthlyEvaluation ? $evaluation : null, $hasEvalForWeek, $scoreLabels, $courseOut),
            'attendance' => $this->formatAttendance(
                is_array($payload['attendanceReport'] ?? null)
                    ? $payload['attendanceReport']
                    : ['items' => [], 'attended' => 0, 'total' => 0]
            ),
        ];
    }

    /**
     * @param  array<int, string>  $scoreLabels
     * @param  array<string, mixed>|null  $courseOut
     * @return array<string, mixed>|null
     */
    private function formatEvaluation(
        ?CourseMonthlyEvaluation $evaluation,
        bool $hasEvalForWeek,
        array $scoreLabels,
        ?array $courseOut
    ): ?array {
        if (! $hasEvalForWeek || $evaluation === null) {
            return null;
        }

        $scoreMap = [
            'attitude' => 'score_attitude',
            'progress' => 'score_progress',
            'participation' => 'score_participation',
        ];
        $scores = [];
        $scoreSum = 0;
        $scoreCount = 0;

        foreach ($scoreMap as $key => $field) {
            $value = (int) ($evaluation->{$field} ?? 0);

            if ($value >= 1 && $value <= 5) {
                $scores[$key] = [
                    'value' => $value,
                    'label' => $scoreLabels[$value] ?? (string) $value,
                ];
                $scoreSum += $value;
                $scoreCount++;
            }
        }

        $averageScore = $scoreCount > 0 ? round($scoreSum / $scoreCount, 1) : 0.0;

        return [
            'id' => (int) $evaluation->id,
            'is_filled' => $evaluation->isFilled(),
            'scores' => $scores,
            'average_score' => $averageScore,
            'average_percent' => $scoreCount > 0 ? (int) round(($averageScore / 5) * 100) : 0,
            'summary' => $evaluation->summary(),
            'comment' => $evaluation->renderComment(),
            'comment_text' => $this->api->plainHtml((string) ($evaluation->comment ?? '')),
            'teacher' => [
                'id' => (int) ($evaluation->teacher_id ?? 0),
                'full_name' => trim((string) ($courseOut['teacher_name'] ?? '')),
            ],
            'updated_at' => optional($evaluation->updated_at)?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array<string, mixed>
     */
    private function formatAttendance(array $report): array
    {
        $itemsOut = [];

        foreach ($report['items'] ?? [] as $item) {
            if (! is_array($item)) {
                continue;
            }

            $session = is_array($item['session'] ?? null) ? $item['session'] : [];
            $statusLabel = (string) ($item['status_label'] ?? '');
            $sessionDate = trim((string) ($session['session_date'] ?? ''));
            $startTime = substr((string) ($session['start_time'] ?? ''), 0, 5);

            $itemsOut[] = [
                'session_id' => (int) ($session['id'] ?? 0),
                'title' => (string) ($session['title'] ?? 'Buổi học'),
                'session_date' => $sessionDate,
                'session_date_label' => $sessionDate !== '' ? date('d/m/Y', strtotime($sessionDate)) : '',
                'start_time' => $startTime,
                'status' => match ($statusLabel) {
                    'Có mặt' => 'present',
                    'Chưa diễn ra' => 'upcoming',
                    default => 'absent',
                },
                'status_label' => $statusLabel,
                'attended' => ! empty($item['attended']),
                'joined_at' => $item['joined_at'] ?? null,
            ];
        }

        return [
            'attended' => (int) ($report['attended'] ?? 0),
            'total' => (int) ($report['total'] ?? 0),
            'items' => $itemsOut,
        ];
    }
}
