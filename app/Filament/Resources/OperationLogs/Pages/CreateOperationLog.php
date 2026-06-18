<?php

namespace App\Filament\Resources\OperationLogs\Pages;

use App\Filament\Resources\OperationLogs\OperationLogResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOperationLog extends CreateRecord
{
    protected static string $resource = OperationLogResource::class;
}
