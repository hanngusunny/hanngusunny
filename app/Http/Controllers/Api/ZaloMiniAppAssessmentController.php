<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\RespondsAsZaloApi;
use App\Http\Controllers\Controller;
use App\Services\Api\ZaloAssessmentApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ZaloMiniAppAssessmentController extends Controller
{
    use RespondsAsZaloApi;

    public function __construct(private ZaloAssessmentApiService $service)
    {
    }

    public function listByPhone(Request $request): JsonResponse
    {
        if ($error = $this->service->ensureReady()) {
            return $this->failApi($error['code'], $error['message'], $error['status']);
        }

        $phone = (string) ($request->query('phone') ?? $request->query('so_dien_thoai') ?? '');

        if (trim($phone) === '') {
            return $this->failApi('VALIDATION_ERROR', 'Tham số phone (số điện thoại) là bắt buộc.', 422);
        }

        $courseId = (int) ($request->query('khoa') ?? $request->query('course_id') ?? 0);
        $levelId = (int) ($request->query('trinh-do') ?? $request->query('level_id') ?? 0);
        $monthRaw = (string) ($request->query('thang') ?? $request->query('month') ?? '');

        if ($monthRaw !== '' && preg_match('/^\d{4}-\d{2}$/', $monthRaw) !== 1) {
            return $this->failApi('VALIDATION_ERROR', 'Tham số thang phải có định dạng YYYY-MM.', 422);
        }

        return $this->fromResult($this->service->getStudentListByPhone(
            $phone,
            $courseId > 0 ? $courseId : null,
            $levelId > 0 ? $levelId : null,
            $monthRaw !== '' ? $monthRaw : null
        ));
    }

    public function detailByPhone(Request $request, int $id): JsonResponse
    {
        if ($error = $this->service->ensureReady()) {
            return $this->failApi($error['code'], $error['message'], $error['status']);
        }

        $phone = (string) ($request->query('phone') ?? $request->query('so_dien_thoai') ?? '');

        if (trim($phone) === '') {
            return $this->failApi('VALIDATION_ERROR', 'Tham số phone (số điện thoại) là bắt buộc.', 422);
        }

        if ($id <= 0) {
            return $this->failApi('VALIDATION_ERROR', 'ID bài kiểm tra không hợp lệ.', 422);
        }

        return $this->fromResult($this->service->getStudentDetailByPhone($phone, $id));
    }
}
