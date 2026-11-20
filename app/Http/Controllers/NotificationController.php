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
        // Luôn nạp toàn bộ items để client-side có thể lọc tức thì 0ms giữa Tất cả, Chưa đọc, Đã đọc mà không cần reload trang
        $items = $typeFilter !== '' ? $service->filterItems($all['items'], $typeFilter, 'all') : $all['items'];

        $groupedItems = [];
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        foreach ($items as $item) {
            $sortAt = (int) ($item['sort_at'] ?? 0);
            if ($sortAt > 0) {
                $date = date('Y-m-d', $sortAt);
                if ($date === $today) {
                    $groupKey = 'Hôm nay';
                } elseif ($date === $yesterday) {
                    $groupKey = 'Hôm qua';
                } else {
                    $groupKey = 'Trước đó';
                }
            } else {
                $groupKey = 'Trước đó';
            }
            $groupedItems[$groupKey][] = $item;
        }

        return view('notifications.index', [
            'items' => $items,
            'groupedItems' => $groupedItems,
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
        TopbarNotificationService::clearBuildCache();

        return response()->json(['ok' => true]);
    }

    public function markAllRead(Request $request, ?TopbarNotificationService $service = null): JsonResponse
    {
        $service ??= app(TopbarNotificationService::class);
        $user = $request->user();
        $userId = (int) ($user?->id ?? 0);

        if ($userId <= 0) {
            return response()->json(['ok' => false, 'message' => 'Unauthorized'], 401);
        }

        $role = (string) ($user?->role ?: 'student');
        $rawIds = $request->input('ids');
        $isGlobalAll = $request->boolean('all') || ! $request->has('ids');

        $keys = [];
        if (is_array($rawIds)) {
            foreach ($rawIds as $id) {
                $key = trim((string) $id);
                if ($key !== '' && strlen($key) <= 120) {
                    $keys[] = $key;
                }
            }
        }

        // Lấy toàn bộ thông báo chưa đọc của user từ hệ thống để đảm bảo 100% không bị sót bất kỳ thông báo nào
        $all = $service->buildAll($userId, $role);
        $allUnreadKeys = [];
        foreach ($all['items'] as $item) {
            if (! empty($item['unread'])) {
                $allUnreadKeys[] = (string) ($item['id'] ?? '');
            }
        }

        $mergedKeys = array_values(array_unique(array_filter(array_merge($keys, $allUnreadKeys))));

        if (! empty($mergedKeys)) {
            UserNotificationRead::markManyRead($userId, $mergedKeys);
        }

        TopbarNotificationService::clearBuildCache();

        return response()->json(['ok' => true, 'count' => count($mergedKeys)]);
    }
}
