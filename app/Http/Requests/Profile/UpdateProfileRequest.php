<?php

namespace App\Http\Requests\Profile;

use App\Enums\PrizeClaimType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
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
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'claimType' => ['sometimes', 'nullable', Rule::enum(PrizeClaimType::class)],
            'receiverName' => ['sometimes', 'nullable', 'string', 'max:100'],
            'receiverPhone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'receiverAddress' => ['sometimes', 'nullable', 'string', 'max:255'],
            'pickupName' => ['sometimes', 'nullable', 'string', 'max:100'],
            'pickupPhone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'pickupEmployeeNo' => ['sometimes', 'nullable', 'string', 'max:50'],
            'pickupAddress' => ['sometimes', 'nullable', 'string', 'max:255'],
            'pickupRemark' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
