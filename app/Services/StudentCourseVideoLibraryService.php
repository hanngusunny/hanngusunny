<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseMember;
use App\Models\StudyProgramSession;
use App\Support\HtmlContent;
use App\Support\MediaUrl;
use App\Support\VideoEmbed;
use Illuminate\Support\Collection;

class StudentCourseVideoLibraryService
{
    public function __construct(
        private StudentSessionCatalogService $catalog,
        private StudentCourseNavService $nav,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function buildForStudent(int $userId, ?int $courseId = null, ?int $requestedLevelId = null): array
    {
        $empty = [
            'course' => null,
            'course_heading' => 'Thư viện video',
            'course_subtitle' => '',
            'levels' => [],
            'active_level_id' => 0,
            'active_level_title' => '',
            'videos' => [],
            'total_videos' => 0,
            'total_playlist_items' => 0,
            'initial_index' => 0,
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

        $course->loadMissing(['teacher', 'program']);

        $allSessions = $this->catalog->forStudentCourse((int) $course->id, $userId);
        $nearestSessionId = $this->catalog->nearestSessionId($allSessions);
        $fallbackLevel = $this->nav->resolveActiveLevelId($userId);

        // Gom danh sách cấp độ trong khóa học và đánh dấu cấp độ đã mở / đang học
        $levelsById = [];
        $firstSessionDate = '';

        foreach ($allSessions as $session) {
            if ($firstSessionDate === '' && trim((string) ($session['session_date'] ?? '')) !== '') {
                $firstSessionDate = trim((string) $session['session_date']);
            }

            $lid = (int) ($session['level_id'] ?? 0);
            if ($lid <= 0) {
                continue;
            }

            if (! isset($levelsById[$lid])) {
                $levelsById[$lid] = [
                    'id' => $lid,
                    'title' => trim((string) ($session['level_title'] ?? '')) ?: ('Cấp độ #'.$lid),
                    'has_viewable' => false,
                    'has_nearest' => false,
                ];
            }

            if (! empty($session['is_viewable'])) {
                $levelsById[$lid]['has_viewable'] = true;
            }

            if ((int) ($session['id'] ?? 0) === $nearestSessionId) {
                $levelsById[$lid]['has_nearest'] = true;
            }
        }

        $unlockedLevels = array_values(array_filter(
            $levelsById,
            static fn (array $lvl): bool => $lvl['has_viewable']
        ));

        $activeLevelId = $this->resolveActiveLevel($unlockedLevels, $requestedLevelId, $fallbackLevel);
        $levelSessions = $activeLevelId > 0
            ? $this->catalog->forProgramLevel($allSessions, $activeLevelId)
            : $allSessions;

        // Preload toàn bộ StudyProgramSession của các buổi đã mở trong 1 câu query duy nhất (khử N+1)
        $programSessionsMap = $this->preloadProgramSessions($levelSessions);

        $built = $this->buildLevelPlaylist(
            $levelSessions,
            $nearestSessionId,
            $fallbackLevel,
            $programSessionsMap
        );

        // Nếu cấp độ mặc định không có video đã mở nào và không có lọc thủ công, thử chuyển sang cấp độ mở khác có video
        if ($built['playable_count'] === 0 && $requestedLevelId === null && count($unlockedLevels) > 1) {
            foreach ($unlockedLevels as $candidate) {
                if ((int) $candidate['id'] === $activeLevelId) {
                    continue;
                }

                $candSessions = $this->catalog->forProgramLevel($allSessions, (int) $candidate['id']);
                $candMap = $this->preloadProgramSessions($candSessions);
                $candBuilt = $this->buildLevelPlaylist($candSessions, $nearestSessionId, $fallbackLevel, $candMap);

                if ($candBuilt['playable_count'] > 0) {
                    $activeLevelId = (int) $candidate['id'];
                    $built = $candBuilt;
                    break;
                }
            }
        }

        $activeLevelTitle = $activeLevelId > 0 && isset($levelsById[$activeLevelId])
            ? $levelsById[$activeLevelId]['title']
            : '';

        $courseHeading = trim((string) $course->title) ?: 'Thư viện video';
        $subtitleParts = [];
        $programTitle = trim((string) ($course->program?->title ?? ''));
        if ($programTitle !== '' && mb_stripos($courseHeading, $programTitle, 0, 'UTF-8') === false) {
            $subtitleParts[] = $programTitle;
        }
        if ($firstSessionDate !== '') {
            $ts = strtotime($firstSessionDate);
            if ($ts !== false) {
                $subtitleParts[] = 'Lớp online ngày '.date('j/n/Y', $ts);
            }
        }
        if ($activeLevelTitle !== '') {
            $subtitleParts[] = $activeLevelTitle;
        }

        return [
            'course' => $course,
            'course_heading' => $courseHeading,
            'course_subtitle' => implode(' · ', $subtitleParts),
            'levels' => $unlockedLevels,
            'active_level_id' => $activeLevelId,
            'active_level_title' => $activeLevelTitle,
            'videos' => $built['items'],
            'total_videos' => $built['playable_count'],
            'total_playlist_items' => count($built['items']),
            'initial_index' => $built['initial_index'],
            'navService' => $this->nav,
        ];
    }

    /**
     * @param  list<array{id: int, title: string, has_viewable: bool, has_nearest: bool}>  $unlockedLevels
     */
    private function resolveActiveLevel(array $unlockedLevels, ?int $requestedLevelId, int $fallbackLevel): int
    {
        if ($unlockedLevels === []) {
            return 0;
        }

        if ($requestedLevelId !== null && $requestedLevelId > 0) {
            foreach ($unlockedLevels as $lvl) {
                if ((int) $lvl['id'] === $requestedLevelId) {
                    return $requestedLevelId;
                }
            }
        }

        foreach ($unlockedLevels as $lvl) {
            if (! empty($lvl['has_nearest'])) {
                return (int) $lvl['id'];
            }
        }

        if ($fallbackLevel > 0) {
            foreach ($unlockedLevels as $lvl) {
                if ((int) $lvl['id'] === $fallbackLevel) {
                    return $fallbackLevel;
                }
            }
        }

        return (int) $unlockedLevels[0]['id'];
    }

    /**
     * @param  list<array<string, mixed>>  $sessions
     * @return Collection<int, StudyProgramSession>
     */
    private function preloadProgramSessions(array $sessions): Collection
    {
        $ids = [];

        foreach ($sessions as $session) {
            if (empty($session['is_viewable'])) {
                continue;
            }

            $pid = (int) ($session['program_session_id'] ?? 0);
            if ($pid > 0) {
                $ids[$pid] = $pid;
            }
        }

        if ($ids === []) {
            return collect();
        }

        return StudyProgramSession::query()
            ->whereIn('id', array_values($ids))
            ->get()
            ->keyBy('id');
    }

    /**
     * @param  list<array<string, mixed>>  $levelSessions
     * @param  Collection<int, StudyProgramSession>  $programSessionsMap
     * @return array{items: list<array<string, mixed>>, playable_count: int, initial_index: int}
     */
    private function buildLevelPlaylist(
        array $levelSessions,
        int $nearestSessionId,
        int $fallbackLevel,
        Collection $programSessionsMap
    ): array {
        $items = [];
        $sessionOrder = 0;
        $globalIndex = 0;
        $playableCount = 0;
        $firstLearningIdx = null;
        $firstPlayableIdx = null;

        foreach ($levelSessions as $session) {
            $sessionOrder++;
            $sessionId = (int) ($session['id'] ?? 0);
            $sessionTitle = trim((string) ($session['title'] ?? '')) ?: ('Buổi '.$sessionOrder);
            $displaySessionNo = $this->extractSessionNumber($sessionTitle, $sessionOrder);
            $cleanTitle = $this->stripSessionPrefix($sessionTitle);
            $isViewable = ! empty($session['is_viewable']);

            // Buổi chưa mở: chỉ hiển thị thẻ trạng thái Chưa mở, tuyệt đối không xuất link video ra HTML
            if (! $isViewable) {
                $globalIndex++;
                $items[] = [
                    'global_index' => $globalIndex,
                    'video_index' => 1,
                    'session_video_count' => 1,
                    'session_no' => $displaySessionNo,
                    'session_id' => $sessionId,
                    'session_public_code' => '',
                    'session_title' => $sessionTitle,
                    'card_kicker' => 'Buổi '.$displaySessionNo,
                    'card_title' => $cleanTitle,
                    'display_title' => 'Buổi '.$displaySessionNo.': '.$cleanTitle,
                    'session_date' => (string) ($session['session_date'] ?? ''),
                    'start_time' => substr((string) ($session['start_time'] ?? ''), 0, 5),
                    'title' => $sessionTitle,
                    'status' => 'locked',
                    'status_label' => 'Chưa mở',
                    'is_locked' => true,
                    'file_id' => '',
                    'player_type' => '',
                    'embed_src' => '',
                    'thumbnail_url' => '',
                    'share_url' => '',
                    'detail_url' => '',
                ];
                continue;
            }

            $sessionVideos = $this->extractVideos($session, $programSessionsMap);
            if ($sessionVideos === []) {
                continue;
            }

            $publicCode = trim((string) ($session['public_code'] ?? ''));
            $levelId = (int) ($session['level_id'] ?? 0);
            $detailUrl = $publicCode !== ''
                ? $this->nav->dashboardSessionUrl(
                    $levelId > 0 ? $levelId : $fallbackLevel,
                    $publicCode,
                    StudentCourseNavService::TAB_PRE
                ).'#session-pre-materials'
                : '';

            $isLearning = ($sessionId === $nearestSessionId && $nearestSessionId > 0)
                || (string) ($session['phase'] ?? '') === 'live';
            $statusKey = $isLearning ? 'learning' : 'done';
            $statusLabel = $isLearning ? 'Đang học' : 'Đã học';
            $videoCountInSession = count($sessionVideos);

            foreach ($sessionVideos as $video) {
                $globalIndex++;
                $playableCount++;
                $videoIndex = (int) ($video['index'] ?? 1);
                $kicker = 'Buổi '.$displaySessionNo.($videoCountInSession > 1 ? ' · Video '.$videoIndex : '');
                $displayTitle = 'Buổi '.$displaySessionNo.': '.$cleanTitle
                    .($videoCountInSession > 1 ? ' (Video '.$videoIndex.')' : '');

                $idx = count($items);
                if ($firstPlayableIdx === null) {
                    $firstPlayableIdx = $idx;
                }
                if ($isLearning && $firstLearningIdx === null) {
                    $firstLearningIdx = $idx;
                }

                $items[] = [
                    'global_index' => $globalIndex,
                    'video_index' => $videoIndex,
                    'session_video_count' => $videoCountInSession,
                    'session_no' => $displaySessionNo,
                    'session_id' => $sessionId,
                    'session_public_code' => $publicCode,
                    'session_title' => $sessionTitle,
                    'card_kicker' => $kicker,
                    'card_title' => $cleanTitle,
                    'display_title' => $displayTitle,
                    'session_date' => (string) ($session['session_date'] ?? ''),
                    'start_time' => substr((string) ($session['start_time'] ?? ''), 0, 5),
                    'title' => $sessionTitle.' — Video '.$videoIndex,
                    'status' => $statusKey,
                    'status_label' => $statusLabel,
                    'is_locked' => false,
                    'file_id' => (string) ($video['file_id'] ?? ''),
                    'player_type' => (string) ($video['player_type'] ?? 'drive'),
                    'embed_src' => (string) ($video['embed_src'] ?? ''),
                    'thumbnail_url' => (string) ($video['thumbnail_url'] ?? ''),
                    'share_url' => (string) ($video['share_url'] ?? ''),
                    'detail_url' => $detailUrl,
                ];
            }
        }

        $initialIndex = $firstLearningIdx ?? ($firstPlayableIdx ?? 0);

        return [
            'items' => $items,
            'playable_count' => $playableCount,
            'initial_index' => $initialIndex,
        ];
    }

    private function extractSessionNumber(string $title, int $fallback): int
    {
        if (preg_match('/^Buổi\s+(\d+)\b/iu', trim($title), $matches) === 1) {
            $num = (int) $matches[1];
            if ($num > 0) {
                return $num;
            }
        }

        return max(1, $fallback);
    }

    private function stripSessionPrefix(string $title): string
    {
        $cleaned = preg_replace('/^Buổi\s+\d+\s*[:：\-–·]?\s*/iu', '', trim($title));
        $cleaned = trim((string) $cleaned);

        return $cleaned !== '' ? $cleaned : trim($title);
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
            ->with(['course.teacher', 'course.program'])
            ->first();

        return $member?->course;
    }

    /**
     * @param  array<string, mixed>  $session
     * @param  Collection<int, StudyProgramSession>|null  $programSessionsMap
     * @return list<array{index: int, player_type: string, embed_src: string, share_url: string, file_id: string, thumbnail_url: string}>
     */
    private function extractVideos(array $session, ?Collection $programSessionsMap = null): array
    {
        $sources = $this->collectSources($session, $programSessionsMap);
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
     * @param  Collection<int, StudyProgramSession>|null  $programSessionsMap
     * @return list<string>
     */
    private function collectSources(array $session, ?Collection $programSessionsMap = null): array
    {
        $program = $this->programSessionFor($session, $programSessionsMap);
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
     * @param  Collection<int, StudyProgramSession>|null  $programSessionsMap
     */
    private function programSessionFor(array $session, ?Collection $programSessionsMap = null): ?StudyProgramSession
    {
        $programSessionId = (int) ($session['program_session_id'] ?? 0);

        if ($programSessionId <= 0) {
            return null;
        }

        if ($programSessionsMap !== null) {
            return $programSessionsMap->get($programSessionId);
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
