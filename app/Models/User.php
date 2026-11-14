<?php

namespace App\Models;

use App\Support\MediaUrl;
use App\Support\UserRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'email',
        'phone',
        'password',
        'full_name',
        'student_code',
        'avatar',
        'role',
        'status',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
        'last_login_at' => 'datetime',
    ];

    public function getNameAttribute(): string
    {
        return trim((string) ($this->attributes['full_name'] ?? '')) ?: (string) ($this->attributes['username'] ?? '');
    }

    public static function initialsFrom(?string $name): string
    {
        $name = trim((string) preg_replace('/\s+/u', ' ', (string) $name));

        if ($name === '') {
            return '?';
        }

        $parts = preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($parts === []) {
            return '?';
        }

        if (count($parts) >= 2) {
            return mb_strtoupper(mb_substr($parts[0], 0, 1).mb_substr($parts[array_key_last($parts)], 0, 1));
        }

        $letters = mb_strtoupper(mb_substr($parts[0], 0, 2));

        return $letters !== '' ? $letters : '?';
    }

    public function getInitialAttribute(): string
    {
        return self::initialsFrom($this->name);
    }

    public function getInitialsAttribute(): string
    {
        return $this->initial;
    }

    public function avatarUrl(): ?string
    {
        $path = trim(str_replace('\\', '/', (string) ($this->avatar ?? '')));

        if ($path === '') {
            return null;
        }

        $url = MediaUrl::to($path);

        return $url !== '' ? $url : null;
    }

    public function getRoleLabelAttribute(): string
    {
        return UserRole::label((string) $this->role);
    }

    public function getStatusLabelAttribute(): string
    {
        return UserRole::statuses()[$this->status] ?? $this->status;
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isLearner(): bool
    {
        return in_array((string) $this->role, UserRole::learnerRoles(), true);
    }

    public function isTeacher(): bool
    {
        return $this->role === UserRole::TEACHER;
    }

    public function isGuest(): bool
    {
        return $this->role === UserRole::GUEST;
    }

    public function isStaff(): bool
    {
        return $this->isAdmin() || $this->isTeacher();
    }

    public function courseMemberships()
    {
        return $this->hasMany(CourseMember::class, 'user_id');
    }

    public function homePath(): string
    {
        if ($this->isGuest()) {
            $roomId = (int) session('guest_room_id', 0);

            return $roomId > 0 ? route('classroom.lobby', $roomId) : route('login');
        }

        return match ($this->role) {
            UserRole::STUDENT, UserRole::STUDY_ABROAD_STUDENT => route('overview'),
            default => route('dashboard'),
        };
    }

    public static function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/[\s\-.]/', '', trim($phone)) ?? '';

        if ($phone === '') {
            return '';
        }

        if (str_starts_with($phone, '+84')) {
            $phone = '0'.substr($phone, 3);
        } elseif (str_starts_with($phone, '84') && strlen($phone) >= 11) {
            $phone = '0'.substr($phone, 2);
        }

        if (preg_match('/^9\d{8}$/', $phone) === 1) {
            $phone = '0'.$phone;
        }

        return $phone;
    }

    public static function findByLogin(string $login): ?self
    {
        $login = trim($login);

        if ($login === '') {
            return null;
        }

        $normalizedPhone = self::normalizePhone($login);

        if ($normalizedPhone !== '' && preg_match('/^\d{9,15}$/', $normalizedPhone) === 1) {
            $byPhone = static::query()
                ->where('phone', $normalizedPhone)
                ->where('status', 'active')
                ->where('role', '!=', UserRole::GUEST)
                ->first();

            if ($byPhone) {
                return $byPhone;
            }
        }

        return static::query()
            ->where(function (Builder $query) use ($login) {
                $query->where('email', $login)->orWhere('username', $login);
            })
            ->where('status', 'active')
            ->where('role', '!=', UserRole::GUEST)
            ->first();
    }

    /**
     * Học viên active theo SĐT — khớp hệ thống cũ (role=student, không gồm du học sinh).
     */
    public static function findStudentByPhone(string $phone): ?self
    {
        $normalized = self::normalizePhone($phone);

        if ($normalized !== '' && preg_match('/^\d{9,15}$/', $normalized) === 1) {
            $exact = static::query()
                ->where('phone', $normalized)
                ->where('role', UserRole::STUDENT)
                ->where('status', 'active')
                ->first();

            if ($exact !== null) {
                return $exact;
            }
        }

        $digits = preg_replace('/\D/', '', $phone) ?? '';

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '84') && strlen($digits) >= 11) {
            $digits = '0'.substr($digits, 2);
        }

        if (preg_match('/^9\d{8}$/', $digits) === 1) {
            $digits = '0'.$digits;
        }

        if (preg_match('/^\d{9,15}$/', $digits) === 1) {
            $byDigits = static::query()
                ->where('phone', $digits)
                ->where('role', UserRole::STUDENT)
                ->where('status', 'active')
                ->first();

            if ($byDigits !== null) {
                return $byDigits;
            }
        }

        if (strlen($digits) >= 9) {
            $suffix = substr($digits, -9);

            return static::query()
                ->where('role', UserRole::STUDENT)
                ->where('status', 'active')
                ->whereRaw("RIGHT(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '.', ''), 9) = ?", [$suffix])
                ->first();
        }

        return null;
    }

    public function generateUsernameFromPhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', self::normalizePhone($phone)) ?: 'user';
        $base = 'u'.$digits;
        $username = $base;
        $suffix = 1;

        while (static::query()->where('username', $username)->exists()) {
            $username = $base.$suffix;
            $suffix++;
        }

        return $username;
    }

    public static function nextStudentCode(): string
    {
        $max = (int) static::query()
            ->whereNotNull('student_code')
            ->whereRaw("student_code REGEXP ?", ['^SN-[0-9]{4}$'])
            ->selectRaw('MAX(CAST(SUBSTRING(student_code, 4) AS UNSIGNED)) as max_num')
            ->value('max_num');

        return 'SN-'.str_pad((string) max(1, $max + 1), 4, '0', STR_PAD_LEFT);
    }

    public function syncStudentCode(): void
    {
        if (! UserRole::hasStudentCode((string) $this->role)) {
            $this->student_code = null;

            return;
        }

        if (trim((string) $this->student_code) === '') {
            $this->student_code = self::nextStudentCode();
        }
    }

    /**
     * @param  array{search?: string, role?: string, status?: string}  $filters
     */
    public function scopeForAdmin(Builder $query, array $filters): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $role = trim((string) ($filters['role'] ?? ''));
        $status = trim((string) ($filters['status'] ?? ''));

        if ($search !== '') {
            $query->where(function (Builder $inner) use ($search) {
                $inner->where('full_name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('student_code', 'like', "%{$search}%");
            });
        }

        if (in_array($role, UserRole::keys(), true)) {
            $query->where('role', $role);
        } else {
            $query->where('role', '!=', UserRole::GUEST);
        }

        if (in_array($status, ['active', 'inactive'], true)) {
            $query->where('status', $status);
        }

        return $query;
    }
}
