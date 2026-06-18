<?php

namespace App\Filament\Resources\RegistrationProfiles\Pages;

use App\Filament\Resources\RegistrationProfiles\RegistrationProfileResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRegistrationProfiles extends ListRecords
{
    protected static string $resource = RegistrationProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
