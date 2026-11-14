<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Models\Course;
use App\Models\StudyProgram;
use App\Models\User;
use App\Support\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;

trait ManagesCourses
{
    protected function findCourse(int $id): ?Course
    {
        return Course::query()->find($id);
    }

    protected function cancelledRedirect(Course $course): RedirectResponse
    {
        return redirect()
            ->route('admin.courses.show', ['id' => $course->id, 'tab' => 'info'])
            ->with('error', 'Khóa học đã hủy — chỉ xem được thông tin.');
    }

    /**
     * @return Collection<int, User>
     */
    protected function teacherOptions(): Collection
    {
        return User::query()
            ->where('status', 'active')
            ->whereIn('role', UserRole::teachingRoles())
            ->orderBy('full_name')
            ->orderBy('username')
            ->get();
    }

    /**
     * @return Collection<int, StudyProgram>
     */
    protected function programOptions(?int $currentId = null): Collection
    {
        return StudyProgram::query()
            ->where(function ($query) use ($currentId) {
                $query->where('status', 'active');
                if ($currentId) {
                    $query->orWhere('id', $currentId);
                }
            })
            ->orderBy('title')
            ->get();
    }
}
