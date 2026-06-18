<?php

namespace App\Filament\Resources\Prizes\Schemas;

use App\Support\AdminDisplay;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class PrizeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->label('奖品名称'),
                TextEntry::make('level')
                    ->label('奖项标识'),
                TextEntry::make('stock')
                    ->label('库存/名额')
                    ->numeric(),
                TextEntry::make('status')
                    ->label('状态')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => AdminDisplay::prizeStatus($state)),
                ImageEntry::make('image_url')
                    ->label('奖品图片')
                    ->getStateUsing(fn ($record): ?string => AdminDisplay::url($record->image_url))
                    ->imageHeight(180)
                    ->placeholder('未上传'),
                TextEntry::make('created_at')
                    ->label('创建时间')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->label('更新时间')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
