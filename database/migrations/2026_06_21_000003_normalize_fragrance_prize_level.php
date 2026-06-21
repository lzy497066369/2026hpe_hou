<?php

use App\Support\AwardLevels;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('prizes')
            ->where('level', 'vote_lucky')
            ->update([
                'level' => AwardLevels::FRAGRANCE_VOTE,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('prizes')
            ->where('level', AwardLevels::FRAGRANCE_VOTE)
            ->where('name', '手有余香奖')
            ->update([
                'level' => 'vote_lucky',
                'updated_at' => now(),
            ]);
    }
};
