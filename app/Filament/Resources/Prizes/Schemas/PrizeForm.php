<?php

namespace App\Filament\Resources\Prizes\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PrizeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('奖品名称')
                    ->required(),
                TextInput::make('level')
                    ->label('奖项标识')
                    ->required(),
                TextInput::make('stock')
                    ->label('库存/名额')
                    ->required()
                    ->numeric()
                    ->default(0),
                Select::make('status')
                    ->label('状态')
                    ->options([
                        'active' => '启用',
                        'disabled' => '禁用',
                    ])
                    ->required()
                    ->default('active'),
                FileUpload::make('image_url')
                    ->label('奖品图片')
                    ->image(),
            ]);
    }
}
