@extends('admin.layouts.app')

@section('content')
  @if (session('ok'))
    <p class="alert alert-success">{{ session('ok') }}</p>
  @endif
  <h1 class="admin-page-title">Báo cáo truy cập</h1>
  <form method="get" class="flex gap-2 flex-wrap mb-3">
    <select name="preset" class="form-control" style="max-width:10rem">
      <option value="today" @selected(($preset ?? '') === 'today')>Hôm nay</option>
      <option value="7d" @selected(($preset ?? '') === '7d')>7 ngày</option>
      <option value="90d" @selected(($preset ?? '') === '90d')>90 ngày</option>
    </select>
    <input type="date" name="from" class="form-control" style="max-width:11rem" value="{{ $from }}">
    <input type="date" name="to" class="form-control" style="max-width:11rem" value="{{ $to }}">
    <label class="form-check-label"><input type="checkbox" name="bots" value="1" @checked($includeBots)> Gồm bot</label>
    <button class="btn btn-primary" type="submit">Lọc</button>
    <a class="btn btn-outline-secondary" href="{{ route('admin.analytics.export', request()->query()) }}">Xuất CSV</a>
  </form>
  <p class="mb-3">
    @foreach (['trends','sources','google','pages','referrers','devices','visitors','utm','log'] as $sec)
      <a href="{{ url('/admin/analytics-reports/'.$sec).'?'.http_build_query(request()->query()) }}">{{ $sec }}</a>
      @if (! $loop->last) · @endif
    @endforeach
  </p>
  <p>Lượt xem: <strong>{{ $total }}</strong> — Khách: <strong>{{ $visitors }}</strong></p>
  <h2 class="h5">Nguồn</h2>
  <ul>
    @foreach ($sources as $row)
      <li>{{ $row->source_type }} — {{ $row->c }}</li>
    @endforeach
  </ul>
  <h2 class="h5">Thiết bị</h2>
  <ul>
    @foreach ($devices as $row)
      <li>{{ $row->device_type }} — {{ $row->c }}</li>
    @endforeach
  </ul>
  <h2 class="h5">Trang</h2>
  <table class="table">
    <thead><tr><th>Path</th><th>Lượt</th></tr></thead>
    <tbody>
      @foreach ($pages as $row)
        <tr><td>{{ $row->request_path }}</td><td>{{ $row->c }}</td></tr>
      @endforeach
    </tbody>
  </table>
  <h2 class="h5">Nhật ký</h2>
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
@endsection
