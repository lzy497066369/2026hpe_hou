<?php

namespace App\Http\Resources\Voting;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VoteResultResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'workId' => $this->resource['workId'],
            'voteCount' => $this->resource['voteCount'],
            'remainingVotes' => $this->resource['remainingVotes'],
        ];
    }
}
