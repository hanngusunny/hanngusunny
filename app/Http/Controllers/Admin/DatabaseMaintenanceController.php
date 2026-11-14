<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\QuestionRichTextUpgradeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DatabaseMaintenanceController extends Controller
{
    public function index(QuestionRichTextUpgradeService $upgrade): View
    {
        $columns = $upgrade->inspect();
        $pending = count(array_filter(
            $columns,
            static fn (array $row): bool => $row['status'] === 'needs_update'
        ));

        return view('admin.database.index', [
            'columns' => $columns,
            'pending' => $pending,
            'results' => session('upgrade_results', []),
        ]);
    }

    public function expandQuestionText(QuestionRichTextUpgradeService $upgrade): RedirectResponse
    {
        $results = $upgrade->run();
        $upgrade->markMigrationRan();
        $updated = count(array_filter($results, static fn (array $row): bool => $row['action'] === 'updated'));
        $errors = count(array_filter($results, static fn (array $row): bool => $row['action'] === 'error'));

        $redirect = redirect()
            ->route('admin.database.index')
            ->with('upgrade_results', $results);

        if ($errors > 0) {
            return $redirect->with('error', "Đã chạy xong nhưng có {$errors} lệnh lỗi. Xem chi tiết bên dưới.");
        }

        if ($updated === 0) {
            return $redirect->with('info', 'Tất cả cột đã là LONGTEXT, không cần chạy thêm.');
        }

        return $redirect->with('success', "Đã cập nhật {$updated} cột sang LONGTEXT.");
    }
}
