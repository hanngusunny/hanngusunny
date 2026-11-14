@php
    use App\Services\StudentCourseNavService;
    $navService = $navService ?? app(StudentCourseNavService::class);
    $sessionCode = (string) ($selectedPublicCode ?? '');
    $overviewUrl = $sessionCode !== '' ? $navService->dashboardSessionUrl($activeLevelId, $sessionCode, StudentCourseNavService::TAB_OVERVIEW) : '#';
    $assessmentsUrl = $activeLevelId > 0 ? $navService->dashboardSessionUrl($activeLevelId, $sessionCode, StudentCourseNavService::TAB_ASSESSMENTS) : '#';
    $preUrl = $sessionCode !== '' ? $navService->dashboardSessionUrl($activeLevelId, $sessionCode, StudentCourseNavService::TAB_PRE) : '#';
    $postUrl = $sessionCode !== '' ? $navService->dashboardSessionUrl($activeLevelId, $sessionCode, StudentCourseNavService::TAB_POST) : '#';
    $reviewUrl = $sessionCode !== '' ? $navService->dashboardSessionUrl($activeLevelId, $sessionCode, StudentCourseNavService::TAB_KNOWLEDGE_REVIEW) : '#';
    $knowledgeOpen = in_array($workspaceTab, [StudentCourseNavService::TAB_PRE, StudentCourseNavService::TAB_POST, StudentCourseNavService::TAB_KNOWLEDGE_REVIEW], true);
    $preCount = count($sessionDetail['preQuestions'] ?? []);
    $postCount = count($sessionDetail['quizQuestions'] ?? []);
    $reviewCount = count($sessionDetail['knowledgeReviewQuestions'] ?? []);
    $menuDisabled = $sessionCode === '';
@endphp

<nav class="sw-menu">
    <a class="sw-menu-link{{ $workspaceTab === StudentCourseNavService::TAB_OVERVIEW ? ' is-on' : '' }}{{ $menuDisabled ? ' is-off' : '' }}" href="{{ $overviewUrl }}">
        <span class="sw-menu-ico is-overview"><i class="fa-solid fa-table-cells-large"></i></span>
        <span><strong>Tổng quan buổi học</strong><small>Lịch học & tham gia lớp</small></span>
    </a>
    <a class="sw-menu-link{{ $workspaceTab === StudentCourseNavService::TAB_ASSESSMENTS ? ' is-on' : '' }}" href="{{ $assessmentsUrl }}">
        <span class="sw-menu-ico is-test"><i class="fa-solid fa-list-check"></i></span>
        <span><strong>Bài kiểm tra</strong><small>Danh sách bài kiểm tra khóa học</small></span>
    </a>
    <div class="sw-menu-group{{ $knowledgeOpen ? ' is-open' : '' }}">
        <div class="sw-menu-group-head">
            <span class="sw-menu-ico is-book"><i class="fa-solid fa-book-open"></i></span>
            <span><strong>Kiến thức</strong><small>Bài tập & ôn tập</small></span>
        </div>
        <div class="sw-menu-sub">
            <a class="sw-menu-sublink{{ $workspaceTab === StudentCourseNavService::TAB_PRE ? ' is-on' : '' }}{{ $menuDisabled ? ' is-off' : '' }}" href="{{ $preUrl }}">
                <span>Trước buổi học</span>
                @if ($preCount > 0)<b>{{ $preCount }} câu</b>@endif
            </a>
            <a class="sw-menu-sublink{{ $workspaceTab === StudentCourseNavService::TAB_POST ? ' is-on' : '' }}{{ $menuDisabled ? ' is-off' : '' }}" href="{{ $postUrl }}">
                <span>Sau buổi học</span>
                @if ($postCount > 0)<b>{{ $postCount }} câu</b>@endif
            </a>
            @if ($hasSessionKnowledgeReview ?? false)
                <a class="sw-menu-sublink{{ $workspaceTab === StudentCourseNavService::TAB_KNOWLEDGE_REVIEW ? ' is-on' : '' }}{{ $menuDisabled ? ' is-off' : '' }}" href="{{ $reviewUrl }}">
                    <span>Ôn tập kiến thức</span>
                    @if ($reviewCount > 0)<b>{{ $reviewCount }} câu</b>@endif
                </a>
            @endif
        </div>
    </div>
</nav>
