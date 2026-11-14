<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateProfileRequest;
use App\Services\Admin\AdminUserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(private readonly AdminUserService $users)
    {
    }

    public function edit(): View
    {
        $user = auth('admin')->user();

        return view('admin.profile.edit', [
            'pageTitle' => __('admin_profile.profile_page_title'),
            'adminNavActive' => 'profile',
            'profileUser' => $user,
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user('admin');
        $this->users->updateProfile(
            $user,
            $request->validated(),
            $request->file('avatar'),
            $request->boolean('remove_avatar')
        );

        return redirect()->route('admin.profile.edit')->with('profile_success', __('admin_profile.profile_flash_success'));
    }
}
