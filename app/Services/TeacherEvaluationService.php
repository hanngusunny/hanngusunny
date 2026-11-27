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
    public const TAB_ALL = 'all';

    public const TAB_IN_PROGRESS = 'in-progress';

    public const TAB_DONE = 'done';

    /**
     * @return array<string, mixed>|null
     */
    public function build(
        int $teacherId,
        int $courseId,
        int $userId,
        string $evalWeek,
        string $activeTab = self::TAB_ALL,
        string $search = '',
    ): ?array {
        if ($teacherId <= 0 || ! $this->tablesReady()) {
            return null;
        }

        $courses = Course::activeForTeacher($teacherId);

        if ($courses->isEmpty()) {
            return null;
        }

        $requestedCourseId = $courseId;
        $isCourseSelected = $requestedCourseId > 0 && $courses->contains('id', $requestedCourseId);

        if (! $isCourseSelected) {
            $courseId = (int) $courses->first()?->id;
        }

        $course = $courses->firstWhere('id', $courseId);

        if ($course === null) {
            return null;
        }

        $evalWeek = CourseMonthlyEvaluation::normalizePeriod($evalWeek);
        $activeTab = $this->normalizeTab($activeTab);
        $search = trim($search);
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
            $courseMembers = $id === $courseId
                ? $members
                : CourseMember::activeMembersForCourse($id);
            $courseEvalMap = $id === $courseId
                ? $evalMap
                : CourseMonthlyEvaluation::mapForCoursePeriod($id, $evalWeek);
            $done = 0;

            foreach ($courseMembers as $courseMember) {
                $uid = (int) $courseMember->user_id;
                $eval = $courseEvalMap[$uid] ?? null;

                if ($eval !== null && $eval->isFilled()) {
                    $done++;
                }
            }

            $memberTotal = $courseMembers->count();
            $isCompleted = $memberTotal > 0 && $done >= $memberTotal;
            $progressPct = $memberTotal > 0 ? min(100, (int) round(($done / $memberTotal) * 100)) : 0;

            $courseSummaries[] = [
                'course' => $row,
                'member_count' => $memberTotal,
                'evaluated' => $done,
                'progress_pct' => $progressPct,
                'is_completed' => $isCompleted,
                'status' => $isCompleted ? self::TAB_DONE : self::TAB_IN_PROGRESS,
                'session_count' => $id === $courseId
                    ? $weekSessionCount
                    : CourseSession::scheduledForWeek($id, $evalWeek)->count(),
            ];
        }

        $needle = mb_strtolower($search, 'UTF-8');
        $filteredCourseSummaries = array_values(array_filter(
            $courseSummaries,
            static function (array $summary) use ($activeTab, $needle): bool {
                if ($activeTab !== self::TAB_ALL && ($summary['status'] ?? '') !== $activeTab) {
                    return false;
                }

                if ($needle !== '') {
                    $title = mb_strtolower((string) ($summary['course']?->title ?? ''), 'UTF-8');
                    if (! str_contains($title, $needle)) {
                        return false;
                    }
                }

                return true;
            }
        ));

        return [
            'courses' => $courses,
            'courseSummaries' => $courseSummaries,
            'filteredCourseSummaries' => $filteredCourseSummaries,
            'isCourseSelected' => $isCourseSelected,
            'activeTab' => $activeTab,
            'search' => $search,
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

    public function normalizeTab(string $tab): string
    {
        return match ($tab) {
            self::TAB_IN_PROGRESS, self::TAB_DONE => $tab,
            default => self::TAB_ALL,
        };
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    public function pageUrl(int $courseId, int $userId, string $evalWeek, array $extra = []): string
    {
        $params = array_merge([
            'khoa' => $courseId > 0 ? $courseId : null,
            'hoc-vien' => $userId > 0 ? $userId : null,
            'tuan' => $evalWeek !== '' ? $evalWeek : null,
        ], $extra);

        if (($params['tab'] ?? '') === self::TAB_ALL) {
            unset($params['tab']);
        }

        return route('teacher.evaluations', array_filter(
            $params,
            static fn ($value) => $value !== null && $value !== ''
        ));
    }

    private function tablesReady(): bool
    {
        return Schema::hasTable('course_monthly_evaluations')
            && Schema::hasTable('courses')
            && Schema::hasTable('course_members');
    }
}
