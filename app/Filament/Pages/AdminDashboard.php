<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard;
use Filament\Support\Icons\Heroicon;

class AdminDashboard extends Dashboard
{
    protected static ?string $title = '运营总览';

    protected static ?string $navigationLabel = '运营总览';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;
}
