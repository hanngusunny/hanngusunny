<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ManagesStudyPrograms;
use App\Http\Controllers\Controller;
use App\Models\StudyProgramAssessment;
use App\Models\StudyProgramAssessmentQuestion;
use App\Services\ProgramCourseSyncService;
use App\Support\Questions\QuestionParser;
use App\Support\Questions\QuestionUploader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudyProgramAssessmentController extends Controller
{
    use ManagesStudyPrograms;

    public function __construct(private ProgramCourseSyncService $sync)
    {
    }

    public function store(Request $request, int $id): RedirectResponse
    {
        $program = $this->findProgram($id);

        if (! $program) {
            return redirect()->route('admin.programs.index')->with('error', 'Không tìm thấy chương trình.');
        }

        if ($program->isArchived()) {
            return $this->archivedRedirect($program);
        }

        $data = $this->validateMeta($request);
        $assessment = $program->assessments()->create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'duration_minutes' => $data['duration_minutes'] ?? null,
            'sort_order' => ((int) $program->assessments()->max('sort_order')) + 1,
        ]);

        $this->sync->syncAssessment($assessment);

        return redirect()
            ->route('admin.programs.assessments.show', [$program->id, $assessment->id])
            ->with('success', 'Đã tạo bài kiểm tra.');
    }

    public function update(Request $request, int $id, int $assessmentId): RedirectResponse
    {
        [$program, $assessment] = $this->pair($id, $assessmentId);

        if (! $program || ! $assessment) {
            return redirect()->route('admin.programs.index')->with('error', 'Không tìm thấy bài kiểm tra.');
        }

        if ($program->isArchived()) {
            return $this->archivedRedirect($program);
        }

        $assessment->update($this->validateMeta($request));
        $this->sync->syncAssessment($assessment);

        return redirect()
            ->route('admin.programs.show', ['id' => $program->id, 'tab' => 'assessments'])
            ->with('success', 'Đã lưu bài kiểm tra.');
    }

    public function destroy(int $id, int $assessmentId): RedirectResponse
    {
        [$program, $assessment] = $this->pair($id, $assessmentId);

        if (! $program || ! $assessment) {
            return redirect()->route('admin.programs.index')->with('error', 'Không tìm thấy bài kiểm tra.');
        }

        if ($program->isArchived()) {
            return $this->archivedRedirect($program);
        }

        $assessment->delete();

        return redirect()
            ->route('admin.programs.show', ['id' => $program->id, 'tab' => 'assessments'])
            ->with('success', 'Đã xóa bài kiểm tra.');
    }

    public function reorder(Request $request, int $id): JsonResponse
    {
        $program = $this->findProgram($id);

        if (! $program || $program->isArchived()) {
            return $this->jsonError('Không thể sắp xếp.');
        }

        $order = array_map('intval', (array) $request->input('order', []));
        $ids = $program->assessments()->pluck('id')->map(fn ($v) => (int) $v)->all();

        if ($order === [] || count($order) !== count($ids) || array_diff($ids, $order) !== []) {
            return $this->jsonError('Danh sách bài kiểm tra không khớp.');
        }

        foreach ($order as $index => $assessmentId) {
            StudyProgramAssessment::query()->where('program_id', $program->id)->whereKey($assessmentId)->update(['sort_order' => $index]);
        }

        $this->sync->reconcileAssessments($program->id);

        return response()->json(['ok' => true]);
    }

    public function show(int $id, int $assessmentId): View|RedirectResponse
    {
        [$program, $assessment] = $this->pair($id, $assessmentId);

        if (! $program || ! $assessment) {
            return redirect()->route('admin.programs.index')->with('error', 'Không tìm thấy bài kiểm tra.');
        }

        $assessment->load('questions');

        return view('admin.programs.assessment', [
            'program' => $program,
            'assessment' => $assessment,
            'typeLabels' => QuestionParser::TYPE_LABELS,
            'questionTypes' => $this->extendedQuestionTypes(),
        ]);
    }

    public function createQuestion(Request $request, int $id, int $assessmentId, ?string $typeSlug = null): RedirectResponse
    {
        [$program, $assessment] = $this->pair($id, $assessmentId);

        if (! $program || ! $assessment) {
            return redirect()->route('admin.programs.index')->with('error', 'Không tìm thấy bài kiểm tra.');
        }

        if ($program->isArchived()) {
            return $this->archivedRedirect($program);
        }

        $type = $this->resolveQuestionType($typeSlug ?? (string) $request->query('type'));
        $question = StudyProgramAssessmentQuestion::createEmpty($assessment->id, $type);
        $this->sync->syncAssessment($assessment);

        return redirect()->route('admin.programs.assessments.questions.edit', [$program->id, $assessment->id, $question->id]);
    }

    public function editQuestion(int $id, int $assessmentId, int $questionId): View|RedirectResponse
    {
        [$program, $assessment] = $this->pair($id, $assessmentId);
        $question = $assessment
            ? StudyProgramAssessmentQuestion::query()->where('assessment_id', $assessment->id)->whereKey($questionId)->first()
            : null;

        if (! $program || ! $assessment || ! $question) {
            return redirect()->route('admin.programs.index')->with('error', 'Không tìm thấy câu hỏi.');
        }

        $siblings = $assessment->questions()->get();
        $index = $siblings->search(fn ($row) => (int) $row->id === (int) $question->id);

        return view('admin.programs.question-edit', [
            'program' => $program,
            'session' => null,
            'assessment' => $assessment,
            'question' => $question,
            'questionRow' => $question->toEditArray() + ['id' => $question->id],
            'group' => 'post_practice',
            'groupLabel' => 'Bài kiểm tra',
            'questionNo' => $index === false ? 1 : $index + 1,
            'questionTotal' => $siblings->count(),
            'prevQuestionId' => $index !== false && $index > 0 ? $siblings[$index - 1]->id : null,
            'nextQuestionId' => $index !== false && $index < $siblings->count() - 1 ? $siblings[$index + 1]->id : null,
            'showPointsField' => true,
            'saveUrl' => route('admin.programs.assessments.questions.update', [$program->id, $assessment->id, $question->id]),
            'autosaveUrl' => route('admin.programs.assessments.questions.autosave', [$program->id, $assessment->id, $question->id]),
            'deleteUrl' => route('admin.programs.assessments.questions.destroy', [$program->id, $assessment->id, $question->id]),
            'listUrl' => route('admin.programs.assessments.show', [$program->id, $assessment->id]),
            'pairUploadUrl' => route('admin.programs.assessments.upload.image', [$program->id, $assessment->id]),
            'audioUploadUrl' => route('admin.programs.assessments.upload.audio', [$program->id, $assessment->id]),
            'videoUploadUrl' => route('admin.programs.sessions.upload.video', [$program->id, $program->sessions()->value('id') ?: 0]),
            'prevQuestionUrl' => $index !== false && $index > 0
                ? route('admin.programs.assessments.questions.edit', [$program->id, $assessment->id, $siblings[$index - 1]->id])
                : '',
            'nextQuestionUrl' => $index !== false && $index < $siblings->count() - 1
                ? route('admin.programs.assessments.questions.edit', [$program->id, $assessment->id, $siblings[$index + 1]->id])
                : '',
        ]);
    }

    public function updateQuestion(Request $request, int $id, int $assessmentId, int $questionId): RedirectResponse|JsonResponse
    {
        return $this->persistQuestion($request, $id, $assessmentId, $questionId, false);
    }

    public function autosaveQuestion(Request $request, int $id, int $assessmentId, int $questionId): JsonResponse
    {
        $result = $this->persistQuestion($request, $id, $assessmentId, $questionId, true);

        return $result instanceof JsonResponse ? $result : response()->json(['ok' => true]);
    }

    public function deleteQuestion(int $id, int $assessmentId, int $questionId): RedirectResponse
    {
        [$program, $assessment] = $this->pair($id, $assessmentId);
        $question = $assessment
            ? StudyProgramAssessmentQuestion::query()->where('assessment_id', $assessment->id)->whereKey($questionId)->first()
            : null;

        if (! $program || ! $assessment || ! $question) {
            return redirect()->route('admin.programs.index')->with('error', 'Không tìm thấy câu hỏi.');
        }

        if ($program->isArchived()) {
            return $this->archivedRedirect($program);
        }

        $question->delete();
        $this->sync->syncAssessment($assessment);

        return redirect()
            ->route('admin.programs.assessments.show', [$program->id, $assessment->id])
            ->with('success', 'Đã xóa câu hỏi.');
    }

    public function cloneQuestion(int $id, int $assessmentId, int $questionId): RedirectResponse
    {
        [$program, $assessment] = $this->pair($id, $assessmentId);
        $question = $assessment
            ? StudyProgramAssessmentQuestion::query()->where('assessment_id', $assessment->id)->whereKey($questionId)->first()
            : null;

        if (! $program || ! $assessment || ! $question) {
            return redirect()->route('admin.programs.index')->with('error', 'Không tìm thấy câu hỏi.');
        }

        if ($program->isArchived()) {
            return $this->archivedRedirect($program);
        }

        $clone = $question->replicate();
        $clone->sort_order = ((int) $assessment->questions()->max('sort_order')) + 1;
        $clone->save();

        $this->sync->syncAssessment($assessment);

        return redirect()
            ->route('admin.programs.assessments.questions.edit', [$program->id, $assessment->id, $clone->id])
            ->with('success', 'Đã nhân bản câu hỏi.');
    }

    public function equalizePoints(int $id, int $assessmentId): RedirectResponse
    {
        [$program, $assessment] = $this->pair($id, $assessmentId);

        if (! $program || ! $assessment) {
            return redirect()->route('admin.programs.index')->with('error', 'Không tìm thấy bài kiểm tra.');
        }

        if ($program->isArchived()) {
            return $this->archivedRedirect($program);
        }

        $questions = $assessment->questions()->get();
        $total = $questions->count();

        if ($total > 0) {
            $each = round(100 / $total, 2);
            foreach ($questions as $q) {
                $q->update(['points' => $each]);
            }
            $this->sync->syncAssessment($assessment);
            return redirect()
                ->route('admin.programs.assessments.show', [$program->id, $assessment->id])
                ->with('success', "Đã chia đều 100 điểm ({$each} đ/câu) cho {$total} câu hỏi.");
        }

        return redirect()
            ->route('admin.programs.assessments.show', [$program->id, $assessment->id])
            ->with('error', 'Bài kiểm tra chưa có câu hỏi nào.');
    }

    public function reorderQuestions(Request $request, int $id, int $assessmentId): JsonResponse
    {
        [$program, $assessment] = $this->pair($id, $assessmentId);

        if (! $program || ! $assessment || $program->isArchived()) {
            return $this->jsonError('Không thể sắp xếp.');
        }

        $order = array_map('intval', (array) $request->input('order', []));
        $ids = $assessment->questions()->pluck('id')->map(fn ($v) => (int) $v)->all();

        if ($order === [] || count($order) !== count($ids) || array_diff($ids, $order) !== []) {
            return $this->jsonError('Danh sách câu hỏi không khớp.');
        }

        foreach ($order as $index => $questionId) {
            StudyProgramAssessmentQuestion::query()->where('assessment_id', $assessment->id)->whereKey($questionId)->update(['sort_order' => $index]);
        }

        $this->sync->syncAssessment($assessment);

        return response()->json(['ok' => true]);
    }

    public function uploadPairImage(Request $request, int $id, int $assessmentId): JsonResponse
    {
        return $this->upload($request, $id, $assessmentId, 'image');
    }

    public function uploadListeningAudio(Request $request, int $id, int $assessmentId): JsonResponse
    {
        return $this->upload($request, $id, $assessmentId, 'audio');
    }

    /**
     * @return array{0: ?\App\Models\StudyProgram, 1: ?StudyProgramAssessment}
     */
    private function pair(int $id, int $assessmentId): array
    {
        $program = $this->findProgram($id);
        $assessment = $program?->assessments()->whereKey($assessmentId)->first();

        return [$program, $assessment];
    }

    /**
     * @return array{title: string, description: ?string, duration_minutes: ?int}
     */
    private function validateMeta(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
        ], [
            'title.required' => 'Vui lòng nhập tên bài kiểm tra.',
        ]);

        $description = trim((string) ($data['description'] ?? ''));
        $data['description'] = $description !== ''
            ? \App\Support\HtmlContent::sanitize($description)
            : null;

        $data['duration_minutes'] = isset($data['duration_minutes']) && $data['duration_minutes'] !== ''
            ? (int) $data['duration_minutes']
            : null;

        return $data;
    }

    private function persistQuestion(Request $request, int $id, int $assessmentId, int $questionId, bool $autosave): RedirectResponse|JsonResponse
    {
        [$program, $assessment] = $this->pair($id, $assessmentId);
        $question = $assessment
            ? StudyProgramAssessmentQuestion::query()->where('assessment_id', $assessment->id)->whereKey($questionId)->first()
            : null;

        if (! $program || ! $assessment || ! $question) {
            return $autosave ? $this->jsonError('Không tìm thấy câu hỏi.', 404) : redirect()->route('admin.programs.index')->with('error', 'Không tìm thấy câu hỏi.');
        }

        if ($program->isArchived()) {
            return $autosave ? $this->jsonError('Chương trình đã lưu trữ.') : $this->archivedRedirect($program);
        }

        $question->updateFromPost($this->questionPayload($request));
        $this->sync->syncAssessment($assessment);

        if ($autosave) {
            return response()->json(['ok' => true, 'saved_at' => now()->format('H:i')]);
        }

        return redirect()
            ->route('admin.programs.assessments.questions.edit', [$program->id, $assessment->id, $question->id])
            ->with('success', 'Đã lưu câu hỏi.');
    }

    private function upload(Request $request, int $id, int $assessmentId, string $kind): JsonResponse
    {
        [$program, $assessment] = $this->pair($id, $assessmentId);

        if (! $program || ! $assessment || $program->isArchived()) {
            return $this->jsonError('Không thể tải tệp.');
        }

        $file = $this->requireUploadedFile($request, $kind === 'audio' ? 'audio' : 'image');

        if (! $file) {
            return $this->jsonError('Thiếu tệp tải lên.');
        }

        $uploader = app(QuestionUploader::class);
        $path = $kind === 'audio' ? $uploader->storeListeningAudio($file) : $uploader->storePairImage($file);

        if (! $path) {
            return $this->jsonError('Tệp không hợp lệ hoặc vượt dung lượng.');
        }

        return response()->json(['ok' => true, 'path' => $path, 'url' => asset($path)]);
    }
}
