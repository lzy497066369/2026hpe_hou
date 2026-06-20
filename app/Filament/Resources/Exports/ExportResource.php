<?php

namespace App\Filament\Resources\Exports;

use App\Filament\Resources\Exports\Pages\ListExports;
use App\Filament\Resources\Exports\Tables\ExportsTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Filament\Actions\Exports\Models\Export;
use UnitEnum;

class ExportResource extends Resource
{
    protected static ?string $model = Export::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowDownTray;

    protected static string|UnitEnum|null $navigationGroup = '系统运营';

    protected static ?string $modelLabel = '导出记录';

    protected static ?string $pluralModelLabel = '导出记录';

    public static function table(Table $table): Table
    {
        return ExportsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListExports::route('/'),
        ];
    }
}
