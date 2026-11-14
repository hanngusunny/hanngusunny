<?php

namespace App\Services\Api;

use App\Models\Course;
use App\Models\CourseMember;
use App\Models\User;
use App\Support\ClassroomConfig;
use App\Support\HtmlContent;
use App\Support\JitsiMeetLink;
use App\Support\MediaUrl;
use App\Support\UserRole;
use Illuminate\Support\Facades\Schema;
use Throwable;

class ZaloApiSupport
{
    public function integrationKey(): string
    {
        $key = trim((string) config('api.key', ''));

        return $key !== '' ? $key : trim((string) config('api.fixed_key', ''));
    }

    public function publicBaseUrl(): string
    {
        return rtrim((string) config('api.public_base_url', 'https://online.hanngusunny.edu.vn'), '/');
    }

    public function apiBaseUrl(): string
    {
        return $this->publicBaseUrl().'/api/v1';
    }

    public function apiPath(string $path): string
    {
        return rtrim($this->apiBaseUrl(), '/').'/'.ltrim($path, '/');
    }

    public function absoluteUrl(string $path): string
    {
        return rtrim($this->publicBaseUrl(), '/').'/'.ltrim($path, '/');
    }

    public function tokenTtlDays(): int
    {
        return max(1, (int) config('api.token_ttl_days', 30));
    }

    /**
     * @return array{code: string, message: string, status: int}
     */
    public function error(string $code, string $message, int $status): array
    {
        return [
            'code' => $code,
            'message' => $message,
            'status' => $status,
        ];
    }

    /**
     * @param  list<string>  $tables
     * @return array{code: string, message: string, status: int}|null
     */
    public function ensureTables(array $tables, string $notReadyMessage): ?array
    {
        try {
            foreach ($tables as $table) {
                if (! Schema::hasTable($table)) {
                    return $this->error('MODULE_NOT_READY', $notReadyMessage, 503);
                }
            }
        } catch (Throwable) {
            return $this->error('DATABASE_ERROR', 'Không thể kết nối database.', 503);
        }

        return null;
    }

