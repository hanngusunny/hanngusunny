<?php

namespace App\Http\Requests\Admin;

use App\Models\OnlineRoom;
use App\Support\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveClassroomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:255'],
            'description' => ['nullable', 'string'],
            'host_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(function ($query) {
                    $query->whereIn('role', [UserRole::ADMIN, UserRole::TEACHER]);
                }),
            ],
            'scheduled_start' => ['nullable', 'date'],
            'scheduled_end' => ['nullable', 'date'],
            'status' => ['required', Rule::in(array_keys(OnlineRoom::statusLabels()))],
            'access_type' => ['required', Rule::in(array_keys(OnlineRoom::accessTypeLabels()))],
            'max_participants' => ['required', 'integer', 'min:1', 'max:200'],
            'waiting_room' => ['nullable'],
            'room_password' => ['nullable', 'string', 'max:100'],
            'presets' => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Vui lòng nhập tên phòng học.',
            'title.min' => 'Tên phòng học phải có ít nhất 3 ký tự.',
            'host_id.required' => 'Vui lòng chọn giáo viên chủ trì.',
            'host_id.exists' => 'Giáo viên chủ trì không hợp lệ.',
            'status.required' => 'Vui lòng chọn trạng thái phòng.',
            'access_type.required' => 'Vui lòng chọn quyền truy cập.',
            'max_participants.required' => 'Vui lòng nhập số người tối đa.',
        ];
    }
}
