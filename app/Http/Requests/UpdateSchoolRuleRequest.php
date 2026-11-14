<?php

namespace App\Http\Requests;

use App\Support\HtmlContent;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSchoolRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isStaff() === true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:200'],
            'content' => ['required', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (! HtmlContent::has((string) $this->input('content'))) {
                $validator->errors()->add('content', 'Vui lòng nhập nội dung nội quy.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Vui lòng nhập tiêu đề nội quy.',
            'content.required' => 'Vui lòng nhập nội dung nội quy.',
        ];
    }
}
