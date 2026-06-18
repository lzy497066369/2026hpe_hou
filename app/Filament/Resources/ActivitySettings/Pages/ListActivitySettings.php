<?php

namespace App\Filament\Resources\ActivitySettings\Pages;

use App\Filament\Resources\ActivitySettings\ActivitySettingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListActivitySettings extends ListRecords
{
    protected static string $resource = ActivitySettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
