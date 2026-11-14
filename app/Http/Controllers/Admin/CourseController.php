<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ManagesCourses;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCourseRequest;
use App\Http\Requests\Admin\UpdateCourseRequest;
use App\Models\Course;
use App\Models\CourseMember;
use App\Models\CourseSession;
use App\Models\CourseMonthlyEvaluation;
use App\Services\CourseAdminExtrasService;
use App\Services\CourseMemberService;
use App\Services\CourseScheduleService;
use App\Services\OnlineRoomService;
use App\Services\ProgramCourseSyncService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CourseController extends Controller
{
    use ManagesCourses;

    public function __construct(
        private ProgramCourseSyncService $sync,
        private CourseScheduleService $schedule,
        private CourseMemberService $members,
        private OnlineRoomService $rooms,
        private CourseAdminExtrasService $extras,
    ) {
    }

    public function index(Request $request): View
    {
        $filters = [
            'search' => trim((string) $request->query('q', '')),
            'status' => trim((string) $request->query('status', '')),
        ];

        $stats = [
            'total' => Course::query()->count(),
            'active' => Course::query()->where('status', Course::STATUS_ACTIVE)->count(),
            'members' => CourseMember::query()->where('status', CourseMember::STATUS_ACTIVE)->count(),
            'sessions' => CourseSession::query()->where('status', '!=', 'cancelled')->count(),
        ];

        $courses = Course::query()
            ->forAdmin($filters)
            ->with(['teacher', 'program'])
            ->withCount([
                'activeMembers as active_members_count',
                'members as members_count',
                'sessions',
            ])
            ->orderByRaw("CASE status WHEN 'active' THEN 0 ELSE 1 END")
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.courses.index', [
            'courses' => $courses,
            'stats' => $stats,
            'filters' => $filters,
            'emptyCourseCount' => Course::query()->withoutMembers()->count(),
        ]);
    }

    public function create(): View
    {
        return view('admin.courses.create', [
            'teachers' => $this->teacherOptions(),
            'programs' => $this->programOptions(),
            'weekdayOptions' => $this->schedule->weekdayOptions(),
            'selectedDays' => old('weekly_days', []),
            'scheduleService' => $this->schedule,
        ]);
    }

    public function store(StoreCourseRequest $request): RedirectResponse
    {
        $scheduleFields = $this->schedule->collectFields($request->all());
        $programId = (int) ($request->validated('program_id') ?: 0);

        $course = Course::query()->create(array_merge([
            'title' => $request->validated('title'),
            'description' => $request->validated('description'),
            'teacher_id' => (int) $request->validated('teacher_id'),
            'program_id' => $programId > 0 ? $programId : null,
            'status' => Course::STATUS_ACTIVE,
        ], $scheduleFields));

        if ($programId > 0) {
            $this->sync->syncProgramToCourse($programId);
        }

        $applied = $this->schedule->applyIfConfigured($course->fresh() ?? $course, $scheduleFields);
        $message = $programId > 0
            ? 'Đã tạo khóa học. Tiếp theo: kiểm tra thời gian học và thêm học viên.'
            : 'Đã tạo khóa học. Tiếp theo: thêm học viên.';

        if ($applied > 0) {
            $message .= ' Đã xếp lịch '.$applied.' buổi học theo lịch tuần.';
        }

        return redirect()
            ->route('admin.courses.show', ['id' => $course->id, 'tab' => $programId > 0 ? 'schedule' : 'members'])
            ->with('success', $message);
    }

    public function show(Request $request, int $id): View|RedirectResponse
    {
        $course = $this->findCourse($id);
        if (! $course) {
            return redirect()->route('admin.courses.index')->with('error', 'Không tìm thấy khóa học.');
        }

        $tab = (string) $request->query('tab', 'info');
        $allowed = array_merge(['info', 'members', 'schedule'], $this->extras->extraTabs());
        if (! in_array($tab, $allowed, true)) {
            $tab = 'info';
        }

        if ($course->isCancelled() && $tab !== 'info') {
            return redirect()->route('admin.courses.show', ['id' => $course->id, 'tab' => 'info']);
        }

        if ($tab === 'schedule' && ! $course->program_id) {
            return redirect()
                ->route('admin.courses.show', ['id' => $course->id, 'tab' => 'info'])
                ->with('error', 'Khóa học chưa gắn chương trình — không thể đặt thời gian học.');
        }

        $course->load(['teacher', 'program', 'room']);
        $course->loadCount(['activeMembers as active_members_count', 'sessions', 'assessments']);

        $data = [
            'course' => $course,
            'tab' => $tab,
            'teachers' => $this->teacherOptions(),
            'programs' => $this->programOptions($course->program_id ? (int) $course->program_id : null),
            'weekdayOptions' => $this->schedule->weekdayOptions(),
            'selectedDays' => old('weekly_days', $this->schedule->decodeWeekdaysForForm($course->weekly_schedule_days)),
            'scheduleService' => $this->schedule,
        ];

        if ($tab === 'members') {
            $data['activeMembers'] = $course->activeMembers()->with('user')->get()->sortBy(fn ($row) => mb_strtolower($row->user?->name ?? ''));
            $data['pastMembers'] = $course->members()
                ->with('user')
                ->whereIn('status', [CourseMember::STATUS_LEFT, CourseMember::STATUS_COMPLETED])
                ->orderByRaw('COALESCE(completed_at, left_at, updated_at) DESC')
                ->get();
            $data['transferCourses'] = Course::query()
                ->where('status', Course::STATUS_ACTIVE)
                ->where('id', '!=', $course->id)
                ->orderBy('title')
                ->get();
        }

        if ($tab === 'schedule') {
            $this->sync->ensureMissingSessionsForCourse((int) $course->id, (int) $course->program_id);
            $course->unsetRelation('sessions');
            $course->unsetRelation('assessments');
            $sessionChunk = 30;
            $sessionTotal = (int) CourseSession::query()
                ->where('course_id', $course->id)
                ->where('status', '!=', 'cancelled')
                ->count();
            $sessions = $course->sessions()
                ->select(CourseSession::scheduleListColumns())
                ->limit($sessionChunk)
                ->get();
            $shiftSessions = $course->sessions()
                ->select(['course_sessions.id', 'course_sessions.title'])
                ->get();
            $assessments = $course->assessments()->with('slots')->get();
            $weekdays = $this->schedule->resolveWeekdaysForCourse($course, $sessions->all());
            $data['course'] = $course;
            $data['sessions'] = $sessions;
            $data['shiftSessions'] = $shiftSessions;
            $data['sessionTotal'] = $sessionTotal;
            $data['sessionChunk'] = $sessionChunk;
            $data['sessionRowsUrl'] = route('admin.courses.schedule.sessions', $course->id);
            $data['assessments'] = $assessments;
            $data['onlineRoom'] = $course->room;
            $data['weekdayLabels'] = $weekdays !== [] ? $this->schedule->formatWeekdayLabels($weekdays) : '';
        }

        if ($tab === 'evaluations') {
            $data = array_merge($data, $this->extras->evaluations(
                $course,
                (string) ($request->query('tuan') ?: CourseMonthlyEvaluation::currentWeekKey())
            ));
        }

        if ($tab === 'practice') {
            $data = array_merge($data, $this->extras->practiceStats($course, max(0, (int) $request->query('buoi', 0))));
        }

        if ($tab === 'assessments') {
            $data = array_merge($data, $this->extras->assessmentResults($course, max(0, (int) $request->query('bai', 0))));
        }

        if ($tab === 'leave-requests') {
            $data = array_merge($data, $this->extras->leaveRequests($course));
        }

        if ($tab === 'homework') {
            $data = array_merge($data, $this->extras->homework($course));
        }

        return view('admin.courses.workspace', $data);
    }

    public function update(UpdateCourseRequest $request, int $id): RedirectResponse
    {
        $course = $this->findCourse($id);
        if (! $course) {
            return redirect()->route('admin.courses.index')->with('error', 'Không tìm thấy khóa học.');
        }
        if ($course->isCancelled()) {
            return $this->cancelledRedirect($course);
        }

        $oldProgramId = (int) ($course->program_id ?: 0);
        $oldTeacherId = (int) $course->teacher_id;
        $oldSchedule = [
            'schedule_start_date' => optional($course->schedule_start_date)?->format('Y-m-d'),
            'default_start_time' => $course->default_start_time,
            'default_end_time' => $course->default_end_time,
            'weekly_schedule_days' => $course->weekly_schedule_days,
        ];

        $scheduleFields = $this->schedule->collectFields($request->all());
        $programId = (int) ($request->validated('program_id') ?: 0);

        $course->fill(array_merge([
            'title' => $request->validated('title'),
            'description' => $request->validated('description'),
            'teacher_id' => (int) $request->validated('teacher_id'),
            'program_id' => $programId > 0 ? $programId : null,
        ], $scheduleFields));
        $course->save();

        if ($programId > 0 && $programId !== $oldProgramId) {
            $this->sync->syncProgramToCourse($programId);
        }

        $scheduleChanged = $oldSchedule !== [
            'schedule_start_date' => $scheduleFields['schedule_start_date'],
            'default_start_time' => $scheduleFields['default_start_time'],
            'default_end_time' => $scheduleFields['default_end_time'],
            'weekly_schedule_days' => $scheduleFields['weekly_schedule_days'],
        ];

        $applied = 0;
        if ($scheduleChanged) {
            $applied = $this->schedule->applyIfConfigured($course->fresh() ?? $course, $scheduleFields);
        }

        if ((int) $course->teacher_id !== $oldTeacherId && $course->online_room_id) {
            $this->rooms->syncSchedule($course->fresh(['sessions']) ?? $course);
        }

        $message = 'Đã cập nhật thông tin khóa học.';
        if ($applied > 0) {
            $message .= ' Đã xếp lịch '.$applied.' buổi học theo lịch tuần.';
        }

        return redirect()
            ->route('admin.courses.show', ['id' => $course->id, 'tab' => 'info'])
            ->with('success', $message);
    }

    public function cancel(int $id): RedirectResponse
    {
        $course = $this->findCourse($id);
        if (! $course) {
            return redirect()->route('admin.courses.index')->with('error', 'Không tìm thấy khóa học.');
        }
        if ($course->isCancelled()) {
            return redirect()->route('admin.courses.index')->with('error', 'Khóa học đã hủy trước đó.');
        }

        $course->update(['status' => Course::STATUS_CANCELLED]);
        $this->members->releaseActiveMembers($course);

        return redirect()->route('admin.courses.index')->with('success', 'Đã hủy khóa học. Học viên có thể tham gia khóa khác.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $course = $this->findCourse($id);
        if (! $course) {
            return redirect()->route('admin.courses.index')->with('error', 'Không tìm thấy khóa học.');
        }
        if (! $course->canBeDeleted()) {
            return redirect()->route('admin.courses.index')->with('error', 'Chỉ xóa được khóa đã hủy hoặc khóa không có học viên.');
        }

        $this->deleteCourse($course);

        return redirect()->route('admin.courses.index')->with('success', 'Đã xóa khóa học.');
    }

    public function destroyEmpty(): RedirectResponse
    {
        $courses = Course::query()->withoutMembers()->get();

        if ($courses->isEmpty()) {
            return redirect()->route('admin.courses.index')->with('error', 'Không có khóa học nào thiếu học viên để xóa.');
        }

        $deleted = 0;

        DB::transaction(function () use ($courses, &$deleted) {
            foreach ($courses as $course) {
                $this->deleteCourse($course);
                $deleted++;
            }
        });

        return redirect()->route('admin.courses.index')->with('success', 'Đã xóa '.$deleted.' khóa học không có học viên.');
    }

    private function deleteCourse(Course $course): void
    {
        $roomId = $course->online_room_id ? (int) $course->online_room_id : null;
        $course->delete();
        $this->rooms->deleteIfUnused($roomId);
    }
}
