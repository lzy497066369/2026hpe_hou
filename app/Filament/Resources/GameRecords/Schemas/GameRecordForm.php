<?php

namespace App\Filament\Resources\GameRecords\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class GameRecordForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('user_id')
                    ->label('员工')
                    ->relationship('user', 'name')
                    ->searchable(['name', 'employee_no', 'email'])
                    ->preload()
                    ->required()
                    ->disabled(fn (string $operation): bool => $operation === 'edit'),
                TextInput::make('distance')
                    ->label('距离')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('score')
                    ->label('分数')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('duration')
                    ->label('时长')
                    ->required()
                    ->numeric()
                    ->default(0),
                DateTimePicker::make('played_at')
                    ->label('游戏时间'),
            ]);
    }
}
