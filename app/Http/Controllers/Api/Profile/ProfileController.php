<?php

namespace App\Http\Controllers\Api\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\Profile\ProfileSummaryResource;
use App\Services\Auth\CurrentUserResolver;
use App\Services\Profile\ProfileService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function summary(Request $request, ProfileService $service, CurrentUserResolver $resolver): JsonResponse
    {
        return ApiResponse::success(
            (new ProfileSummaryResource($service->summary($resolver->require($request))))->resolve()
        );
    }

    public function update(UpdateProfileRequest $request, ProfileService $service, CurrentUserResolver $resolver): JsonResponse
    {
        return ApiResponse::success(
            (new ProfileSummaryResource($service->update($resolver->require($request), $request->validated())))->resolve()
        );
    }
}
