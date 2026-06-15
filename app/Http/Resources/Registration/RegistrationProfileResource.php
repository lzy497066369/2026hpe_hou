<?php

namespace App\Http\Resources\Registration;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RegistrationProfileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource['id'],
            'userId' => $this->resource['userId'],
            'employeeNo' => $this->resource['employeeNo'],
            'name' => $this->resource['name'],
            'department' => $this->resource['department'],
            'contact' => $this->resource['contact'],
            'materialFileId' => $this->resource['materialFileId'] ?? null,
            'auditStatus' => $this->resource['auditStatus'],
            'auditRemark' => $this->resource['auditRemark'] ?? null,
            'submittedAt' => $this->resource['submittedAt'] ?? null,
            'reviewedAt' => $this->resource['reviewedAt'] ?? null,
        ];
    }
}
