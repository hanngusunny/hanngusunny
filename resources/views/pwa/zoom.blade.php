@extends('layouts.pwa', ['body_class' => 'is-home'])

@section('title', 'Zoom')

@section('content')
<main class="pwa-home pwa-page">
    @include('pwa.partials.header')

    @if (session('error'))
        <div class="pwa-alert is-error">{{ session('error') }}</div>
    @endif
    @if (session('success') || session('info'))
        <div class="pwa-alert is-ok">{{ session('success') ?: session('info') }}</div>
    @endif

    <div class="pwa-course-intro">
        <p class="pwa-kicker">Phòng học online</p>
        <h1>Zoom</h1>
    </div>

    @forelse (($onlineRooms ?? []) as $item)
        <article class="pwa-card pwa-room-card">
            <div class="pwa-room-card-head">
                <span class="pwa-phase pwa-phase--{{ $item['isLive'] ? 'live' : ($item['status'] ?? 'upcoming') }}">{{ $item['statusLabel'] }}</span>
                <span class="pwa-room-online"><i class="fa-solid fa-users"></i> {{ $item['onlineCount'] }}/{{ $item['maxParticipants'] }}</span>
            </div>
            <h3>{{ $item['title'] }}</h3>
            @if (($item['description'] ?? '') !== '')
                <p class="pwa-help">{{ $item['description'] }}</p>
            @endif
            @if (($item['hostName'] ?? '') !== '')
                <p class="pwa-meta"><i class="fa-solid fa-id-badge"></i> {{ $item['hostName'] }}</p>
            @endif
            @if (($item['startLabel'] ?? '') !== '')
                <p class="pwa-meta"><i class="fa-regular fa-clock"></i> {{ $item['startLabel'] }}</p>
            @endif
            @if (! empty($isStaff) && ($item['code'] ?? '') !== '')
                <p class="pwa-meta"><i class="fa-solid fa-key"></i> {{ $item['code'] }}</p>
            @endif
            <form method="post" action="{{ route('pwa.join') }}" data-pwa-wait="Đang vào lớp…">
                @csrf
                <input type="hidden" name="room_id" value="{{ (int) $item['id'] }}">
                @if (! empty($item['hasPassword']))
                    <input class="pwa-room-password" type="password" name="room_password" placeholder="Mật khẩu phòng" autocomplete="current-password">
                @endif
                <button type="submit" class="pwa-btn">
                    <i class="fa-solid fa-video"></i>
                    {{ ! empty($item['isLive']) ? 'Vào Zoom ngay' : 'Vào phòng chờ' }}
                </button>
            </form>
        </article>
    @empty
        <section class="pwa-card">
            <h2>Chưa có phòng Zoom</h2>
            <p class="pwa-help">Giáo viên sẽ mở phòng khi đến giờ học. Hãy quay lại sau.</p>
        </section>
    @endforelse
</main>
@endsection
