<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTeacherEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isTeacher() === true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'eval_week' => trim((string) $this->input('eval_week', '')),
            'comment' => (string) $this->input('comment', ''),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'course_id' => ['required', 'integer', 'min:1'],
            'user_id' => ['required', 'integer', 'min:1'],
            'eval_week' => ['required', 'string', 'regex:/^\d{4}-W\d{1,2}$/i'],
            'score_attitude' => ['nullable', 'integer', 'min:1', 'max:5'],
            'score_progress' => ['nullable', 'integer', 'min:1', 'max:5'],
            'score_participation' => ['nullable', 'integer', 'min:1', 'max:5'],
            'comment' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'course_id.required' => 'Thiếu khóa học.',
            'user_id.required' => 'Thiếu học viên.',
            'eval_week.required' => 'Thiếu tuần đánh giá.',
            'eval_week.regex' => 'Tuần đánh giá không hợp lệ.',
        ];
    }
}
