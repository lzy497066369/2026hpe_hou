<?php

namespace App\Services\Auth;

use App\Models\User;

class AuthService
{
    /**
     * @param array{employeeNo: string, email: string} $payload
     * @return array<string, mixed>
     */
    public function login(array $payload): array
    {
        $user = User::query()
            ->where('employee_no', $payload['employeeNo'])
            ->where('email', $payload['email'])
            ->where('status', 'active')
            ->first();

        abort_if($user === null, 422, '员工号与邮箱不匹配');

        $token = $user->createToken('h5-api')->plainTextToken;

        return [
            'token' => $token,
            'user' => [
                'id' => (string) $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'employeeNo' => $user->employee_no,
                'avatar' => $user->avatar,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function me(User $user): array
    {
        return [
            'id' => (string) $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'employeeNo' => $user->employee_no,
            'avatar' => $user->avatar,
        ];
    }

    public function logout(string $token): void
    {
        [$tokenId] = explode('|', $token, 2) + [null];

        if ($tokenId !== null) {
            $model = $this->findToken($token);
            $model?->delete();
        }
    }

    private function findToken(string $plainTextToken): ?\Laravel\Sanctum\PersonalAccessToken
    {
        return \Laravel\Sanctum\PersonalAccessToken::findToken($plainTextToken);
    }
}
