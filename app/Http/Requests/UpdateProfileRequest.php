<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $phone = User::normalizePhone((string) $this->input('phone', ''));

        $this->merge([
            'full_name' => trim((string) $this->input('full_name', '')),
            'email' => trim((string) $this->input('email', '')),
            'phone' => $phone !== '' ? $phone : null,
            'remove_avatar' => $this->boolean('remove_avatar'),
        ]);
    }

    public function rules(): array
    {
        $userId = (int) ($this->user()?->id ?? 0);

        return [
            'full_name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email', 'max:120', Rule::unique('users', 'email')->ignore($userId)],
            'phone' => ['nullable', 'string', 'min:9', 'max:20', 'regex:/^[0-9+\-\s\.]+$/', Rule::unique('users', 'phone')->ignore($userId)],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
            'remove_avatar' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'Vui lòng nhập họ và tên.',
            'full_name.min' => 'Họ và tên phải có ít nhất 2 ký tự.',
            'email.required' => 'Vui lòng nhập email.',
            'email.email' => 'Email không hợp lệ.',
            'email.unique' => 'Email đã được sử dụng.',
            'phone.min' => 'Số điện thoại phải có ít nhất 9 ký tự.',
            'phone.unique' => 'Số điện thoại đã được sử dụng.',
            'phone.regex' => 'Số điện thoại không hợp lệ.',
            'avatar.image' => 'Ảnh đại diện không hợp lệ.',
            'avatar.mimes' => 'Ảnh đại diện phải là JPG, PNG, WEBP hoặc GIF.',
            'avatar.max' => 'Ảnh đại diện tối đa 5MB.',
        ];
    }
}
