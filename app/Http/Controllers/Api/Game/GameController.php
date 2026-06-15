<?php

namespace App\Http\Controllers\Api\Game;

use App\Http\Controllers\Controller;
use App\Http\Requests\Game\StoreGameRecordRequest;
use App\Services\Auth\CurrentUserResolver;
use App\Services\Game\GameService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GameController extends Controller
{
    public function store(StoreGameRecordRequest $request, GameService $service, CurrentUserResolver $resolver): JsonResponse
    {
        return ApiResponse::success(
            $service->store($resolver->require($request), $request->validated())
        );
    }

    public function rankings(Request $request, GameService $service, CurrentUserResolver $resolver): JsonResponse
    {
        return ApiResponse::success(
            $service->rankings($resolver->resolve($request), (int) $request->query('limit', 30))
        );
    }
}
