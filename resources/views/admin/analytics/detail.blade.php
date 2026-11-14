@extends('admin.layouts.app')
@section('content')
  <p><a href="{{ route('admin.analytics.index', request()->query()) }}">← Báo cáo</a></p>
  <h1 class="admin-page-title">{{ $section }}</h1>
  @if ($log)
    <table class="table">
      <thead><tr><th>Thời gian</th><th>Path</th><th>Nguồn</th><th>IP</th></tr></thead>
      <tbody>
        @foreach ($log as $row)
          <tr>
            <td>{{ $row->created_at }}</td>
            <td>{{ $row->request_path }}</td>
            <td>{{ $row->source_type }}</td>
            <td>{{ $row->client_ip }}</td>
          </tr>
        @endforeach
      </tbody>
    </table>
    {{ $log->links() }}
  @else
    <table class="table">
      <thead><tr><th>Nhãn</th><th>Lượt</th></tr></thead>
      <tbody>
        @forelse ($rows as $row)
          <tr><td>{{ $row->label }}</td><td>{{ $row->c }}</td></tr>
        @empty
          <tr><td colspan="2">Không có dữ liệu</td></tr>
        @endforelse
      </tbody>
    </table>
  @endif
@endsection
