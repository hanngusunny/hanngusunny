<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminUserRequest;
use App\Http\Requests\Admin\UpdateAdminUserRequest;
use App\Models\AdminUser;
use App\Services\Admin\AdminUserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AdminUserController extends Controller
{
    public function __construct(private readonly AdminUserService $users)
    {
    }

    public function index(): View
    {
        $list = AdminUser::query()->orderByDesc('is_active')->orderBy('id')->get();

        return view('admin.users.index', [
            'pageTitle' => __('admin_users.users_page_title'),
            'adminNavActive' => 'users_list',
            'users' => $list,
        ]);
    }

    public function create(): View
    {
        return view('admin.users.form', [
            'pageTitle' => __('admin_users.users_add_page_title'),
            'adminNavActive' => 'users_add',
            'editUser' => null,
        ]);
    }

    public function store(StoreAdminUserRequest $request): RedirectResponse
    {
        $this->users->create($request->validated(), (bool) $request->user('admin')?->isSuperAdmin());

        return redirect()->route('admin.users.index')->with('users_success', __('admin_users.users_flash_success'));
    }

    public function edit(AdminUser $admin_user): View
    {
        return view('admin.users.form', [
            'pageTitle' => __('admin_users.users_edit_page_title'),
            'adminNavActive' => 'users_edit',
            'editUser' => $admin_user,
        ]);
    }

    public function update(UpdateAdminUserRequest $request, AdminUser $admin_user): RedirectResponse
    {
        $actor = $request->user('admin');
        $isSelf = $actor !== null && (int) $actor->id === (int) $admin_user->id;
        if ($isSelf && ! $request->boolean('is_active')) {
            return redirect()->route('admin.users.edit', $admin_user)->with('users_error', __('admin_users.users_edit_cannot_deactivate_self'));
        }
        $updated = $this->users->update($admin_user, $request->validated(), (bool) $actor?->isSuperAdmin(), $isSelf);
        if ($isSelf) {
            $request->session()->put('admin_is_super', $updated->isSuperAdmin() ? 1 : 0);
        }

        return redirect()->route('admin.users.index')->with('users_success', __('admin_users.users_flash_edit_success'));
    }
}
