<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseMember;
use App\Models\CourseSessionQuizAnswer;
use App\Models\StudyProgramLevel;
use App\Support\HtmlContent;

class StudentOverviewService
{
    public function __construct(
        private StudentSessionCatalogService $catalog,
        private StudentCourseNavService $nav,
    ) {
    }

    /**
     * @return array{
     *   course: Course|null,
     *   levels: list<array<string, mixed>>,
     *   nextSchedule: array<string, mixed>
     * }
     */
    public function build(int $userId): array
    {
        $course = CourseMember::activeCourseFor($userId);

        if ($course === null) {
            return [
                'course' => null,
                'levels' => [],
                'nextSchedule' => $this->emptyNextSchedule(),
            ];
        }

        $sessions = $this->catalog->forStudentCourse((int) $course->id, $userId);

        return [
            'course' => $course,
            'levels' => $this->levelCards($course, $sessions, $userId),
            'nextSchedule' => $this->nextSchedule($sessions),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $sessions
     * @return list<array<string, mixed>>
     */
    private function levelCards(Course $course, array $sessions, int $userId): array
    {
        $programId = (int) $course->program_id;

        if ($programId <= 0) {
            return [];
        }

        $levels = StudyProgramLevel::query()
            ->where('program_id', $programId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $tones = ['blue', 'teal', 'violet', 'amber', 'rose'];
        $cards = [];

        foreach ($levels as $index => $level) {
            $levelSessions = $this->catalog->forProgramLevel($sessions, (int) $level->id);
            $stats = $this->catalog->summarize($levelSessions);
            $total = (int) $stats['total'];
            $attended = (int) $stats['attended'];
            $sessionIds = array_values(array_filter(array_map(
                static fn (array $session): int => (int) ($session['id'] ?? 0),
                $levelSessions
            )));

            $cards[] = [
                'id' => (int) $level->id,
                'title' => (string) $level->title,
                'display_title' => trim((string) $level->title) !== '' ? (string) $level->title : 'Trình độ học',
                'description' => (string) ($level->description ?? ''),
                'display_index' => $index + 1,
                'session_count' => $total,
                'attended_count' => $attended,
                'completed_exercises_count' => CourseSessionQuizAnswer::countCompletedExercisesForUser($userId, $sessionIds),
                'live_count' => (int) $stats['live'],
                'ended_count' => (int) $stats['ended'],
                'progress_pct' => $total > 0 ? (int) round(($attended / $total) * 100) : 0,
                'url' => $this->nav->levelUrl((int) $level->id),
                'tone' => $tones[$index % count($tones)],
            ];
        }

        return $cards;
    }

    /**
     * @param  list<array<string, mixed>>  $sessions
     * @return array<string, mixed>
     */
    private function nextSchedule(array $sessions): array
    {
        $nearestId = $this->catalog->nearestSessionId($sessions);

        foreach ($sessions as $session) {
            if ((int) ($session['id'] ?? 0) !== $nearestId || $nearestId <= 0) {
                continue;
            }

            $display = is_array($session['display'] ?? null) ? $session['display'] : [];
            $phase = (string) ($session['phase'] ?? 'upcoming');
            $title = trim((string) ($session['title'] ?? ''));
            $title = $title !== '' ? $title : 'Buổi học';
            $levelId = (int) ($session['level_id'] ?? 0);
            $publicCode = (string) ($session['public_code'] ?? '');
            $preTab = $phase === 'ended' ? StudentCourseNavService::TAB_POST : StudentCourseNavService::TAB_PRE;
            $sessionUrl = $publicCode !== ''
                ? $this->nav->dashboardSessionUrl($levelId, $publicCode, $preTab)
                : null;
            $hasSchedule = ! empty($display['hasSchedule']);
            $timeLabel = trim((string) ($display['timeLabel'] ?? ''));
            $scheduleWhen = '';

            if ($hasSchedule) {
                $scheduleWhen = trim(($display['weekdayFull'] ?? '').', '.($display['dateLabel'] ?? ''));
                if ($timeLabel !== '') {
                    $scheduleWhen = $scheduleWhen !== '' ? $scheduleWhen.' · '.$timeLabel : $timeLabel;
                }
            }

            $materials = (string) ($session['pre_session_materials'] ?? '');
            $hasPreStudy = HtmlContent::has($materials);
            $levelTitle = trim((string) ($session['level_title'] ?? ''));

            if ($levelTitle === '' && $levelId > 0) {
                $levelTitle = trim((string) (StudyProgramLevel::query()->find($levelId)?->title ?? ''));
            }

            $base = [
                'hasSession' => true,
                'session' => $session,
                'sessionUrl' => $sessionUrl,
                'sessionTitle' => $title,
                'title' => $title,
                'scheduleWhen' => $scheduleWhen,
                'timeLabel' => $timeLabel,
                'preSessionPreview' => $hasPreStudy ? \Illuminate\Support\Str::limit(HtmlContent::plain($materials), 140) : '',
                'hasPreStudy' => $hasPreStudy,
                'preSessionHtml' => $hasPreStudy ? HtmlContent::render($materials) : '',
                'levelTitle' => $levelTitle,
                'levelId' => $levelId,
                'levelUrl' => $levelId > 0 ? $this->nav->levelUrl($levelId) : null,
                'countdownLabel' => trim((string) ($display['countdownLabel'] ?? '')),
                'durationLabel' => trim((string) ($display['durationLabel'] ?? '')),
                'opensAtLabel' => trim((string) ($display['opensAtLabel'] ?? '')),
                'hasSchedule' => $hasSchedule,
                'phase' => $phase,
                'publicCode' => $publicCode,
                'url' => $sessionUrl ?: route('overview'),
                'isJoinable' => ! empty($session['is_joinable']),
            ];

            if ($phase === 'live') {
                return array_merge($base, [
                    'label' => 'Đang tới giờ học',
                    'detail' => $title.' · đang diễn ra',
                ]);
            }

            if ($hasSchedule) {
                return array_merge($base, [
                    'label' => 'Buổi học sắp tới',
                    'detail' => $title.' · '.$scheduleWhen,
                ]);
            }

            return array_merge($base, [
                'label' => 'Buổi học sắp tới',
                'detail' => $title.' · chưa lên lịch cụ thể',
            ]);
        }

        return array_merge($this->emptyNextSchedule(), [
            'label' => 'Chưa có buổi học sắp tới',
            'detail' => 'Giáo viên sẽ cập nhật lịch học sớm nhất có thể.',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyNextSchedule(): array
    {
        return [
            'hasSession' => false,
            'session' => null,
            'sessionUrl' => null,
            'sessionTitle' => '',
            'title' => '',
            'scheduleWhen' => '',
            'timeLabel' => '',
            'preSessionPreview' => '',
            'hasPreStudy' => false,
            'preSessionHtml' => '',
            'levelTitle' => '',
            'levelId' => 0,
            'levelUrl' => null,
            'countdownLabel' => '',
            'durationLabel' => '',
            'opensAtLabel' => '',
            'hasSchedule' => false,
            'phase' => '',
            'publicCode' => '',
            'url' => route('overview'),
            'isJoinable' => false,
            'label' => '',
            'detail' => '',
        ];
    }
}
