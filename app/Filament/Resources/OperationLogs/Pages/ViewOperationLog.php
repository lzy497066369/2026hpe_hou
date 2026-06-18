<?php

namespace App\Filament\Resources\OperationLogs\Pages;

use App\Filament\Resources\OperationLogs\OperationLogResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewOperationLog extends ViewRecord
{
    protected static string $resource = OperationLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
