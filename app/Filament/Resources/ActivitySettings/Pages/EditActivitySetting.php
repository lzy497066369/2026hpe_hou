<?php

namespace App\Filament\Resources\ActivitySettings\Pages;

use App\Filament\Resources\ActivitySettings\ActivitySettingResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditActivitySetting extends EditRecord
{
    protected static string $resource = ActivitySettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
