<?php

namespace App\Http\Requests\Upload;

use App\Enums\UploadUsageType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LocalUploadRequest extends FormRequest
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
            'file' => ['required', 'file', 'max:20480'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($this->input('usageType') !== UploadUsageType::RegistrationMaterial->value) {
                return;
            }

            $file = $this->file('file');
            if (!$file || str_starts_with((string) $file->getMimeType(), 'image/')) {
                return;
            }

            $validator->errors()->add('file', '报名材料仅支持图片文件。');
        });
    }
}
