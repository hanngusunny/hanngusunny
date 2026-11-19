@extends('admin.layouts.app')

@section('content')
  @if (session('profile_success'))
    <p class="alert alert-success">{{ session('profile_success') }}</p>
  @endif
  @if ($errors->any())
    <p class="alert alert-danger">{{ $errors->first() }}</p>
  @endif
  <h1 class="admin-page-title">{{ __('admin_profile.profile_heading') }}</h1>
  <form method="post" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data">
    @csrf
    <x-sunny.input name="name" :label="__('admin_users.users_add_field_name')" :value="$profileUser->name" />
    <x-sunny.input name="password_old" type="password" :label="__('admin_profile.profile_password_old')" />
    <x-sunny.input name="password_new" type="password" :label="__('admin_profile.profile_password_new')" />
    <x-sunny.input name="password_confirm" type="password" :label="__('admin_profile.profile_password_confirm')" />

    <div class="sn-form-group">
      <label class="sn-label" for="avatar">{{ __('admin_profile.profile_avatar') }}</label>
      <input class="sn-input" type="file" id="avatar" name="avatar" accept="image/*">
    </div>

    <div class="form-check mt-2">
      <input class="form-check-input" type="checkbox" name="remove_avatar" value="1" id="remove_avatar">
      <label class="form-check-label" for="remove_avatar">{{ __('admin_profile.profile_remove_avatar') }}</label>
    </div>

    <x-sunny.button type="submit" class="mt-3">{{ __('admin_profile.profile_save') }}</x-sunny.button>
  </form>
@endsection
