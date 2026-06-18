<?php

namespace App\Filament\Resources\QuotaApplications\Pages;

use App\Filament\Resources\QuotaApplications\QuotaApplicationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditQuotaApplication extends EditRecord
{
    protected static string $resource = QuotaApplicationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
