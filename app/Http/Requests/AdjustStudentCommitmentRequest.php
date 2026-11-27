<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdjustStudentCommitmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isTeacher() === true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'metric' => strtolower(trim((string) $this->input('metric', ''))),
            'delta' => (int) $this->input('delta', 0),
            'eval_week' => trim((string) $this->input('eval_week', '')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'metric' => ['required', 'in:absence,homework'],
            'delta' => ['required', 'integer', 'in:-1,1'],
            'eval_week' => ['nullable', 'string', 'regex:/^\d{4}-W\d{1,2}$/i'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'metric.required' => 'Thiếu loại mục cam kết.',
            'metric.in' => 'Mục cam kết không hợp lệ.',
            'delta.in' => 'Giá trị điều chỉnh đặc cách phải là +1 hoặc -1.',
        ];
    }
}
