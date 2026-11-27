<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\CourseMember;
use App\Services\StudentCourseNavService;
use App\Services\StudentEvaluationService;
use App\Services\StudentResultsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EvaluationController extends Controller
{
    public function __invoke(
        Request $request,
        StudentEvaluationService $evaluations,
        StudentResultsService $results,
        StudentCourseNavService $nav
    ): View|RedirectResponse {
        $user = $request->user();

        if (! $user?->isLearner()) {
            return redirect()->route('dashboard');
        }

        $userId = (int) $user->id;
        $requestedTab = trim((string) $request->query('tab', 'nhan-xet'));
        $activeTab = $requestedTab === 'ket-qua' ? 'ket-qua' : 'nhan-xet';

        if ($activeTab === 'ket-qua') {
            $course = CourseMember::activeCourseFor($userId);
            if ($course !== null) {
                $course->loadMissing('teacher');
            }

            $filter = trim((string) $request->query('loc', 'all'));
            if (! in_array($filter, StudentResultsService::FILTER_KEYS, true)) {
                $filter = 'all';
            }

            $detailType = trim((string) $request->query('loai', ''));
            $detailId = max(0, (int) $request->query('id', 0));
            $resultDetail = null;
            $resultsData = ['items' => [], 'counts' => ['all' => 0, 'pre' => 0, 'post' => 0, 'kr' => 0, 'exam' => 0]];

            if ($course !== null) {
                if ($detailType !== '' && $detailId > 0) {
                    $resultDetail = $results->detailForStudent((int) $course->id, $userId, $detailType, $detailId, $course);
                }

                if ($resultDetail === null) {
                    $resultsData = $results->listForStudent((int) $course->id, $userId);
                }
            }

            return view('student.evaluation', [
                'activeTab' => 'ket-qua',
                'course' => $course,
                'navService' => $nav,
                'activeFilter' => $filter,
                'resultsData' => $resultsData,
                'resultDetail' => $resultDetail,
            ]);
        }

        $payload = $evaluations->build($userId, trim((string) $request->query('tuan', '')) ?: null);

        if ($payload === null) {
            return redirect()->route('dashboard')->with('error', 'Không thể tải trang đánh giá.');
        }

        $payload['activeTab'] = 'nhan-xet';
        $payload['navService'] = $nav;

        return view('student.evaluation', $payload);
    }
}
