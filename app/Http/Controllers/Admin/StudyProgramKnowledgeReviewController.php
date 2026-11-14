<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\ManagesStudyPrograms;
use App\Http\Controllers\Controller;
use App\Models\StudyProgramKnowledgeReview;
use App\Models\StudyProgramKnowledgeReviewQuestion;
use App\Services\ProgramCourseSyncService;
use App\Support\Questions\QuestionParser;
use App\Support\Questions\QuestionUploader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudyProgramKnowledgeReviewController extends Controller
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
        $review = $program->knowledgeReviews()->create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'duration_minutes' => $data['duration_minutes'] ?? null,
            'sort_order' => ((int) $program->knowledgeReviews()->max('sort_order')) + 1,
        ]);

        $this->sync->syncKnowledgeReview($review);

        return redirect()
            ->route('admin.programs.reviews.show', [$program->id, $review->id])
            ->with('success', 'Đã tạo ôn tập kiến thức.');
    }

    public function update(Request $request, int $id, int $reviewId): RedirectResponse
    {
        [$program, $review] = $this->pair($id, $reviewId);

        if (! $program || ! $review) {
            return redirect()->route('admin.programs.index')->with('error', 'Không tìm thấy ôn tập kiến thức.');
        }

        if ($program->isArchived()) {
            return $this->archivedRedirect($program);
        }

        $review->update($this->validateMeta($request));
        $this->sync->syncKnowledgeReview($review);

        return redirect()
            ->route('admin.programs.reviews.show', ['id' => $program->id, 'reviewId' => $review->id, 'tab' => 'info'])
            ->with('success', 'Đã lưu ôn tập kiến thức.');
    }

    public function destroy(int $id, int $reviewId): RedirectResponse
    {
        [$program, $review] = $this->pair($id, $reviewId);

        if (! $program || ! $review) {
            return redirect()->route('admin.programs.index')->with('error', 'Không tìm thấy ôn tập kiến thức.');
        }

        if ($program->isArchived()) {
            return $this->archivedRedirect($program);
        }

        $review->delete();

        return redirect()
            ->route('admin.programs.show', ['id' => $program->id, 'tab' => 'reviews'])
            ->with('success', 'Đã xóa ôn tập kiến thức.');
    }

    public function reorder(Request $request, int $id): JsonResponse
    {
        $program = $this->findProgram($id);

        if (! $program || $program->isArchived()) {
            return $this->jsonError('Không thể sắp xếp.');
        }

        $order = array_map('intval', (array) $request->input('order', []));
        $ids = $program->knowledgeReviews()->pluck('id')->map(fn ($v) => (int) $v)->all();

        if ($order === [] || count($order) !== count($ids) || array_diff($ids, $order) !== []) {
            return $this->jsonError('Danh sách ôn tập không khớp.');
        }

        foreach ($order as $index => $reviewId) {
            StudyProgramKnowledgeReview::query()->where('program_id', $program->id)->whereKey($reviewId)->update(['sort_order' => $index]);
        }

        $this->sync->reconcileKnowledgeReviews($program->id);

        return response()->json(['ok' => true]);
    }

    public function show(Request $request, int $id, int $reviewId): View|RedirectResponse
    {
        [$program, $review] = $this->pair($id, $reviewId);

        if (! $program || ! $review) {
            return redirect()->route('admin.programs.index')->with('error', 'Không tìm thấy ôn tập kiến thức.');
        }

        $tab = (string) $request->query('tab', 'info');
        if (! in_array($tab, ['info', 'questions'], true)) {
            $tab = 'info';
        }

        $review->load('questions');
        $siblings = $program->knowledgeReviews()->orderBy('sort_order')->orderBy('id')->get();
        $index = $siblings->search(fn ($row) => (int) $row->id === (int) $review->id);

        return view('admin.programs.review', [
            'program' => $program,
            'review' => $review,
            'tab' => $tab,
            'typeLabels' => QuestionParser::TYPE_LABELS,
            'questionTypes' => $this->extendedQuestionTypes(),
            'prevReview' => $index !== false && $index > 0 ? $siblings[$index - 1] : null,
            'nextReview' => $index !== false && $index < $siblings->count() - 1 ? $siblings[$index + 1] : null,
            'reviewNo' => $index === false ? 1 : $index + 1,
        ]);
    }

    public function createQuestion(Request $request, int $id, int $reviewId, ?string $typeSlug = null): RedirectResponse
    {
        [$program, $review] = $this->pair($id, $reviewId);

        if (! $program || ! $review) {
            return redirect()->route('admin.programs.index')->with('error', 'Không tìm thấy ôn tập kiến thức.');
        }

        if ($program->isArchived()) {
            return $this->archivedRedirect($program);
        }

        $type = $this->resolveQuestionType($typeSlug ?? (string) $request->query('type'));
        $question = StudyProgramKnowledgeReviewQuestion::createEmpty($review->id, $type);
        $this->sync->syncKnowledgeReview($review);

        return redirect()->route('admin.programs.reviews.questions.edit', [$program->id, $review->id, $question->id]);
    }

    public function editQuestion(int $id, int $reviewId, int $questionId): View|RedirectResponse
    {
        [$program, $review] = $this->pair($id, $reviewId);
        $question = $review
            ? StudyProgramKnowledgeReviewQuestion::query()->where('knowledge_review_id', $review->id)->whereKey($questionId)->first()
            : null;

        if (! $program || ! $review || ! $question) {
            return redirect()->route('admin.programs.index')->with('error', 'Không tìm thấy câu hỏi.');
        }

        $siblings = $review->questions()->get();
        $index = $siblings->search(fn ($row) => (int) $row->id === (int) $question->id);

        return view('admin.programs.question-edit', [
            'program' => $program,
            'session' => null,
            'review' => $review,
            'question' => $question,
            'questionRow' => $question->toEditArray() + ['id' => $question->id],
            'group' => 'post_practice',
            'groupLabel' => 'Ôn tập kiến thức',
            'questionNo' => $index === false ? 1 : $index + 1,
            'questionTotal' => $siblings->count(),
            'prevQuestionId' => $index !== false && $index > 0 ? $siblings[$index - 1]->id : null,
            'nextQuestionId' => $index !== false && $index < $siblings->count() - 1 ? $siblings[$index + 1]->id : null,
            'showPointsField' => false,
            'saveUrl' => route('admin.programs.reviews.questions.update', [$program->id, $review->id, $question->id]),
            'autosaveUrl' => route('admin.programs.reviews.questions.autosave', [$program->id, $review->id, $question->id]),
            'deleteUrl' => route('admin.programs.reviews.questions.destroy', [$program->id, $review->id, $question->id]),
            'listUrl' => route('admin.programs.reviews.show', [$program->id, $review->id]),
            'pairUploadUrl' => route('admin.programs.reviews.upload.image', [$program->id, $review->id]),
            'audioUploadUrl' => route('admin.programs.reviews.upload.audio', [$program->id, $review->id]),
            'videoUploadUrl' => route('admin.programs.sessions.upload.video', [$program->id, $program->sessions()->value('id') ?: 0]),
            'prevQuestionUrl' => $index !== false && $index > 0
                ? route('admin.programs.reviews.questions.edit', [$program->id, $review->id, $siblings[$index - 1]->id])
                : '',
            'nextQuestionUrl' => $index !== false && $index < $siblings->count() - 1
                ? route('admin.programs.reviews.questions.edit', [$program->id, $review->id, $siblings[$index + 1]->id])
                : '',
        ]);
    }

    public function updateQuestion(Request $request, int $id, int $reviewId, int $questionId): RedirectResponse|JsonResponse
    {
        return $this->persistQuestion($request, $id, $reviewId, $questionId, false);
    }

    public function autosaveQuestion(Request $request, int $id, int $reviewId, int $questionId): JsonResponse
    {
        $result = $this->persistQuestion($request, $id, $reviewId, $questionId, true);

        return $result instanceof JsonResponse ? $result : response()->json(['ok' => true]);
    }

    public function deleteQuestion(int $id, int $reviewId, int $questionId): RedirectResponse
    {
        [$program, $review] = $this->pair($id, $reviewId);
        $question = $review
            ? StudyProgramKnowledgeReviewQuestion::query()->where('knowledge_review_id', $review->id)->whereKey($questionId)->first()
            : null;

        if (! $program || ! $review || ! $question) {
            return redirect()->route('admin.programs.index')->with('error', 'Không tìm thấy câu hỏi.');
        }

        if ($program->isArchived()) {
            return $this->archivedRedirect($program);
        }

        $question->delete();
        $this->sync->syncKnowledgeReview($review);

        return redirect()
            ->route('admin.programs.reviews.show', [$program->id, $review->id])
            ->with('success', 'Đã xóa câu hỏi.');
    }

    public function reorderQuestions(Request $request, int $id, int $reviewId): JsonResponse
    {
        [$program, $review] = $this->pair($id, $reviewId);

        if (! $program || ! $review || $program->isArchived()) {
            return $this->jsonError('Không thể sắp xếp.');
        }

        $order = array_map('intval', (array) $request->input('order', []));
        $ids = $review->questions()->pluck('id')->map(fn ($v) => (int) $v)->all();

        if ($order === [] || count($order) !== count($ids) || array_diff($ids, $order) !== []) {
            return $this->jsonError('Danh sách câu hỏi không khớp.');
        }

        foreach ($order as $index => $questionId) {
            StudyProgramKnowledgeReviewQuestion::query()->where('knowledge_review_id', $review->id)->whereKey($questionId)->update(['sort_order' => $index]);
        }

        $this->sync->syncKnowledgeReview($review);

        return response()->json(['ok' => true]);
    }

    public function uploadPairImage(Request $request, int $id, int $reviewId): JsonResponse
    {
        return $this->upload($request, $id, $reviewId, 'image');
    }

    public function uploadListeningAudio(Request $request, int $id, int $reviewId): JsonResponse
    {
        return $this->upload($request, $id, $reviewId, 'audio');
    }

    /**
     * @return array{0: ?\App\Models\StudyProgram, 1: ?StudyProgramKnowledgeReview}
     */
    private function pair(int $id, int $reviewId): array
    {
        $program = $this->findProgram($id);
        $review = $program?->knowledgeReviews()->whereKey($reviewId)->first();

        return [$program, $review];
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
            'title.required' => 'Vui lòng nhập tên ôn tập kiến thức.',
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

    private function persistQuestion(Request $request, int $id, int $reviewId, int $questionId, bool $autosave): RedirectResponse|JsonResponse
    {
        [$program, $review] = $this->pair($id, $reviewId);
        $question = $review
            ? StudyProgramKnowledgeReviewQuestion::query()->where('knowledge_review_id', $review->id)->whereKey($questionId)->first()
            : null;

        if (! $program || ! $review || ! $question) {
            return $autosave ? $this->jsonError('Không tìm thấy câu hỏi.', 404) : redirect()->route('admin.programs.index')->with('error', 'Không tìm thấy câu hỏi.');
        }

        if ($program->isArchived()) {
            return $autosave ? $this->jsonError('Chương trình đã lưu trữ.') : $this->archivedRedirect($program);
        }

        $question->updateFromPost($this->questionPayload($request));
        $this->sync->syncKnowledgeReview($review);

        if ($autosave) {
            return response()->json(['ok' => true, 'saved_at' => now()->format('H:i')]);
        }

        return redirect()
            ->route('admin.programs.reviews.questions.edit', [$program->id, $review->id, $question->id])
            ->with('success', 'Đã lưu câu hỏi.');
    }

    private function upload(Request $request, int $id, int $reviewId, string $kind): JsonResponse
    {
        [$program, $review] = $this->pair($id, $reviewId);

        if (! $program || ! $review || $program->isArchived()) {
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
