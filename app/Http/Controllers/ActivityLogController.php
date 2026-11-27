<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\UserActivityService;
use App\Support\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function __construct(
        private UserActivityService $activity,
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        $viewer = $request->user();

        if ($viewer === null || ! $viewer->isAdmin()) {
            return redirect()->route('dashboard');
        }

        $role = trim((string) $request->query('role', UserRole::STUDENT));
        $from = $this->dateQuery($request, 'from', now()->subDays(30)->toDateString());
        $to = $this->dateQuery($request, 'to', now()->toDateString());
        $roles = $this->activity->roleOptions();

        if (! isset($roles[$role])) {
            $role = UserRole::STUDENT;
        }

        $summaries = $this->activity->getUserSummaries($role, $from, $to);

        return view('activity_logs.index', [
            'summaries' => $summaries,
            'filters' => compact('role', 'from', 'to'),
            'roles' => $roles,
            'activity' => $this->activity,
        ]);
    }

    public function show(Request $request, int $id): View|RedirectResponse
    {
        $viewer = $request->user();
        $user = User::query()->find($id);

        if ($user === null) {
            return redirect()->route('activity-logs.index')->with('error', 'Không tìm thấy người dùng.');
        }

        if ($viewer === null || ! $this->activity->canViewUser($viewer, $user)) {
            return redirect()->route('activity-logs.index')->with('error', 'Bạn không có quyền xem lịch sử người dùng này.');
        }

        $from = $this->dateQuery($request, 'from', now()->subDays(30)->toDateString());
        $to = $this->dateQuery($request, 'to', now()->toDateString());
        $tab = trim((string) $request->query('tab', 'daily'));

        if (! in_array($tab, ['daily', 'sessions', 'logs'], true)) {
            $tab = 'daily';
        }

        $logs = $this->activity->getActivityLogs($id, $from, $to, 40);

        return view('activity_logs.show', [
            'subject' => $user,
            'dailyStats' => $this->activity->getDailyStats($id, $from, $to),
            'loginSessions' => $this->activity->getLoginSessions($id, $from, $to, 100),
            'activityLogs' => $logs['rows'],
            'pager' => $logs['pager'],
            'filters' => compact('from', 'to', 'tab'),
            'activity' => $this->activity,
        ]);
    }

    private function dateQuery(Request $request, string $key, string $default): string
    {
        $value = trim((string) $request->query($key, $default));

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1 ? $value : $default;
    }
}
