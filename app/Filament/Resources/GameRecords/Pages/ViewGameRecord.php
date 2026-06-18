<?php

namespace App\Filament\Resources\GameRecords\Pages;

use App\Filament\Resources\GameRecords\GameRecordResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewGameRecord extends ViewRecord
{
    protected static string $resource = GameRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
