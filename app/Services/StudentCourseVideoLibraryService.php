<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseMember;
use App\Models\StudyProgramSession;
use App\Support\HtmlContent;
use App\Support\MediaUrl;
use App\Support\VideoEmbed;

class StudentCourseVideoLibraryService
{
    public function __construct(
        private StudentSessionCatalogService $catalog,
        private StudentCourseNavService $nav,
    ) {
    }

    /**
     * @return array{course: Course|null, videos: list<array<string, mixed>>, total_videos: int, navService: StudentCourseNavService}
     */
    public function buildForStudent(int $userId, ?int $courseId = null): array
    {
        $empty = [
            'course' => null,
            'videos' => [],
            'total_videos' => 0,
            'navService' => $this->nav,
        ];

        if ($userId <= 0) {
            return $empty;
        }

        $course = $courseId !== null && $courseId > 0
            ? $this->resolveCourseForStudent($userId, $courseId)
            : CourseMember::activeCourseFor($userId);

        if ($course === null) {
            return $empty;
        }

        if (! $course->relationLoaded('teacher')) {
            $course->load('teacher');
        }

        $sessions = $this->catalog->forStudentCourse((int) $course->id, $userId);
        $fallbackLevel = $this->nav->resolveActiveLevelId($userId);
        $videos = [];
        $sessionNo = 0;
        $globalIndex = 0;

        foreach ($sessions as $session) {
            $sessionNo++;

            if (empty($session['is_viewable'])) {
                continue;
            }

            $sessionId = (int) ($session['id'] ?? 0);
            $publicCode = trim((string) ($session['public_code'] ?? ''));
            $sessionTitle = trim((string) ($session['title'] ?? '')) ?: ('Buổi '.$sessionNo);
            $levelId = (int) ($session['level_id'] ?? 0);
            $detailUrl = $publicCode !== ''
                ? $this->nav->dashboardSessionUrl(
                    $levelId > 0 ? $levelId : $fallbackLevel,
                    $publicCode,
                    StudentCourseNavService::TAB_PRE
                ).'#session-pre-materials'
                : '';

            foreach ($this->extractVideos($session) as $video) {
                $globalIndex++;
                $videoIndex = (int) ($video['index'] ?? 0);
                $videos[] = [
                    'global_index' => $globalIndex,
                    'video_index' => $videoIndex,
                    'session_no' => $sessionNo,
                    'session_id' => $sessionId,
                    'session_public_code' => $publicCode,
                    'session_title' => $sessionTitle,
                    'session_date' => (string) ($session['session_date'] ?? ''),
                    'start_time' => substr((string) ($session['start_time'] ?? ''), 0, 5),
                    'title' => $sessionTitle.' — Video '.$videoIndex,
                    'file_id' => (string) ($video['file_id'] ?? ''),
                    'player_type' => (string) ($video['player_type'] ?? 'drive'),
                    'embed_src' => (string) ($video['embed_src'] ?? ''),
                    'thumbnail_url' => (string) ($video['thumbnail_url'] ?? ''),
                    'share_url' => (string) ($video['share_url'] ?? ''),
                    'detail_url' => $detailUrl,
                ];
            }
        }

        return [
            'course' => $course,
            'videos' => $videos,
            'total_videos' => count($videos),
            'navService' => $this->nav,
        ];
    }

    /**
     * @param  array<string, mixed>  $session
     * @return list<array{index: int, player_type: string, embed_src: string, share_url: string, file_id: string, thumbnail_url: string}>
     */
    public function videosForSession(array $session): array
    {
        return $this->extractVideos($session);
    }

    private function resolveCourseForStudent(int $userId, int $courseId): ?Course
    {
        if ($userId <= 0 || $courseId <= 0) {
            return null;
        }

        $member = CourseMember::query()
            ->where('user_id', $userId)
            ->where('course_id', $courseId)
            ->whereIn('status', [
                CourseMember::STATUS_ACTIVE,
                CourseMember::STATUS_COMPLETED,
                CourseMember::STATUS_LEFT,
            ])
            ->with(['course.teacher'])
            ->first();

        return $member?->course;
    }

