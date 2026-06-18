<?php

namespace App\Filament\Resources\QuotaApplications\Pages;

use App\Filament\Resources\QuotaApplications\QuotaApplicationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListQuotaApplications extends ListRecords
{
    protected static string $resource = QuotaApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
