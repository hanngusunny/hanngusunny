<?php

namespace App\Support\Questions;

use Illuminate\Http\UploadedFile;

class QuestionUploader
{
    public function storeAnswerImage(UploadedFile $file): ?string
    {
        if (! $file->isValid() || $file->getSize() > 5 * 1024 * 1024) {
            return null;
        }

        $mime = strtolower((string) $file->getMimeType());
        $ext = match (true) {
            str_contains($mime, 'jpeg'), str_contains($mime, 'jpg') => 'jpg',
            str_contains($mime, 'png') => 'png',
            str_contains($mime, 'gif') => 'gif',
            str_contains($mime, 'webp') => 'webp',
            default => null,
        };

        if ($ext === null) {
            return null;
        }

        $uploadDir = public_path('uploads/question-bank/images');

        if (! is_dir($uploadDir) && ! mkdir($uploadDir, 0755, true) && ! is_dir($uploadDir)) {
            return null;
        }

        $fileName = 'qb-img-'.date('Ymd-His').'-'.bin2hex(random_bytes(6)).'.'.$ext;
        $file->move($uploadDir, $fileName);

        return 'uploads/question-bank/images/'.$fileName;
    }

    public function storeStudentRecording(UploadedFile $file): ?string
    {
        if (! $file->isValid() || $file->getSize() > 15 * 1024 * 1024) {
            return null;
        }

        $mime = strtolower((string) $file->getMimeType());
        $ext = strtolower((string) $file->getClientOriginalExtension());
        $ok = str_contains($mime, 'audio')
            || str_contains($mime, 'webm')
            || str_contains($mime, 'ogg')
            || in_array($ext, ['webm', 'ogg', 'mp3', 'wav', 'm4a'], true);

        if (! $ok) {
            return null;
        }

        $uploadDir = public_path('uploads/student-audio');

        if (! is_dir($uploadDir) && ! mkdir($uploadDir, 0755, true) && ! is_dir($uploadDir)) {
            return null;
        }

        $safeExt = in_array($ext, ['webm', 'ogg', 'mp3', 'wav', 'm4a'], true) ? $ext : 'webm';
        $fileName = 'rec-'.date('Ymd-His').'-'.bin2hex(random_bytes(6)).'.'.$safeExt;
        $file->move($uploadDir, $fileName);

        return 'uploads/student-audio/'.$fileName;
    }

    public function storePairImage(UploadedFile $file): ?string
    {
        return app(QuestionParser::class)->storePairImageUpload($file);
    }

    public function storeListeningAudio(UploadedFile $file): ?string
    {
        if (! $file->isValid() || $file->getSize() > 10 * 1024 * 1024) {
            return null;
        }

        $mime = strtolower((string) $file->getMimeType());
        $ext = strtolower((string) $file->getClientOriginalExtension());
        $ok = str_contains($mime, 'mpeg') || str_contains($mime, 'mp3') || $ext === 'mp3';

        if (! $ok) {
            return null;
        }

        $uploadDir = public_path('uploads/question-bank/audio');

        if (! is_dir($uploadDir) && ! mkdir($uploadDir, 0755, true) && ! is_dir($uploadDir)) {
            return null;
        }

        $fileName = 'qb-audio-' . date('Ymd-His') . '-' . bin2hex(random_bytes(6)) . '.mp3';
        $file->move($uploadDir, $fileName);

        return 'uploads/question-bank/audio/' . $fileName;
    }

    public function storePreSessionVideo(UploadedFile $file): ?string
    {
        if (! $file->isValid() || $file->getSize() > 200 * 1024 * 1024) {
            return null;
        }

        $ext = strtolower((string) $file->getClientOriginalExtension());
        $allowed = ['mp4', 'webm', 'mov', 'm4v', 'ogg'];

        if (! in_array($ext, $allowed, true)) {
            return null;
        }

        $uploadDir = public_path('uploads/session-videos');

        if (! is_dir($uploadDir) && ! mkdir($uploadDir, 0755, true) && ! is_dir($uploadDir)) {
            return null;
        }

        $fileName = 'pre-video-' . date('Ymd-His') . '-' . bin2hex(random_bytes(6)) . '.' . $ext;
        $file->move($uploadDir, $fileName);

        return 'uploads/session-videos/' . $fileName;
    }
}
