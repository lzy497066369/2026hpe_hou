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
            'claimType' => $this->resource['claimType'] ?? null,
            'receiverName' => $this->resource['receiverName'] ?? null,
            'receiverPhone' => $this->resource['receiverPhone'] ?? null,
            'receiverAddress' => $this->resource['receiverAddress'] ?? null,
            'pickupName' => $this->resource['pickupName'] ?? null,
            'pickupPhone' => $this->resource['pickupPhone'] ?? null,
            'pickupEmployeeNo' => $this->resource['pickupEmployeeNo'] ?? null,
            'pickupAddress' => $this->resource['pickupAddress'] ?? null,
            'pickupRemark' => $this->resource['pickupRemark'] ?? null,
            'registrationStatus' => $this->resource['registrationStatus'],
            'workCount' => $this->resource['workCount'],
            'prizeCount' => $this->resource['prizeCount'],
        ];
    }
}
