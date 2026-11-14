<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseMember;
use App\Models\CourseSession;
use App\Models\StudyProgramLevel;
use App\Models\StudyProgramSession;
use Illuminate\Support\Facades\Session;

class StudentCourseNavService
{
    public const TAB_PRE = 'pre';

    public const TAB_POST = 'post';

    public const TAB_KNOWLEDGE_REVIEW = 'on-tap-kien-thuc';

    public const TAB_OVERVIEW = 'tong-quan';

    public const TAB_ASSESSMENTS = 'bai-kiem-tra';

    private const SESSION_LEVEL_KEY = 'student_active_level_id';

    private const SESSION_COURSE_KEY = 'student_active_course_id';

    public function overviewUrl(): string
    {
        return route('overview');
    }

    public function levelUrl(int $levelId): string
    {
        return $levelId > 0
            ? route('dashboard', ['trinh-do' => $levelId])
            : route('dashboard');
    }

    public function dashboardUrlForStudent(int $userId): string
    {
        $levelId = $this->resolveActiveLevelId($userId);

        return $levelId > 0
            ? route('dashboard', ['trinh-do' => $levelId])
            : route('dashboard');
    }

    public function assessmentsPageUrl(int $levelId, ?string $publicCode = null): string
    {
        if ($levelId <= 0) {
            return route('student.assessments');
        }

        $params = ['trinh-do' => (string) $levelId];

        if ($publicCode !== null && $publicCode !== '') {
            $params['buoi'] = $publicCode;
        }

        return route('student.assessments', $params);
    }

    public function assessmentsUrlForStudent(int $userId): string
    {
        return $this->assessmentsPageUrl($this->resolveActiveLevelId($userId));
    }

    public function progressUrl(): string
    {
        return route('student.progress');
    }

    public function isProgressTab(mixed $tab): bool
    {
        return in_array($tab, ['tien-do-hoc-tap', 'tien-do', 'progress'], true);
    }

    public function videoLibraryUrl(): string
    {
        return route('student.video-library');
    }

    public function isVideoLibraryTab(mixed $tab): bool
    {
        return in_array($tab, ['thu-vien-video-khoa-hoc', 'video-khoa-hoc'], true);
    }

    public function evaluationsUrl(): string
    {
        return route('student.evaluations');
    }

    public function isEvaluationsTab(mixed $tab): bool
    {
        return in_array($tab, ['danh-gia-hoc-vien', 'danh-gia', 'evaluations'], true);
    }

    public function rememberActiveContext(int $courseId, int $levelId): void
    {
        if ($courseId <= 0 || $levelId <= 0) {
            return;
        }

        Session::put([
            self::SESSION_LEVEL_KEY => $levelId,
            self::SESSION_COURSE_KEY => $courseId,
        ]);
    }

    public function resolveActiveLevelId(int $userId, int $requestedLevelId = 0): int
    {
        if ($requestedLevelId > 0) {
            return $requestedLevelId;
        }

        if ($userId <= 0) {
            return 0;
        }

        $course = CourseMember::activeCourseFor($userId);

        if ($course === null) {
            return 0;
        }

        $storedCourseId = (int) Session::get(self::SESSION_COURSE_KEY, 0);
        $storedLevelId = (int) Session::get(self::SESSION_LEVEL_KEY, 0);

        if ($storedCourseId === (int) $course->id && $storedLevelId > 0 && $this->isValidLevelForCourse($course, $storedLevelId)) {
            return $storedLevelId;
        }

        return $this->inferDefaultLevelId($course, $userId);
    }

    public function dashboardSessionUrl(int $levelId, string $publicCode, ?string $tab = null): string
    {
        $params = [
            'trinh-do' => (string) $levelId,
            'buoi' => $publicCode,
        ];

        if ($tab === self::TAB_ASSESSMENTS) {
            return $this->assessmentsPageUrl($levelId, $publicCode);
        }

        if ($tab === self::TAB_OVERVIEW) {
            $params['tab'] = self::TAB_OVERVIEW;
        } elseif (in_array($tab, [self::TAB_PRE, self::TAB_POST, self::TAB_KNOWLEDGE_REVIEW], true)) {
            $params['tab'] = $tab;
        }

        return route('dashboard', $params);
    }

    /**
     * @param  array<string, mixed>  $session
     */
    public function defaultTabForSession(array $session): string
    {
        $phase = (string) ($session['phase'] ?? '');

        return $phase === 'ended' ? self::TAB_POST : self::TAB_PRE;
    }

    public function isSessionContentTab(mixed $tab): bool
    {
        return in_array($tab, [
            self::TAB_PRE,
            self::TAB_POST,
            self::TAB_KNOWLEDGE_REVIEW,
            self::TAB_OVERVIEW,
            self::TAB_ASSESSMENTS,
        ], true);
    }

    public function isAssessmentsTab(mixed $tab): bool
    {
        return $tab === self::TAB_ASSESSMENTS;
    }

    /**
     * @return array{level: StudyProgramLevel|null, level_id: int}
     */
    public function resolveLevelContext(?Course $course, CourseSession $session): array
    {
        $programSessionId = (int) $session->program_session_id;
        $programId = (int) ($course?->program_id ?? 0);

        if ($programSessionId <= 0 || $programId <= 0) {
            return ['level' => null, 'level_id' => 0];
        }

        $programSession = StudyProgramSession::query()->find($programSessionId);
        $levelId = (int) ($programSession?->level_id ?? 0);

        if ($levelId <= 0) {
            return ['level' => null, 'level_id' => 0];
        }

        $level = StudyProgramLevel::query()
            ->where('program_id', $programId)
            ->where('id', $levelId)
            ->first();

        return [
            'level' => $level,
            'level_id' => $levelId,
        ];
    }

    private function isValidLevelForCourse(Course $course, int $levelId): bool
    {
        $programId = (int) ($course->program_id ?? 0);

        if ($programId <= 0 || $levelId <= 0) {
            return false;
        }

        return StudyProgramLevel::query()
            ->where('program_id', $programId)
            ->where('id', $levelId)
            ->exists();
    }

    private function inferDefaultLevelId(Course $course, int $userId): int
    {
        $catalog = app(StudentSessionCatalogService::class);
        $sessions = $catalog->forStudentCourse((int) $course->id, $userId);
        $nearestId = $catalog->nearestSessionId($sessions);

        foreach ($sessions as $session) {
            if ((int) ($session['id'] ?? 0) === $nearestId) {
                $levelId = (int) ($session['level_id'] ?? 0);

                if ($levelId > 0) {
                    return $levelId;
                }
            }
        }

        $firstLevel = StudyProgramLevel::query()
            ->where('program_id', (int) $course->program_id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();

        return (int) ($firstLevel?->id ?? 0);
    }
}
