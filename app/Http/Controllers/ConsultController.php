<?php

namespace App\Http\Controllers;

use App\Http\Requests\Public\SubmitConsultRequest;
use App\Services\Leads\ConsultLeadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ConsultController extends Controller
{
    public function __construct(private readonly ConsultLeadService $leads)
    {
    }

    public function submit(SubmitConsultRequest $request): RedirectResponse
    {
        if (trim((string) $request->input('consult_hp')) !== '') {
            return $this->toNotice(false, __('consult.spam_rejected'));
        }
        $this->leads->createFromRequest($request);

        return $this->toNotice(true, '');
    }

    public function notice(): View|RedirectResponse
    {
        $payload = session('consult_notice');
        if (! is_array($payload)) {
            return redirect()->route('home');
        }
        $success = ! empty($payload['success']);

        return view('public.consult-notice', [
            'success' => $success,
            'title' => $success ? __('consult.notice_title_success') : __('consult.notice_title_error'),
            'body' => $success
                ? __('consult.notice_success_body')
                : (trim((string) ($payload['message'] ?? '')) ?: __('consult.notice_error_generic')),
        ]);
    }

    private function toNotice(bool $success, string $message): RedirectResponse
    {
        return redirect()
            ->route('consult.notice')
            ->with('consult_notice', ['success' => $success, 'message' => $message]);
    }
}
