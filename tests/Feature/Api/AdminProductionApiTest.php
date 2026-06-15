<?php

namespace Tests\Feature\Api;

use App\Enums\UploadUsageType;
use App\Enums\WorkAuditStatus;
use App\Enums\WorkGroup;
use App\Enums\WorkPublishStatus;
use App\Enums\WorkType;
use App\Models\GameRecord;
use App\Models\UploadedFile;
use App\Models\User;
use App\Models\Work;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProductionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_uses_sanctum_personal_access_tokens(): void
    {
        User::query()->create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
            'employee_no' => 'E0001',
            'password' => 'unused',
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/v1/auth/login', [
            'employeeNo' => 'E0001',
            'email' => 'demo@example.com',
        ]);

        $response->assertOk();
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_admin_can_manage_employees_and_approve_work_and_calculate_awards(): void
    {
        $admin = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'employee_no' => 'A0001',
            'password' => 'unused',
            'status' => 'active',
            'role' => 'admin',
        ]);
        $author = User::query()->create([
            'name' => 'Author',
            'email' => 'author@example.com',
            'employee_no' => 'E0001',
            'password' => 'unused',
            'status' => 'active',
        ]);
        $file = UploadedFile::query()->create([
            'user_id' => $author->id,
            'disk' => 'local',
            'path' => 'uploads/work.mp4',
            'url' => 'http://localhost/storage/work.mp4',
            'mime_type' => 'video/mp4',
            'size' => 1024,
            'usage_type' => UploadUsageType::WorkContent->value,
            'is_committed' => true,
        ]);
        $work = Work::query()->create([
            'user_id' => $author->id,
            'type' => WorkType::Traditional->value,
            'group' => WorkGroup::Employee->value,
            'title' => 'Pending Work',
            'description' => 'Demo',
            'content_file_id' => $file->id,
            'audit_status' => WorkAuditStatus::Submitted->value,
            'publish_status' => WorkPublishStatus::Hidden->value,
            'vote_count' => 10,
        ]);
        GameRecord::query()->create([
            'user_id' => $author->id,
            'distance' => 1000,
            'score' => 5000,
            'duration' => 90,
            'played_at' => now(),
        ]);

        $token = $this->postJson('/api/v1/auth/login', [
            'employeeNo' => $admin->employee_no,
            'email' => $admin->email,
        ])->json('data.token');
        $headers = ['Authorization' => 'Bearer '.$token];

        $this->withHeaders($headers)->postJson('/api/v1/admin/employees', [
            'name' => 'New User',
            'employeeNo' => 'E0002',
            'email' => 'new@example.com',
        ])
            ->assertOk()
            ->assertJsonPath('data.employeeNo', 'E0002');

        $this->withHeaders($headers)->postJson('/api/v1/admin/works/'.$work->id.'/approve')
            ->assertOk()
            ->assertJsonPath('data.audit_status', WorkAuditStatus::Published->value);

        $this->withHeaders($headers)->postJson('/api/v1/admin/works/'.$work->id.'/adjust-votes', [
            'delta' => 5,
        ])
            ->assertOk()
            ->assertJsonPath('data.vote_count', 15);

        $this->withHeaders($headers)->postJson('/api/v1/admin/prize-records/calculate-final-awards')
            ->assertOk()
            ->assertJsonPath('data.talentAwards', 1)
            ->assertJsonPath('data.gameAwards', 1);
    }
}
