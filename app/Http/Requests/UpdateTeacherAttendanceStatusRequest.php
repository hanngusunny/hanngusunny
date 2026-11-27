<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTeacherAttendanceStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isTeacher() === true;
    }

    protected function prepareForValidation(): void
    {
        $status = trim((string) $this->input('status', ''));

        if ($status === '' && $this->has('attended')) {
            $status = (string) $this->input('attended') === '1' ? 'present' : 'absent';
        }

        $this->merge([
            'status' => strtolower($status),
            'eval_week' => trim((string) $this->input('eval_week', '')),
            'joined_at' => trim((string) $this->input('joined_at', '')),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'in:present,late,absent'],
            'eval_week' => ['nullable', 'string', 'regex:/^\d{4}-W\d{1,2}$/i'],
            'joined_at' => ['nullable', 'string', 'max:32'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Vui lòng chọn trạng thái điểm danh.',
            'status.in' => 'Trạng thái điểm danh không hợp lệ.',
        ];
    }
}
