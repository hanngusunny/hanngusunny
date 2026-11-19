@extends('admin.layouts.app')

@section('content')
  @if (session('users_error'))
    <p class="alert alert-danger">{{ session('users_error') }}</p>
  @endif
  <h1 class="admin-page-title">{{ $editUser ? __('admin_users.users_edit_heading') : __('admin_users.users_add_heading') }}</h1>
  <form method="post" action="{{ $editUser ? route('admin.users.update', $editUser) : route('admin.users.store') }}">
    @csrf
    @unless ($editUser)
      <x-sunny.input name="username" :label="__('admin_users.users_add_field_username')" :required="true" />
    @else
      <p class="mb-3">{{ __('admin_users.users_edit_username_locked') }}: <strong>{{ $editUser->username }}</strong></p>
    @endunless
    <x-sunny.input name="password" type="password" :label="__('admin_users.users_add_field_password')" :required="!$editUser" />
    <x-sunny.input name="name" :label="__('admin_users.users_add_field_name')" :value="$editUser->name ?? ''" />

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
    <x-sunny.button type="submit" class="mt-3">{{ $editUser ? __('admin_users.users_edit_btn_submit') : __('admin_users.users_add_btn_submit') }}</x-sunny.button>
  </form>
@endsection
