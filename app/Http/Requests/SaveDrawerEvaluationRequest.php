<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SaveDrawerEvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isTeacher() === true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'eval_week' => trim((string) $this->input('eval_week', '')),
            'mode' => strtolower(trim((string) $this->input('mode', 'submit'))),
            'comment' => (string) $this->input('comment', ''),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $isDraft = $this->input('mode') === 'draft';
        $scoreRule = $isDraft
            ? ['nullable', 'integer', 'min:1', 'max:5']
            : ['required', 'integer', 'min:1', 'max:5'];

        return [
            'eval_week' => ['required', 'string', 'regex:/^\d{4}-W\d{1,2}$/i'],
            'mode' => ['required', 'in:draft,submit'],
            'score_attitude' => $scoreRule,
            'score_progress' => $scoreRule,
            'score_participation' => $scoreRule,
            'comment' => ['nullable', 'string', 'max:10000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'eval_week.required' => 'Thiếu tuần đánh giá.',
            'eval_week.regex' => 'Tuần đánh giá không hợp lệ.',
            'score_attitude.required' => 'Vui lòng chọn mức Thái độ học tập.',
            'score_progress.required' => 'Vui lòng chọn mức Bài tập.',
            'score_participation.required' => 'Vui lòng chọn mức Chuyên cần.',
        ];
    }
}
