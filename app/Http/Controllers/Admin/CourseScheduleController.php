<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ManagesCourses;
use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseAssessment;
use App\Models\CourseAssessmentSlot;
use App\Models\CourseSession;
use App\Services\CourseScheduleService;
use App\Services\OnlineRoomService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CourseScheduleController extends Controller
{
    use ManagesCourses;

    public function __construct(
        private CourseScheduleService $schedule,
        private OnlineRoomService $rooms,
    ) {
    }

    public function save(Request $request, int $id): RedirectResponse
    {
        $course = $this->guardActiveCourse($id);
        if ($course instanceof RedirectResponse || $course instanceof JsonResponse) {
            return $course;
        }

        $this->saveSessions($course, $request->input('sessions'));
        $this->saveAssessments($course, $request->input('assessments'));
        $this->rooms->ensure($course->fresh() ?? $course);

        return redirect()
            ->route('admin.courses.show', ['id' => $id, 'tab' => 'schedule'])
            ->with('success', 'Đã lưu thời gian học và bài kiểm tra.');
    }

    public function sessionRows(Request $request, int $id): JsonResponse
    {
        $course = $this->findCourse($id);

        if ($course === null || ! $course->program_id || $course->isCancelled()) {
            return response()->json(['ok' => false, 'message' => 'Không tải được buổi học.'], 404);
        }

        $limit = min(50, max(10, (int) $request->query('limit', 30)));
        $offset = max(0, (int) $request->query('offset', 0));
        $total = (int) CourseSession::query()
            ->where('course_id', $course->id)
            ->where('status', '!=', 'cancelled')
            ->count();
        $sessions = $course->sessions()
            ->select(CourseSession::scheduleListColumns())
            ->skip($offset)
            ->take($limit)
            ->get();

        return response()->json([
            'ok' => true,
            'html' => view('admin.courses.partials.schedule-session-rows', [
                'sessions' => $sessions,
                'course' => $course,
                'indexStart' => $offset,
            ])->render(),
            'nextOffset' => $offset + $sessions->count(),
            'done' => ($offset + $sessions->count()) >= $total,
            'total' => $total,
        ]);
    }

    public function shift(Request $request, int $id): RedirectResponse
    {
        $course = $this->guardActiveCourse($id);
        if ($course instanceof RedirectResponse || $course instanceof JsonResponse) {
            return $course;
        }

        $fromSessionId = (int) $request->input('from_session_id', 0);
        $shiftDate = trim((string) $request->input('shift_date', ''));
        if ($fromSessionId <= 0) {
            return redirect()->route('admin.courses.show', ['id' => $id, 'tab' => 'schedule'])
                ->with('error', 'Vui lòng chọn buổi học bắt đầu lùi.');
        }

        $result = $this->schedule->shiftSessionsFrom($course, $fromSessionId, $shiftDate);
        if (isset($result['error'])) {
            return redirect()->route('admin.courses.show', ['id' => $id, 'tab' => 'schedule'])
                ->with('error', $result['error']);
        }

        $updated = (int) ($result['updated'] ?? 0);
        if ($updated <= 0) {
            return redirect()->route('admin.courses.show', ['id' => $id, 'tab' => 'schedule'])
                ->with('error', 'Không có buổi học nào được cập nhật.');
        }

        return redirect()->route('admin.courses.show', ['id' => $id, 'tab' => 'schedule'])
            ->with('success', 'Đã lùi lịch từ buổi '.($result['from_session_no'] ?? '').' — cập nhật '.$updated.' buổi học theo lịch tuần.');
    }

    public function updateSession(Request $request, int $id, int $sessionId): RedirectResponse|JsonResponse
    {
        $course = $this->guardActiveCourse($id);
        if ($course instanceof RedirectResponse || $course instanceof JsonResponse) {
            return $course;
        }

        if (! $this->applySessionUpdate($course, $sessionId, $request->only(['session_date', 'start_time', 'end_time', 'record_video_url']))) {
            return $this->scheduleReply($request, $id, 'Không tìm thấy buổi học.', false);
        }

        $this->rooms->ensure($course->fresh() ?? $course);

        return $this->scheduleReply($request, $id, 'Đã lưu thời gian buổi học.');
    }

    public function updateAssessment(Request $request, int $id, int $assessmentId): RedirectResponse|JsonResponse
    {
        $course = $this->guardActiveCourse($id);
        if ($course instanceof RedirectResponse || $course instanceof JsonResponse) {
            return $course;
        }

        if (! $this->applyAssessmentUpdate($course, $assessmentId, $request->only(['assessment_date', 'start_time', 'end_time']))) {
            return $this->scheduleReply($request, $id, 'Không tìm thấy bài kiểm tra.', false);
        }

        return $this->scheduleReply($request, $id, 'Đã lưu thời gian bài kiểm tra.');
    }

    public function storeAssessmentSlot(Request $request, int $id, int $assessmentId): JsonResponse
    {
        $course = $this->guardActiveCourse($id);
        if ($course instanceof RedirectResponse || $course instanceof JsonResponse) {
            return $course instanceof JsonResponse
                ? $course
                : response()->json(['ok' => false, 'message' => 'Không tìm thấy khóa học.'], 422);
        }

        $assessment = $this->findCourseAssessment($course, $assessmentId);
        if (! $assessment) {
            return response()->json(['ok' => false, 'message' => 'Không tìm thấy bài kiểm tra.'], 422);
        }

        $slot = $assessment->slots()->create([
            'slot_date' => null,
            'start_time' => null,
            'end_time' => null,
            'sort_order' => ((int) $assessment->slots()->max('sort_order')) + 1,
        ]);
        $assessment->unsetRelation('slots');
        $assessment->syncScheduleFromSlots();

        return response()->json([
            'ok' => true,
            'message' => 'Đã thêm khung giờ.',
            'html' => view('admin.courses.partials.schedule-assessment-slot-row', [
                'course' => $course,
                'assessment' => $assessment,
                'slot' => $slot,
            ])->render(),
        ]);
    }

    public function updateAssessmentSlot(Request $request, int $id, int $assessmentId, int $slotId): RedirectResponse|JsonResponse
    {
        $course = $this->guardActiveCourse($id);
        if ($course instanceof RedirectResponse || $course instanceof JsonResponse) {
            return $course;
        }

        $slot = $this->findCourseAssessmentSlot($course, $assessmentId, $slotId);
        if (! $slot) {
            return $this->scheduleReply($request, $id, 'Không tìm thấy khung giờ.', false);
        }

        $this->applySlotUpdate($slot, $request->only(['slot_date', 'start_time', 'end_time']));
        $slot->assessment?->unsetRelation('slots');
        $slot->assessment?->syncScheduleFromSlots();

        return $this->scheduleReply($request, $id, 'Đã lưu khung giờ bài kiểm tra.');
    }

    public function destroyAssessmentSlot(Request $request, int $id, int $assessmentId, int $slotId): JsonResponse
    {
        $course = $this->guardActiveCourse($id);
        if ($course instanceof RedirectResponse || $course instanceof JsonResponse) {
            return $course instanceof JsonResponse
                ? $course
                : response()->json(['ok' => false, 'message' => 'Không tìm thấy khóa học.'], 422);
        }

        $slot = $this->findCourseAssessmentSlot($course, $assessmentId, $slotId);
        if (! $slot) {
            return response()->json(['ok' => false, 'message' => 'Không tìm thấy khung giờ.'], 422);
        }

        $assessment = $slot->assessment;
        $slot->delete();
        $assessment?->unsetRelation('slots');
        $assessment?->syncScheduleFromSlots();

        return response()->json(['ok' => true, 'message' => 'Đã xóa khung giờ.']);
    }

    private function guardActiveCourse(int $id): Course|RedirectResponse|JsonResponse
    {
        $course = $this->findCourse($id);
        if (! $course) {
            return $this->scheduleGuardFail(request(), 'Không tìm thấy khóa học.', route('admin.courses.index'));
        }
        if (! $course->program_id) {
            return $this->scheduleGuardFail(
                request(),
                'Khóa học chưa gắn chương trình.',
                route('admin.courses.show', ['id' => $id, 'tab' => 'info'])
            );
        }
        if ($course->isCancelled()) {
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json(['ok' => false, 'message' => 'Khóa học đã hủy.'], 422);
            }

            return $this->cancelledRedirect($course);
        }

        return $course;
    }

    private function scheduleGuardFail(Request $request, string $message, string $redirectUrl): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['ok' => false, 'message' => $message], 422);
        }

        return redirect()->to($redirectUrl)->with('error', $message);
    }

    private function scheduleReply(Request $request, int $courseId, string $message, bool $ok = true): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['ok' => $ok, 'message' => $message], $ok ? 200 : 422);
        }

        $redirect = redirect()->route('admin.courses.show', ['id' => $courseId, 'tab' => 'schedule']);

        return $ok ? $redirect->with('success', $message) : $redirect->with('error', $message);
    }

    private function saveSessions(Course $course, mixed $rows): void
    {
        if (! is_array($rows)) {
            return;
        }

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $sessionId = (int) ($row['id'] ?? 0);
            if ($sessionId > 0) {
                $this->applySessionUpdate($course, $sessionId, $row);
            }
        }
    }

    private function saveAssessments(Course $course, mixed $rows): void
    {
        if (! is_array($rows)) {
            return;
        }

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $assessmentId = (int) ($row['id'] ?? 0);
            if ($assessmentId > 0) {
                $this->applyAssessmentUpdate($course, $assessmentId, $row);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function applySessionUpdate(Course $course, int $sessionId, array $data): bool
    {
        $session = CourseSession::query()
            ->where('course_id', $course->id)
            ->whereKey($sessionId)
            ->where('status', '!=', 'cancelled')
            ->first();

        if (! $session) {
            return false;
        }

        $date = trim((string) ($data['session_date'] ?? ''));
        $record = trim((string) ($data['record_video_url'] ?? ''));
        $payload = ['record_video_url' => $record !== '' ? $record : null];

        if ($date === '') {
            $session->update(array_merge($payload, [
                'session_date' => null,
                'start_time' => null,
                'end_time' => null,
            ]));

            return true;
        }

        $startTime = $this->schedule->normalizeClock((string) ($data['start_time'] ?? ''));
        if ($startTime === null) {
            $session->update($payload);

            return true;
        }

        $session->update(array_merge($payload, [
            'session_date' => $date,
            'start_time' => $startTime,
            'end_time' => $this->schedule->normalizeClock((string) ($data['end_time'] ?? '')),
        ]));

        return true;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function applyAssessmentUpdate(Course $course, int $assessmentId, array $data): bool
    {
        $assessment = CourseAssessment::query()
            ->where('course_id', $course->id)
            ->whereKey($assessmentId)
            ->where('status', '!=', 'cancelled')
            ->first();

        if (! $assessment) {
            return false;
        }

        if (! array_key_exists('assessment_date', $data)) {
            return true;
        }

        if ($assessment->slots()->exists()) {
            return true;
        }

        $date = trim((string) ($data['assessment_date'] ?? ''));
        if ($date === '') {
            $assessment->update([
                'assessment_date' => null,
                'start_time' => null,
                'end_time' => null,
            ]);

            return true;
        }

        $startTime = $this->schedule->normalizeClock((string) ($data['start_time'] ?? ''));
        if ($startTime === null) {
            return true;
        }

        $assessment->slots()->create([
            'slot_date' => $date,
            'start_time' => $startTime,
            'end_time' => $this->schedule->normalizeClock((string) ($data['end_time'] ?? '')),
            'sort_order' => 1,
        ]);
        $assessment->unsetRelation('slots');
        $assessment->syncScheduleFromSlots();

        return true;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function applySlotUpdate(CourseAssessmentSlot $slot, array $data): void
    {
        $date = trim((string) ($data['slot_date'] ?? ''));
        if ($date === '') {
            $slot->update([
                'slot_date' => null,
                'start_time' => null,
                'end_time' => null,
            ]);

            return;
        }

        $startTime = $this->schedule->normalizeClock((string) ($data['start_time'] ?? ''));
        $slot->update([
            'slot_date' => $date,
            'start_time' => $startTime,
            'end_time' => $this->schedule->normalizeClock((string) ($data['end_time'] ?? '')),
        ]);
    }

    private function findCourseAssessment(Course $course, int $assessmentId): ?CourseAssessment
    {
        return CourseAssessment::query()
            ->where('course_id', $course->id)
            ->whereKey($assessmentId)
            ->where('status', '!=', 'cancelled')
            ->first();
    }

    private function findCourseAssessmentSlot(Course $course, int $assessmentId, int $slotId): ?CourseAssessmentSlot
    {
        return CourseAssessmentSlot::query()
            ->whereKey($slotId)
            ->where('course_assessment_id', $assessmentId)
            ->whereHas('assessment', function ($query) use ($course) {
                $query->where('course_id', $course->id)->where('status', '!=', 'cancelled');
            })
            ->first();
    }
}
