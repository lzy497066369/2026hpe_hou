<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class CurrentUserResolver
{
    private const LAST_USED_AT_THROTTLE_MINUTES = 5;

    public function resolve(Request $request): ?User
    {
        $token = $request->bearerToken();

        if ($token === null || $token === '') {
            return null;
        }

        $apiToken = PersonalAccessToken::findToken($token);

        if ($apiToken === null) {
            return null;
        }

        if ($apiToken->last_used_at === null || $apiToken->last_used_at->lte(now()->subMinutes(self::LAST_USED_AT_THROTTLE_MINUTES))) {
            $apiToken->forceFill(['last_used_at' => now()])->save();
        }

        $tokenable = $apiToken->tokenable;

        return $tokenable instanceof User ? $tokenable : null;
    }

    public function require(Request $request): User
    {
        $user = $this->resolve($request);

        abort_if($user === null, 401, 'Unauthenticated.');

        return $user;
    }
}
