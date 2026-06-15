<?php

namespace App\Http\Requests\Registration;

use Illuminate\Foundation\Http\FormRequest;

class SubmitRegistrationRequest extends FormRequest
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
            'employeeNo' => ['sometimes', 'required', 'string', 'max:50'],
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'department' => ['sometimes', 'required', 'string', 'max:100'],
            'contact' => ['sometimes', 'required', 'string', 'max:100'],
            'materialFileId' => ['sometimes', 'nullable', 'string', 'max:100'],
        ];
    }
}
