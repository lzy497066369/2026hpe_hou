<?php

namespace App\Filament\Resources\RegistrationProfiles\Pages;

use App\Filament\Resources\RegistrationProfiles\RegistrationProfileResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewRegistrationProfile extends ViewRecord
{
    protected static string $resource = RegistrationProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
