<?php

namespace App\Filament\Resources\LotteryRecords\Pages;

use App\Filament\Resources\LotteryRecords\LotteryRecordResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditLotteryRecord extends EditRecord
{
    protected static string $resource = LotteryRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
