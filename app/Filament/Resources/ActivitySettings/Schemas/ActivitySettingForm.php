<?php

namespace App\Filament\Resources\ActivitySettings\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ActivitySettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('key')
                    ->label('配置键')
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('label')
                    ->label('配置名称')
                    ->required(),
                Select::make('type')
                    ->label('类型')
                    ->options([
                        'string' => '文本',
                        'boolean' => '开关',
                        'datetime' => '时间',
                        'number' => '数字',
                    ])
                    ->required()
                    ->default('string'),
                Textarea::make('value')
                    ->label('配置值')
                    ->helperText('开关建议填写 true/false，时间建议填写 ISO 或 Y-m-d H:i:s。')
                    ->columnSpanFull(),
                Textarea::make('description')
                    ->label('说明')
                    ->columnSpanFull(),
            ]);
    }
}
