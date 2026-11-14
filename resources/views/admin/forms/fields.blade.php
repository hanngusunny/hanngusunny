@extends('admin.layouts.app')
@section('content')
  @if (session('ok')) <p class="alert alert-success">{{ session('ok') }}</p> @endif
  <h1 class="admin-page-title">Trường — {{ $form->name }}</h1>
  <form method="post" action="{{ route('admin.forms.fields.store', $form) }}">@csrf
    <input class="form-control" name="label" placeholder="Nhãn" required>
    <select class="form-control mt-1" name="field_type">
      @foreach ($types as $t) <option value="{{ $t }}">{{ $t }}</option> @endforeach
    </select>
    <textarea class="form-control mt-1" name="options" placeholder="Options (mỗi dòng một giá trị)"></textarea>
    <label class="form-check-label mt-1"><input type="checkbox" name="is_required" value="1"> Bắt buộc</label>
    <button class="btn btn-primary mt-2" type="submit">Thêm trường</button>
  </form>
  <ul class="mt-3">
    @foreach ($fields as $f)
      <li class="mb-3">
        <form method="post" action="{{ route('admin.forms.fields.update', $f) }}">@csrf
          <input class="form-control" name="label" value="{{ $f->label }}" required>
          <select class="form-control mt-1" name="field_type">
            @foreach ($types as $t)
              <option value="{{ $t }}" @selected($f->field_type === $t)>{{ $t }}</option>
            @endforeach
          </select>
          <textarea class="form-control mt-1" name="options">{{ $f->options }}</textarea>
          <label class="form-check-label"><input type="checkbox" name="is_required" value="1" @checked($f->is_required)> Bắt buộc</label>
          <button class="btn btn-sm btn-primary mt-1" type="submit">Lưu</button>
        </form>
        <form method="post" action="{{ route('admin.forms.fields.up', $f) }}" class="inline">@csrf<button class="btn btn-link" type="submit">↑</button></form>
        <form method="post" action="{{ route('admin.forms.fields.down', $f) }}" class="inline">@csrf<button class="btn btn-link" type="submit">↓</button></form>
        <form method="post" action="{{ route('admin.forms.fields.destroy', $f) }}" class="inline">@csrf<button class="btn btn-link" type="submit">Xóa</button></form>
      </li>
    @endforeach
  </ul>
@endsection
