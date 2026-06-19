<?php

namespace Database\Seeders;

use App\Models\GameRecord;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DemoGameRecordSeeder extends Seeder
{
    /**
     * Seed enough game scores to preview the 30-row ranking list locally.
     */
    public function run(): void
    {
        $nicknames = [
            '疾风01',
            '星轨02',
            '像素03',
            '逐梦04',
            '闪电05',
            '彩虹06',
            '星光07',
            '飞驰08',
            '能量09',
            '冲刺10',
            '跃迁11',
            '光轮12',
            '风暴13',
            '绿影14',
            '蓝焰15',
            '银翼16',
            '超跑17',
            '星河18',
            '云端19',
            '光速20',
            '霓虹21',
            '幻影22',
            '电波23',
            '飞星24',
            '奇旅25',
            '追光26',
            '闪耀27',
            '远航28',
            '领跑29',
            '满分30',
            '补位31',
            '补位32',
            '补位33',
            '补位34',
            '补位35',
            '补位36',
        ];

        DB::transaction(function () use ($nicknames): void {
            foreach ($nicknames as $index => $nickname) {
                $number = $index + 1;
                $score = 9880 - ($index * 173) + (($index % 4) * 21);
                $distance = 4200 - ($index * 61) + (($index % 5) * 17);

                $user = User::query()->updateOrCreate(
                    ['employee_no' => sprintf('DEMO_GAME_%02d', $number)],
                    [
                        'name' => sprintf('排行榜演示用户%02d', $number),
                        'email' => sprintf('demo-game-%02d@example.com', $number),
                        'nickname' => $nickname,
                        'phone' => sprintf('1399000%04d', $number),
                        'address' => '排行榜演示数据',
                        'password' => 'unused',
                        'status' => 'active',
                        'role' => 'user',
                    ],
                );

                GameRecord::query()->updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'distance' => max($distance, 800),
                        'score' => max($score, 1200),
                        'duration' => 60 + ($index % 9) * 6,
                        'played_at' => Carbon::now()->subMinutes($index * 7),
                    ],
                );
            }
        });
    }
}
