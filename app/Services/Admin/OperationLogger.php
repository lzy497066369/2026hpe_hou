<?php

namespace App\Services\Admin;

use App\Models\OperationLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class OperationLogger
{
    /**
     * @param array<string, mixed> $payload
     */
    public function log(string $module, string $action, ?Model $target = null, array $payload = []): void
    {
        OperationLog::query()->create([
            'user_id' => Auth::id(),
            'module' => $module,
            'action' => $action,
            'target_type' => $target ? $target::class : null,
            'target_id' => $target?->getKey(),
            'payload' => $payload,
            'ip_address' => Request::ip(),
        ]);
    }
}
