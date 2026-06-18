<?php

namespace App\Filament\Resources\RegistrationProfiles\Pages;

use App\Filament\Resources\RegistrationProfiles\RegistrationProfileResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditRegistrationProfile extends EditRecord
{
    protected static string $resource = RegistrationProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
