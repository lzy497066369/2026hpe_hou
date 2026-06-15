<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\Auth\LoginResource;
use App\Services\Auth\AuthService;
use App\Services\Auth\CurrentUserResolver;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(LoginRequest $request, AuthService $service): JsonResponse
    {
        return ApiResponse::success(
            (new LoginResource($service->login($request->validated())))->resolve()
        );
    }

    public function me(Request $request, AuthService $service, CurrentUserResolver $resolver): JsonResponse
    {
        return ApiResponse::success($service->me($resolver->require($request)));
    }

    public function logout(Request $request, AuthService $service, CurrentUserResolver $resolver): JsonResponse
    {
        $resolver->require($request);
        $service->logout((string) $request->bearerToken());

        return ApiResponse::success(['success' => true]);
    }
}
