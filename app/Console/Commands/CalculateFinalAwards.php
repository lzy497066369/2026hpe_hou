<?php

namespace App\Console\Commands;

use App\Services\Admin\FinalAwardService;
use Illuminate\Console\Command;

class CalculateFinalAwards extends Command
{
    protected $signature = 'activity:calculate-final-awards';

    protected $description = 'Calculate final talent, game, and participation awards.';

    public function handle(FinalAwardService $service): int
    {
        $result = $service->calculate();

        $this->info(json_encode($result, JSON_UNESCAPED_UNICODE));

        return self::SUCCESS;
    }
}
