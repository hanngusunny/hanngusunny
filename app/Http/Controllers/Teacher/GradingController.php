<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Services\TeacherGradingService;
use App\Support\Questions\QuestionParser;
use App\Support\TeacherRecordingUpload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GradingController extends Controller
{
    public function index(Request $request, TeacherGradingService $grading): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user?->isTeacher()) {
            return redirect()->route('dashboard');
        }

        $payload = $grading->build(
            (int) $user->id,
            max(0, (int) $request->query('khoa', 0)),
            (string) ($request->query('muc') ?: TeacherGradingService::TAB_PRE),
            (string) ($request->query('bai') ?: '')
        );

        if ($payload === null) {
            return redirect()->route('dashboard')
                ->with('error', 'Bạn chưa có khóa học đang dạy.');
        }

        return view('teacher.grading', $payload);
    }

    public function save(Request $request, TeacherGradingService $grading, QuestionParser $parser): RedirectResponse
    {
        $teacher = $request->user();

        if (! $teacher?->isTeacher()) {
            return redirect()->route('dashboard');
        }

        $courseId = (int) $request->input('course_id', 0);
        $activeTab = (string) ($request->input('muc') ?: TeacherGradingService::TAB_PRE);
        $selectedKey = (string) ($request->input('bai') ?: '');
        $isCorrectRaw = (string) $request->input('is_correct', '');
        $comment = trim((string) $request->input('teacher_comment', ''));
        $gradeMode = (string) $request->input('grade_mode', '');
        $redirectUrl = $grading->pageUrl($courseId, $activeTab, $selectedKey);

        $course = Course::query()
            ->where('id', $courseId)
            ->where('teacher_id', (int) $teacher->id)
            ->where('status', Course::STATUS_ACTIVE)
            ->first();

        if ($course === null) {
            return redirect()->route('teacher.grading')
                ->with('error', 'Không tìm thấy khóa học đang dạy.');
        }

        $payload = $grading->build((int) $teacher->id, $courseId, $activeTab, $selectedKey);
        $item = is_array($payload['selectedItem'] ?? null) ? $payload['selectedItem'] : null;

        if (! is_array($item) || ($item['key'] ?? '') !== $selectedKey) {
            return redirect()->to($redirectUrl)->with('error', 'Không tìm thấy bài cần chấm.');
        }

        $wasGraded = trim((string) (($item['answer_row']['teacher_graded_at'] ?? '') ?: '')) !== '';
        $isWordTranslate = ($item['question_type'] ?? '') === 'word_translate' || $gradeMode === 'word_translate';
        $question = $grading->questionFromGradingItem($item);
        $isCompositeFill = $gradeMode === 'composite_fill'
            || (! $isWordTranslate && $parser->hasCompositeFillItems($question));
        $isPhatAm = ! $isCompositeFill && ($item['question_type'] ?? '') === 'phat_am' && $gradeMode === 'phat_am';
        $recordingPath = trim((string) $request->input('teacher_recording', ''));

        if ($isWordTranslate) {
            $itemCount = $grading->wordTranslateItemCount($item);
            $itemGrades = $grading->parseWordTranslateItemGrades($request->input('item_grades'), $itemCount);

            if ($itemGrades === null) {
                return redirect()->to($redirectUrl)->with('error', 'Vui lòng chấm Đúng/Sai cho tất cả các từ dịch.');
            }

            $saved = $grading->saveWordTranslateGrade($item, $itemGrades);
        } elseif ($isCompositeFill) {
            $fillIndexes = $parser->compositeFillItemIndexes($question);
            $itemGrades = $grading->parseCompositeFillItemGrades($request->input('item_grades'), $fillIndexes);

            if ($itemGrades === null) {
                return redirect()->to($redirectUrl)->with('error', 'Vui lòng chấm Đúng/Sai cho từng câu tự luận.');
            }

            $saved = $grading->saveCompositeFillGrade($item, $itemGrades, $comment, $recordingPath);
        } elseif ($isPhatAm) {
            if (! in_array($isCorrectRaw, ['0', '1'], true)) {
                return redirect()->to($redirectUrl)->with('error', 'Vui lòng chọn Đúng hoặc Sai trước khi lưu.');
            }

            $saved = $grading->savePhatAmGrade($item, $isCorrectRaw === '1', $comment, $recordingPath);
        } else {
            if (! in_array($isCorrectRaw, ['0', '1'], true)) {
                return redirect()->to($redirectUrl)->with('error', 'Vui lòng chọn Đúng hoặc Sai trước khi lưu.');
            }

            $saved = $grading->saveGrade($item, $isCorrectRaw === '1', $comment);
        }

        if (! $saved) {
            return redirect()->to($redirectUrl)->with('error', 'Không thể lưu chấm bài.');
        }

        $remaining = $grading->build((int) $teacher->id, $courseId, $activeTab, '');
        $nextKey = '';

        if ($remaining !== null) {
            $nextItems = $remaining['tabItems'] ?? [];

            if ($nextItems !== []) {
                $nextKey = (string) ($nextItems[0]['key'] ?? '');
            }
        }

        $successUrl = $nextKey !== ''
            ? $grading->pageUrl($courseId, $activeTab, $nextKey)
            : $grading->pageUrl($courseId, $activeTab);

        return redirect()->to($successUrl)->with(
            'success',
            $wasGraded
                ? ($isWordTranslate ? 'Đã cập nhật chấm bài dịch từ.' : ($isCompositeFill ? 'Đã cập nhật chấm từng câu tự luận.' : ($isPhatAm ? 'Đã cập nhật chấm bài phát âm.' : 'Đã cập nhật chấm bài tự luận.')))
                : ($isWordTranslate ? 'Đã lưu chấm bài dịch từ.' : ($isCompositeFill ? 'Đã lưu chấm từng câu tự luận.' : ($isPhatAm ? 'Đã lưu chấm bài phát âm.' : 'Đã lưu chấm bài tự luận.')))
        );
    }

    public function uploadRecording(Request $request): JsonResponse
    {
        if (! $request->user()?->isTeacher()) {
            return response()->json([
                'ok' => false,
                'message' => 'Khu vực này dành cho giáo viên.',
            ], 403);
        }

        $relativePath = TeacherRecordingUpload::store($request->file('recording'));

        if ($relativePath === null) {
            return response()->json([
                'ok' => false,
                'message' => 'Không tải được bản ghi. Chỉ hỗ trợ WEBM, OGG, MP3, WAV tối đa 15MB.',
            ], 422);
        }

        return response()->json([
            'ok' => true,
            'path' => $relativePath,
            'url' => asset($relativePath),
        ]);
    }
}
