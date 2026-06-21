<?php

namespace App\Http\Controllers\Api\Lottery;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lottery\SubmitPrizeClaimRequest;
use App\Http\Resources\Lottery\LotteryQualificationResource;
use App\Http\Resources\Lottery\LotteryRecordResource;
use App\Services\Auth\CurrentUserResolver;
use App\Services\Lottery\LotteryService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LotteryController extends Controller
{
    public function qualification(Request $request, LotteryService $service, CurrentUserResolver $resolver): JsonResponse
    {
        return ApiResponse::success(
            (new LotteryQualificationResource($service->qualification($resolver->require($request), $request->query('sourceType'))))->resolve()
        );
    }

    public function draw(Request $request, LotteryService $service, CurrentUserResolver $resolver): JsonResponse
    {
        return ApiResponse::success(
            (new LotteryRecordResource($service->draw($resolver->require($request), $request->input('sourceType'))))->resolve()
        );
    }

    public function claim(string $recordId, SubmitPrizeClaimRequest $request, LotteryService $service, CurrentUserResolver $resolver): JsonResponse
    {
        return ApiResponse::success(
            $service->claim($resolver->require($request), $recordId, $request->validated())
        );
    }

    public function myPrizes(Request $request, LotteryService $service, CurrentUserResolver $resolver): JsonResponse
    {
        return ApiResponse::success(
            $service->myPrizes($resolver->require($request))
        );
    }

    public function announcements(LotteryService $service): JsonResponse
    {
        return ApiResponse::success($service->announcements());
    }
}
