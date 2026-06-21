<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DemoAccountSeeder extends Seeder
{
    /**
     * Seed reusable mock accounts for local login and page-flow testing.
     */
    public function run(): void
    {
        $users = [
            [
                'employee_no' => 'E1001',
                'name' => '测试用户一',
                'email' => 'user1001@example.com',
                'nickname' => '像素筑梦用户1',
                'phone' => '13810001001',
                'city' => '上海',
                'role' => 'user',
            ],
            [
                'employee_no' => 'E1002',
                'name' => '测试用户二',
                'email' => 'user1002@example.com',
                'nickname' => '像素筑梦用户2',
                'phone' => '13810001002',
                'city' => '北京',
                'role' => 'user',
            ],
            [
                'employee_no' => 'E1003',
                'name' => '测试用户三',
                'email' => 'user1003@example.com',
                'nickname' => '像素筑梦用户3',
                'phone' => '13810001003',
                'city' => '广州',
                'role' => 'user',
            ],
            [
                'employee_no' => 'E1004',
                'name' => '测试用户四',
                'email' => 'user1004@example.com',
                'nickname' => '像素筑梦用户4',
                'phone' => '13810001004',
                'city' => '深圳',
                'role' => 'user',
            ],
            [
                'employee_no' => 'E1005',
                'name' => '儿童组测试家长',
                'email' => 'child1005@example.com',
                'nickname' => '小画家家长',
                'phone' => '13810001005',
                'city' => '杭州',
                'role' => 'user',
            ],
            [
                'employee_no' => 'E1006',
                'name' => '员工组测试用户',
                'email' => 'staff1006@example.com',
                'nickname' => '员工绘趣',
                'phone' => '13810001006',
                'city' => '成都',
                'role' => 'user',
            ],
            [
                'employee_no' => 'A1001',
                'name' => '测试管理员',
                'email' => 'admin1001@example.com',
                'nickname' => '像素筑梦管理员',
                'phone' => '13810001999',
                'city' => '上海',
                'role' => 'admin',
            ],
        ];

        foreach ($users as $user) {
            User::query()->updateOrCreate(
                ['employee_no' => $user['employee_no']],
                [
                    ...$user,
                    'password' => 'unused',
                    'status' => 'active',
                ],
            );
        }
    }
}
