<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStudyProgramRequest;
use App\Http\Requests\Admin\UpdateStudyProgramRequest;
use App\Models\CourseSession;
use App\Models\StudyProgram;
use App\Models\StudyProgramLevel;
use App\Models\StudyProgramSession;
use App\Services\ProgramCourseSyncService;
use App\Services\StudyProgramCopyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StudyProgramController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'search' => trim((string) $request->query('q', '')),
            'status' => trim((string) $request->query('status', '')),
        ];

        $stats = [
            'total' => StudyProgram::query()->count(),
            'active' => StudyProgram::query()->where('status', 'active')->count(),
            'sessions' => StudyProgramSession::query()->count(),
            'archived' => StudyProgram::query()->where('status', 'archived')->count(),
        ];

        $programs = StudyProgram::query()
            ->forAdmin($filters)
            ->with(['teacher', 'levels', 'sessions'])
            ->withCount(['levels', 'sessions'])
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.programs.index', [
            'programs' => $programs,
            'stats' => $stats,
            'filters' => $filters,
        ]);
    }

    public function store(StoreStudyProgramRequest $request): RedirectResponse
    {
        $program = StudyProgram::query()->create([
            'title' => $request->validated('title'),
            'description' => $request->validated('description'),
            'teacher_id' => $request->user()->id,
            'status' => 'active',
        ]);

        $program->levels()->create([
            'title' => 'Trình độ 1',
            'sort_order' => 1,
        ]);

        return redirect()
            ->route('admin.programs.show', ['id' => $program->id, 'tab' => 'levels'])
            ->with('success', 'Đã tạo chương trình mới.');
    }

    public function copy(int $id, StudyProgramCopyService $copier): RedirectResponse
    {
        $program = $this->findProgram($id);

        if (! $program) {
            return redirect()->route('admin.programs.index')->with('error', 'Không tìm thấy chương trình.');
        }

        $copy = $copier->copy($program, (int) (auth()->id() ?: 0));

        return redirect()
            ->route('admin.programs.show', ['id' => $copy->id, 'tab' => 'levels'])
            ->with('success', 'Đã tạo bản sao: '.$copy->title);
    }

    public function show(Request $request, int $id): View|RedirectResponse
    {
        $program = $this->findProgram($id);

        if (! $program) {
            return redirect()->route('admin.programs.index')->with('error', 'Không tìm thấy chương trình.');
        }

        $tab = $request->query('tab', 'info');
        if (! in_array($tab, ['info', 'levels', 'assessments', 'reviews'], true)) {
            $tab = 'info';
        }

        $program->load(['teacher', 'assessments.questions', 'knowledgeReviews.questions']);
        $program->load(['levels.sessions' => function ($query) {
            $query->withCount([
                'questions',
                'questions as pre_count' => fn ($q) => $q->where('question_group', 'pre'),
                'questions as review_count' => fn ($q) => $q->where('question_group', 'review'),
                'questions as quiz_count' => fn ($q) => $q->where('question_group', 'quiz'),
                'questions as post_practice_count' => fn ($q) => $q->where('question_group', 'post_practice'),
            ])->orderBy('sort_order')->orderBy('id');
        }]);

        $selectedLevelId = (int) $request->query('level', 0);
        $selectedLevel = $program->levels->firstWhere('id', $selectedLevelId) ?? $program->levels->first();

        return view('admin.programs.workspace', [
            'program' => $program,
            'tab' => $tab,
            'selectedLevel' => $selectedLevel,
        ]);
    }

    public function refresh(int $id): RedirectResponse
    {
        $program = $this->findProgram($id);

        if (! $program) {
            return redirect()->route('admin.programs.index')->with('error', 'Không tìm thấy chương trình.');
        }

        if ($program->isArchived()) {
            return $this->archivedRedirect($program);
        }

        $result = app(ProgramCourseSyncService::class)->refreshProgramStructure($program->id);

        $parts = [
            "{$result['levels']} trình độ",
            "{$result['sessions']} buổi học",
        ];
        if ($result['removed_program_sessions'] > 0) {
            $parts[] = "đã xóa {$result['removed_program_sessions']} buổi mồ côi";
        }
        if ($result['removed_course_sessions'] > 0) {
            $parts[] = "đã dọn {$result['removed_course_sessions']} buổi trên khóa học";
        }

        return $this->levelsRedirect($program)->with(
            'success',
            'Đã cập nhật lại chương trình: '.implode(', ', $parts).'.'
        );
    }

    public function update(UpdateStudyProgramRequest $request, int $id): RedirectResponse
    {
        $program = $this->findProgram($id);

        if (! $program) {
            return redirect()->route('admin.programs.index')->with('error', 'Không tìm thấy chương trình.');
        }

        if ($program->isArchived()) {
            return $this->archivedRedirect($program);
        }

        $program->update($request->validated());

        return redirect()
            ->route('admin.programs.show', ['id' => $program->id, 'tab' => 'info'])
            ->with('success', 'Đã cập nhật chương trình.');
    }

    public function archive(int $id): RedirectResponse
    {
        $program = $this->findProgram($id);

        if (! $program) {
            return redirect()->route('admin.programs.index')->with('error', 'Không tìm thấy chương trình.');
        }

        $program->update(['status' => 'archived']);

        return redirect()
            ->route('admin.programs.show', ['id' => $program->id, 'tab' => 'info'])
            ->with('success', 'Đã lưu trữ chương trình.');
    }

    public function saveLevel(Request $request, int $id): RedirectResponse
    {
        $program = $this->findProgram($id);

        if (! $program) {
            return redirect()->route('admin.programs.index')->with('error', 'Không tìm thấy chương trình.');
        }

        if ($program->isArchived()) {
            return $this->archivedRedirect($program);
        }

        $data = $request->validate([
            'level_id' => ['nullable', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ], [
            'title.required' => 'Vui lòng nhập tên trình độ.',
        ]);

        if (! empty($data['level_id'])) {
            $level = $program->levels()->whereKey($data['level_id'])->first();

            if (! $level) {
                return $this->levelsRedirect($program)->with('error', 'Không tìm thấy trình độ.');
            }

            $level->update([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
            ]);

            return $this->levelsRedirect($program, $level->id)->with('success', 'Đã cập nhật trình độ.');
        }

        $sort = ((int) $program->levels()->max('sort_order')) + 1;
        $level = $program->levels()->create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'sort_order' => $sort,
        ]);

        return $this->levelsRedirect($program, $level->id)->with('success', 'Đã thêm trình độ.');
    }

    public function deleteLevel(int $id, int $levelId): RedirectResponse
    {
        $program = $this->findProgram($id);

        if (! $program) {
            return redirect()->route('admin.programs.index')->with('error', 'Không tìm thấy chương trình.');
        }

        if ($program->isArchived()) {
            return $this->archivedRedirect($program);
        }

        $level = $program->levels()->whereKey($levelId)->first();

        if (! $level) {
            return $this->levelsRedirect($program)->with('error', 'Không tìm thấy trình độ.');
        }

        if ($program->levels()->count() <= 1) {
            return $this->levelsRedirect($program, $levelId)->with('error', 'Cần giữ lại ít nhất một trình độ.');
        }

        $sessionIds = $level->sessions()->pluck('id')->map(fn ($v) => (int) $v)->all();
        // Dọn buổi học từng bị gỡ liên kết khi xóa trình độ (hành vi cũ).
        $orphanIds = $program->sessions()->whereNull('level_id')->pluck('id')->map(fn ($v) => (int) $v)->all();
        $deleteIds = array_values(array_unique(array_merge($sessionIds, $orphanIds)));

        DB::transaction(function () use ($level, $deleteIds) {
            $this->deleteProgramSessionsAndLinkedCourseSessions($deleteIds);
            $level->delete();
        });

        $count = count($sessionIds);
        $message = $count > 0
            ? "Đã xóa trình độ và {$count} buổi học thuộc trình độ đó."
            : 'Đã xóa trình độ.';

        return $this->levelsRedirect($program)->with('success', $message);
    }

    public function reorderLevels(Request $request, int $id): JsonResponse
    {
        $program = $this->findProgram($id);

        if (! $program || $program->isArchived()) {
            return response()->json(['ok' => false, 'message' => 'Không thể sắp xếp.'], 422);
        }

        $order = $request->input('order', []);
        if (! is_array($order) || $order === []) {
            return response()->json(['ok' => false, 'message' => 'Danh sách sắp xếp không hợp lệ.'], 422);
        }

        $ids = $program->levels()->pluck('id')->map(fn ($v) => (int) $v)->all();
        $order = array_map('intval', $order);

        if (count($order) !== count($ids) || array_diff($ids, $order) !== []) {
            return response()->json(['ok' => false, 'message' => 'Danh sách trình độ không khớp.'], 422);
        }

        foreach ($order as $index => $levelId) {
            StudyProgramLevel::query()->where('program_id', $program->id)->whereKey($levelId)->update(['sort_order' => $index + 1]);
        }

        return response()->json(['ok' => true]);
    }

    public function saveSession(Request $request, int $id): RedirectResponse
    {
        $program = $this->findProgram($id);

        if (! $program) {
            return redirect()->route('admin.programs.index')->with('error', 'Không tìm thấy chương trình.');
        }

        if ($program->isArchived()) {
            return $this->archivedRedirect($program);
        }

        $data = $request->validate([
            'session_id' => ['nullable', 'integer'],
            'level_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ], [
            'level_id.required' => 'Vui lòng chọn trình độ.',
            'title.required' => 'Vui lòng nhập tên buổi học.',
        ]);

        $level = $program->levels()->whereKey($data['level_id'])->first();

        if (! $level) {
            return $this->levelsRedirect($program)->with('error', 'Không tìm thấy trình độ.');
        }

        if (! empty($data['session_id'])) {
            $session = $program->sessions()->whereKey($data['session_id'])->first();

            if (! $session) {
                return $this->levelsRedirect($program, $level->id)->with('error', 'Không tìm thấy buổi học.');
            }

            $session->update([
                'level_id' => $level->id,
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
            ]);

            return $this->levelsRedirect($program, $level->id)->with('success', 'Đã cập nhật buổi học.');
        }

        $sort = ((int) $level->sessions()->max('sort_order')) + 1;
        $session = $level->sessions()->create([
            'program_id' => $program->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'sort_order' => $sort,
        ]);

        app(ProgramCourseSyncService::class)->reconcileSessions($program->id);
        app(ProgramCourseSyncService::class)->syncSession($session);

        return $this->levelsRedirect($program, $level->id)->with('success', 'Đã thêm buổi học.');
    }

    public function deleteSession(int $id, int $sessionId): RedirectResponse
    {
        $program = $this->findProgram($id);

        if (! $program) {
            return redirect()->route('admin.programs.index')->with('error', 'Không tìm thấy chương trình.');
        }

        if ($program->isArchived()) {
            return $this->archivedRedirect($program);
        }

        $session = $program->sessions()->whereKey($sessionId)->first();

        if (! $session) {
            return $this->levelsRedirect($program)->with('error', 'Không tìm thấy buổi học.');
        }

        $levelId = $session->level_id;
        $this->deleteProgramSessionsAndLinkedCourseSessions([(int) $session->id]);

        return $this->levelsRedirect($program, $levelId)->with('success', 'Đã xóa buổi học.');
    }

    public function reorderSessions(Request $request, int $id): JsonResponse
    {
        $program = $this->findProgram($id);

        if (! $program || $program->isArchived()) {
            return response()->json(['ok' => false, 'message' => 'Không thể sắp xếp.'], 422);
        }

        $levelId = (int) $request->input('level_id');
        $level = $program->levels()->whereKey($levelId)->first();

        if (! $level) {
            return response()->json(['ok' => false, 'message' => 'Không tìm thấy trình độ.'], 422);
        }

        $order = $request->input('order', []);
        if (! is_array($order) || $order === []) {
            return response()->json(['ok' => false, 'message' => 'Danh sách sắp xếp không hợp lệ.'], 422);
        }

        $ids = $level->sessions()->pluck('id')->map(fn ($v) => (int) $v)->all();
        $order = array_map('intval', $order);

        if (count($order) !== count($ids) || array_diff($ids, $order) !== []) {
            return response()->json(['ok' => false, 'message' => 'Danh sách buổi học không khớp.'], 422);
        }

        foreach ($order as $index => $sessionId) {
            StudyProgramSession::query()
                ->where('program_id', $program->id)
                ->where('level_id', $level->id)
                ->whereKey($sessionId)
                ->update(['sort_order' => $index + 1]);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * @param  list<int>  $sessionIds
     */
    private function deleteProgramSessionsAndLinkedCourseSessions(array $sessionIds): void
    {
        $sessionIds = array_values(array_filter(array_map('intval', $sessionIds)));

        if ($sessionIds === []) {
            return;
        }

        // course_sessions.program_session_id chỉ nullOnDelete — phải xóa thủ công.
        CourseSession::query()->whereIn('program_session_id', $sessionIds)->delete();
        StudyProgramSession::query()->whereIn('id', $sessionIds)->delete();
    }

    private function findProgram(int $id): ?StudyProgram
    {
        return StudyProgram::query()->find($id);
    }

    private function archivedRedirect(StudyProgram $program): RedirectResponse
    {
        return redirect()
            ->route('admin.programs.show', ['id' => $program->id, 'tab' => 'info'])
            ->with('error', 'Chương trình đã lưu trữ.');
    }

    private function levelsRedirect(StudyProgram $program, ?int $levelId = null): RedirectResponse
    {
        $params = ['id' => $program->id, 'tab' => 'levels'];

        if ($levelId) {
            $params['level'] = $levelId;
        }

        return redirect()->route('admin.programs.show', $params);
    }
}
