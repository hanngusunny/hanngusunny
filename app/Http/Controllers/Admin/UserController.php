<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use App\Support\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'search' => trim((string) $request->query('q', '')),
            'role' => trim((string) $request->query('role', '')),
            'status' => trim((string) $request->query('status', '')),
        ];

        $stats = [
            'total' => User::query()->count(),
            'active' => User::query()->where('status', 'active')->count(),
            'admins' => User::query()->where('role', UserRole::ADMIN)->count(),
            'month' => User::query()->where('created_at', '>=', now()->startOfMonth())->count(),
        ];

        $users = User::query()
            ->forAdmin($filters)
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'stats' => $stats,
            'filters' => $filters,
            'roles' => UserRole::labels(),
            'statuses' => UserRole::statuses(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $user = new User;

        if (($data['username'] ?? '') === '' && ! empty($data['phone'])) {
            $data['username'] = $user->generateUsernameFromPhone($data['phone']);
        }

        $user->fill($data);
        $user->syncStudentCode();
        $user->save();

        return redirect()->route('admin.users.index')->with('success', 'Đã thêm tài khoản mới.');
    }

    public function update(UpdateUserRequest $request, int $id): RedirectResponse
    {
        $user = User::query()->find($id);

        if (! $user) {
            return redirect()->route('admin.users.index')->with('error', 'Không tìm thấy người dùng.');
        }

        $data = $request->validated();

        if (($data['username'] ?? '') === '' && ! empty($data['phone'])) {
            $data['username'] = $user->generateUsernameFromPhone($data['phone']);
        }

        if (empty($data['password'])) {
            unset($data['password']);
        }

        $user->fill($data);
        $user->syncStudentCode();
        $user->save();

        return redirect()->route('admin.users.index')->with('success', 'Đã cập nhật tài khoản.');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        if ((int) $request->user()->id === $id) {
            return redirect()->route('admin.users.index')->with('error', 'Không thể xóa tài khoản đang đăng nhập.');
        }

        $user = User::query()->find($id);

        if (! $user) {
            return redirect()->route('admin.users.index')->with('error', 'Không tìm thấy người dùng.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'Đã xóa tài khoản.');
    }

    public function toggleStatus(Request $request, int $id): RedirectResponse
    {
        if ((int) $request->user()->id === $id) {
            return redirect()->route('admin.users.index')->with('error', 'Không thể khóa tài khoản đang đăng nhập.');
        }

        $user = User::query()->find($id);

        if (! $user) {
            return redirect()->route('admin.users.index')->with('error', 'Không tìm thấy người dùng.');
        }

        $user->status = $user->status === 'active' ? 'inactive' : 'active';
        $user->save();

        $message = $user->status === 'inactive' ? 'Đã khóa tài khoản.' : 'Đã mở khóa tài khoản.';

        return redirect()->route('admin.users.index')->with('success', $message);
    }
}
