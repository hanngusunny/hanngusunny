<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseSession;
use App\Models\OnlineRoom;

class OnlineRoomService
{
    public function ensure(Course $course): ?OnlineRoom
    {
        if ($course->online_room_id) {
            $room = OnlineRoom::query()->find($course->online_room_id);
            if ($room) {
                $this->syncSchedule($course->fresh(['sessions', 'room']) ?? $course);

                return $room->fresh();
            }
        }

        $room = OnlineRoom::query()->create([
            'title' => $course->title,
            'description' => (string) $course->description,
            'room_code' => 'pending',
            'host_id' => (int) $course->teacher_id,
            'scheduled_start' => null,
            'scheduled_end' => null,
            'status' => 'draft',
            'access_type' => 'students',
            'max_participants' => 50,
            'waiting_room' => false,
        ]);

        $room->update(['room_code' => OnlineRoom::generateRoomCode((int) $room->id)]);
        $course->update(['online_room_id' => $room->id]);
        $course->setRelation('room', $room);
        $this->syncSchedule($course->fresh(['sessions']) ?? $course);

        return $room->fresh();
    }

    public function syncSchedule(Course $course): void
    {
        $roomId = (int) $course->online_room_id;
        if ($roomId <= 0) {
            return;
        }

        $room = $course->relationLoaded('room') ? $course->room : OnlineRoom::query()->find($roomId);
        if (! $room) {
            return;
        }

        $sessions = ($course->relationLoaded('sessions') ? $course->sessions : $course->sessions()->get())
            ->filter(fn (CourseSession $session) => $session->isTimed())
            ->values();

        $now = time();
        $liveSession = null;
        $nextSession = null;
        $nextStart = PHP_INT_MAX;

        foreach ($sessions as $session) {
            $window = $session->window();
            if ($now >= $window['early'] && $now <= $window['end']) {
                $liveSession = $session;
                break;
            }
            if ($now < $window['early'] && $window['start'] < $nextStart) {
                $nextStart = $window['start'];
                $nextSession = $session;
            }
        }

        $target = $liveSession ?? $nextSession;
        $hostId = (int) $course->teacher_id;
        $baseTitle = $course->title;

        if ($target === null) {
            $room->update([
                'title' => $baseTitle,
                'description' => (string) $course->description,
                'host_id' => $hostId,
                'status' => $sessions->isNotEmpty() ? 'ended' : 'draft',
            ]);

            return;
        }

        $window = $target->window();
        $sessionTitle = trim((string) $target->title);
        $roomTitle = $sessionTitle !== '' ? $baseTitle.' — '.$sessionTitle : $baseTitle;

        $room->update([
            'title' => $roomTitle,
            'description' => (string) ($target->description ?: $course->description),
            'host_id' => $hostId,
            'scheduled_start' => date('Y-m-d H:i:s', $window['start']),
            'scheduled_end' => date('Y-m-d H:i:s', $window['end']),
            'status' => $liveSession !== null ? 'live' : 'scheduled',
        ]);
    }

    public function deleteIfUnused(?int $roomId): void
    {
        if (! $roomId) {
            return;
        }

        if (Course::query()->where('online_room_id', $roomId)->exists()) {
            return;
        }

        OnlineRoom::query()->whereKey($roomId)->delete();
    }
}
