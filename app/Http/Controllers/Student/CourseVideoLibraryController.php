<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Services\StudentCourseVideoLibraryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseVideoLibraryController extends Controller
{
    public function __invoke(Request $request, StudentCourseVideoLibraryService $library): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user?->isLearner()) {
            return redirect()->route('dashboard');
        }

        return view('student.video-library', $library->buildForStudent((int) $user->id));
    }
}
