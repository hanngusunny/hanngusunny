<?php

namespace App\Http\Requests\Admin;

use App\Support\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['default_start_time', 'default_end_time', 'program_id', 'schedule_start_date'] as $key) {
            if ($this->input($key) === '') {
                $this->merge([$key => null]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'description' => ['nullable', 'string'],
            'teacher_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->where('status', 'active')->whereIn('role', UserRole::teachingRoles());
                }),
            ],
            'program_id' => ['nullable', 'integer', 'exists:study_programs,id'],
            'schedule_start_date' => ['nullable', 'date'],
            'default_start_time' => ['nullable', 'date_format:H:i'],
            'default_end_time' => ['nullable', 'date_format:H:i'],
            'weekly_days' => ['nullable', 'array'],
            'weekly_days.*' => ['integer', 'between:1,7'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Vui lòng nhập tên khóa học.',
            'title.min' => 'Tên khóa học phải có ít nhất 3 ký tự.',
            'teacher_id.required' => 'Vui lòng chọn giáo viên đứng lớp.',
            'teacher_id.exists' => 'Giáo viên đứng lớp không hợp lệ.',
            'program_id.exists' => 'Chương trình học không hợp lệ.',
        ];
    }
}
