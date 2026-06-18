<?php

namespace App\Filament\Resources\GameRecords\Pages;

use App\Filament\Resources\GameRecords\GameRecordResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditGameRecord extends EditRecord
{
    protected static string $resource = GameRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
