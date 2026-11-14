<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Validator;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('admin') !== null;
    }

    public function rules(): array
    {
        return [
            'name' => ['nullable', 'string', 'max:255'],
            'password_old' => ['nullable', 'string'],
            'password_new' => ['nullable', 'string', 'min:8'],
            'password_confirm' => ['nullable', 'string'],
            'remove_avatar' => ['sometimes', 'boolean'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v) {
            $new = trim((string) $this->input('password_new'));
            $old = trim((string) $this->input('password_old'));
            $conf = trim((string) $this->input('password_confirm'));
            if ($new === '' && $old === '' && $conf === '') {
                return;
            }
            if ($new === '' || $old === '' || $new !== $conf) {
                $v->errors()->add('password_new', __('admin_profile.profile_val_password_group_incomplete'));

                return;
            }
            $user = $this->user('admin');
            if ($user === null || ! Hash::check($old, $user->getAuthPassword())) {
                $v->errors()->add('password_new', __('admin_profile.profile_val_password_old_wrong'));
            }
        });
    }
}
