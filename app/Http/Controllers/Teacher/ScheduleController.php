<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Services\TeacherScheduleCalendarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function __invoke(Request $request, TeacherScheduleCalendarService $calendar): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user?->isTeacher()) {
            return redirect()->route('dashboard');
        }

        $payload = $calendar->build((int) $user->id);

        if (! ($payload['ready'] ?? false)) {
            return redirect()->route('dashboard')
                ->with('error', 'Module khóa học chưa sẵn sàng. Vui lòng chạy migrate database.');
        }

        return view('teacher.schedule', [
            'payload' => $payload,
        ]);
    }
}
