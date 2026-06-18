<?php

namespace App\Services\Admin;

class FinalAwardService
{
    public function __construct(private readonly AwardSettlementService $awards)
    {
    }

    /**
     * @return array<string, int>
     */
    public function calculate(): array
    {
        return $this->awards->awardAllFixedAwards();
    }
}
