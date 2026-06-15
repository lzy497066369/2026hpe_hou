<?php

namespace App\Http\Resources\Lottery;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LotteryRecordResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource['id'],
            'resultStatus' => $this->resource['resultStatus'],
            'prize' => $this->resource['prize'] ?? null,
            'drawnAt' => $this->resource['drawnAt'] ?? null,
        ];
    }
}
