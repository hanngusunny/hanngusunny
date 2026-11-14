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
    <label class="form-label">{{ __('admin_users.users_add_field_name') }}</label>
    <input class="form-control" name="name" value="{{ old('name', $profileUser->name) }}">
    <label class="form-label">{{ __('admin_profile.profile_password_old') }}</label>
    <input class="form-control" type="password" name="password_old">
    <label class="form-label">{{ __('admin_profile.profile_password_new') }}</label>
    <input class="form-control" type="password" name="password_new">
    <label class="form-label">{{ __('admin_profile.profile_password_confirm') }}</label>
    <input class="form-control" type="password" name="password_confirm">
    <label class="form-label">{{ __('admin_profile.profile_avatar') }}</label>
    <input class="form-control" type="file" name="avatar" accept="image/*">
    <div class="form-check mt-2">
      <input class="form-check-input" type="checkbox" name="remove_avatar" value="1" id="remove_avatar">
      <label class="form-check-label" for="remove_avatar">{{ __('admin_profile.profile_remove_avatar') }}</label>
    </div>
    <button class="btn btn-primary mt-3" type="submit">{{ __('admin_profile.profile_save') }}</button>
  </form>
@endsection
