<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseMonthlyEvaluation;
use App\Models\CourseSessionReflection;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class StudentReflectionsService
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
     * @return LengthAwarePaginator<int, CourseSessionReflection>
     */
    public function paginate(User $viewer, array $filters, int $perPage = 24): LengthAwarePaginator
    {
        $query = CourseSessionReflection::query()
            ->with([
                'user:id,full_name,username,phone',
                'session:id,course_id,title,session_date,start_time,end_time,status',
                'session.course:id,title,teacher_id',
                'session.course.teacher:id,full_name,username',
            ])
            ->where(function ($inner) {
                $inner->where(function ($comment) {
                    $comment->whereNotNull('student_comment')->where('student_comment', '!=', '');
                })->orWhere('session_rating', '>', 0);
            })
            ->whereHas('session', static function ($session) {
                $session->where('status', '!=', 'cancelled');
            })
            ->orderByDesc('updated_at')
            ->orderByDesc('id');

        if (! $viewer->isAdmin()) {
            $query->whereRaw('1 = 0');
        }

        $courseId = (int) ($filters['course_id'] ?? 0);
        if ($courseId > 0) {
            $query->whereHas('session', static fn ($session) => $session->where('course_id', $courseId));
        }

        $userId = (int) ($filters['user_id'] ?? 0);
        if ($userId > 0) {
            $query->where('user_id', $userId);
        }

        $period = trim((string) ($filters['period'] ?? ''));
        if ($period !== '') {
            $range = CourseMonthlyEvaluation::weekDateRange($period);
            if ($range !== null) {
                $query->whereHas('session', static function ($session) use ($range) {
                    $session->whereNotNull('session_date')
                        ->whereDate('session_date', '>=', $range['start'])
                        ->whereDate('session_date', '<', $range['end']);
                });
            }
        }

        $search = trim((string) ($filters['q'] ?? ''));
        if ($search !== '') {
            $query->where(function ($inner) use ($search) {
                $inner->where('student_comment', 'like', '%'.$search.'%')
                    ->orWhereHas('user', static function ($user) use ($search) {
                        $user->where('full_name', 'like', '%'.$search.'%')
                            ->orWhere('username', 'like', '%'.$search.'%')
                            ->orWhere('phone', 'like', '%'.$search.'%');
                    })
                    ->orWhereHas('session', static function ($session) use ($search) {
                        $session->where('title', 'like', '%'.$search.'%')
                            ->orWhereHas('course', static fn ($course) => $course->where('title', 'like', '%'.$search.'%'));
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
        $base = CourseSessionReflection::query()
            ->where(function ($inner) {
                $inner->where(function ($comment) {
                    $comment->whereNotNull('student_comment')->where('student_comment', '!=', '');
                })->orWhere('session_rating', '>', 0);
            })
            ->whereHas('session', static function ($session) {
                $session->where('status', '!=', 'cancelled');
            });

        if (! $viewer->isAdmin()) {
            return ['total' => 0, 'courses' => 0, 'students' => 0, 'this_week' => 0];
        }

        $range = CourseMonthlyEvaluation::weekDateRange(CourseMonthlyEvaluation::currentWeekKey());

        $thisWeek = 0;
        if ($range !== null) {
            $thisWeek = (clone $base)->whereHas('session', static function ($session) use ($range) {
                $session->whereNotNull('session_date')
                    ->whereDate('session_date', '>=', $range['start'])
                    ->whereDate('session_date', '<', $range['end']);
            })->count();
        }

        return [
            'total' => (clone $base)->count(),
            'courses' => (int) (clone $base)
                ->join('course_sessions', 'course_sessions.id', '=', 'course_session_reflections.course_session_id')
                ->selectRaw('COUNT(DISTINCT course_sessions.course_id) as aggregate')
                ->value('aggregate'),
            'students' => (int) (clone $base)->selectRaw('COUNT(DISTINCT user_id) as aggregate')->value('aggregate'),
            'this_week' => $thisWeek,
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
