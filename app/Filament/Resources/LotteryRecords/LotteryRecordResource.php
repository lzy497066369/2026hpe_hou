<?php

namespace App\Filament\Resources\LotteryRecords;

use App\Filament\Resources\LotteryRecords\Pages\CreateLotteryRecord;
use App\Filament\Resources\LotteryRecords\Pages\EditLotteryRecord;
use App\Filament\Resources\LotteryRecords\Pages\ListLotteryRecords;
use App\Filament\Resources\LotteryRecords\Pages\ViewLotteryRecord;
use App\Filament\Resources\LotteryRecords\Schemas\LotteryRecordForm;
use App\Filament\Resources\LotteryRecords\Schemas\LotteryRecordInfolist;
use App\Filament\Resources\LotteryRecords\Tables\LotteryRecordsTable;
use App\Models\LotteryRecord;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class LotteryRecordResource extends Resource
{
    protected static ?string $model = LotteryRecord::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGift;

    protected static string|UnitEnum|null $navigationGroup = '奖项管理';

    protected static ?string $modelLabel = '获奖记录';

    protected static ?string $pluralModelLabel = '获奖管理';

    public static function form(Schema $schema): Schema
    {
        return LotteryRecordForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return LotteryRecordInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LotteryRecordsTable::configure($table);
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
            'index' => ListLotteryRecords::route('/'),
            'create' => CreateLotteryRecord::route('/create'),
            'view' => ViewLotteryRecord::route('/{record}'),
            'edit' => EditLotteryRecord::route('/{record}/edit'),
        ];
    }
}
