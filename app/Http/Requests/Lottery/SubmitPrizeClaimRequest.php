<?php

namespace App\Http\Requests\Lottery;

use App\Enums\PrizeClaimType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitPrizeClaimRequest extends FormRequest
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
            'claimType' => ['required', Rule::enum(PrizeClaimType::class)],
            'receiverName' => ['required', 'string', 'max:100'],
            'receiverPhone' => ['required', 'string', 'max:30'],
            'receiverAddress' => ['required_if:claimType,shipping', 'nullable', 'string', 'max:255'],
            'pickupName' => ['required_if:claimType,pickup', 'nullable', 'string', 'max:100'],
            'pickupPhone' => ['required_if:claimType,pickup', 'nullable', 'string', 'max:30'],
            'pickupEmployeeNo' => ['nullable', 'string', 'max:50'],
            'pickupAddress' => ['required_if:claimType,pickup', 'nullable', 'string', 'max:255'],
            'pickupRemark' => ['nullable', 'string', 'max:255'],
        ];
    }
}