    /**
     * @return array{code: string, message: string, status: int}|null
     */
    public function requireStudent(int $userId): ?array
    {
        if ($userId <= 0) {
            return $this->error('UNAUTHORIZED', 'Thiếu token đăng nhập.', 401);
        }

        $user = User::query()->find($userId);

        if ($user === null || (string) $user->status !== 'active') {
            return $this->error('USER_NOT_FOUND', 'Tài khoản không tồn tại hoặc đã bị khóa.', 404);
        }

        if ((string) $user->role !== UserRole::STUDENT) {
            return $this->error('FORBIDDEN', 'API này chỉ dành cho học viên.', 403);
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function formatUser(User $user): array
    {
        $role = (string) $user->role;
        $code = trim((string) ($user->student_code ?? ''));

        return [
            'id' => (int) $user->id,
            'username' => (string) $user->username,
            'full_name' => (string) ($user->full_name ?? ''),
            'email' => (string) ($user->email ?? ''),
            'phone' => (string) ($user->phone ?? ''),
            'student_code' => $code !== '' ? $code : null,
            'role' => $role,
            'role_label' => $this->roleLabel($role),
            'status' => (string) $user->status,
            'avatar' => $user->avatarUrl(),
        ];
    }

    public function roleLabel(string $role): string
    {
        return match ($role) {
            UserRole::STUDENT => 'Học viên',
            UserRole::STUDY_ABROAD_STUDENT => 'Du học sinh',
            UserRole::TEACHER => 'Giáo viên',
            UserRole::LECTURER => 'Giảng viên',
            UserRole::ACCOUNTANT => 'Kế toán',
            UserRole::ADMIN => 'Quản trị',
            default => $role !== '' ? $role : 'Người dùng',
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function formatStudentBrief(User $student): array
    {
        return [
            'id' => (int) $student->id,
            'full_name' => (string) ($student->full_name ?? ''),
            'username' => (string) $student->username,
            'email' => (string) ($student->email ?? ''),
            'phone' => (string) ($student->phone ?? ''),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function formatCourse(Course $course, ?string $membershipStatus = null, ?string $lookupSource = null): array
    {
        $course->loadMissing('teacher');

        $formatted = [
            'id' => (int) $course->id,
            'title' => (string) $course->title,
            'status' => (string) $course->status,
            'teacher_name' => (string) ($course->teacher?->name ?? ''),
            'online_room_id' => (int) ($course->online_room_id ?? 0),
            'description' => (string) ($course->description ?? ''),
            'teacher_id' => (int) ($course->teacher_id ?? 0),
            'program_id' => (int) ($course->program_id ?? 0) > 0 ? (int) $course->program_id : null,
        ];

        $status = $membershipStatus ?? '';

        if ($status !== '') {
            $formatted['membership_status'] = $status;
        }

        if ($lookupSource !== null && $lookupSource !== '') {
            $formatted['course_lookup_source'] = $lookupSource;
        }

        return $formatted;
    }

    /**
     * @return array{course: Course, membership_status: string, course_lookup_source: string}|null
     */
    public function courseForLookup(int $userId): ?array
    {
        $active = CourseMember::activeCourseFor($userId);

        if ($active !== null) {
            return [
                'course' => $active,
                'membership_status' => CourseMember::STATUS_ACTIVE,
                'course_lookup_source' => 'active',
            ];
        }

        $member = CourseMember::query()
            ->where('user_id', $userId)
            ->with(['course.teacher', 'course.room'])
            ->orderByRaw("CASE status WHEN 'active' THEN 0 WHEN 'completed' THEN 1 ELSE 2 END")
            ->orderByRaw('COALESCE(completed_at, left_at, joined_at) DESC')
            ->orderByDesc('id')
            ->first();

        if ($member === null || $member->course === null) {
            return null;
        }

        return [
            'course' => $member->course,
            'membership_status' => (string) $member->status,
            'course_lookup_source' => 'history',
        ];
    }

    public function resolveCourseForStudent(int $userId, int $courseId): ?Course
    {
        if ($userId <= 0 || $courseId <= 0) {
            return null;
        }

        $member = CourseMember::query()
            ->where('user_id', $userId)
            ->where('course_id', $courseId)
            ->whereIn('status', [
                CourseMember::STATUS_ACTIVE,
                CourseMember::STATUS_COMPLETED,
                CourseMember::STATUS_LEFT,
            ])
            ->with(['course.teacher', 'course.room', 'course.program'])
            ->first();

        return $member?->course;
    }

    /**
     * @return array{description: string, description_html: string, has_description: bool}
     */
    public function htmlFields(?string $raw): array
    {
        $raw = (string) $raw;
        $has = HtmlContent::has($raw);

        return [
            'description' => $has ? HtmlContent::plain($raw) : trim($raw),
            'description_html' => $has ? HtmlContent::render($raw) : '',
            'has_description' => $has,
        ];
    }

    public function hasHtml(?string $raw): bool
    {
        return HtmlContent::has($raw);
    }

    public function renderHtml(?string $raw): string
    {
        return HtmlContent::has($raw) ? HtmlContent::render($raw) : '';
    }

    public function plainHtml(?string $raw): string
    {
        return HtmlContent::has($raw) ? HtmlContent::plain($raw) : trim((string) $raw);
    }

    public function mediaUrl(?string $path): ?string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        $url = MediaUrl::to($path);

        return $url !== '' ? $url : $path;
    }

    /**
     * @param  array{start: int, end: int, early: int}  $window
     * @return array<string, string|null>
     */
    public function formatWindow(array $window): array
    {
        if (($window['start'] ?? PHP_INT_MAX) === PHP_INT_MAX) {
            return [
                'opens_at' => null,
                'starts_at' => null,
                'ends_at' => null,
            ];
        }

        return [
            'opens_at' => date('c', (int) $window['early']),
            'starts_at' => date('c', (int) $window['start']),
            'ends_at' => date('c', (int) $window['end']),
        ];
    }

    /**
     * @return array<string, string|null>
     */
    public function jitsiMeta(?string $roomCode): array
    {
        $config = app(ClassroomConfig::class);
        $roomCode = trim((string) $roomCode);
        $jaasReady = $config->isJaasReady();
        $domain = JitsiMeetLink::domain($jaasReady, $config->jitsiDomain() !== '' ? $config->jitsiDomain() : null);
        $roomName = JitsiMeetLink::roomName($config->jaasAppId(), $roomCode, $jaasReady);

        if ($roomName === '') {
            return [
                'jitsi_domain' => null,
                'jitsi_room_name' => null,
                'jitsi_meet_url' => null,
                'jitsi_app_scheme' => JitsiMeetLink::APP_SCHEME,
                'jitsi_app_link' => null,
            ];
        }

        return [
            'jitsi_domain' => $domain,
            'jitsi_room_name' => $roomName,
            'jitsi_meet_url' => JitsiMeetLink::baseMeetUrl($domain, $roomName),
            'jitsi_app_scheme' => JitsiMeetLink::APP_SCHEME,
            'jitsi_app_link' => null,
        ];
    }

    /**
     * @return array<string, string|null>
     */
    public function roomWebUrls(int $roomId): array
    {
        $webLobby = $this->absoluteUrl('lop-hoc-online/'.$roomId.'/phong-cho');
        $webRoom = $this->absoluteUrl('lop-hoc-online/'.$roomId.'/vao');

        return [
            'join_url' => $webLobby,
            'meeting_url' => $webRoom,
            'web_lobby' => $webLobby,
            'web_room' => $webRoom,
            'web_detail' => $webLobby,
            'enter_api' => $this->apiPath('lop-hoc-online/'.$roomId.'/vao'),
            'jitsi_token' => $this->apiPath('lop-hoc-online/'.$roomId.'/jitsi-token'),
            'join_request_status' => $this->apiPath('lop-hoc-online/'.$roomId.'/xin-vao/trang-thai'),
            'video_link' => $this->apiPath('lop-hoc-online/'.$roomId.'/video-link'),
            'participants' => $this->apiPath('lop-hoc-online/'.$roomId.'/nguoi-tham-gia'),
            'ping' => $this->apiPath('lop-hoc-online/'.$roomId.'/ping'),
            'leave' => $this->apiPath('lop-hoc-online/'.$roomId.'/roi-phong'),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function roomApiUrls(int $roomId): array
    {
        return [
            'enter_room' => $this->apiPath('lop-hoc-online/'.$roomId.'/vao'),
            'jitsi_token' => $this->apiPath('lop-hoc-online/'.$roomId.'/jitsi-token'),
            'video_link' => $this->apiPath('lop-hoc-online/'.$roomId.'/video-link'),
            'join_request_status' => $this->apiPath('lop-hoc-online/'.$roomId.'/xin-vao/trang-thai'),
            'participants' => $this->apiPath('lop-hoc-online/'.$roomId.'/nguoi-tham-gia'),
            'ping' => $this->apiPath('lop-hoc-online/'.$roomId.'/ping'),
            'leave' => $this->apiPath('lop-hoc-online/'.$roomId.'/roi-phong'),
        ];
    }

    public function formatIso8601(string $datetime): string
    {
        $ts = strtotime($datetime);

        return $ts === false ? $datetime : date('c', $ts);
    }

    public function displayFromSessionRow(array $session): array
    {
        $display = is_array($session['display'] ?? null) ? $session['display'] : [];

        return [
            'time_label' => (string) ($display['timeLabel'] ?? ''),
            'date_label' => (string) ($display['dateLabel'] ?? ''),
            'weekday_full' => (string) ($display['weekdayFull'] ?? ''),
            'weekday_short' => (string) ($display['weekdayShort'] ?? ''),
            'countdown_label' => (string) ($display['countdownLabel'] ?? ''),
            'opens_at_label' => (string) ($display['opensAtLabel'] ?? ''),
            'duration_label' => (string) ($display['durationLabel'] ?? ''),
        ];
    }
}
