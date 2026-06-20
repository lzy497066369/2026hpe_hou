<?php

namespace App\Http\Requests\Work;

use App\Enums\WorkGroup;
use App\Enums\WorkType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitWorkRequest extends FormRequest
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
            'type' => ['required', Rule::enum(WorkType::class)],
            'group' => ['required', Rule::enum(WorkGroup::class)],
            'title' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:200'],
            'coverFileId' => ['nullable', 'integer'],
            'contentFileId' => ['required', 'integer'],
            'toolName' => ['nullable', 'string', 'max:100'],
            'promptText' => ['nullable', 'string', 'max:200'],
        ];
    }
}
