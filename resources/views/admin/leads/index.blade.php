@extends('admin.layouts.app')

@section('content')
  @if (session('content_success'))
    <p class="alert alert-success">{{ session('content_success') }}</p>
  @endif
  <h1 class="admin-page-title">Liên hệ / tư vấn</h1>
  <form method="get" class="flex gap-2 mb-3">
    <input class="form-control" type="month" name="ym" value="{{ $ym }}">
    <button class="btn btn-outline-secondary" type="submit">Lọc tháng</button>
    <a class="btn btn-outline-secondary" href="{{ route('admin.consult-leads.export', $ym !== '' ? ['ym' => $ym] : []) }}">Xuất CSV</a>
  </form>
  <table class="table">
    <thead><tr><th>Tên</th><th>SĐT</th><th>Chủ đề</th><th>Ngày</th><th></th></tr></thead>
    <tbody>
      @forelse ($leads as $lead)
        <tr>
          <td>{{ $lead->full_name }}</td>
          <td>{{ $lead->phone }}</td>
          <td>{{ $lead->topic_label }}</td>
          <td>{{ $lead->created_at }}</td>
          <td>
            <a href="{{ route('admin.consult-leads.show', $lead) }}">Xem</a>
            <form class="inline" method="post" action="{{ route('admin.consult-leads.destroy', $lead) }}" onsubmit="return confirm('Xóa?')">@csrf<button class="btn btn-link" type="submit">Xóa</button></form>
          </td>
        </tr>
      @empty
        <tr><td colspan="5">Chưa có lead</td></tr>
      @endforelse
    </tbody>
  </table>
  {{ $leads->links() }}
@endsection
