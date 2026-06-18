<?php

namespace App\Filament\Resources\LotteryRecords\Pages;

use App\Filament\Resources\LotteryRecords\LotteryRecordResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLotteryRecords extends ListRecords
{
    protected static string $resource = LotteryRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
