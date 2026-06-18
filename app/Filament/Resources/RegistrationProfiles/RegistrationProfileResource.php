<?php

namespace App\Filament\Resources\RegistrationProfiles;

use App\Filament\Resources\RegistrationProfiles\Pages\CreateRegistrationProfile;
use App\Filament\Resources\RegistrationProfiles\Pages\EditRegistrationProfile;
use App\Filament\Resources\RegistrationProfiles\Pages\ListRegistrationProfiles;
use App\Filament\Resources\RegistrationProfiles\Pages\ViewRegistrationProfile;
use App\Filament\Resources\RegistrationProfiles\Schemas\RegistrationProfileForm;
use App\Filament\Resources\RegistrationProfiles\Schemas\RegistrationProfileInfolist;
use App\Filament\Resources\RegistrationProfiles\Tables\RegistrationProfilesTable;
use App\Models\RegistrationProfile;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class RegistrationProfileResource extends Resource
{
    protected static ?string $model = RegistrationProfile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = '活动内容';

    protected static ?string $modelLabel = '材料申请';

    protected static ?string $pluralModelLabel = '材料审核';

    protected static bool $shouldRegisterNavigation = false;

    public static function form(Schema $schema): Schema
    {
        return RegistrationProfileForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RegistrationProfileInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RegistrationProfilesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRegistrationProfiles::route('/'),
            'create' => CreateRegistrationProfile::route('/create'),
            'view' => ViewRegistrationProfile::route('/{record}'),
            'edit' => EditRegistrationProfile::route('/{record}/edit'),
        ];
    }
}
