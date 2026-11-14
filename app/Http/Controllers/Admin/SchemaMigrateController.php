<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\View\View;

class SchemaMigrateController extends Controller
{
    public function index(): View
    {
        $files = collect(glob(database_path('migrations/*.php')) ?: [])
            ->map(fn ($p) => basename($p))
            ->values();

        return view('admin.sql.index', [
            'pageTitle' => 'Cập nhật CSDL',
            'adminNavActive' => 'sql_updates',
            'files' => $files,
        ]);
    }

    public function run(): RedirectResponse
    {
        abort_unless((bool) auth('admin')->user()?->isSuperAdmin(), 403);
        Artisan::call('migrate', ['--force' => true]);

        return back()->with('ok', 'Đã chạy migrate (idempotent, không xóa dữ liệu).');
    }
}
