<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

class SubmitLandingLeadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'phone' => ['required', 'string', 'max:64', 'regex:/^[\d\s+().\-]{8,64}$/u'],
            'program' => ['required', 'string', 'max:64'],
            'academic_score' => ['nullable', 'string', 'max:100'],
            'website' => ['nullable', 'string', 'max:200'],
            'cta_id' => ['nullable', 'string', 'max:100'],
            'landing_page' => ['nullable', 'string', 'max:500'],
        ];
    }
}
