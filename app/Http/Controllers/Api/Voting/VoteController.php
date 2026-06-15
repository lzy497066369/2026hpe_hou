<?php

namespace App\Http\Controllers\Api\Voting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Voting\SubmitVoteRequest;
use App\Http\Resources\Voting\VoteResultResource;
use App\Services\Auth\CurrentUserResolver;
use App\Services\Voting\VotingService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class VoteController extends Controller
{
    public function store(SubmitVoteRequest $request, VotingService $service, CurrentUserResolver $resolver): JsonResponse
    {
        return ApiResponse::success(
            (new VoteResultResource($service->vote($resolver->require($request), $request->validated('workId'))))->resolve()
        );
    }
}
