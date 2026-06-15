<?php

namespace App\Http\Resources\Upload;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UploadedFileResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource['id'],
            'name' => $this->resource['name'],
            'url' => $this->resource['url'],
            'mimeType' => $this->resource['mimeType'],
            'size' => $this->resource['size'],
            'usageType' => $this->resource['usageType'],
            'isCommitted' => $this->resource['isCommitted'],
        ];
    }
}
