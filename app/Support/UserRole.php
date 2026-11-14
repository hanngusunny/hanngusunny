<?php

namespace App\Support;

class UserRole
{
    public const ADMIN = 'admin';
    public const TEACHER = 'teacher';
    public const STUDENT = 'student';
    public const ACCOUNTANT = 'accountant';
    public const STUDY_ABROAD_STUDENT = 'study_abroad_student';
    public const LECTURER = 'lecturer';
    public const GUEST = 'guest';

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::ADMIN => 'Quản trị viên',
            self::TEACHER => 'Giáo viên',
            self::STUDENT => 'Học sinh',
            self::ACCOUNTANT => 'Kế toán',
            self::STUDY_ABROAD_STUDENT => 'Du học sinh',
            self::LECTURER => 'Giảng viên',
            self::GUEST => 'Khách',
        ];
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_values(array_filter(array_keys(self::labels()), static fn (string $role): bool => $role !== self::GUEST));
    }

    public static function label(string $role): string
    {
        return self::labels()[$role] ?? 'Học sinh';
    }

    public static function hasStudentCode(string $role): bool
    {
        return in_array($role, [self::STUDENT, self::STUDY_ABROAD_STUDENT], true);
    }

    /**
     * @return list<string>
     */
    public static function teachingRoles(): array
    {
        return [self::ADMIN, self::TEACHER, self::LECTURER];
    }

    /**
     * @return list<string>
     */
    public static function learnerRoles(): array
    {
        return [self::STUDENT, self::STUDY_ABROAD_STUDENT];
    }

    /**
     * @return array<string, string>
     */
    public static function statuses(): array
    {
        return [
            'active' => 'Hoạt động',
            'inactive' => 'Ngưng hoạt động',
        ];
    }
}
