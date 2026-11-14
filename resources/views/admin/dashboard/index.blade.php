@extends('admin.layouts.app')

@section('content')
  <h1 class="admin-page-title">{{ __('admin_common.dashboard_heading') }}</h1>
  <p class="admin-page-desc">{{ __('admin_common.dashboard_intro') }}</p>
  <div class="flex flex-wrap gap-3 mt-4">
    <div class="border rounded p-3"><strong>{{ $statPublished }}</strong><br>Bài đã xuất bản</div>
    <div class="border rounded p-3"><strong>{{ $statLeads }}</strong><br>Lead tư vấn</div>
    <div class="border rounded p-3"><strong>{{ $statCommentsPending }}</strong><br>Bình luận chờ duyệt</div>
    <div class="border rounded p-3"><strong>{{ $statVisitsToday }}</strong><br>Lượt xem hôm nay</div>
  </div>
@endsection
