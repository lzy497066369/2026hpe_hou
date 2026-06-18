<?php

namespace App\Filament\Pages\Auth;

use Filament\Auth\Pages\Login;
use Illuminate\Contracts\Support\Htmlable;

class AdminLogin extends Login
{
    public function mount(): void
    {
        parent::mount();

        $this->form->fill([
            'email' => 'admin@example.com',
            'password' => 'unused',
        ]);
    }

    public function getTitle(): string | Htmlable
    {
        return '后台登录';
    }

    public function getHeading(): string | Htmlable | null
    {
        return '像素筑梦，随心绘趣';
    }

    public function getSubheading(): string | Htmlable | null
    {
        return '活动后台管理系统';
    }
}
