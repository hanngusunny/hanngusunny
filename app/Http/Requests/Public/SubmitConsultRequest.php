<?php

namespace App\Http\Requests\Public;

use App\Models\ConsultLead;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitConsultRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'chuDe' => ['required', Rule::in(array_keys(ConsultLead::TOPICS))],
            'hoTen' => ['required', 'string', 'max:255'],
            'sdt' => ['required', 'string', 'max:64', 'regex:/^[\d\s+().\-]{8,64}$/u'],
            'tinhThanh' => ['required', 'string', 'max:255'],
            'cauHoi' => ['required', 'string', 'max:20000'],
        ];
    }
}
