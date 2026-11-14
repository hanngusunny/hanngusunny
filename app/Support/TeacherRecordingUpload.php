<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

class TeacherRecordingUpload
{
    public static function store(?UploadedFile $file): ?string
    {
        if ($file === null || ! $file->isValid()) {
            return null;
        }

        if ($file->getSize() > 15 * 1024 * 1024) {
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

        $safeExt = in_array($ext, ['webm', 'ogg', 'mp3', 'wav', 'm4a'], true) ? $ext : 'webm';
        $directory = public_path('uploads/teacher-audio');

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            return null;
        }

        $fileName = 'trec-'.date('Ymd-His').'-'.bin2hex(random_bytes(6)).'.'.$safeExt;

        if (! $file->move($directory, $fileName)) {
            return null;
        }

        return 'uploads/teacher-audio/'.$fileName;
    }
}
