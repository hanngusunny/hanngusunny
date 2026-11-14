<header class="pwa-home-head">
    <img class="pwa-mark pwa-mark--sm" src="{{ asset('assets/images/pwa-icon.png') }}" alt="Hàn Ngữ SUNNY" width="32" height="32">
    <div class="pwa-who">
        <div>
            <small>Xin chào</small>
            <strong>{{ $userName }}</strong>
            <em>{{ $roleLabel ?? '' }}@if (($courseTitle ?? '') !== '') · {{ $courseTitle }}@endif</em>
        </div>
        @if (($userAvatar ?? '') !== '')
            <img class="pwa-avatar pwa-avatar-img" src="{{ $userAvatar }}" alt="{{ $userName }}" width="32" height="32">
        @else
            <span class="pwa-avatar">{{ $userInitials }}</span>
        @endif
    </div>
</header>
