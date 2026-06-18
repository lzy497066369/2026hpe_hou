<?php

namespace App\Filament\Resources\LotteryRecords\Pages;

use App\Filament\Resources\LotteryRecords\LotteryRecordResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewLotteryRecord extends ViewRecord
{
    protected static string $resource = LotteryRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
