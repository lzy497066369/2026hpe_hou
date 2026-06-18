<?php

namespace App\Services\Auth;

use App\Models\User;

class AuthService
{
    /**
     * @param array{employeeNo: string, email: string, nickname?: string|null} $payload
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

        $nickname = trim((string) ($payload['nickname'] ?? ''));
        if ($user->nickname === null || $user->nickname === '') {
            abort_if($nickname === '', 422, '首次登录请填写昵称');

            $user->forceFill(['nickname' => $nickname])->save();
        }

        $token = $user->createToken('h5-api')->plainTextToken;

        return [
            'token' => $token,
            'user' => $this->formatUser($user),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function me(User $user): array
    {
        return $this->formatUser($user);
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

    /**
     * @return array<string, mixed>
     */
    private function formatUser(User $user): array
    {
        return [
            'id' => (string) $user->id,
            'name' => $user->name,
            'nickname' => $user->nickname,
            'phone' => $user->phone,
            'employeeNo' => $user->employee_no,
            'email' => $user->email,
            'avatar' => $user->avatar,
        ];
    }
}
