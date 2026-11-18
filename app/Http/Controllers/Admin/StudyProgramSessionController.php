<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ManagesStudyPrograms;
use App\Http\Controllers\Controller;
use App\Models\StudyProgramSessionQuestion;
use App\Services\ProgramCourseSyncService;
use App\Support\Questions\QuestionParser;
use App\Support\Questions\QuestionUploader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudyProgramSessionController extends Controller
{
    use ManagesStudyPrograms;

    public function __construct(private ProgramCourseSyncService $sync)
    {
    }

    public function show(Request $request, int $id, int $sessionId): View|RedirectResponse
    {
        $program = $this->findProgram($id);
        $session = $program?->sessions()->with('level')->whereKey($sessionId)->first();

        if (! $program || ! $session) {
            return redirect()->route('admin.programs.index')->with('error', 'Không tìm thấy buổi học.');
        }

        $tab = (string) $request->query('tab', 'info');
        if (! in_array($tab, ['info', 'pre', 'review', 'quiz', 'post_practice'], true)) {
            $tab = 'info';
        }

        $program->load(['knowledgeReviews', 'levels']);
        $grouped = StudyProgramSessionQuestion::groupedForSession($session->id);
        $siblings = $program->sessions()->where('level_id', $session->level_id)->orderBy('sort_order')->orderBy('id')->get();
        $index = $siblings->search(fn ($row) => (int) $row->id === (int) $session->id);

        return view('admin.programs.session', [
            'program' => $program,
            'session' => $session,
            'tab' => $tab,
            'grouped' => $grouped,
            'groups' => QuestionParser::GROUPS,
            'typeLabels' => QuestionParser::TYPE_LABELS,
            'questionTypes' => $this->extendedQuestionTypes(),
            'prevSession' => $index !== false && $index > 0 ? $siblings[$index - 1] : null,
            'nextSession' => $index !== false && $index < $siblings->count() - 1 ? $siblings[$index + 1] : null,
            'sessionNo' => $index === false ? 1 : $index + 1,
        ]);
    }

    public function update(Request $request, int $id, int $sessionId): RedirectResponse
    {
        $program = $this->findProgram($id);
        $session = $program?->sessions()->whereKey($sessionId)->first();

        if (! $program || ! $session) {
            return redirect()->route('admin.programs.index')->with('error', 'Không tìm thấy buổi học.');
        }

        if ($program->isArchived()) {
            return $this->archivedRedirect($program);
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'pre_session_materials' => ['nullable', 'string'],
            'pre_session_video_url' => ['nullable', 'string', 'max:512'],
            'materials' => ['nullable', 'string'],
            'session_summary' => ['nullable', 'string'],
            'program_knowledge_review_id' => ['nullable', 'integer'],
        ]);

        $reviewId = $data['program_knowledge_review_id'] ?? null;
        if ($reviewId && ! $program->knowledgeReviews()->whereKey($reviewId)->exists()) {
            $reviewId = null;
        }

        $session->update([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'pre_session_materials' => $data['pre_session_materials'] ?? null,
            'pre_session_video_url' => $data['pre_session_video_url'] ?? null,
            'materials' => $data['materials'] ?? null,
            'session_summary' => $data['session_summary'] ?? null,
            'program_knowledge_review_id' => $reviewId ?: null,
        ]);

        $this->sync->syncSession($session);

        return redirect()
            ->route('admin.programs.sessions.show', [$program->id, $session->id])
            ->with('success', 'Đã lưu buổi học.');
    }

    public function createQuestion(Request $request, int $id, int $sessionId, string $groupSlug, ?string $typeSlug = null): RedirectResponse
    {
        $program = $this->findProgram($id);
        $session = $program?->sessions()->whereKey($sessionId)->first();
        $group = $this->groupFromSlug($groupSlug);

        if (! $program || ! $session || ! $group) {
            return redirect()->route('admin.programs.index')->with('error', 'Không tạo được câu hỏi.');
        }

        if ($program->isArchived()) {
            return $this->archivedRedirect($program);
        }

        $type = $group === 'quiz' ? 'choice' : $this->resolveQuestionType($typeSlug ?? (string) $request->query('type'));
        $question = StudyProgramSessionQuestion::createEmpty($session->id, $group, $type);
        $this->sync->syncSessionQuestions($session->id);

        return redirect()->route('admin.programs.sessions.questions.edit', [$program->id, $session->id, $question->id]);
    }

    public function editQuestion(int $id, int $sessionId, int $questionId): View|RedirectResponse
    {
        $program = $this->findProgram($id);
        $session = $program?->sessions()->with('level')->whereKey($sessionId)->first();
        $question = $session
            ? StudyProgramSessionQuestion::query()->where('session_id', $session->id)->whereKey($questionId)->first()
            : null;

        if (! $program || ! $session || ! $question) {
            return redirect()->route('admin.programs.index')->with('error', 'Không tìm thấy câu hỏi.');
        }

        $siblings = StudyProgramSessionQuestion::query()
            ->where('session_id', $session->id)
            ->where('question_group', $question->question_group)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
        $index = $siblings->search(fn ($row) => (int) $row->id === (int) $question->id);

        return view('admin.programs.question-edit', [
            'program' => $program,
            'session' => $session,
            'question' => $question,
            'questionRow' => $question->toEditArray() + ['id' => $question->id],
            'group' => $question->question_group,
            'groupLabel' => QuestionParser::GROUPS[$question->question_group] ?? 'Câu hỏi',
            'questionNo' => $index === false ? 1 : $index + 1,
            'questionTotal' => $siblings->count(),
            'prevQuestionId' => $index !== false && $index > 0 ? $siblings[$index - 1]->id : null,
            'nextQuestionId' => $index !== false && $index < $siblings->count() - 1 ? $siblings[$index + 1]->id : null,
            'showPointsField' => false,
            'saveUrl' => route('admin.programs.sessions.questions.update', [$program->id, $session->id, $question->id]),
            'autosaveUrl' => route('admin.programs.sessions.questions.autosave', [$program->id, $session->id, $question->id]),
            'deleteUrl' => route('admin.programs.sessions.questions.destroy', [$program->id, $session->id, $question->id]),
            'listUrl' => route('admin.programs.sessions.show', [$program->id, $session->id, 'tab' => $question->question_group]),
            'pairUploadUrl' => route('admin.programs.sessions.upload.image', [$program->id, $session->id]),
            'audioUploadUrl' => route('admin.programs.sessions.upload.audio', [$program->id, $session->id]),
            'videoUploadUrl' => route('admin.programs.sessions.upload.video', [$program->id, $session->id]),
            'prevQuestionUrl' => $index !== false && $index > 0
                ? route('admin.programs.sessions.questions.edit', [$program->id, $session->id, $siblings[$index - 1]->id])
                : '',
            'nextQuestionUrl' => $index !== false && $index < $siblings->count() - 1
                ? route('admin.programs.sessions.questions.edit', [$program->id, $session->id, $siblings[$index + 1]->id])
                : '',
        ]);
    }

    public function updateQuestion(Request $request, int $id, int $sessionId, int $questionId): RedirectResponse|JsonResponse
    {
        return $this->persistQuestion($request, $id, $sessionId, $questionId, false);
    }

    public function autosaveQuestion(Request $request, int $id, int $sessionId, int $questionId): JsonResponse
    {
        $result = $this->persistQuestion($request, $id, $sessionId, $questionId, true);

        return $result instanceof JsonResponse ? $result : response()->json(['ok' => true]);
    }

    public function deleteQuestion(int $id, int $sessionId, int $questionId): RedirectResponse
    {
        $program = $this->findProgram($id);
        $session = $program?->sessions()->whereKey($sessionId)->first();
        $question = $session
            ? StudyProgramSessionQuestion::query()->where('session_id', $session->id)->whereKey($questionId)->first()
            : null;

        if (! $program || ! $session || ! $question) {
            return redirect()->route('admin.programs.index')->with('error', 'Không tìm thấy câu hỏi.');
        }

        if ($program->isArchived()) {
            return $this->archivedRedirect($program);
        }

        $group = $question->question_group;
        $question->delete();
        $this->sync->syncSessionQuestions($session->id);

        return redirect()
            ->route('admin.programs.sessions.show', [$program->id, $session->id, 'tab' => $group])
            ->with('success', 'Đã xóa câu hỏi.');
    }

    public function cloneQuestion(int $id, int $sessionId, int $questionId): RedirectResponse
    {
        $program = $this->findProgram($id);
        $session = $program?->sessions()->whereKey($sessionId)->first();
        $question = $session
            ? StudyProgramSessionQuestion::query()->where('session_id', $session->id)->whereKey($questionId)->first()
            : null;

        if (! $program || ! $session || ! $question) {
            return redirect()->route('admin.programs.index')->with('error', 'Không tìm thấy câu hỏi.');
        }

        if ($program->isArchived()) {
            return $this->archivedRedirect($program);
        }

        $clone = $question->replicate();
        $clone->sort_order = ((int) StudyProgramSessionQuestion::query()
            ->where('session_id', $session->id)
            ->where('question_group', $question->question_group)
            ->max('sort_order')) + 1;
        $clone->save();

        $this->sync->syncSessionQuestions($session->id);

        return redirect()
            ->route('admin.programs.sessions.questions.edit', [$program->id, $session->id, $clone->id])
            ->with('success', 'Đã nhân bản câu hỏi.');
    }

    public function reorderQuestions(Request $request, int $id, int $sessionId): JsonResponse
    {
        $program = $this->findProgram($id);
        $session = $program?->sessions()->whereKey($sessionId)->first();

        if (! $program || ! $session || $program->isArchived()) {
            return $this->jsonError('Không thể sắp xếp.');
        }

        $group = (string) $request->input('group', $request->input('question_group', ''));
        if (! isset(QuestionParser::GROUPS[$group])) {
            return $this->jsonError('Nhóm câu hỏi không hợp lệ.');
        }

        $order = array_map('intval', (array) $request->input('order', []));
        $ids = StudyProgramSessionQuestion::query()
            ->where('session_id', $session->id)
            ->where('question_group', $group)
            ->pluck('id')
            ->map(fn ($v) => (int) $v)
            ->all();

        if ($order === [] || count($order) !== count($ids) || array_diff($ids, $order) !== []) {
            return $this->jsonError('Danh sách câu hỏi không khớp.');
        }

        foreach ($order as $index => $questionId) {
            StudyProgramSessionQuestion::query()
                ->where('session_id', $session->id)
                ->whereKey($questionId)
                ->update(['sort_order' => $index]);
        }

        $this->sync->syncSessionQuestions($session->id);

        return response()->json(['ok' => true]);
    }

    public function uploadPairImage(Request $request, int $id, int $sessionId): JsonResponse
    {
        return $this->upload($request, $id, $sessionId, 'image');
    }

    public function uploadListeningAudio(Request $request, int $id, int $sessionId): JsonResponse
    {
        return $this->upload($request, $id, $sessionId, 'audio');
    }

    public function uploadPreSessionVideo(Request $request, int $id, int $sessionId): JsonResponse
    {
        return $this->upload($request, $id, $sessionId, 'video');
    }

    private function persistQuestion(Request $request, int $id, int $sessionId, int $questionId, bool $autosave): RedirectResponse|JsonResponse
    {
        $program = $this->findProgram($id);
        $session = $program?->sessions()->whereKey($sessionId)->first();
        $question = $session
            ? StudyProgramSessionQuestion::query()->where('session_id', $session->id)->whereKey($questionId)->first()
            : null;

        if (! $program || ! $session || ! $question) {
            return $autosave
                ? $this->jsonError('Không tìm thấy câu hỏi.', 404)
                : redirect()->route('admin.programs.index')->with('error', 'Không tìm thấy câu hỏi.');
        }

        if ($program->isArchived()) {
            return $autosave ? $this->jsonError('Chương trình đã lưu trữ.') : $this->archivedRedirect($program);
        }

        $question->updateFromPost($this->questionPayload($request));
        $this->sync->syncSessionQuestions($session->id);

        if ($autosave) {
            return response()->json(['ok' => true, 'saved_at' => now()->format('H:i')]);
        }

        return redirect()
            ->route('admin.programs.sessions.questions.edit', [$program->id, $session->id, $question->id])
            ->with('success', 'Đã lưu câu hỏi.');
    }

    private function upload(Request $request, int $id, int $sessionId, string $kind): JsonResponse
    {
        $program = $this->findProgram($id);
        $session = $program?->sessions()->whereKey($sessionId)->first();

        if (! $program || ! $session || $program->isArchived()) {
            return $this->jsonError('Không thể tải tệp.');
        }

        $field = $kind === 'audio' ? 'audio' : ($kind === 'video' ? 'video' : 'image');
        $file = $this->requireUploadedFile($request, $field);

        if (! $file) {
            return $this->jsonError('Thiếu tệp tải lên.');
        }

        $uploader = app(QuestionUploader::class);
        $path = match ($kind) {
            'audio' => $uploader->storeListeningAudio($file),
            'video' => $uploader->storePreSessionVideo($file),
            default => $uploader->storePairImage($file),
        };

        if (! $path) {
            return $this->jsonError('Tệp không hợp lệ hoặc vượt dung lượng.');
        }

        if ($kind === 'video') {
            $session->update(['pre_session_video_url' => $path]);
            $this->sync->syncSession($session);
        }

        return response()->json(['ok' => true, 'path' => $path, 'url' => asset($path)]);
    }
}
