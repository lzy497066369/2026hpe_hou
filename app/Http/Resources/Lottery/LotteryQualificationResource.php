<?php

namespace App\Http\Resources\Lottery;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LotteryQualificationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'qualified' => $this->resource['qualified'],
            'chanceCount' => $this->resource['chanceCount'],
            'usedCount' => $this->resource['usedCount'],
            'reason' => $this->resource['reason'] ?? null,
        ];
    }
}
