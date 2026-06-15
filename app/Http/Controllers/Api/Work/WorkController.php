<?php

namespace App\Http\Controllers\Api\Work;

use App\Http\Controllers\Controller;
use App\Http\Requests\Work\SubmitWorkRequest;
use App\Http\Requests\Work\UpdateWorkRequest;
use App\Http\Resources\Work\WorkResource;
use App\Services\Auth\CurrentUserResolver;
use App\Services\Work\WorkService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkController extends Controller
{
    public function index(WorkService $service): JsonResponse
    {
        return ApiResponse::success(
            $service->list(
                (int) request()->query('page', 1),
                (int) request()->query('pageSize', 10),
                request()->query('group'),
                request()->query('type'),
                request()->query('keyword')
            )
        );
    }

    public function show(string $workId, WorkService $service): JsonResponse
    {
        return ApiResponse::success(
            (new WorkResource($service->detail($workId)))->resolve()
        );
    }

    public function submit(SubmitWorkRequest $request, WorkService $service, CurrentUserResolver $resolver): JsonResponse
    {
        return ApiResponse::success(
            (new WorkResource($service->submit($resolver->require($request), $request->validated())))->resolve()
        );
    }

    public function update(string $workId, UpdateWorkRequest $request, WorkService $service, CurrentUserResolver $resolver): JsonResponse
    {
        return ApiResponse::success(
            (new WorkResource($service->update($resolver->require($request), $workId, $request->validated())))->resolve()
        );
    }

    public function mine(Request $request, WorkService $service, CurrentUserResolver $resolver): JsonResponse
    {
        return ApiResponse::success(
            $service->mine(
                $resolver->require($request),
                (int) $request->query('page', 1),
                (int) $request->query('pageSize', 10)
            )
        );
    }
}
