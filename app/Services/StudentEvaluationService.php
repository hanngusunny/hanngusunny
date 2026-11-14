<?php

namespace App\Services;

use App\Models\CourseMember;
use App\Models\CourseMonthlyEvaluation;
use App\Models\CourseSessionAttendance;
use Illuminate\Support\Facades\Schema;

class StudentEvaluationService
{
    /**
     * @return array<string, mixed>|null
     */
    public function build(int $userId, ?string $evalWeek = null): ?array
    {
        if ($userId <= 0) {
            return null;
        }

        if (! Schema::hasTable('course_members') || ! Schema::hasTable('course_monthly_evaluations')) {
            return $this->emptyPayload();
        }

        $course = CourseMember::activeCourseFor($userId);
        $scoreLabels = CourseMonthlyEvaluation::scoreLabels();
        $filledWeeks = [];
        $weekOptions = CourseMonthlyEvaluation::weekOptions(20);
        $evalWeek = CourseMonthlyEvaluation::resolveStudentEvalWeek([], $evalWeek);
        $evaluation = null;
        $hasEvalForWeek = false;
        $attendanceReport = ['items' => [], 'attended' => 0, 'total' => 0];

        if ($course !== null) {
            $course->loadMissing('teacher');
            $courseId = (int) $course->id;
            $filledWeeks = CourseMonthlyEvaluation::filledWeeklyPeriodsForStudent($courseId, $userId);
            $evalWeek = CourseMonthlyEvaluation::resolveStudentEvalWeek($filledWeeks, $evalWeek);
            $weekOptions = CourseMonthlyEvaluation::weekPickerOptionsForStudent($filledWeeks, 20);
            $evaluation = CourseMonthlyEvaluation::forStudent($courseId, $userId, $evalWeek);
            $hasEvalForWeek = $evaluation !== null && $evaluation->isFilled();

            if (Schema::hasTable('course_session_attendance')) {
                $attendanceReport = CourseSessionAttendance::studentWeekReport($courseId, $userId, $evalWeek);
            }
        }

        return [
            'course' => $course,
            'evaluation' => $evaluation,
            'scoreLabels' => $scoreLabels,
            'evalWeek' => $evalWeek,
            'weekLabel' => CourseMonthlyEvaluation::formatWeekLabel($evalWeek),
            'weekOptions' => $weekOptions,
            'filledWeeks' => $filledWeeks,
            'hasEvalForWeek' => $hasEvalForWeek,
            'attendanceReport' => $attendanceReport,
            'currentWeek' => CourseMonthlyEvaluation::currentWeekKey(),
        ];
    }

    public function pageUrl(string $evalWeek): string
    {
        return route('student.evaluations', ['tuan' => $evalWeek]);
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyPayload(): array
    {
        $evalWeek = CourseMonthlyEvaluation::currentWeekKey();

        return [
            'course' => null,
            'evaluation' => null,
            'scoreLabels' => CourseMonthlyEvaluation::scoreLabels(),
            'evalWeek' => $evalWeek,
            'weekLabel' => CourseMonthlyEvaluation::formatWeekLabel($evalWeek),
            'weekOptions' => [],
            'filledWeeks' => [],
            'hasEvalForWeek' => false,
            'attendanceReport' => ['items' => [], 'attended' => 0, 'total' => 0],
            'currentWeek' => $evalWeek,
        ];
    }
}
