<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Support\MediaUrl;
use App\Support\Questions\QuestionUploader;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MediaUploadController extends Controller
{
    public function image(Request $request, QuestionUploader $uploader): JsonResponse
    {
        $file = $request->file('image');

        if ($file === null) {
            return response()->json(['ok' => false, 'message' => 'Chưa chọn ảnh.'], 422);
        }

        $path = $uploader->storeAnswerImage($file);

        if ($path === null) {
            return response()->json([
                'ok' => false,
                'message' => 'Không tải được ảnh. Chỉ hỗ trợ JPG, PNG, GIF, WEBP tối đa 5MB.',
            ], 422);
        }

        $url = MediaUrl::to($path);

        return response()->json([
            'ok' => true,
            'path' => $path,
            'url' => $url,
            'location' => $url,
            'csrfName' => '_token',
            'csrfHash' => csrf_token(),
        ]);
    }

    public function recording(Request $request, QuestionUploader $uploader): JsonResponse
    {
        $file = $request->file('recording');

        if ($file === null) {
            return response()->json(['ok' => false, 'message' => 'Chưa có bản ghi.'], 422);
        }

        $path = $uploader->storeStudentRecording($file);

        if ($path === null) {
            return response()->json([
                'ok' => false,
                'message' => 'Không tải được bản ghi. Chỉ hỗ trợ WEBM, OGG, MP3, WAV tối đa 15MB.',
            ], 422);
        }

        $url = MediaUrl::to($path);

        return response()->json([
            'ok' => true,
            'path' => $path,
            'url' => $url,
            'csrfName' => '_token',
            'csrfHash' => csrf_token(),
        ]);
    }
}
