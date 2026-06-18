<?php

namespace App\Filament\Resources\ActivitySettings;

use App\Filament\Resources\ActivitySettings\Pages\CreateActivitySetting;
use App\Filament\Resources\ActivitySettings\Pages\EditActivitySetting;
use App\Filament\Resources\ActivitySettings\Pages\ListActivitySettings;
use App\Filament\Resources\ActivitySettings\Pages\ViewActivitySetting;
use App\Filament\Resources\ActivitySettings\Schemas\ActivitySettingForm;
use App\Filament\Resources\ActivitySettings\Schemas\ActivitySettingInfolist;
use App\Filament\Resources\ActivitySettings\Tables\ActivitySettingsTable;
use App\Models\ActivitySetting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ActivitySettingResource extends Resource
{
    protected static ?string $model = ActivitySetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|UnitEnum|null $navigationGroup = '系统运营';

    protected static ?string $modelLabel = '活动配置';

    protected static ?string $pluralModelLabel = '活动配置';

    public static function form(Schema $schema): Schema
    {
        return ActivitySettingForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ActivitySettingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ActivitySettingsTable::configure($table);
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
            'index' => ListActivitySettings::route('/'),
            'create' => CreateActivitySetting::route('/create'),
            'view' => ViewActivitySetting::route('/{record}'),
            'edit' => EditActivitySetting::route('/{record}/edit'),
        ];
    }
}
