<?php

namespace App\Filament\Resources\GameRecords\Pages;

use App\Filament\Resources\GameRecords\GameRecordResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGameRecords extends ListRecords
{
    protected static string $resource = GameRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
