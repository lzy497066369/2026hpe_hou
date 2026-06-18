<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
            'employeeNo' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
            'nickname' => ['nullable', 'string', 'max:100'],
        ];
    }
}
