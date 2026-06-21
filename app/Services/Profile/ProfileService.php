<?php

namespace App\Services\Profile;

use App\Enums\RegistrationAuditStatus;
use App\Models\User;

class ProfileService
{
    /**
     * @return array<string, mixed>
     */
    public function summary(User $user): array
    {
        $registration = $user->registrationProfile;

        return [
            'userId' => (string) $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'address' => $user->address,
            'city' => $user->city,
            'claimType' => $user->claim_type,
            'receiverName' => $user->receiver_name,
            'receiverPhone' => $user->receiver_phone,
            'receiverAddress' => $user->receiver_address,
            'pickupName' => $user->pickup_name,
            'pickupPhone' => $user->pickup_phone,
            'pickupEmployeeNo' => $user->pickup_employee_no,
            'pickupAddress' => $user->pickup_address,
            'pickupRemark' => $user->pickup_remark,
            'registrationStatus' => $registration?->audit_status ?? RegistrationAuditStatus::Draft->value,
            'workCount' => $user->works()->count(),
            'prizeCount' => 0,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function update(User $user, array $payload): array
    {
        $user->fill([
            'name' => $payload['name'] ?? $user->name,
            'phone' => array_key_exists('phone', $payload) ? $payload['phone'] : $user->phone,
            'address' => array_key_exists('address', $payload) ? $payload['address'] : $user->address,
            'claim_type' => array_key_exists('claimType', $payload) ? $payload['claimType'] : $user->claim_type,
            'receiver_name' => array_key_exists('receiverName', $payload) ? $payload['receiverName'] : $user->receiver_name,
            'receiver_phone' => array_key_exists('receiverPhone', $payload) ? $payload['receiverPhone'] : $user->receiver_phone,
            'receiver_address' => array_key_exists('receiverAddress', $payload) ? $payload['receiverAddress'] : $user->receiver_address,
            'pickup_name' => array_key_exists('pickupName', $payload) ? $payload['pickupName'] : $user->pickup_name,
            'pickup_phone' => array_key_exists('pickupPhone', $payload) ? $payload['pickupPhone'] : $user->pickup_phone,
            'pickup_employee_no' => array_key_exists('pickupEmployeeNo', $payload) ? $payload['pickupEmployeeNo'] : $user->pickup_employee_no,
            'pickup_address' => array_key_exists('pickupAddress', $payload) ? $payload['pickupAddress'] : $user->pickup_address,
            'pickup_remark' => array_key_exists('pickupRemark', $payload) ? $payload['pickupRemark'] : $user->pickup_remark,
        ])->save();

        return $this->summary($user->fresh());
    }
}
