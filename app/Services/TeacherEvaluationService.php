<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseMember;
use App\Models\CourseMonthlyEvaluation;
use App\Models\CourseSession;
use App\Models\CourseSessionAttendance;
use Illuminate\Support\Facades\Schema;

class TeacherEvaluationService
{
    /**
     * @return array<string, mixed>|null
     */
    public function build(int $teacherId, int $courseId, int $userId, string $evalWeek): ?array
    {
        if ($teacherId <= 0 || ! $this->tablesReady()) {
            return null;
        }

        $courses = Course::activeForTeacher($teacherId);

        if ($courses->isEmpty()) {
            return null;
        }

        if ($courseId <= 0 || ! $courses->contains('id', $courseId)) {
            $courseId = (int) $courses->first()?->id;
        }

        $course = $courses->firstWhere('id', $courseId);

        if ($course === null) {
            return null;
        }

        $evalWeek = CourseMonthlyEvaluation::normalizePeriod($evalWeek);
        $weekOptions = CourseMonthlyEvaluation::weekOptions(20);
        $members = CourseMember::activeMembersForCourse($courseId);
        $evalMap = CourseMonthlyEvaluation::mapForCoursePeriod($courseId, $evalWeek);
        $attendanceMap = CourseSessionAttendance::summaryMapForCourseWeek($courseId, $evalWeek);
        $weekSessionCount = CourseSession::scheduledForWeek($courseId, $evalWeek)->count();

        if ($userId <= 0 && $members->isNotEmpty()) {
            $userId = (int) $members->first()?->user_id;
        }

        $member = $members->firstWhere('user_id', $userId);

        if ($member === null && $members->isNotEmpty()) {
            $member = $members->first();
            $userId = (int) ($member?->user_id ?? 0);
        }

        $evaluation = $userId > 0
            ? CourseMonthlyEvaluation::forStudent($courseId, $userId, $evalWeek)
            : null;

        $evaluatedCount = 0;

        foreach ($members as $row) {
            $uid = (int) $row->user_id;
            $eval = $evalMap[$uid] ?? null;

            if ($eval !== null && $eval->isFilled()) {
                $evaluatedCount++;
            }
        }

        $courseSummaries = [];

        foreach ($courses as $row) {
            $id = (int) $row->id;
            $courseMembers = CourseMember::activeMembersForCourse($id);
            $courseEvalMap = CourseMonthlyEvaluation::mapForCoursePeriod($id, $evalWeek);
            $done = 0;

            foreach ($courseMembers as $courseMember) {
                $uid = (int) $courseMember->user_id;
                $eval = $courseEvalMap[$uid] ?? null;

                if ($eval !== null && $eval->isFilled()) {
                    $done++;
                }
            }

            $courseSummaries[] = [
                'course' => $row,
                'member_count' => $courseMembers->count(),
                'evaluated' => $done,
                'session_count' => CourseSession::scheduledForWeek($id, $evalWeek)->count(),
            ];
        }

        return [
            'courses' => $courses,
            'courseSummaries' => $courseSummaries,
            'course' => $course,
            'courseId' => $courseId,
            'members' => $members,
            'member' => $member,
            'userId' => $userId,
            'evalWeek' => $evalWeek,
            'weekLabel' => CourseMonthlyEvaluation::formatWeekLabel($evalWeek),
            'weekOptions' => $weekOptions,
            'evalMap' => $evalMap,
            'attendanceMap' => $attendanceMap,
            'weekSessionCount' => $weekSessionCount,
            'evaluatedCount' => $evaluatedCount,
            'evaluation' => $evaluation,
            'canEdit' => CourseMonthlyEvaluation::canEditPeriod($evalWeek),
            'scoreLabels' => CourseMonthlyEvaluation::scoreLabels(),
        ];
    }

    public function pageUrl(int $courseId, int $userId, string $evalWeek): string
    {
        return route('teacher.evaluations', array_filter([
            'khoa' => $courseId > 0 ? $courseId : null,
            'hoc-vien' => $userId > 0 ? $userId : null,
            'tuan' => $evalWeek !== '' ? $evalWeek : null,
        ], static fn ($value) => $value !== null && $value !== ''));
    }

    private function tablesReady(): bool
    {
        return Schema::hasTable('course_monthly_evaluations')
            && Schema::hasTable('courses')
            && Schema::hasTable('course_members');
    }
}
