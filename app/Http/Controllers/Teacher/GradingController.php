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
            (string) ($request->query('muc') ?: TeacherGradingService::TAB_ALL),
            (string) ($request->query('bai') ?: ''),
            (string) ($request->query('q') ?: ''),
            (string) ($request->query('trang_thai') ?: 'all'),
            (string) ($request->query('sap_xep') ?: 'priority'),
            max(1, (int) $request->query('page', 1)),
            $request->boolean('sua'),
            max(0, (int) $request->query('cau', 0))
        );

        if ($payload === null) {
            return redirect()->route('dashboard')
                ->with('error', 'Bạn chưa có khóa học đang dạy.');
        }

        return view('teacher.grading', $payload);
    }

    public function save(
        Request $request,
        TeacherGradingService $grading,
        QuestionParser $parser
    ): JsonResponse|RedirectResponse {
        $teacher = $request->user();
        $wantsJson = $request->ajax() || $request->wantsJson();

        if (! $teacher?->isTeacher()) {
            return $wantsJson
                ? response()->json(['ok' => false, 'error' => 'Khu vực này dành cho giáo viên.'], 403)
                : redirect()->route('dashboard');
        }

        $courseId = (int) $request->input('course_id', 0);
        $activeTab = (string) ($request->input('muc') ?: TeacherGradingService::TAB_ALL);
        $answerKey = (string) ($request->input('answer_key') ?: $request->input('bai') ?: '');
        $submissionKey = (string) ($request->input('submission_key') ?: '');
        $isCorrectRaw = (string) $request->input('is_correct', '');
        $comment = trim((string) $request->input('teacher_comment', ''));
        $gradeMode = (string) $request->input('grade_mode', '');
        $redirectTargetKey = $submissionKey !== '' ? $submissionKey : $answerKey;
        $redirectUrl = $grading->pageUrl($courseId, $activeTab, $redirectTargetKey);

        $course = Course::query()
            ->where('id', $courseId)
            ->where('teacher_id', (int) $teacher->id)
            ->where('status', Course::STATUS_ACTIVE)
            ->first();

        if ($course === null) {
            return $wantsJson
                ? response()->json(['ok' => false, 'error' => 'Không tìm thấy khóa học đang dạy.'], 404)
                : redirect()->route('teacher.grading')->with('error', 'Không tìm thấy khóa học đang dạy.');
        }

        $item = $grading->findSingleAnswerItem((int) $teacher->id, $courseId, $answerKey);

        if ($item === null) {
            return $wantsJson
                ? response()->json(['ok' => false, 'error' => 'Không tìm thấy câu cần chấm.'], 404)
                : redirect()->to($redirectUrl)->with('error', 'Không tìm thấy bài cần chấm.');
        }

        if ($submissionKey === '') {
            $submissionKey = (string) ($item['submission_key'] ?? '');
            $redirectUrl = $grading->pageUrl($courseId, $activeTab, $submissionKey);
        }

        $wasGraded = trim((string) (($item['answer_row']['teacher_graded_at'] ?? '') ?: '')) !== '';
        $isWordTranslate = ($item['question_type'] ?? '') === 'word_translate' || $gradeMode === 'word_translate';
        $question = $grading->questionFromGradingItem($item);
        $isCompositeFill = $gradeMode === 'composite_fill'
            || (! $isWordTranslate && $parser->hasCompositeFillItems($question));
        $isPhatAm = ! $isCompositeFill && ($item['question_type'] ?? '') === 'phat_am' && $gradeMode === 'phat_am';
        $recordingPath = trim((string) $request->input('teacher_recording', ''));

        $errorMessage = null;
        $saved = false;

        if ($isWordTranslate) {
            $itemCount = $grading->wordTranslateItemCount($item);
            $itemGrades = $grading->parseWordTranslateItemGrades($request->input('item_grades'), $itemCount);

            if ($itemGrades === null) {
                $errorMessage = 'Vui lòng chấm Đúng/Sai cho tất cả các từ dịch.';
            } else {
                $saved = $grading->saveWordTranslateGrade($item, $itemGrades);
            }
        } elseif ($isCompositeFill) {
            $fillIndexes = $parser->compositeFillItemIndexes($question);
            $itemGrades = $grading->parseCompositeFillItemGrades($request->input('item_grades'), $fillIndexes);

            if ($itemGrades === null) {
                $errorMessage = 'Vui lòng chấm Đúng/Sai cho từng câu tự luận.';
            } else {
                $saved = $grading->saveCompositeFillGrade($item, $itemGrades, $comment, $recordingPath);
            }
        } elseif ($isPhatAm) {
            if (! in_array($isCorrectRaw, ['0', '1'], true)) {
                $errorMessage = 'Vui lòng chọn Đúng hoặc Sai trước khi lưu.';
            } else {
                $saved = $grading->savePhatAmGrade($item, $isCorrectRaw === '1', $comment, $recordingPath);
            }
        } else {
            if (! in_array($isCorrectRaw, ['0', '1'], true)) {
                $errorMessage = 'Vui lòng chọn Đúng hoặc Sai trước khi lưu.';
            } else {
                $saved = $grading->saveGrade($item, $isCorrectRaw === '1', $comment);
            }
        }

        if ($errorMessage !== null) {
            return $wantsJson
                ? response()->json(['ok' => false, 'error' => $errorMessage], 422)
                : redirect()->to($redirectUrl)->with('error', $errorMessage);
        }

        if (! $saved) {
            return $wantsJson
                ? response()->json(['ok' => false, 'error' => 'Không thể lưu chấm bài.'], 500)
                : redirect()->to($redirectUrl)->with('error', 'Không thể lưu chấm bài.');
        }

        $message = $wasGraded
            ? ($isWordTranslate ? 'Đã cập nhật chấm bài dịch từ.' : ($isCompositeFill ? 'Đã cập nhật chấm từng câu tự luận.' : ($isPhatAm ? 'Đã cập nhật chấm bài phát âm.' : 'Đã cập nhật chấm bài tự luận.')))
            : ($isWordTranslate ? 'Đã lưu chấm bài dịch từ.' : ($isCompositeFill ? 'Đã lưu chấm từng câu tự luận.' : ($isPhatAm ? 'Đã lưu chấm bài phát âm.' : 'Đã lưu chấm bài tự luận.')));

        if ($wantsJson) {
            $detail = $grading->buildSubmissionDetail(
                (int) $teacher->id,
                $course,
                '',
                $submissionKey,
                $activeTab,
                [],
                true
            );

            $questionEarned = null;
            $questionMax = null;
            $scoreState = 'full';
            if (is_array($detail['all_questions'] ?? null)) {
                foreach ($detail['all_questions'] as $qEntry) {
                    if ((string) ($qEntry['answer_key'] ?? '') === $answerKey) {
                        $questionEarned = $qEntry['earned_points'] ?? null;
                        $questionMax = $qEntry['max_points'] ?? null;
                        $scoreState = (string) ($qEntry['score_state'] ?? 'full');
                        break;
                    }
                }
            }

            return response()->json([
                'ok' => true,
                'message' => $message,
                'answer_key' => $answerKey,
                'complete' => true,
                'question_earned' => $questionEarned,
                'question_earned_display' => $questionEarned !== null ? $grading->formatScoreVi((float) $questionEarned) : '—',
                'question_max' => $questionMax,
                'score_state' => $scoreState,
                'points_earned' => $detail['total_earned'] ?? 0,
                'points_earned_display' => $detail['total_earned_display'] ?? '0',
                'points_possible_display' => $detail['total_possible_display'] ?? '100',
                'pending_manual' => (int) ($detail['pending_manual_count'] ?? 0),
                'pending_manual_labels' => $detail['pending_manual_labels'] ?? [],
                'view_url' => $detail['view_url'] ?? $redirectUrl,
            ]);
        }

        return redirect()->to($redirectUrl)->with('success', $message);
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
