<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSchoolRuleRequest;
use App\Services\SchoolRuleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SchoolRuleController extends Controller
{
    public function __construct(
        private SchoolRuleService $rules,
    ) {
    }

    public function index(Request $request): View|RedirectResponse
    {
        if (! $this->rules->tablesReady()) {
            return redirect()->to($request->user()?->homePath() ?? route('dashboard'))
                ->with('error', 'Module nội quy chưa sẵn sàng.');
        }

        $canEdit = $this->rules->canEdit($request->user());

        return view('school_rules.show', [
            'rules' => $this->rules->current(),
            'canEdit' => $canEdit,
            'preview' => false,
        ]);
    }

    public function edit(Request $request): View|RedirectResponse
    {
        if (! $this->rules->tablesReady()) {
            return redirect()->to($request->user()?->homePath() ?? route('dashboard'))
                ->with('error', 'Module nội quy chưa sẵn sàng.');
        }

        if (! $this->rules->canEdit($request->user())) {
            return redirect()->route('school-rules.show');
        }

        return view('school_rules.edit', [
            'rules' => $this->rules->current(),
        ]);
    }

    public function update(UpdateSchoolRuleRequest $request): RedirectResponse|JsonResponse
    {
        $updated = $this->rules->save(
            (string) $request->validated('title'),
            (string) $request->validated('content'),
            $request->user()
        );

        if ($request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'message' => 'Lưu nội quy thành công.',
                'rules' => $updated,
            ]);
        }

        return redirect()->route('school-rules.edit')->with('success', 'Lưu nội quy thành công.');
    }

    public function preview(Request $request): View|RedirectResponse
    {
        if (! $this->rules->canEdit($request->user())) {
            return redirect()->route('school-rules.show');
        }

        return view('school_rules.show', [
            'rules' => $this->rules->current(),
            'canEdit' => true,
            'preview' => true,
        ]);
    }
}
