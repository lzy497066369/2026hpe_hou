<?php

namespace App\Filament\Resources\QuotaApplications;

use App\Filament\Resources\QuotaApplications\Pages\CreateQuotaApplication;
use App\Filament\Resources\QuotaApplications\Pages\EditQuotaApplication;
use App\Filament\Resources\QuotaApplications\Pages\ListQuotaApplications;
use App\Filament\Resources\QuotaApplications\Pages\ViewQuotaApplication;
use App\Filament\Resources\QuotaApplications\Schemas\QuotaApplicationForm;
use App\Filament\Resources\QuotaApplications\Schemas\QuotaApplicationInfolist;
use App\Filament\Resources\QuotaApplications\Tables\QuotaApplicationsTable;
use App\Models\QuotaApplication;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class QuotaApplicationResource extends Resource
{
    protected static ?string $model = QuotaApplication::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentDuplicate;

    protected static string|UnitEnum|null $navigationGroup = '活动内容';

    protected static ?string $navigationLabel = '材料审核';

    protected static ?string $modelLabel = '材料审核';

    protected static ?string $pluralModelLabel = '材料审核';

    public static function form(Schema $schema): Schema
    {
        return QuotaApplicationForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return QuotaApplicationInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return QuotaApplicationsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListQuotaApplications::route('/'),
            'create' => CreateQuotaApplication::route('/create'),
            'view' => ViewQuotaApplication::route('/{record}'),
            'edit' => EditQuotaApplication::route('/{record}/edit'),
        ];
    }
}
