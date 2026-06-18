<?php

namespace App\Filament\Resources\ActivitySettings\Pages;

use App\Filament\Resources\ActivitySettings\ActivitySettingResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewActivitySetting extends ViewRecord
{
    protected static string $resource = ActivitySettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
