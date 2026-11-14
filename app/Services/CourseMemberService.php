<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseMember;
use App\Models\User;
use App\Support\UserRole;
use Illuminate\Support\Facades\DB;
use Throwable;

class CourseMemberService
{
    /**
     * @return array{ok: bool, message?: string, member?: CourseMember}
     */
    public function add(Course $course, int $userId): array
    {
        if (! $course->isActive()) {
            return ['ok' => false, 'message' => 'Không thể thêm học viên vào khóa này.'];
        }

        $user = User::query()->find($userId);
        if (! $user || ! in_array((string) $user->role, UserRole::learnerRoles(), true)) {
            return ['ok' => false, 'message' => 'Học viên không hợp lệ.'];
        }

        $other = CourseMember::query()
            ->where('user_id', $userId)
            ->where('status', CourseMember::STATUS_ACTIVE)
            ->first();

        if ($other && (int) $other->course_id !== (int) $course->id) {
            $otherTitle = trim((string) Course::query()->whereKey($other->course_id)->value('title'));

            return [
                'ok' => false,
                'message' => $otherTitle !== ''
                    ? 'Học viên đang học khóa "'.$otherTitle.'". Mỗi học viên chỉ được ở một lớp — gỡ khỏi khóa cũ trước.'
                    : 'Học viên đang thuộc khóa học khác. Mỗi học viên chỉ được ở một lớp — gỡ khỏi khóa cũ trước.',
            ];
        }

        $existing = CourseMember::query()
            ->where('course_id', $course->id)
            ->where('user_id', $userId)
            ->first();
        $now = now();

        if ($existing) {
            if ($existing->isActive()) {
                return ['ok' => false, 'message' => 'Học viên đã có trong khóa này.'];
            }

            $existing->fill([
                'status' => CourseMember::STATUS_ACTIVE,
                'joined_at' => $now,
                'left_at' => null,
                'completed_at' => null,
            ]);
            $existing->applyActiveUserKey();
            $existing->save();

            return ['ok' => true, 'member' => $existing];
        }

        $member = new CourseMember([
            'course_id' => $course->id,
            'user_id' => $userId,
            'status' => CourseMember::STATUS_ACTIVE,
            'joined_at' => $now,
        ]);
        $member->applyActiveUserKey();
        $member->save();

        return ['ok' => true, 'member' => $member];
    }

    public function remove(Course $course, int $userId): bool
    {
        $row = CourseMember::query()
            ->where('course_id', $course->id)
            ->where('user_id', $userId)
            ->where('status', CourseMember::STATUS_ACTIVE)
            ->first();

        if (! $row) {
            return false;
        }

        $row->fill([
            'status' => CourseMember::STATUS_LEFT,
            'left_at' => now(),
        ]);
        $row->applyActiveUserKey();
        $row->save();

        return true;
    }

    public function complete(Course $course, int $userId): bool
    {
        $row = CourseMember::query()
            ->where('course_id', $course->id)
            ->where('user_id', $userId)
            ->where('status', CourseMember::STATUS_ACTIVE)
            ->first();

        if (! $row) {
            return false;
        }

        $row->fill([
            'status' => CourseMember::STATUS_COMPLETED,
            'completed_at' => now(),
            'left_at' => null,
        ]);
        $row->applyActiveUserKey();
        $row->save();

        return true;
    }

    /**
     * @return array{ok: bool, message?: string}
     */
    public function transfer(Course $from, Course $to, int $userId): array
    {
        if ((int) $from->id === (int) $to->id) {
            return ['ok' => false, 'message' => 'Không thể chuyển sang cùng một khóa.'];
        }

        if (! $to->isActive()) {
            return ['ok' => false, 'message' => 'Khóa đích không tồn tại hoặc đã đóng.'];
        }

        $active = CourseMember::query()
            ->where('user_id', $userId)
            ->where('status', CourseMember::STATUS_ACTIVE)
            ->first();

        if (! $active || (int) $active->course_id !== (int) $from->id) {
            return ['ok' => false, 'message' => 'Học viên không đang học khóa này.'];
        }

        try {
            return DB::transaction(function () use ($from, $to, $userId) {
                if (! $this->complete($from, $userId)) {
                    return ['ok' => false, 'message' => 'Không thể cập nhật khóa cũ.'];
                }

                $add = $this->add($to, $userId);
                if (! $add['ok']) {
                    throw new \RuntimeException($add['message'] ?? 'Không thể ghi danh khóa mới.');
                }

                return ['ok' => true];
            });
        } catch (Throwable $e) {
            return ['ok' => false, 'message' => $e->getMessage() ?: 'Không thể chuyển học viên. Vui lòng thử lại.'];
        }
    }

    public function releaseActiveMembers(Course $course): int
    {
        $count = 0;
        foreach ($course->activeMembers()->get() as $member) {
            if ($this->remove($course, (int) $member->user_id)) {
                $count++;
            }
        }

        return $count;
    }
}
