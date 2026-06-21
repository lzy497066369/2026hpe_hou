<?php

namespace App\Http\Resources\Profile;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProfileSummaryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'userId' => $this->resource['userId'],
            'name' => $this->resource['name'],
            'phone' => $this->resource['phone'] ?? null,
            'address' => $this->resource['address'] ?? null,
            'city' => $this->resource['city'] ?? null,
            'registrationStatus' => $this->resource['registrationStatus'],
            'workCount' => $this->resource['workCount'],
            'prizeCount' => $this->resource['prizeCount'],
        ];
    }
}
