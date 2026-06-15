<?php

namespace Tests\Feature\Api;

use App\Enums\RegistrationAuditStatus;
use App\Enums\UploadUsageType;
use App\Enums\WorkAuditStatus;
use App\Enums\WorkGroup;
use App\Enums\WorkPublishStatus;
use App\Enums\WorkType;
use App\Models\RegistrationProfile;
use App\Models\UploadedFile;
use App\Models\User;
use App\Models\Work;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseBackedApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_login_and_use_core_authenticated_flows(): void
    {
        $user = User::query()->create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
            'employee_no' => 'E0001',
            'nickname' => 'demo',
            'phone' => '13800000000',
            'address' => 'Shanghai',
            'password' => 'unused',
            'status' => 'active',
        ]);

        $login = $this->postJson('/api/v1/auth/login', [
            'employeeNo' => 'E0001',
            'email' => 'demo@example.com',
        ]);

        $login
            ->assertOk()
            ->assertJsonPath('data.user.employeeNo', 'E0001')
            ->assertJsonPath('data.user.name', 'Demo User');

        $token = $login->json('data.token');
        $headers = ['Authorization' => 'Bearer '.$token];

        $policy = $this->withHeaders($headers)->postJson('/api/v1/uploads/policy', [
            'usageType' => UploadUsageType::RegistrationMaterial->value,
        ]);
        $policy->assertOk()->assertJsonPath('data.usageType', UploadUsageType::RegistrationMaterial->value);

        $fileId = $policy->json('data.fileId');
        $this->withHeaders($headers)->postJson('/api/v1/uploads/complete', [
            'fileId' => $fileId,
        ])->assertOk()->assertJsonPath('data.id', $fileId);

        $this->withHeaders($headers)->postJson('/api/v1/registration/submit', [
            'employeeNo' => 'E0001',
            'name' => 'Demo User',
            'department' => 'Demo Department',
            'contact' => '13800000000',
            'materialFileId' => $fileId,
        ])
            ->assertOk()
            ->assertJsonPath('data.auditStatus', RegistrationAuditStatus::Submitted->value);

        $this->withHeaders($headers)->getJson('/api/v1/profile/summary')
            ->assertOk()
            ->assertJsonPath('data.userId', (string) $user->id)
            ->assertJsonPath('data.registrationStatus', RegistrationAuditStatus::Submitted->value);
    }

    public function test_published_works_can_be_listed_and_voted(): void
    {
        $author = User::query()->create([
            'name' => 'Author',
            'email' => 'author@example.com',
            'employee_no' => 'E0001',
            'password' => 'unused',
            'status' => 'active',
        ]);
        $voter = User::query()->create([
            'name' => 'Voter',
            'email' => 'voter@example.com',
            'employee_no' => 'E0002',
            'password' => 'unused',
            'status' => 'active',
        ]);

        RegistrationProfile::query()->create([
            'user_id' => $author->id,
            'employee_no' => 'E0001',
            'name' => 'Author',
            'department' => 'Demo',
            'contact' => '13800000000',
            'audit_status' => RegistrationAuditStatus::Approved->value,
        ]);

        $content = UploadedFile::query()->create([
            'user_id' => $author->id,
            'disk' => 'local',
            'path' => 'uploads/work.mp4',
            'url' => 'https://example.com/work.mp4',
            'mime_type' => 'video/mp4',
            'size' => 1024,
            'checksum' => 'checksum',
            'usage_type' => UploadUsageType::WorkContent->value,
            'is_committed' => true,
        ]);

        $work = Work::query()->create([
            'user_id' => $author->id,
            'type' => WorkType::Traditional->value,
            'group' => WorkGroup::Employee->value,
            'title' => 'Demo Work',
            'description' => 'Demo Description',
            'content_file_id' => $content->id,
            'audit_status' => WorkAuditStatus::Published->value,
            'publish_status' => WorkPublishStatus::Published->value,
            'vote_count' => 0,
        ]);

        $this->getJson('/api/v1/works')
            ->assertOk()
            ->assertJsonPath('data.0.id', (string) $work->id)
            ->assertJsonPath('data.0.voteCount', 0);

        $token = $this->postJson('/api/v1/auth/login', [
            'employeeNo' => 'E0002',
            'email' => 'voter@example.com',
        ])->json('data.token');

        $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->postJson('/api/v1/votes', ['workId' => (string) $work->id])
            ->assertOk()
            ->assertJsonPath('data.workId', (string) $work->id)
            ->assertJsonPath('data.voteCount', 1)
            ->assertJsonPath('data.remainingVotes', 4);

        $this->assertDatabaseHas('work_votes', [
            'work_id' => $work->id,
            'user_id' => $voter->id,
        ]);
    }
}
