<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\RespondsAsZaloApi;
use App\Http\Controllers\Controller;
use App\Services\Api\ZaloSessionApiService;
use App\Services\Api\ZaloSessionQuizApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ZaloMiniAppSessionController extends Controller
{
    use RespondsAsZaloApi;

    public function __construct(
        private ZaloSessionApiService $service,
        private ZaloSessionQuizApiService $quizService,
    ) {
    }

    public function nearestByPhone(Request $request): JsonResponse
    {
        if ($error = $this->service->ensureReady()) {
            return $this->failApi($error['code'], $error['message'], $error['status']);
        }

        $phone = (string) ($request->query('phone') ?? $request->query('so_dien_thoai') ?? '');

        if (trim($phone) === '') {
            return $this->failApi('VALIDATION_ERROR', 'Tham số phone (số điện thoại) là bắt buộc.', 422);
        }

        return $this->fromResult($this->service->getNearestZoomSessionByPhone($phone));
    }

    public function index(Request $request): JsonResponse
    {
        if ($error = $this->service->ensureReady()) {
            return $this->failApi($error['code'], $error['message'], $error['status']);
        }

        $userId = (int) $request->attributes->get('apiUserId', 0);
        $courseId = (int) ($request->query('khoa') ?? $request->query('course_id') ?? 0);
        $levelId = (int) ($request->query('trinh-do') ?? $request->query('level_id') ?? 0);

        return $this->fromResult($this->service->listCourseSessions(
            $userId,
            $courseId > 0 ? $courseId : null,
            $levelId > 0 ? $levelId : null
        ));
    }

    public function show(Request $request, string $publicCode): JsonResponse
    {
        return $this->handleQuizRead(fn () => $this->quizService->getSessionDetail(
            (int) $request->attributes->get('apiUserId', 0),
            $publicCode
        ));
    }

    public function joinMeeting(Request $request, string $publicCode): JsonResponse
    {
        if ($error = $this->service->ensureReady()) {
            return $this->failApi($error['code'], $error['message'], $error['status']);
        }

        $json = $request->json()->all();

        return $this->fromResult($this->service->joinSessionMeeting(
            (int) $request->attributes->get('apiUserId', 0),
            $publicCode,
            $json !== [] ? $json : $request->all()
        ));
    }

    public function meetingStatus(Request $request, string $publicCode): JsonResponse
    {
        if ($error = $this->service->ensureReady()) {
            return $this->failApi($error['code'], $error['message'], $error['status']);
        }

        return $this->fromResult($this->service->getMeetingRoomStatus(
            (int) $request->attributes->get('apiUserId', 0),
            $publicCode
        ));
    }

    public function preStudy(Request $request, string $publicCode): JsonResponse
    {
        return $this->handleQuizRead(fn () => $this->quizService->getSessionPreTab(
            (int) $request->attributes->get('apiUserId', 0),
            $publicCode
        ));
    }

    public function postStudy(Request $request, string $publicCode): JsonResponse
    {
        return $this->handleQuizRead(fn () => $this->quizService->getSessionPostTab(
            (int) $request->attributes->get('apiUserId', 0),
            $publicCode
        ));
    }

    /**
     * @param  callable(): array{payload?: array<string, mixed>, error?: array<string, mixed>}  $action
     */
    private function handleQuizRead(callable $action): JsonResponse
    {
        if ($error = $this->quizService->ensureReady()) {
            return $this->failApi($error['code'], $error['message'], $error['status']);
        }

        return $this->fromResult($action());
    }
}
