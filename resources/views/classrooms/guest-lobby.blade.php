@extends('layouts.classroom')

@section('title', ($room->title ?? 'Phòng học').' · Vào phòng (khách)')

@section('content')
<main class="guest-lobby-shell">
    <article class="guest-lobby-card">
        <header class="guest-lobby-head">
            <span class="guest-lobby-badge"><i class="fa-solid fa-door-open"></i> Khách nick ngoài</span>
            <h1>{{ $room->title ?? 'Phòng học online' }}</h1>
            <p>Nhập tên hiển thị để xin vào lớp. Giáo viên sẽ duyệt trước khi bạn vào phòng video.</p>
        </header>

        @if (session('error'))
            <div class="guest-lobby-alert">{{ session('error') }}</div>
        @endif

        <form action="{{ route('classroom.guest.request', $roomId) }}" method="post" class="guest-lobby-form">
            @csrf
            <label>Tên hiển thị (nick)
                <input type="text" name="guest_name" id="guest_name" required minlength="2" maxlength="80" placeholder="Ví dụ: Minh Anh" autocomplete="nickname">
            </label>
            @if (filled($room->room_password))
                <label>Mật khẩu phòng
                    <input type="password" name="room_password" id="room_password" required>
                </label>
            @endif
            <button class="op-btn" type="submit">
                <i class="fa-solid fa-paper-plane"></i> Gửi yêu cầu vào lớp
            </button>
        </form>

        <p class="guest-lobby-note">
            Đã có tài khoản? <a href="{{ route('logout') }}">Đăng nhập tại đây</a>.
        </p>
    </article>
</main>
@endsection
