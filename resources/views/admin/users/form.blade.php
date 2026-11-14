@extends('admin.layouts.app')

@section('content')
  @if (session('users_error'))
    <p class="alert alert-danger">{{ session('users_error') }}</p>
  @endif
  <h1 class="admin-page-title">{{ $editUser ? __('admin_users.users_edit_heading') : __('admin_users.users_add_heading') }}</h1>
  <form method="post" action="{{ $editUser ? route('admin.users.update', $editUser) : route('admin.users.store') }}">
    @csrf
    @unless ($editUser)
      <label class="form-label">{{ __('admin_users.users_add_field_username') }}</label>
      <input class="form-control" name="username" value="{{ old('username') }}" required>
    @else
      <p>{{ __('admin_users.users_edit_username_locked') }}: <strong>{{ $editUser->username }}</strong></p>
    @endunless
    <label class="form-label">{{ __('admin_users.users_add_field_password') }}</label>
    <input class="form-control" type="password" name="password" {{ $editUser ? '' : 'required' }}>
    <label class="form-label">{{ __('admin_users.users_add_field_name') }}</label>
    <input class="form-control" name="name" value="{{ old('name', $editUser->name ?? '') }}">
    <div class="form-check mt-2">
      <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $editUser->is_active ?? true))>
      <label class="form-check-label" for="is_active">{{ __('admin_users.users_add_active_label') }}</label>
    </div>
    @if (auth('admin')->user()?->isSuperAdmin())
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="is_super_admin" value="1" id="is_super_admin" @checked(old('is_super_admin', $editUser->is_super_admin ?? false))>
        <label class="form-check-label" for="is_super_admin">{{ __('admin_users.users_field_super_admin') }}</label>
      </div>
    @endif
    <button class="btn btn-primary mt-3" type="submit">{{ $editUser ? __('admin_users.users_edit_btn_submit') : __('admin_users.users_add_btn_submit') }}</button>
  </form>
@endsection
