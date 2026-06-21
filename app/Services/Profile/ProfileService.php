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
        ])->save();

        return $this->summary($user->fresh());
    }
}
