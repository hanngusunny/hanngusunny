<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ManagesCourses;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseMember;
use App\Models\User;
use App\Services\CourseMemberService;
use App\Support\UserRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CourseMemberController extends Controller
{
    use ManagesCourses;

    public function __construct(private CourseMemberService $members)
    {
    }

    public function search(Request $request, int $id): JsonResponse
    {
        $course = $this->findCourse($id);
        if (! $course || $course->isCancelled()) {
            return response()->json(['ok' => false, 'message' => 'Không tìm thấy khóa học.'], 404);
        }

        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['ok' => true, 'items' => []]);
        }

        $busyIds = CourseMember::query()
            ->where('status', CourseMember::STATUS_ACTIVE)
            ->pluck('course_id', 'user_id');

        $users = User::query()
            ->where('status', 'active')
            ->whereIn('role', UserRole::learnerRoles())
            ->where(function ($query) use ($q) {
                $query->where('full_name', 'like', "%{$q}%")
                    ->orWhere('username', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('student_code', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%");
            })
            ->orderBy('full_name')
            ->limit(12)
            ->get();

        $busyTitles = Course::query()
            ->whereIn('id', $busyIds->unique()->values())
            ->pluck('title', 'id');

        return response()->json([
            'ok' => true,
            'items' => $users->map(function (User $user) use ($busyIds, $busyTitles, $course) {
                $busyCourseId = $busyIds[$user->id] ?? null;
                $sameCourse = $busyCourseId && (int) $busyCourseId === (int) $course->id;

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'student_code' => $user->student_code,
                    'phone' => $user->phone,
                    'busy' => $busyCourseId && ! $sameCourse,
                    'in_course' => (bool) $sameCourse,
                    'busy_title' => $busyCourseId ? (string) ($busyTitles[$busyCourseId] ?? '') : '',
                ];
            })->values(),
        ]);
    }

    public function store(Request $request, int $id): RedirectResponse
    {
        $course = $this->findCourse($id);
        if (! $course || $course->isCancelled()) {
            return redirect()->route('admin.courses.index')->with('error', 'Không thể thêm học viên vào khóa này.');
        }

        $studentId = (int) $request->input('student_id', 0);
        if ($studentId <= 0) {
            return redirect()->route('admin.courses.show', ['id' => $id, 'tab' => 'members'])
                ->with('error', 'Vui lòng chọn học viên.');
        }

        $result = $this->members->add($course, $studentId);
        if (! $result['ok']) {
            return redirect()->route('admin.courses.show', ['id' => $id, 'tab' => 'members'])
                ->with('error', $result['message'] ?? 'Không thêm được học viên.');
        }

        return redirect()->route('admin.courses.show', ['id' => $id, 'tab' => 'members'])
            ->with('success', 'Đã thêm học viên vào khóa.');
    }

    public function complete(int $id, int $userId): RedirectResponse
    {
        $course = $this->findCourse($id);
        if (! $course || $course->isCancelled()) {
            return redirect()->route('admin.courses.index')->with('error', 'Không thể cập nhật học viên trong khóa này.');
        }

        if (! $this->members->complete($course, $userId)) {
            return redirect()->route('admin.courses.show', ['id' => $id, 'tab' => 'members'])
                ->with('error', 'Không tìm thấy học viên đang học trong khóa.');
        }

        return redirect()->route('admin.courses.show', ['id' => $id, 'tab' => 'members'])
            ->with('success', 'Đã đánh dấu học viên hoàn thành khóa.');
    }

    public function transfer(Request $request, int $id, int $userId): RedirectResponse
    {
        $course = $this->findCourse($id);
        if (! $course || $course->isCancelled()) {
            return redirect()->route('admin.courses.index')->with('error', 'Không thể chuyển học viên.');
        }

        $to = $this->findCourse((int) $request->input('target_course_id', 0));
        if (! $to) {
            return redirect()->route('admin.courses.show', ['id' => $id, 'tab' => 'members'])
                ->with('error', 'Khóa đích không hợp lệ.');
        }

        $result = $this->members->transfer($course, $to, $userId);
        if (! $result['ok']) {
            return redirect()->route('admin.courses.show', ['id' => $id, 'tab' => 'members'])
                ->with('error', $result['message'] ?? 'Không chuyển được học viên.');
        }

        return redirect()->route('admin.courses.show', ['id' => $id, 'tab' => 'members'])
            ->with('success', 'Đã chuyển học viên sang khóa "'.$to->title.'".');
    }

    public function destroy(int $id, int $userId): RedirectResponse
    {
        $course = $this->findCourse($id);
        if (! $course || $course->isCancelled()) {
            return redirect()->route('admin.courses.index')->with('error', 'Không thể gỡ học viên.');
        }

        if (! $this->members->remove($course, $userId)) {
            return redirect()->route('admin.courses.show', ['id' => $id, 'tab' => 'members'])
                ->with('error', 'Không tìm thấy học viên đang học trong khóa.');
        }

        return redirect()->route('admin.courses.show', ['id' => $id, 'tab' => 'members'])
            ->with('success', 'Đã gỡ học viên khỏi khóa.');
    }
}