    /**
     * @param  array<string, mixed>  $session
     * @return list<array{index: int, player_type: string, embed_src: string, share_url: string, file_id: string, thumbnail_url: string}>
     */
    private function extractVideos(array $session): array
    {
        $sources = $this->collectSources($session);
        $seen = [];
        $items = [];

        foreach ($sources as $source) {
            foreach ($this->parseSource($source) as $item) {
                $key = $item['player_type'].':'.($item['file_id'] !== '' ? $item['file_id'] : $item['embed_src']);

                if (isset($seen[$key]) || $item['embed_src'] === '') {
                    continue;
                }

                $seen[$key] = true;
                $items[] = $item;
            }
        }

        $videos = [];

        foreach ($items as $index => $item) {
            $videos[] = array_merge($item, ['index' => $index + 1]);
        }

        return $videos;
    }

    /**
     * @param  array<string, mixed>  $session
     * @return list<string>
     */
    private function collectSources(array $session): array
    {
        $program = $this->programSessionFor($session);
        $keys = [
            'pre_session_materials',
            'pre_session_videos',
            'pre_session_video_content',
            'pre_session_video_url',
        ];
        $sources = [];

        foreach ($keys as $key) {
            $value = $this->pickField($session, $program, $key);

            if ($value !== '') {
                $sources[] = $value;
            }

            if ($key === 'pre_session_videos') {
                foreach ($this->decodeVideoRecords($value) as $record) {
                    $content = trim((string) ($record['content'] ?? ''));
                    $url = trim((string) ($record['video_url'] ?? ''));

                    if ($content !== '') {
                        $sources[] = $content;
                    }

                    if ($url !== '') {
                        $sources[] = $url;
                    }
                }
            }
        }

        return $sources;
    }

    /**
     * @param  array<string, mixed>  $session
     */
    private function programSessionFor(array $session): ?StudyProgramSession
    {
        $programSessionId = (int) ($session['program_session_id'] ?? 0);

        if ($programSessionId <= 0) {
            return null;
        }

        return StudyProgramSession::query()->find($programSessionId);
    }

