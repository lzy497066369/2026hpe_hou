<?php

namespace App\Filament\Resources\GameRecords;

use App\Filament\Resources\GameRecords\Pages\CreateGameRecord;
use App\Filament\Resources\GameRecords\Pages\EditGameRecord;
use App\Filament\Resources\GameRecords\Pages\ListGameRecords;
use App\Filament\Resources\GameRecords\Pages\ViewGameRecord;
use App\Filament\Resources\GameRecords\Schemas\GameRecordForm;
use App\Filament\Resources\GameRecords\Schemas\GameRecordInfolist;
use App\Filament\Resources\GameRecords\Tables\GameRecordsTable;
use App\Models\GameRecord;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class GameRecordResource extends Resource
{
    protected static ?string $model = GameRecord::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTrophy;

    protected static string|UnitEnum|null $navigationGroup = '活动内容';

    protected static ?string $modelLabel = '游戏记录';

    protected static ?string $pluralModelLabel = '游戏记录';

    public static function form(Schema $schema): Schema
    {
        return GameRecordForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return GameRecordInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GameRecordsTable::configure($table);
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
            'index' => ListGameRecords::route('/'),
            'create' => CreateGameRecord::route('/create'),
            'view' => ViewGameRecord::route('/{record}'),
            'edit' => EditGameRecord::route('/{record}/edit'),
        ];
    }
}
