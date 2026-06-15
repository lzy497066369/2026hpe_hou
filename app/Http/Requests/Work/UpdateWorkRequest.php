<?php

namespace App\Http\Requests\Work;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkRequest extends FormRequest
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
            'title' => ['sometimes', 'required', 'string', 'max:100'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'toolName' => ['sometimes', 'nullable', 'string', 'max:100'],
            'promptText' => ['sometimes', 'nullable', 'string', 'max:2000'],
        ];
    }
}
