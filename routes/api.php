<?php

use App\Http\Controllers\Api\ZaloMiniAppAssessmentController;
use App\Http\Controllers\Api\ZaloMiniAppAuthController;
use App\Http\Controllers\Api\ZaloMiniAppEvaluationController;
use App\Http\Controllers\Api\ZaloMiniAppSessionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::prefix('v1/zalo-mini-app')->middleware('zalo.apikey')->group(function () {
    Route::post('auth/login', [ZaloMiniAppAuthController::class, 'login']);
    Route::get('buoi-hoc/gan-nhat', [ZaloMiniAppSessionController::class, 'nearestByPhone']);
    Route::get('hoc-vien/danh-gia', [ZaloMiniAppEvaluationController::class, 'weeklyByPhone']);
    Route::get('hoc-vien/bai-kiem-tra', [ZaloMiniAppAssessmentController::class, 'listByPhone']);
    Route::get('hoc-vien/bai-kiem-tra/{id}', [ZaloMiniAppAssessmentController::class, 'detailByPhone'])
        ->whereNumber('id');

    Route::middleware('zalo.apitoken')->group(function () {
        Route::get('auth/me', [ZaloMiniAppAuthController::class, 'me']);
        Route::post('auth/logout', [ZaloMiniAppAuthController::class, 'logout']);
        Route::get('buoi-hoc', [ZaloMiniAppSessionController::class, 'index']);
        Route::get('buoi-hoc/{publicCode}/phong-hoc', [ZaloMiniAppSessionController::class, 'meetingStatus']);
        Route::post('buoi-hoc/{publicCode}/vao-phong', [ZaloMiniAppSessionController::class, 'joinMeeting']);
        Route::get('buoi-hoc/{publicCode}/truoc-buoi', [ZaloMiniAppSessionController::class, 'preStudy']);
        Route::get('buoi-hoc/{publicCode}/sau-buoi', [ZaloMiniAppSessionController::class, 'postStudy']);
        Route::get('buoi-hoc/{publicCode}', [ZaloMiniAppSessionController::class, 'show']);
    });
});
