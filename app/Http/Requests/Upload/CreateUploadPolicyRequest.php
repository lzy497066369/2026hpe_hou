<?php

namespace App\Http\Requests\Upload;

use App\Enums\UploadUsageType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateUploadPolicyRequest extends FormRequest
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
            'usageType' => ['required', Rule::enum(UploadUsageType::class)],
        ];
    }
}
