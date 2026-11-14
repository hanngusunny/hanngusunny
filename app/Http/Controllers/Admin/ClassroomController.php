<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveClassroomRequest;
use App\Models\OnlineRoom;
use App\Services\AdminClassroomService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassroomController extends Controller
{
    public function __construct(
        private AdminClassroomService $classrooms,
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        if (! $this->classrooms->tablesReady()) {
            return redirect()->route('dashboard')->with('error', 'Phòng học Zoom chưa sẵn sàng.');
        }

        $rooms = $this->classrooms->rooms();

        return view('admin.classrooms.index', [
            'rooms' => $rooms,
            'statuses' => OnlineRoom::statusLabels(),
            'liveCount' => $rooms->where('status', 'live')->count(),
            'onlineTotal' => (int) $rooms->sum('online_count'),
        ]);
    }

    public function create(): View
    {
        return view('admin.classrooms.form', [
            'room' => null,
            'teachers' => $this->classrooms->hostOptions(),
            'statuses' => OnlineRoom::statusLabels(),
            'accessTypes' => OnlineRoom::accessTypeLabels(),
            'presetQuestions' => [],
        ]);
    }

    public function store(SaveClassroomRequest $request): RedirectResponse
    {
        $this->classrooms->create($this->payload($request));

        return redirect()->route('admin.classrooms.index')->with('success', 'Đã thêm phòng học.');
    }

    public function edit(int $id): View|RedirectResponse
    {
        $room = OnlineRoom::query()->find($id);

        if ($room === null) {
            return redirect()->route('admin.classrooms.index')->with('error', 'Không tìm thấy phòng học.');
        }

        return view('admin.classrooms.form', [
            'room' => $room,
            'teachers' => $this->classrooms->hostOptions((int) $room->host_id),
            'statuses' => OnlineRoom::statusLabels(),
            'accessTypes' => OnlineRoom::accessTypeLabels(),
            'presetQuestions' => $this->classrooms->presets((int) $room->id),
        ]);
    }

    public function update(SaveClassroomRequest $request, int $id): RedirectResponse
    {
        $room = OnlineRoom::query()->find($id);

        if ($room === null) {
            return redirect()->route('admin.classrooms.index')->with('error', 'Không tìm thấy phòng học.');
        }

        $this->classrooms->update($room, $this->payload($request));

        return redirect()->route('admin.classrooms.index')->with('success', 'Đã cập nhật phòng học.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $room = OnlineRoom::query()->find($id);

        if ($room === null) {
            return redirect()->route('admin.classrooms.index')->with('error', 'Không tìm thấy phòng học.');
        }

        $this->classrooms->delete($room);

        return redirect()->route('admin.classrooms.index')->with('success', 'Đã xóa phòng học.');
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(SaveClassroomRequest $request): array
    {
        $data = $request->validated();
        $data['scheduled_start'] = $this->normalizeDateTime($data['scheduled_start'] ?? null);
        $data['scheduled_end'] = $this->normalizeDateTime($data['scheduled_end'] ?? null);
        $data['room_password'] = trim((string) ($data['room_password'] ?? ''));
        $data['waiting_room'] = $request->boolean('waiting_room');
        $data['presets'] = is_array($request->input('presets')) ? $request->input('presets') : [];

        return $data;
    }

    private function normalizeDateTime(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        if ($value === '') {
            return null;
        }

        $ts = strtotime($value);

        return $ts !== false ? date('Y-m-d H:i:s', $ts) : null;
    }
}
