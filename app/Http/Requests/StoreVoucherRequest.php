<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'student_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'dob' => ['required', 'date'],
            'note' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'student_name.required' => 'Vui lòng nhập Họ và Tên học viên.',
            'phone.required' => 'Vui lòng nhập Số điện thoại.',
            'dob.required' => 'Vui lòng chọn Ngày tháng năm sinh.',
            'dob.date' => 'Ngày tháng năm sinh không đúng định dạng.',
        ];
    }
}
