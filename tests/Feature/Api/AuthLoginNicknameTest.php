<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuthLoginNicknameTest extends TestCase
{
    use RefreshDatabase;

    public function test_first_login_requires_nickname_when_employee_has_no_saved_nickname(): void
    {
        User::query()->create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
            'employee_no' => 'E0001',
            'nickname' => null,
            'password' => 'unused',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'employeeNo' => 'E0001',
            'email' => 'demo@example.com',
        ]);

        $response
            ->assertStatus(422)
            ->assertJsonPath('message', '首次登录请填写昵称');
    }

    public function test_first_login_saves_nickname_for_existing_employee(): void
    {
        User::query()->create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
            'employee_no' => 'E0001',
            'nickname' => null,
            'password' => 'unused',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'employeeNo' => 'E0001',
            'email' => 'demo@example.com',
            'nickname' => '小绿',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.user.name', 'Demo User')
            ->assertJsonPath('data.user.nickname', '小绿');

        $this->assertDatabaseHas('users', [
            'employee_no' => 'E0001',
            'nickname' => '小绿',
        ]);
    }

    public function test_later_login_does_not_overwrite_saved_nickname(): void
    {
        User::query()->create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
            'employee_no' => 'E0001',
            'nickname' => '第一次昵称',
            'password' => 'unused',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'employeeNo' => 'E0001',
            'email' => 'demo@example.com',
            'nickname' => '第二次昵称',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.user.nickname', '第一次昵称');

        $this->assertDatabaseHas('users', [
            'employee_no' => 'E0001',
            'nickname' => '第一次昵称',
        ]);
    }

    public function test_login_can_skip_nickname_when_employee_has_saved_nickname(): void
    {
        User::query()->create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
            'employee_no' => 'E0001',
            'nickname' => '已有昵称',
            'password' => 'unused',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'employeeNo' => 'E0001',
            'email' => 'demo@example.com',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.user.nickname', '已有昵称');
    }

    public function test_successful_login_records_last_login_at(): void
    {
        $this->travelTo(now('Asia/Shanghai')->setDate(2026, 6, 20)->setTime(10, 30));

        User::query()->create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
            'employee_no' => 'E0001',
            'nickname' => '已有昵称',
            'password' => 'unused',
            'status' => 'active',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'employeeNo' => 'E0001',
            'email' => 'demo@example.com',
        ])->assertOk();

        $this->assertDatabaseHas('users', [
            'employee_no' => 'E0001',
            'last_login_at' => now()->format('Y-m-d H:i:s'),
        ]);

        $this->travelBack();
    }

    public function test_login_still_works_before_last_login_at_migration_is_applied(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex('users_last_login_at_index');
            $table->dropColumn('last_login_at');
        });

        User::query()->create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
            'employee_no' => 'E0001',
            'nickname' => '已有昵称',
            'password' => 'unused',
            'status' => 'active',
        ]);

        $this->postJson('/api/v1/auth/login', [
            'employeeNo' => 'E0001',
            'email' => 'demo@example.com',
        ])->assertOk();
    }
}
