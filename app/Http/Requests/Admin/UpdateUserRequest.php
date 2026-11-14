<?php

namespace App\Http\Requests\Admin;

use App\Support\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    protected function prepareForValidation(): void
    {
        $phone = \App\Models\User::normalizePhone((string) $this->input('phone', ''));

        $this->merge([
            'phone' => $phone !== '' ? $phone : null,
            'username' => trim((string) $this->input('username', '')),
            'email' => trim((string) $this->input('email', '')),
            'full_name' => trim((string) $this->input('full_name', '')),
        ]);
    }

    public function rules(): array
    {
        $id = (int) $this->route('id');

        return [
            'full_name' => ['required', 'string', 'min:2', 'max:120'],
            'phone' => ['required', 'string', 'min:9', 'max:20', 'regex:/^[0-9+\-\s\.]+$/', Rule::unique('users', 'phone')->ignore($id)],
            'username' => ['nullable', 'string', 'min:3', 'max:50', Rule::unique('users', 'username')->ignore($id)],
            'email' => ['required', 'email', 'max:120', Rule::unique('users', 'email')->ignore($id)],
            'password' => ['nullable', 'string', 'min:6', 'max:255'],
            'role' => ['required', Rule::in(UserRole::keys())],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ];
    }

    public function messages(): array
    {
        return [
            'full_name.required' => 'Vui lòng nhập họ tên.',
            'phone.required' => 'Vui lòng nhập số điện thoại.',
            'phone.unique' => 'Số điện thoại đã được sử dụng.',
            'email.required' => 'Vui lòng nhập email.',
            'email.unique' => 'Email đã được sử dụng.',
            'username.unique' => 'Tên đăng nhập đã được sử dụng.',
            'password.min' => 'Mật khẩu phải có ít nhất 6 ký tự.',
        ];
    }
}
