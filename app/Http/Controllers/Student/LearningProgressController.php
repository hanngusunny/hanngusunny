<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\StudentLearningProgressService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LearningProgressController extends Controller
{
    public function __invoke(Request $request, StudentLearningProgressService $progress): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user?->isLearner()) {
            return redirect()->route('dashboard');
        }

        return view('student.progress', $progress->buildPage(
            (int) $user->id,
            $request->query('thang'),
            $request->query('nam')
        ));
    }
}
