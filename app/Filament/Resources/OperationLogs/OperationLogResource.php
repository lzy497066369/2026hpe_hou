<?php

namespace App\Filament\Resources\OperationLogs;

use App\Filament\Resources\OperationLogs\Pages\ListOperationLogs;
use App\Filament\Resources\OperationLogs\Pages\ViewOperationLog;
use App\Filament\Resources\OperationLogs\Schemas\OperationLogForm;
use App\Filament\Resources\OperationLogs\Schemas\OperationLogInfolist;
use App\Filament\Resources\OperationLogs\Tables\OperationLogsTable;
use App\Models\OperationLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class OperationLogResource extends Resource
{
    protected static ?string $model = OperationLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = '系统运营';

    protected static ?string $modelLabel = '操作日志';

    protected static ?string $pluralModelLabel = '操作日志';

    public static function form(Schema $schema): Schema
    {
        return OperationLogForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return OperationLogInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OperationLogsTable::configure($table);
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
            'index' => ListOperationLogs::route('/'),
            'view' => ViewOperationLog::route('/{record}'),
        ];
    }
}
