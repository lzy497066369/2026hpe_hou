<?php

namespace App\Http\Requests\Registration;

use App\Support\ApiResponse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class SubmitRegistrationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->replace($this->normalizedInput());
    }

    /**
     * @return array<string, mixed>
     */
    public function validationData(): array
    {
        return $this->normalizedInput();
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizedInput(): array
    {
        $normalized = [];

        foreach (['applicationType', 'employeeNo', 'name', 'department', 'contact', 'materialFileId'] as $field) {
            if (!$this->has($field)) {
                continue;
            }

            $value = $this->input($field);
            if (is_string($value)) {
                $value = trim($value);
            }

            if ($value !== null && $value !== '') {
                $normalized[$field] = $value;
            }
        }

        if ($this->has('materialFileIds')) {
            $fileIds = $this->input('materialFileIds');
            if (is_array($fileIds)) {
                $normalized['materialFileIds'] = array_values(array_filter(array_map(
                    static fn ($fileId): string => trim((string) $fileId),
                    $fileIds
                )));
            }
        }

        return $normalized;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'applicationType' => ['sometimes', 'nullable', 'in:registration,extra_quota'],
            'employeeNo' => ['sometimes', 'nullable', 'string', 'max:50'],
            'name' => ['sometimes', 'nullable', 'string', 'max:100'],
            'department' => ['sometimes', 'nullable', 'string', 'max:100'],
            'contact' => ['sometimes', 'nullable', 'string', 'max:100'],
            'materialFileId' => ['sometimes', 'nullable', 'string', 'max:100'],
            'materialFileIds' => ['required_without:materialFileId', 'array', 'min:1', 'max:2'],
            'materialFileIds.*' => ['required', 'string', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'employeeNo' => '员工编号',
            'name' => '姓名',
            'department' => '部门',
            'contact' => '联系方式',
            'materialFileId' => '材料图片',
            'materialFileIds' => '材料图片',
            'materialFileIds.*' => '材料图片',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => ':attribute不能为空',
            'required_without' => ':attribute至少需要上传 1 张',
            'string' => ':attribute格式不正确',
            'array' => ':attribute格式不正确',
            'min' => ':attribute至少需要上传 :min 张',
            'max' => ':attribute数量不能超过 :max 张',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ApiResponse::error(422, (string) $validator->errors()->first(), 422, $validator->errors())
        );
    }
}
