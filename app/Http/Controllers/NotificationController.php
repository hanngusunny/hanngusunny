<?php

namespace App\Http\Controllers;

use App\Models\UserNotificationRead;
use App\Services\TopbarNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request, TopbarNotificationService $service): View
    {
        $user = $request->user();
        $userId = (int) ($user?->id ?? 0);
        $role = (string) ($user?->role ?: 'student');

        $typeFilter = trim((string) $request->query('loai', ''));
        $readFilter = trim((string) $request->query('doc', 'all'));

        if (! in_array($readFilter, ['all', 'unread', 'read'], true)) {
            $readFilter = 'all';
        }

        $all = $service->buildAll($userId, $role);
        $items = $service->filterItems($all['items'], $typeFilter, $readFilter);

        return view('notifications.index', [
            'items' => $items,
            'filters' => [
                'loai' => $typeFilter,
                'doc' => $readFilter,
            ],
            'typeOptions' => $service->getTypeOptions($role),
            'typeCounts' => $all['type_counts'] ?? [],
            'unreadCount' => (int) ($all['unread_count'] ?? 0),
            'totalCount' => count($all['items']),
            'filteredCount' => count($items),
        ]);
    }

    public function markRead(Request $request): JsonResponse
    {
        $userId = (int) ($request->user()?->id ?? 0);

        if ($userId <= 0) {
            return response()->json(['ok' => false, 'message' => 'Unauthorized'], 401);
        }

        $notificationKey = trim((string) $request->input('id', ''));

        if ($notificationKey === '' || strlen($notificationKey) > 120) {
            return response()->json(['ok' => false, 'message' => 'Invalid notification id'], 422);
        }

        UserNotificationRead::markRead($userId, $notificationKey);

        return response()->json(['ok' => true]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $userId = (int) ($request->user()?->id ?? 0);

        if ($userId <= 0) {
            return response()->json(['ok' => false, 'message' => 'Unauthorized'], 401);
        }

        $rawIds = $request->input('ids');
        $ids = is_array($rawIds) ? $rawIds : [];
        $keys = [];

        foreach ($ids as $id) {
            $key = trim((string) $id);

            if ($key !== '' && strlen($key) <= 120) {
                $keys[] = $key;
            }
        }

        if ($keys === []) {
            return response()->json(['ok' => false, 'message' => 'No notification ids'], 422);
        }

        UserNotificationRead::markManyRead($userId, $keys);

        return response()->json(['ok' => true, 'count' => count($keys)]);
    }
}
