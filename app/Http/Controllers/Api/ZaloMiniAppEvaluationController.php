<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\RespondsAsZaloApi;
use App\Http\Controllers\Controller;
use App\Models\CourseMonthlyEvaluation;
use App\Services\Api\ZaloEvaluationApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ZaloMiniAppEvaluationController extends Controller
{
    use RespondsAsZaloApi;

    public function __construct(private ZaloEvaluationApiService $service)
    {
    }

    public function weeklyByPhone(Request $request): JsonResponse
    {
        $phone = (string) ($request->query('phone') ?? $request->query('so_dien_thoai') ?? '');

        if (trim($phone) === '') {
            return $this->failApi('VALIDATION_ERROR', 'Tham số phone (số điện thoại) là bắt buộc.', 422);
        }

        $weekRaw = trim((string) ($request->query('tuan') ?? $request->query('week') ?? ''));
        $evalWeek = null;

        if ($weekRaw !== '') {
            if (preg_match('/^\d{4}-W\d{1,2}$/i', $weekRaw) !== 1) {
                return $this->failApi('VALIDATION_ERROR', 'Tham số tuan phải có định dạng YYYY-Www (vd: 2026-W32).', 422);
            }

            $evalWeek = CourseMonthlyEvaluation::normalizePeriod($weekRaw);
        }

        return $this->fromResult($this->service->weeklyEvaluationByPhone($phone, $evalWeek));
    }
}
