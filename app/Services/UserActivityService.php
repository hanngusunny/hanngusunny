<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserActivityLog;
use App\Models\UserDailyStat;
use App\Models\UserLoginSession;
use App\Support\UserRole;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class UserActivityService
{
    /** @var list<string> */
    private array $redactFields = [
        'password',
        'password_confirm',
        'new_password',
        'confirm_password',
        'current_password',
    ];

    public function tablesReady(): bool
    {
        return Schema::hasTable('user_login_sessions')
            && Schema::hasTable('user_activity_logs')
            && Schema::hasTable('user_daily_stats');
    }

    public function startLoginSession(User $user, Request $request): int
    {
        if (! $this->tablesReady()) {
            return 0;
        }

        $now = now();
        $session = UserLoginSession::query()->create([
            'user_id' => (int) $user->id,
            'login_at' => $now,
            'last_seen_at' => $now,
            'active_seconds' => 0,
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        $request->session()->put('login_session_id', (int) $session->id);
        $this->touchDailyStat((int) $user->id, $now->toDateTimeString(), 'login');
        $this->logAction(
            (int) $user->id,
            (int) $session->id,
            'login',
            'Đăng nhập hệ thống',
            $request,
            ['login_at' => $now->toDateTimeString()]
        );

        return (int) $session->id;
    }

    public function endLoginSession(User $user, Request $request): void
    {
        if (! $this->tablesReady()) {
            return;
        }

        $sessionId = (int) $request->session()->get('login_session_id', 0);
        $session = $sessionId > 0
            ? UserLoginSession::query()->where('id', $sessionId)->where('user_id', $user->id)->first()
            : null;

        if ($session === null) {
            return;
        }

        $now = now();
        $session->update([
            'logout_at' => $now,
            'last_seen_at' => $now,
        ]);

        $this->logAction(
            (int) $user->id,
            (int) $session->id,
            'logout',
            'Đăng xuất hệ thống',
            $request,
            [
                'logout_at' => $now->toDateTimeString(),
                'active_seconds' => (int) $session->active_seconds,
            ]
        );
    }

    public function logRequest(Request $request, Response $response): void
    {
        if (! $this->tablesReady()) {
            return;
        }

        $user = $request->user();

        if ($user === null || $this->shouldSkip($request)) {
            return;
        }

        $path = $this->requestPath($request);
        $method = strtoupper($request->method());
        $now = now()->toDateTimeString();
        $loginSessionId = $this->ensureLoginSession((int) $user->id, $request);
        $actionLabel = $this->resolveActionLabel($path, $method);

        if ($method === 'GET' && str_starts_with($actionLabel, 'Truy cập trang:')) {
            if ($loginSessionId > 0) {
                $this->touchLastSeenThrottled($request, $loginSessionId, $now);
            }

            return;
        }

        if ($loginSessionId > 0) {
            $this->touchLastSeenThrottled($request, $loginSessionId, $now);
        }

        $actionType = $method === 'GET' ? 'page_view' : 'action';
        $context = [
            'status_code' => $response->getStatusCode(),
            'query' => $this->sanitizeArray($request->query()),
        ];

        if ($method !== 'GET') {
            $context['payload'] = $this->sanitizeArray($request->except(['_token', '_method']));
        }

        $this->logAction((int) $user->id, $loginSessionId ?: null, $actionType, $actionLabel, $request, $context);
        $this->touchDailyStat((int) $user->id, $now, $actionType);
    }

    public function recordPulse(int $userId, int $seconds, Request $request): void
    {
        if (! $this->tablesReady() || $userId <= 0 || $seconds <= 0) {
            return;
        }

        $seconds = min($seconds, 120);
        $now = now();
        $today = $now->toDateString();
        $loginSessionId = $this->ensureLoginSession($userId, $request);

        if ($loginSessionId > 0) {
            UserLoginSession::query()
                ->where('id', $loginSessionId)
                ->where('user_id', $userId)
                ->update([
                    'active_seconds' => DB::raw('active_seconds + '.(int) $seconds),
                    'last_seen_at' => $now,
                ]);
        }

        $stat = UserDailyStat::query()
            ->where('user_id', $userId)
            ->whereDate('stat_date', $today)
            ->first();

        if ($stat === null) {
            UserDailyStat::query()->create([
                'user_id' => $userId,
                'stat_date' => $today,
                'active_seconds' => $seconds,
                'first_seen_at' => $now,
                'last_seen_at' => $now,
            ]);

            return;
        }

        UserDailyStat::query()->where('id', $stat->id)->update([
            'active_seconds' => DB::raw('active_seconds + '.(int) $seconds),
            'last_seen_at' => $now,
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $context
     */
    public function logAction(
        int $userId,
        ?int $loginSessionId,
        string $actionType,
        string $actionLabel,
        Request $request,
        ?array $context = null
    ): void {
        if (! $this->tablesReady() || $userId <= 0) {
            return;
        }

        $path = $this->requestPath($request);

        UserActivityLog::query()->create([
            'user_id' => $userId,
            'login_session_id' => $loginSessionId ?: null,
            'action_type' => $actionType,
            'action_label' => $actionLabel,
            'http_method' => strtoupper($request->method()),
            'route_path' => $path !== '' ? $path : null,
            'url' => $request->fullUrl(),
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
            'context_json' => $context !== null ? json_encode($context, JSON_UNESCAPED_UNICODE) : null,
            'created_at' => now(),
        ]);
    }

    public function resolveActionLabel(string $path, string $method): string
    {
        foreach ((array) config('activity.route_labels', []) as $pattern => $label) {
            if (preg_match($this->patternToRegex((string) $pattern), $path) === 1) {
                if ($method !== 'GET' && ! str_contains($label, 'Nộp') && ! str_contains($label, 'Đăng')) {
                    return $label.' (gửi dữ liệu)';
                }

                return $label;
            }
        }

        $segments = explode('/', $path);
        $last = end($segments) ?: $path;

        if ($method === 'GET') {
            return 'Truy cập trang: '.$last;
        }

        return 'Thao tác: '.$last;
    }

    public function formatDuration(int $seconds): string
    {
        if ($seconds <= 0) {
            return '0 giây';
        }

        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $secs = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%d giờ %d phút', $hours, $minutes);
        }

        if ($minutes > 0) {
            return sprintf('%d phút %d giây', $minutes, $secs);
        }

        return sprintf('%d giây', $secs);
    }

    public function formatDateTime(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('d/m/Y H:i:s');
        }

        if ($value === null || $value === '') {
            return '—';
        }

        $ts = is_numeric($value) ? (int) $value : strtotime((string) $value);

        return $ts ? date('d/m/Y H:i:s', $ts) : (string) $value;
    }

    public function formatDate(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('d/m/Y');
        }

        if ($value === null || $value === '') {
            return '—';
        }

        $ts = is_numeric($value) ? (int) $value : strtotime((string) $value);

        return $ts ? date('d/m/Y', $ts) : (string) $value;
    }

    /**
     * @return array{
     *   active_days: int,
     *   page_views: int,
     *   actions: int,
     *   active_seconds: int,
     *   last_seen_at: string|null,
     *   period_days: int,
     *   from: string,
     *   to: string
     * }
     */
    public function getProfileActivitySummary(int $userId, int $days = 30): array
    {
        $userId = max(0, $userId);
        $days = max(1, min(90, $days));
        $from = date('Y-m-d', strtotime('-'.$days.' days') ?: time());
        $to = date('Y-m-d');

        $empty = [
            'active_days' => 0,
            'page_views' => 0,
            'actions' => 0,
            'active_seconds' => 0,
            'last_seen_at' => null,
            'period_days' => $days,
            'from' => $from,
            'to' => $to,
        ];

        if ($userId <= 0) {
            return $empty;
        }

        $stats = $this->getDailyStats($userId, $from, $to);
        $pageViews = 0;
        $actions = 0;
        $activeSeconds = 0;

        foreach ($stats as $row) {
            $pageViews += (int) ($row->page_views ?? 0);
            $actions += (int) ($row->action_count ?? $row->actions ?? 0);
            $activeSeconds += (int) ($row->active_seconds ?? 0);
        }

        return [
            'active_days' => count($stats),
            'page_views' => $pageViews,
            'actions' => $actions,
            'active_seconds' => $activeSeconds,
            'last_seen_at' => null,
            'period_days' => $days,
            'from' => $from,
            'to' => $to,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getDailyStats(int $userId, ?string $from = null, ?string $to = null): array
    {
        if (! $this->tablesReady()) {
            return [];
        }

        $query = UserDailyStat::query()->where('user_id', $userId);

        if ($from) {
            $query->whereDate('stat_date', '>=', $from);
        }

        if ($to) {
            $query->whereDate('stat_date', '<=', $to);
        }

        return $query->orderByDesc('stat_date')->get()->all();
    }

    /**
     * @return list<UserLoginSession>
     */
    public function getLoginSessions(int $userId, ?string $from = null, ?string $to = null, int $limit = 50): array
    {
        if (! $this->tablesReady()) {
            return [];
        }

        $query = UserLoginSession::query()->where('user_id', $userId);

        if ($from) {
            $query->whereDate('login_at', '>=', $from);
        }

        if ($to) {
            $query->whereDate('login_at', '<=', $to);
        }

        return $query->orderByDesc('login_at')->limit($limit)->get()->all();
    }

    /**
     * @return array{rows: \Illuminate\Support\Collection<int, UserActivityLog>, pager: LengthAwarePaginator}
     */
    public function getActivityLogs(int $userId, ?string $from = null, ?string $to = null, int $perPage = 40): array
    {
        $empty = new LengthAwarePaginator([], 0, $perPage);

        if (! $this->tablesReady()) {
            return ['rows' => collect(), 'pager' => $empty];
        }

        $query = UserActivityLog::query()->where('user_id', $userId);

        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }

        $pager = $query->orderByDesc('created_at')->paginate($perPage)->withQueryString();

        return [
            'rows' => $pager->getCollection(),
            'pager' => $pager,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getUserSummaries(?string $role, ?string $from, ?string $to): array
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('user_daily_stats')) {
            return [];
        }

        $query = DB::table('users as u')
            ->leftJoin('user_daily_stats as d', function ($join) use ($from, $to) {
                $join->on('d.user_id', '=', 'u.id');

                if ($from) {
                    $join->where(function ($inner) use ($from) {
                        $inner->whereNull('d.stat_date')->orWhereDate('d.stat_date', '>=', $from);
                    });
                }

                if ($to) {
                    $join->where(function ($inner) use ($to) {
                        $inner->whereNull('d.stat_date')->orWhereDate('d.stat_date', '<=', $to);
                    });
                }
            })
            ->selectRaw('u.id, u.full_name, u.username, u.phone, u.role, u.avatar')
            ->selectRaw('COALESCE(SUM(d.active_seconds), 0) as total_active_seconds')
            ->selectRaw('COALESCE(SUM(d.page_views), 0) as total_page_views')
            ->selectRaw('COALESCE(SUM(d.action_count), 0) as total_actions')
            ->selectRaw('MAX(d.last_seen_at) as last_seen_at')
            ->selectRaw('COUNT(DISTINCT d.stat_date) as active_days')
            ->groupBy('u.id', 'u.full_name', 'u.username', 'u.phone', 'u.role', 'u.avatar')
            ->orderByDesc('last_seen_at')
            ->orderBy('u.full_name');

        if ($role) {
            $query->where('u.role', $role);
        }

        return $query->get()->map(static fn ($row): array => (array) $row)->all();
    }

    /**
     * @return array<string, string>
     */
    public function roleOptions(bool $isAdmin = true): array
    {
        return [
            UserRole::STUDENT => UserRole::label(UserRole::STUDENT),
            UserRole::TEACHER => UserRole::label(UserRole::TEACHER),
            UserRole::ADMIN => UserRole::label(UserRole::ADMIN),
        ];
    }

    public function canViewUser(User $viewer, User $subject): bool
    {
        return $viewer->isAdmin();
    }

    public function ensureLoginSession(int $userId, Request $request): int
    {
        if (! $this->tablesReady() || $userId <= 0) {
            return 0;
        }

        $sessionId = (int) $request->session()->get('login_session_id', 0);

        if ($sessionId > 0) {
            $row = UserLoginSession::query()->find($sessionId);

            if ($row !== null && (int) $row->user_id === $userId && $row->logout_at === null) {
                return $sessionId;
            }
        }

        $now = now();
        $session = UserLoginSession::query()->create([
            'user_id' => $userId,
            'login_at' => $now,
            'last_seen_at' => $now,
            'active_seconds' => 0,
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        $request->session()->put('login_session_id', (int) $session->id);

        return (int) $session->id;
    }

    public function requestPath(Request $request): string
    {
        $path = trim($request->path(), '/');
        $base = trim((string) (parse_url((string) config('app.url'), PHP_URL_PATH) ?: ''), '/');

        if ($base !== '' && $path === $base) {
            return '';
        }

        if ($base !== '' && str_starts_with($path, $base.'/')) {
            return substr($path, strlen($base) + 1);
        }

        return $path;
    }

    private function shouldSkip(Request $request): bool
    {
        $path = $this->requestPath($request);

        foreach ((array) config('activity.skip_path_patterns', []) as $pattern) {
            if (preg_match((string) $pattern, $path) === 1) {
                return true;
            }
        }

        return false;
    }

    private function touchLastSeenThrottled(Request $request, int $loginSessionId, string $now): void
    {
        $lastTouch = (int) $request->session()->get('activity_last_seen_touch', 0);

        if (time() - $lastTouch < 60) {
            return;
        }

        $request->session()->put('activity_last_seen_touch', time());
        UserLoginSession::query()->where('id', $loginSessionId)->update(['last_seen_at' => $now]);
    }

    private function touchDailyStat(int $userId, string $now, string $eventType): void
    {
        $today = date('Y-m-d');
        $stat = UserDailyStat::query()
            ->where('user_id', $userId)
            ->whereDate('stat_date', $today)
            ->first();

        if ($stat === null) {
            UserDailyStat::query()->create([
                'user_id' => $userId,
                'stat_date' => $today,
                'page_views' => $eventType === 'page_view' ? 1 : 0,
                'action_count' => $eventType === 'action' ? 1 : 0,
                'first_seen_at' => $now,
                'last_seen_at' => $now,
            ]);

            return;
        }

        $payload = ['last_seen_at' => $now];

        if ($eventType === 'page_view') {
            UserDailyStat::query()->where('id', $stat->id)->update([
                'page_views' => DB::raw('page_views + 1'),
                'last_seen_at' => $now,
            ]);

            return;
        }

        if ($eventType === 'action') {
            UserDailyStat::query()->where('id', $stat->id)->update([
                'action_count' => DB::raw('action_count + 1'),
                'last_seen_at' => $now,
            ]);

            return;
        }

        $stat->update($payload);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function sanitizeArray(array $data): array
    {
        $clean = [];

        foreach ($data as $key => $value) {
            $name = strtolower((string) $key);

            if (in_array((string) $key, $this->redactFields, true)
                || str_contains($name, 'password')
                || str_contains($name, 'csrf')
                || $name === '_token') {
                $clean[$key] = '[đã ẩn]';

                continue;
            }

            if (is_array($value)) {
                $clean[$key] = $this->sanitizeArray($value);

                continue;
            }

            $str = (string) $value;
            $clean[$key] = mb_strlen($str) > 500 ? mb_substr($str, 0, 500).'…' : $str;
        }

        return $clean;
    }

    private function patternToRegex(string $pattern): string
    {
        $parts = explode('/', $pattern);
        $regex = [];

        foreach ($parts as $part) {
            $regex[] = match ($part) {
                '(:num)' => '[0-9]+',
                '(:segment)' => '[^/]+',
                default => preg_quote($part, '#'),
            };
        }

        return '#^'.implode('/', $regex).'$#';
    }
}
