<?php

namespace Database\Seeders;

use App\Enums\WorkAuditStatus;
use App\Enums\WorkGroup;
use App\Enums\WorkPublishStatus;
use App\Enums\WorkType;
use App\Models\ActivitySetting;
use App\Models\Prize;
use App\Models\User;
use App\Models\Work;
use App\Support\AwardLevels;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $demoUser = User::query()->updateOrCreate(
            ['employee_no' => 'E0001'],
            [
                'name' => 'Demo User',
                'email' => 'demo@example.com',
                'nickname' => 'demo',
                'phone' => '13800000000',
                'city' => 'Shanghai',
                'password' => 'unused',
                'status' => 'active',
            ]
        );

        $author = User::query()->updateOrCreate(
            ['employee_no' => 'E0002'],
            [
                'name' => 'Author User',
                'email' => 'author@example.com',
                'nickname' => 'author',
                'phone' => '13900000000',
                'city' => 'Shanghai',
                'password' => 'unused',
                'status' => 'active',
            ]
        );

        User::query()->updateOrCreate(
            ['employee_no' => 'A0001'],
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'nickname' => 'admin',
                'phone' => '13700000000',
                'city' => 'Shanghai',
                'password' => 'unused',
                'status' => 'active',
                'role' => 'admin',
            ]
        );

        Work::query()->updateOrCreate(
            ['user_id' => $author->id, 'title' => 'Demo Work'],
            [
                'type' => WorkType::Traditional->value,
                'group' => WorkGroup::Employee->value,
                'description' => 'Seed work for local API testing.',
                'audit_status' => WorkAuditStatus::Published->value,
                'publish_status' => WorkPublishStatus::Published->value,
                'vote_count' => 0,
            ]
        );

        foreach ([
            ['name' => '手有余香奖', 'level' => AwardLevels::FRAGRANCE_VOTE, 'stock' => 10],
            ['name' => '逐梦乐园奖', 'level' => AwardLevels::DREAM_PARK, 'stock' => 3],
            ['name' => '赛博筑梦家才艺大赛奖', 'level' => AwardLevels::TALENT_TOP, 'stock' => 60],
            ['name' => '线上小游戏奖/像素游戏王', 'level' => AwardLevels::GAME_TOP, 'stock' => 10],
            ['name' => '阳光普照奖', 'level' => AwardLevels::PARTICIPATION, 'stock' => 0],
        ] as $prize) {
            Prize::query()->updateOrCreate(['level' => $prize['level']], [
                'name' => $prize['name'],
                'stock' => $prize['stock'],
                'status' => 'active',
            ]);
        }

        foreach ([
            [
                'key' => 'activity_ends_at',
                'label' => '活动结束时间',
                'type' => 'datetime',
                'value' => '2026-07-09 23:59:59',
                'description' => '活动结束后不可上传作品和玩游戏，资料填写与抽奖仍可继续。',
            ],
            [
                'key' => 'lottery_opens_at',
                'label' => '抽奖开放时间',
                'type' => 'datetime',
                'value' => '2026-07-09 00:00:00',
                'description' => '抽奖中心开放时间。',
            ],
            [
                'key' => 'upload_enabled',
                'label' => '上传作品开关',
                'type' => 'boolean',
                'value' => 'true',
                'description' => '控制前台上传作品入口。',
            ],
            [
                'key' => 'game_enabled',
                'label' => '游戏开关',
                'type' => 'boolean',
                'value' => 'true',
                'description' => '控制前台游戏入口。',
            ],
            [
                'key' => 'vote_enabled',
                'label' => '投票开关',
                'type' => 'boolean',
                'value' => 'true',
                'description' => '控制前台投票能力。',
            ],
        ] as $setting) {
            ActivitySetting::query()->updateOrCreate(['key' => $setting['key']], $setting);
        }

        $demoUser->registrationProfile()->updateOrCreate([], [
            'employee_no' => $demoUser->employee_no,
            'name' => $demoUser->name,
            'department' => 'Demo Department',
            'contact' => $demoUser->phone ?? '',
            'audit_status' => 'draft',
        ]);
    }
}
