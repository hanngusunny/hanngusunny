<?php

namespace App\Http\Controllers;

use App\Models\CustomForm;
use App\Models\CustomFormSubmission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicFormController extends Controller
{
    public function show(string $slug): View
    {
        $form = CustomForm::query()->where('slug', $slug)->where('is_active', 1)->firstOrFail();

        return view('public.custom-form', [
            'form' => $form,
            'fields' => $form->fields,
            'site' => app(\App\Services\Settings\SiteSettingsService::class)->all(),
        ]);
    }

    public function submit(Request $request, string $slug): RedirectResponse
    {
        $form = CustomForm::query()->where('slug', $slug)->where('is_active', 1)->firstOrFail();
        $rules = [];
        foreach ($form->fields as $field) {
            if ($field->field_type === 'checkbox') {
                $rules[$field->field_key] = $field->is_required ? ['required', 'in:0,1'] : ['nullable', 'in:0,1'];
                continue;
            }
            $rule = $field->is_required ? ['required'] : ['nullable'];
            $rule[] = 'string';
            $rules[$field->field_key] = $rule;
        }
        $payload = $request->validate($rules);
        CustomFormSubmission::query()->create([
            'form_id' => $form->id,
            'payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'ip' => (string) $request->ip(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
            'created_at' => now(),
        ]);

        return back()->with('form_ok', $form->success_message !== '' ? $form->success_message : 'Đã gửi thành công.');
    }
}
