<?php

namespace App\Http\Resources\Work;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource['id'],
            'type' => $this->resource['type'],
            'group' => $this->resource['group'],
            'title' => $this->resource['title'],
            'description' => $this->resource['description'],
            'employeeNo' => $this->resource['employeeNo'] ?? null,
            'coverUrl' => $this->resource['coverUrl'] ?? null,
            'contentUrl' => $this->resource['contentUrl'] ?? null,
            'contentFileId' => $this->resource['contentFileId'] ?? null,
            'toolName' => $this->resource['toolName'] ?? null,
            'promptText' => $this->resource['promptText'] ?? null,
            'auditStatus' => $this->resource['auditStatus'],
            'publishStatus' => $this->resource['publishStatus'],
            'voteCount' => $this->resource['voteCount'],
        ];
    }
}
