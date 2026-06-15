<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class CurrentUserResolver
{
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

        $apiToken->forceFill(['last_used_at' => now()])->save();

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