    /**
     * @param  array<string, mixed>  $session
     */
    private function pickField(array $session, ?StudyProgramSession $program, string $key): string
    {
        $value = trim((string) ($session[$key] ?? ''));

        if ($this->hasVideoSource($value) || ($key === 'pre_session_video_url' && $value !== '')) {
            return $value;
        }

        if ($program === null) {
            return $value !== '[]' ? $value : '';
        }

        $fallback = trim((string) ($program->{$key} ?? ''));

        return $fallback !== '[]' ? $fallback : '';
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function decodeVideoRecords(string $raw): array
    {
        $raw = trim($raw);

        if ($raw === '' || $raw === '[]' || ! str_starts_with($raw, '[')) {
            return [];
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? array_values(array_filter($decoded, 'is_array')) : [];
    }

    /**
     * @return list<array{player_type: string, embed_src: string, share_url: string, file_id: string, thumbnail_url: string}>
     */
    private function parseSource(string $raw): array
    {
        $raw = html_entity_decode(trim($raw), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if ($raw === '') {
            return [];
        }

        $items = [];

        foreach ($this->extractDriveFileIds($raw) as $fileId) {
            $items[] = [
                'player_type' => 'drive',
                'file_id' => $fileId,
                'embed_src' => $this->drivePreviewUrl($fileId),
                'share_url' => $this->driveShareUrl($fileId),
                'thumbnail_url' => $this->driveThumbnailUrl($fileId),
            ];
        }

        foreach ($this->extractYoutubeIds($raw) as $youtubeId) {
            $items[] = [
                'player_type' => 'youtube',
                'file_id' => $youtubeId,
                'embed_src' => 'https://www.youtube.com/embed/'.$youtubeId,
                'share_url' => 'https://www.youtube.com/watch?v='.$youtubeId,
                'thumbnail_url' => 'https://img.youtube.com/vi/'.$youtubeId.'/hqdefault.jpg',
            ];
        }

        foreach ($this->extractFileUrls($raw) as $src) {
            $items[] = [
                'player_type' => 'file',
                'file_id' => '',
                'embed_src' => $src,
                'share_url' => $src,
                'thumbnail_url' => '',
            ];
        }

        if ($items === [] && $this->looksLikePlainUrl($raw)) {
            $parsed = VideoEmbed::parse($raw);

            if ($parsed !== null && $parsed['type'] === 'file') {
                $items[] = [
                    'player_type' => 'file',
                    'file_id' => '',
                    'embed_src' => $this->publicMediaUrl($parsed['src']),
                    'share_url' => $this->publicMediaUrl($parsed['src']),
                    'thumbnail_url' => '',
                ];
            }
        }

        return $items;
    }

    /**
     * @return list<string>
     */
    private function extractDriveFileIds(string $raw): array
    {
        $ids = [];
        $patterns = [
            '#https?://(?:drive|docs)\.google\.com/file/d/([a-zA-Z0-9_-]+)(?:/[^\s"\'<>]*)?#i',
            '#https?://(?:drive|docs)\.google\.com/open\?id=([a-zA-Z0-9_-]+)#i',
            '#https?://(?:drive|docs)\.google\.com/uc\?(?:[^"\']*&)?id=([a-zA-Z0-9_-]+)#i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $raw, $matches) < 1) {
                continue;
            }

            foreach ($matches[1] as $fileId) {
                $fileId = trim((string) $fileId);

                if ($fileId !== '') {
                    $ids[] = $fileId;
                }
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @return list<string>
     */
    private function extractYoutubeIds(string $raw): array
    {
        if (preg_match_all('/(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/|shorts\/))([a-zA-Z0-9_-]{11})/', $raw, $matches) < 1) {
            return [];
        }

        return array_values(array_unique(array_filter($matches[1])));
    }

    /**
     * @return list<string>
     */
    private function extractFileUrls(string $raw): array
    {
        $urls = [];
        $patterns = [
            '/<video[^>]+src=["\']([^"\']+)["\']/i',
            '/<source[^>]+src=["\']([^"\']+)["\']/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $raw, $matches) < 1) {
                continue;
            }

            foreach ($matches[1] as $src) {
                $src = $this->publicMediaUrl((string) $src);

                if ($src !== '' && ! $this->isDriveOrYoutube($src)) {
                    $urls[] = $src;
                }
            }
        }

        return array_values(array_unique($urls));
    }

    private function hasVideoSource(string $value): bool
    {
        $value = trim($value);

        if ($value === '' || $value === '[]') {
            return false;
        }

        if (HtmlContent::has($value)) {
            return true;
        }

        return $this->extractDriveFileIds($value) !== []
            || $this->extractYoutubeIds($value) !== []
            || $this->extractFileUrls($value) !== [];
    }

    private function looksLikePlainUrl(string $raw): bool
    {
        return ! str_contains($raw, '<') && (str_contains($raw, '://') || str_starts_with($raw, '/'));
    }

    private function isDriveOrYoutube(string $src): bool
    {
        return str_contains($src, 'drive.google.com')
            || str_contains($src, 'docs.google.com')
            || str_contains($src, 'youtube.com')
            || str_contains($src, 'youtu.be');
    }

    private function publicMediaUrl(string $src): string
    {
        $src = trim($src);

        if ($src === '') {
            return '';
        }

        return MediaUrl::to($src);
    }

    private function drivePreviewUrl(string $fileId): string
    {
        return 'https://drive.google.com/file/d/'.rawurlencode($fileId).'/preview';
    }

    private function driveShareUrl(string $fileId): string
    {
        return 'https://drive.google.com/file/d/'.rawurlencode($fileId).'/view?usp=sharing';
    }

    private function driveThumbnailUrl(string $fileId, int $width = 480): string
    {
        return 'https://drive.google.com/thumbnail?id='.rawurlencode($fileId).'&sz=w'.max(120, $width);
    }
}
