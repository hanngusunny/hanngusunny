<?php

namespace App\Http\Controllers;

use App\Models\CourseMonthlyEvaluation;
use App\Services\TeacherCommentsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TeacherCommentsController extends Controller
{
    public function __construct(
        private TeacherCommentsService $comments,
    ) {
    }

    public function index(Request $request): View|JsonResponse|RedirectResponse
    {
        $viewer = $request->user();

        if ($viewer === null || (! $viewer->isAdmin() && ! $viewer->isTeacher())) {
            return redirect()->route('dashboard');
        }

        $hasWeekParam = $request->query->has('tuan');
        $period = trim((string) $request->query('tuan', ''));

        if (! $hasWeekParam) {
            $period = CourseMonthlyEvaluation::currentWeekKey();
        } elseif ($period !== '') {
            $period = CourseMonthlyEvaluation::normalizePeriod($period);
        }

        $filters = [
            'course_id' => (int) $request->query('khoa', 0),
            'user_id' => (int) $request->query('hoc_vien', 0),
            'period' => $period,
            'q' => trim((string) $request->query('q', '')),
        ];

        $courses = $this->comments->coursesForViewer($viewer);
        $students = $this->comments->studentsForFilter($viewer, $filters['course_id']);
        $rows = $this->comments->paginateComments($viewer, $filters);
        $stats = $this->comments->stats($viewer);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'html' => view('activity_logs.comments.partials.list', ['rows' => $rows])->render(),
                'stats' => $stats,
                'students' => $students->map(static fn ($student): array => [
                    'id' => (int) $student->id,
                    'label' => trim($student->name.($student->phone ? ' · '.$student->phone : '')),
                ])->values()->all(),
            ]);
        }

        return view('activity_logs.comments.index', [
            'rows' => $rows,
            'courses' => $courses,
            'students' => $students,
            'weekOptions' => CourseMonthlyEvaluation::weekOptions(24),
            'filters' => $filters,
            'stats' => $stats,
            'activityTab' => 'comments',
        ]);
    }
}
