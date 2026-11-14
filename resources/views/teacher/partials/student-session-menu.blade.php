@php
    $courseId = (int) ($course->id ?? 0);
    $sessionId = (int) ($sessionId ?? 0);
    $userId = (int) ($userId ?? 0);
    $activeTab = (string) ($activeTab ?? 'tong-quan');
    $preCount = count($preQuestions ?? []);
    $postCount = count($postQuestions ?? []);
    $reviewCount = count($reviewQuestions ?? []);
    $overviewUrl = route('teacher.course.student', ['id' => $courseId, 'sessionId' => $sessionId, 'userId' => $userId, 'muc' => 'tong-quan']);
    $preUrl = route('teacher.course.student', ['id' => $courseId, 'sessionId' => $sessionId, 'userId' => $userId, 'muc' => 'truoc-buoi']);
    $postUrl = route('teacher.course.student', ['id' => $courseId, 'sessionId' => $sessionId, 'userId' => $userId, 'muc' => 'sau-buoi']);
    $reviewUrl = route('teacher.course.student', ['id' => $courseId, 'sessionId' => $sessionId, 'userId' => $userId, 'muc' => 'kiem-tra']);
    $knowledgeOpen = in_array($activeTab, ['truoc-buoi', 'sau-buoi', 'kiem-tra'], true);
@endphp

<nav class="sw-menu">
    <a class="sw-menu-link{{ $activeTab === 'tong-quan' ? ' is-on' : '' }}" href="{{ $overviewUrl }}">
        <span class="sw-menu-ico is-overview"><i class="fa-solid fa-table-cells-large"></i></span>
        <span><strong>Tổng quan buổi học</strong><small>Điểm danh & nhận xét</small></span>
    </a>
    <div class="sw-menu-group{{ $knowledgeOpen ? ' is-open' : '' }}">
        <div class="sw-menu-group-head">
            <span class="sw-menu-ico is-book"><i class="fa-solid fa-book-open"></i></span>
            <span><strong>Kiến thức</strong><small>Bài tập & ôn tập</small></span>
        </div>
        <div class="sw-menu-sub">
            <a class="sw-menu-sublink{{ $activeTab === 'truoc-buoi' ? ' is-on' : '' }}" href="{{ $preUrl }}">
                <span>Trước buổi học</span>
                @if ($preCount > 0)<b>{{ $preCount }} câu</b>@endif
            </a>
            <a class="sw-menu-sublink{{ $activeTab === 'sau-buoi' ? ' is-on' : '' }}" href="{{ $postUrl }}">
                <span>Sau buổi học</span>
                @if ($postCount > 0)<b>{{ $postCount }} câu</b>@endif
            </a>
            @if ($hasSessionKnowledgeReview ?? false)
                <a class="sw-menu-sublink{{ $activeTab === 'kiem-tra' ? ' is-on' : '' }}" href="{{ $reviewUrl }}">
                    <span>Ôn tập kiến thức</span>
                    @if ($reviewCount > 0)<b>{{ $reviewCount }} câu</b>@endif
                </a>
            @endif
        </div>
    </div>
</nav>
