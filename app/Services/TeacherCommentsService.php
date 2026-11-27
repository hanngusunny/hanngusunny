<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseMonthlyEvaluation;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class TeacherCommentsService
{
    /**
     * @return Collection<int, Course>
     */
    public function coursesForViewer(User $viewer): Collection
    {
        $query = Course::query()
            ->with(['teacher:id,full_name,username'])
            ->where('status', Course::STATUS_ACTIVE)
            ->orderBy('title')
            ->orderBy('id');

        if ($viewer->isAdmin()) {
            return $query->get();
        }

        return collect();
    }

    /**
     * @param  array{course_id?:int, user_id?:int, period?:string, q?:string}  $filters
     * @return LengthAwarePaginator<int, CourseMonthlyEvaluation>
     */
    public function paginateComments(User $viewer, array $filters, int $perPage = 24): LengthAwarePaginator
    {
        $query = CourseMonthlyEvaluation::query()
            ->with([
                'course:id,title,teacher_id',
                'student:id,full_name,username,phone',
                'teacher:id,full_name,username',
            ])
            ->where(function ($inner) {
                $inner->whereNotNull('comment')->where('comment', '!=', '');
            })
            ->orderByDesc('updated_at')
            ->orderByDesc('id');

        if (! $viewer->isAdmin()) {
            $query->whereRaw('1 = 0');
        }

        $courseId = (int) ($filters['course_id'] ?? 0);
        if ($courseId > 0) {
            $query->where('course_id', $courseId);
        }

        $userId = (int) ($filters['user_id'] ?? 0);
        if ($userId > 0) {
            $query->where('user_id', $userId);
        }

        $period = trim((string) ($filters['period'] ?? ''));
        if ($period !== '') {
            $query->where('eval_month', CourseMonthlyEvaluation::normalizePeriod($period));
        }

        $search = trim((string) ($filters['q'] ?? ''));
        if ($search !== '') {
            $query->where(function ($inner) use ($search) {
                $inner->where('comment', 'like', '%'.$search.'%')
                    ->orWhereHas('student', static function ($student) use ($search) {
                        $student->where('full_name', 'like', '%'.$search.'%')
                            ->orWhere('username', 'like', '%'.$search.'%')
                            ->orWhere('phone', 'like', '%'.$search.'%');
                    })
                    ->orWhereHas('course', static function ($course) use ($search) {
                        $course->where('title', 'like', '%'.$search.'%');
                    });
            });
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * @return array{total:int, courses:int, students:int, this_week:int}
     */
    public function stats(User $viewer): array
    {
        $base = CourseMonthlyEvaluation::query()
            ->whereNotNull('comment')
            ->where('comment', '!=', '');

        if (! $viewer->isAdmin()) {
            return ['total' => 0, 'courses' => 0, 'students' => 0, 'this_week' => 0];
        }

        $week = CourseMonthlyEvaluation::currentWeekKey();

        return [
            'total' => (clone $base)->count(),
            'courses' => (int) (clone $base)->selectRaw('COUNT(DISTINCT course_id) as aggregate')->value('aggregate'),
            'students' => (int) (clone $base)->selectRaw('COUNT(DISTINCT user_id) as aggregate')->value('aggregate'),
            'this_week' => (clone $base)->where('eval_month', $week)->count(),
        ];
    }

    /**
     * @return Collection<int, User>
     */
    public function studentsForFilter(User $viewer, int $courseId = 0): Collection
    {
        if (! $viewer->isAdmin()) {
            return collect();
        }

        $query = User::query()
            ->select('users.id', 'users.full_name', 'users.username', 'users.phone')
            ->join('course_members', 'course_members.user_id', '=', 'users.id')
            ->join('courses', 'courses.id', '=', 'course_members.course_id')
            ->where('course_members.status', 'active')
            ->where('courses.status', Course::STATUS_ACTIVE)
            ->orderBy('users.full_name')
            ->orderBy('users.id')
            ->distinct();

        if ($courseId > 0) {
            $query->where('courses.id', $courseId);
        }

        return $query->get();
    }
}
