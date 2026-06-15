<?php

namespace Database\Seeders;

use App\Enums\WorkAuditStatus;
use App\Enums\WorkGroup;
use App\Enums\WorkPublishStatus;
use App\Enums\WorkType;
use App\Models\Prize;
use App\Models\User;
use App\Models\Work;
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
                'address' => 'Shanghai',
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
                'address' => 'Shanghai',
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
                'address' => 'Shanghai',
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
            ['name' => '手有余香奖', 'level' => 'vote_lucky', 'stock' => 10],
            ['name' => '逐梦乐园奖', 'level' => 'dream_park', 'stock' => 3],
            ['name' => '赛博筑梦家才艺大赛奖', 'level' => 'talent_top', 'stock' => 60],
            ['name' => '线上小游戏奖/像素游戏王', 'level' => 'game_top', 'stock' => 10],
            ['name' => '阳光普照奖', 'level' => 'participation', 'stock' => 0],
        ] as $prize) {
            Prize::query()->updateOrCreate(['level' => $prize['level']], [
                'name' => $prize['name'],
                'stock' => $prize['stock'],
                'status' => 'active',
            ]);
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
