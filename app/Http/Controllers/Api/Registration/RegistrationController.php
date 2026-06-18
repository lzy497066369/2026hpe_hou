<?php

namespace App\Http\Controllers\Api\Registration;

use App\Http\Controllers\Controller;
use App\Http\Requests\Registration\SubmitRegistrationRequest;
use App\Http\Resources\Registration\RegistrationProfileResource;
use App\Services\Auth\CurrentUserResolver;
use App\Services\Registration\RegistrationService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegistrationController extends Controller
{
    public function show(Request $request, RegistrationService $service, CurrentUserResolver $resolver): JsonResponse
    {
        return ApiResponse::success(
            (new RegistrationProfileResource($service->profile($resolver->require($request))))->resolve()
        );
    }

    public function status(Request $request, RegistrationService $service, CurrentUserResolver $resolver): JsonResponse
    {
        return ApiResponse::success($service->status($resolver->require($request)));
    }

    public function submit(SubmitRegistrationRequest $request, RegistrationService $service, CurrentUserResolver $resolver): JsonResponse
    {
        return ApiResponse::success(
            (new RegistrationProfileResource($service->submit($resolver->require($request), $request->validated())))->resolve()
        );
    }
}
