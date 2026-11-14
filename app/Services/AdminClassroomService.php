<?php

namespace App\Services;

use App\Models\OnlineRoom;
use App\Models\OnlineRoomParticipant;
use App\Models\OnlineRoomPresetQuestion;
use App\Models\User;
use App\Support\ClassroomConfig;
use App\Support\UserRole;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class AdminClassroomService
{
    public function __construct(
        private ClassroomConfig $config,
    ) {
    }

    public function tablesReady(): bool
    {
        return Schema::hasTable('online_rooms');
    }

    /**
     * @return Collection<int, OnlineRoom>
     */
    public function rooms(): Collection
    {
        $timeout = $this->config->presenceTimeout();

        return OnlineRoom::query()
            ->with('host')
            ->withCount([
                'courses',
                'participants as online_count' => function ($query) use ($timeout) {
                    $cutoff = now()->subSeconds(max(30, $timeout));
                    $query->where('is_online', 1)->where('last_seen_at', '>=', $cutoff);
                },
            ])
            ->orderByDesc('scheduled_start')
            ->orderByDesc('id')
            ->get();
    }

    /**
     * @return Collection<int, User>
     */
    public function hostOptions(?int $currentHostId = null): Collection
    {
        return User::query()
            ->whereIn('role', [UserRole::ADMIN, UserRole::TEACHER])
            ->where(function ($query) use ($currentHostId) {
                $query->where('status', 'active');

                if ($currentHostId) {
                    $query->orWhere('id', $currentHostId);
                }
            })
            ->orderBy('full_name')
            ->get();
    }

    /**
     * @return list<OnlineRoomPresetQuestion>
     */
    public function presets(int $roomId): array
    {
        if (! Schema::hasTable('online_room_preset_questions') || $roomId <= 0) {
            return [];
        }

        return OnlineRoomPresetQuestion::query()
            ->where('room_id', $roomId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): OnlineRoom
    {
        $room = OnlineRoom::query()->create([
            'title' => $data['title'],
            'description' => $data['description'] ?? '',
            'room_code' => 'pending',
            'host_id' => (int) $data['host_id'],
            'scheduled_start' => $data['scheduled_start'] ?? null,
            'scheduled_end' => $data['scheduled_end'] ?? null,
            'status' => $data['status'] ?? 'scheduled',
            'access_type' => $data['access_type'] ?? 'all',
            'max_participants' => (int) ($data['max_participants'] ?? 50),
            'waiting_room' => ! empty($data['waiting_room']),
            'room_password' => $data['room_password'] !== '' ? $data['room_password'] : null,
        ]);

        $room->update(['room_code' => OnlineRoom::generateRoomCode((int) $room->id)]);
        $this->replacePresets((int) $room->id, $data['presets'] ?? []);

        return $room->fresh(['host']) ?? $room;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(OnlineRoom $room, array $data): OnlineRoom
    {
        $password = trim((string) ($data['room_password'] ?? ''));

        $room->fill([
            'title' => $data['title'],
            'description' => $data['description'] ?? '',
            'host_id' => (int) ($data['host_id'] ?: $room->host_id),
            'scheduled_start' => $data['scheduled_start'] ?? null,
            'scheduled_end' => $data['scheduled_end'] ?? null,
            'status' => $data['status'] ?? $room->status,
            'access_type' => $data['access_type'] ?? $room->access_type,
            'max_participants' => (int) ($data['max_participants'] ?? $room->max_participants ?? 50),
            'waiting_room' => ! empty($data['waiting_room']),
            'room_password' => $password !== '' ? $password : $room->room_password,
        ]);
        $room->save();
        $this->replacePresets((int) $room->id, $data['presets'] ?? []);

        return $room->fresh(['host']) ?? $room;
    }

    public function delete(OnlineRoom $room): void
    {
        if (Schema::hasTable('online_room_preset_questions')) {
            OnlineRoomPresetQuestion::query()->where('room_id', $room->id)->delete();
        }

        if (Schema::hasTable('online_room_participants')) {
            OnlineRoomParticipant::query()->where('room_id', $room->id)->delete();
        }

        $room->delete();
    }

    /**
     * @param  array<int|string, mixed>  $presets
     */
    public function replacePresets(int $roomId, array $presets): void
    {
        if (! Schema::hasTable('online_room_preset_questions')) {
            return;
        }

        OnlineRoomPresetQuestion::query()->where('room_id', $roomId)->delete();

        $sort = 0;

        foreach ($presets as $preset) {
            if (! is_array($preset)) {
                continue;
            }

            $question = trim((string) ($preset['question_text'] ?? ''));

            if ($question === '') {
                continue;
            }

            $type = (string) ($preset['question_type'] ?? 'fill');
            $type = $type === 'text' ? 'fill' : $type;

            if (! in_array($type, ['fill', 'choice'], true)) {
                $type = 'fill';
            }

            $options = [];

            if ($type === 'choice' && isset($preset['options']) && is_array($preset['options'])) {
                foreach ($preset['options'] as $option) {
                    $option = trim((string) $option);

                    if ($option !== '') {
                        $options[] = $option;
                    }
                }
            }

            if ($type === 'choice' && count($options) < 2) {
                continue;
            }

            OnlineRoomPresetQuestion::query()->create([
                'room_id' => $roomId,
                'question_text' => $question,
                'question_type' => $type,
                'options_json' => $options !== [] ? json_encode($options, JSON_UNESCAPED_UNICODE) : null,
                'sort_order' => $sort,
            ]);

            $sort++;
        }
    }
}
