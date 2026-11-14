<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\StudentCourseNavService;
use App\Services\StudentEvaluationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EvaluationController extends Controller
{
    public function __invoke(
        Request $request,
        StudentEvaluationService $evaluations,
        StudentCourseNavService $nav
    ): View|RedirectResponse {
        $user = $request->user();

        if (! $user?->isLearner()) {
            return redirect()->route('dashboard');
        }

        $payload = $evaluations->build((int) $user->id, trim((string) $request->query('tuan', '')) ?: null);

        if ($payload === null) {
            return redirect()->route('dashboard')->with('error', 'Không thể tải trang đánh giá.');
        }

        $payload['navService'] = $nav;

        return view('student.evaluation', $payload);
    }
}
