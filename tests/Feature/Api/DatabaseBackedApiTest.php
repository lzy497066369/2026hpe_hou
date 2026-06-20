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
            'nickname' => 'demo',
        ]);

        $login
            ->assertOk()
            ->assertJsonPath('data.user.employeeNo', 'E0001')
            ->assertJsonPath('data.user.email', 'demo@example.com')
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

        $secondPolicy = $this->withHeaders($headers)->postJson('/api/v1/uploads/policy', [
            'usageType' => UploadUsageType::RegistrationMaterial->value,
        ]);
        $secondPolicy->assertOk()->assertJsonPath('data.usageType', UploadUsageType::RegistrationMaterial->value);
        $secondFileId = $secondPolicy->json('data.fileId');
        $this->withHeaders($headers)->postJson('/api/v1/uploads/complete', [
            'fileId' => $secondFileId,
        ])->assertOk()->assertJsonPath('data.id', $secondFileId);

        $submitResponse = $this->withHeaders($headers)->postJson('/api/v1/registration/submit', [
            'employeeNo' => 'E0001',
            'name' => '',
            'department' => '',
            'contact' => '',
            'materialFileId' => $fileId,
            'materialFileIds' => [$fileId, $secondFileId],
        ]);

        $submitResponse
            ->assertOk()
            ->assertJsonPath('data.employeeNo', 'E0001')
            ->assertJsonPath('data.email', 'demo@example.com')
            ->assertJsonPath('data.name', 'Demo User')
            ->assertJsonPath('data.contact', '13800000000')
            ->assertJsonPath('data.materialFileId', $fileId)
            ->assertJsonPath('data.materialFileIds.0', $fileId)
            ->assertJsonPath('data.materialFileIds.1', $secondFileId)
            ->assertJsonPath('data.materialFiles.0.id', $fileId)
            ->assertJsonPath('data.materialFiles.1.id', $secondFileId)
            ->assertJsonPath('data.auditStatus', RegistrationAuditStatus::Submitted->value);

        $this->assertStringContainsString('/storage/uploads/registration_material/', $submitResponse->json('data.materialFiles.0.url'));

        $profileResponse = $this->withHeaders($headers)->getJson('/api/v1/registration/profile');
        $profileResponse
            ->assertOk()
            ->assertJsonPath('data.employeeNo', 'E0001')
            ->assertJsonPath('data.email', 'demo@example.com')
            ->assertJsonPath('data.materialFileIds.1', $secondFileId)
            ->assertJsonPath('data.materialFiles.1.id', $secondFileId)
            ->assertJsonPath('data.auditStatus', RegistrationAuditStatus::Submitted->value);
        $this->assertStringContainsString('/storage/uploads/registration_material/', $profileResponse->json('data.materialFiles.0.url'));

        RegistrationProfile::query()
            ->where('user_id', $user->id)
            ->update([
                'audit_status' => RegistrationAuditStatus::Approved->value,
                'reviewed_at' => now(),
            ]);

        $this->withHeaders($headers)->getJson('/api/v1/registration/profile')
            ->assertOk()
            ->assertJsonPath('data.auditStatus', RegistrationAuditStatus::Approved->value);

        $this->withHeaders($headers)->getJson('/api/v1/registration/status')
            ->assertOk()
            ->assertJsonPath('data.auditStatus', RegistrationAuditStatus::Approved->value);

        $this->assertDatabaseHas('uploaded_files', [
            'id' => $fileId,
            'is_committed' => true,
        ]);
        $this->assertDatabaseHas('uploaded_files', [
            'id' => $secondFileId,
            'is_committed' => true,
        ]);

        $this->withHeaders($headers)->getJson('/api/v1/profile/summary')
            ->assertOk()
            ->assertJsonPath('data.userId', (string) $user->id)
            ->assertJsonPath('data.registrationStatus', RegistrationAuditStatus::Approved->value);
    }

    public function test_registration_submit_validation_messages_are_chinese(): void
    {
        $user = User::query()->create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
            'employee_no' => 'E0001',
            'nickname' => 'demo',
            'password' => 'unused',
            'status' => 'active',
        ]);

        $token = $this->postJson('/api/v1/auth/login', [
            'employeeNo' => $user->employee_no,
            'email' => $user->email,
            'nickname' => $user->nickname,
        ])->json('data.token');

        $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->postJson('/api/v1/registration/submit', [
                'materialFileIds' => [''],
            ])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => '材料图片至少需要上传 1 张']);
    }

    public function test_published_works_can_be_listed_and_voted(): void
    {
        config(['app.url' => 'https://2026hpeapi.hzblzh.com']);

        $author = User::query()->create([
            'name' => 'Author',
            'email' => 'author@example.com',
            'employee_no' => 'E0001',
            'nickname' => 'author',
            'password' => 'unused',
            'status' => 'active',
        ]);
        $voter = User::query()->create([
            'name' => 'Voter',
            'email' => 'voter@example.com',
            'employee_no' => 'E0002',
            'nickname' => 'voter',
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
            'url' => 'http://localhost/storage/uploads/work.mp4',
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
            ->assertJsonPath('data.items.0.id', (string) $work->id)
            ->assertJsonPath('data.items.0.voteCount', 0)
            ->assertJsonPath('data.items.0.contentUrl', 'https://2026hpeapi.hzblzh.com/storage/uploads/work.mp4')
            ->assertJsonPath('data.items.0.contentMimeType', 'video/mp4');

        $token = $this->postJson('/api/v1/auth/login', [
            'employeeNo' => 'E0002',
            'email' => 'voter@example.com',
            'nickname' => 'voter',
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

    public function test_traditional_work_accepts_audio_content_and_returns_mime_type(): void
    {
        $user = User::query()->create([
            'name' => 'Traditional User',
            'email' => 'traditional@example.com',
            'employee_no' => 'E0101',
            'nickname' => 'trad-aud',
            'password' => 'unused',
            'status' => 'active',
        ]);

        $content = UploadedFile::query()->create([
            'user_id' => $user->id,
            'disk' => 'local',
            'path' => 'uploads/work.mp3',
            'url' => 'https://example.com/work.mp3',
            'mime_type' => 'audio/mpeg',
            'size' => 1024,
            'checksum' => 'checksum-audio-traditional',
            'usage_type' => UploadUsageType::WorkContent->value,
            'is_committed' => false,
        ]);

        $token = $this->postJson('/api/v1/auth/login', [
            'employeeNo' => $user->employee_no,
            'email' => $user->email,
            'nickname' => $user->nickname,
        ])->json('data.token');

        $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->postJson('/api/v1/works/submit', [
                'type' => WorkType::Traditional->value,
                'group' => WorkGroup::Employee->value,
                'title' => 'Traditional Audio',
                'description' => 'Audio work',
                'contentFileId' => (string) $content->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.contentMimeType', 'audio/mpeg');
    }

    public function test_traditional_work_accepts_video_content_and_returns_mime_type(): void
    {
        $user = User::query()->create([
            'name' => 'Traditional Video User',
            'email' => 'traditional-video@example.com',
            'employee_no' => 'E0103',
            'nickname' => 'trad-vid',
            'password' => 'unused',
            'status' => 'active',
        ]);

        $content = UploadedFile::query()->create([
            'user_id' => $user->id,
            'disk' => 'local',
            'path' => 'uploads/work.mp4',
            'url' => 'https://example.com/work.mp4',
            'mime_type' => 'video/mp4',
            'size' => 1024,
            'checksum' => 'checksum-video-traditional',
            'usage_type' => UploadUsageType::WorkContent->value,
            'is_committed' => false,
        ]);

        $token = $this->postJson('/api/v1/auth/login', [
            'employeeNo' => $user->employee_no,
            'email' => $user->email,
            'nickname' => $user->nickname,
        ])->json('data.token');

        $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->postJson('/api/v1/works/submit', [
                'type' => WorkType::Traditional->value,
                'group' => WorkGroup::Employee->value,
                'title' => 'Traditional Video',
                'description' => 'Video work',
                'contentFileId' => (string) $content->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.contentMimeType', 'video/mp4');
    }

    public function test_ai_work_accepts_audio_content_and_returns_mime_type(): void
    {
        $user = User::query()->create([
            'name' => 'AI User',
            'email' => 'ai@example.com',
            'employee_no' => 'E0102',
            'nickname' => 'ai',
            'password' => 'unused',
            'status' => 'active',
        ]);

        $content = UploadedFile::query()->create([
            'user_id' => $user->id,
            'disk' => 'local',
            'path' => 'uploads/work.mp3',
            'url' => 'https://example.com/work.mp3',
            'mime_type' => 'audio/mpeg',
            'size' => 1024,
            'checksum' => 'checksum-audio-ai',
            'usage_type' => UploadUsageType::WorkContent->value,
            'is_committed' => false,
        ]);

        $token = $this->postJson('/api/v1/auth/login', [
            'employeeNo' => $user->employee_no,
            'email' => $user->email,
            'nickname' => $user->nickname,
        ])->json('data.token');

        $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->postJson('/api/v1/works/submit', [
                'type' => WorkType::Ai->value,
                'group' => WorkGroup::Employee->value,
                'title' => 'AI Audio',
                'description' => 'Audio work',
                'contentFileId' => (string) $content->id,
                'toolName' => 'Tool',
                'promptText' => 'Prompt',
            ])
            ->assertOk()
            ->assertJsonPath('data.contentMimeType', 'audio/mpeg');
    }

    public function test_work_submit_accepts_audio_mime_type_variants(): void
    {
        $user = User::query()->create([
            'name' => 'Audio Variant User',
            'email' => 'audio-variant@example.com',
            'employee_no' => 'E0104',
            'nickname' => 'aud-var',
            'password' => 'unused',
            'status' => 'active',
        ]);

        $content = UploadedFile::query()->create([
            'user_id' => $user->id,
            'disk' => 'local',
            'path' => 'uploads/work.m4a',
            'url' => 'https://example.com/work.m4a',
            'mime_type' => 'audio/mp4',
            'size' => 1024,
            'checksum' => 'checksum-audio-variant',
            'usage_type' => UploadUsageType::WorkContent->value,
            'is_committed' => false,
        ]);

        $token = $this->postJson('/api/v1/auth/login', [
            'employeeNo' => $user->employee_no,
            'email' => $user->email,
            'nickname' => $user->nickname,
        ])->json('data.token');

        $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->postJson('/api/v1/works/submit', [
                'type' => WorkType::Traditional->value,
                'group' => WorkGroup::Employee->value,
                'title' => 'Audio Variant',
                'description' => 'Audio work',
                'contentFileId' => (string) $content->id,
            ])
            ->assertOk()
            ->assertJsonPath('data.contentMimeType', 'audio/mp4');
    }

    public function test_published_works_can_only_be_searched_by_list_serial_number(): void
    {
        $author = User::query()->create([
            'name' => 'Serial Author',
            'email' => 'serial@example.com',
            'employee_no' => 'E0098',
            'nickname' => 'serial-author',
            'password' => 'unused',
            'status' => 'active',
        ]);

        $firstWork = $this->createPublishedWork($author, 'Top Vote Work', 'highest votes', 30);
        $secondWork = $this->createPublishedWork($author, 'Middle Vote Work', 'serial target', 20);
        $thirdWork = $this->createPublishedWork($author, 'Low Vote Work', 'lowest votes', 10);

        $this->getJson('/api/v1/works?keyword='.str_pad((string) $secondWork->id, 3, '0', STR_PAD_LEFT).'&page=1&pageSize=10')
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.serial', $secondWork->id)
            ->assertJsonPath('data.items.0.id', (string) $secondWork->id);

        $this->getJson('/api/v1/works?keyword='.urlencode('#'.str_pad((string) $thirdWork->id, 3, '0', STR_PAD_LEFT)).'&page=1&pageSize=10')
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 1)
            ->assertJsonPath('data.items.0.serial', $thirdWork->id)
            ->assertJsonPath('data.items.0.id', (string) $thirdWork->id);

        $this->getJson('/api/v1/works?keyword='.urlencode('Top Vote').'&page=1&pageSize=10')
            ->assertOk()
            ->assertJsonPath('data.pagination.total', 0)
            ->assertJsonCount(0, 'data.items');
    }

    public function test_vote_rejection_messages_are_specific(): void
    {
        $author = User::query()->create([
            'name' => 'Author',
            'email' => 'author@example.com',
            'employee_no' => 'E0001',
            'nickname' => 'author',
            'password' => 'unused',
            'status' => 'active',
        ]);
        $voter = User::query()->create([
            'name' => 'Voter',
            'email' => 'voter@example.com',
            'employee_no' => 'E0002',
            'nickname' => 'voter',
            'password' => 'unused',
            'status' => 'active',
        ]);

        $content = UploadedFile::query()->create([
            'user_id' => $author->id,
            'disk' => 'local',
            'path' => 'uploads/work.png',
            'url' => 'https://example.com/work.png',
            'mime_type' => 'image/png',
            'size' => 1024,
            'checksum' => 'checksum-vote-message',
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
            'audit_status' => WorkAuditStatus::Submitted->value,
            'publish_status' => WorkPublishStatus::Hidden->value,
            'vote_count' => 0,
        ]);

        $authorToken = $this->postJson('/api/v1/auth/login', [
            'employeeNo' => $author->employee_no,
            'email' => $author->email,
            'nickname' => $author->nickname,
        ])->json('data.token');
        $voterToken = $this->postJson('/api/v1/auth/login', [
            'employeeNo' => $voter->employee_no,
            'email' => $voter->email,
            'nickname' => $voter->nickname,
        ])->json('data.token');

        $this->withHeaders(['Authorization' => 'Bearer '.$authorToken])
            ->postJson('/api/v1/votes', ['workId' => (string) $work->id])
            ->assertStatus(422)
            ->assertJsonPath('message', '不能给自己的作品投票');

        $this->withHeaders(['Authorization' => 'Bearer '.$voterToken])
            ->postJson('/api/v1/votes', ['workId' => (string) $work->id])
            ->assertStatus(422)
            ->assertJsonPath('message', '作品未发布，暂不可投票');
    }

    private function createPublishedWork(User $user, string $title, string $description, int $voteCount): Work
    {
        return Work::query()->create([
            'user_id' => $user->id,
            'type' => WorkType::Traditional->value,
            'group' => WorkGroup::Employee->value,
            'title' => $title,
            'description' => $description,
            'audit_status' => WorkAuditStatus::Published->value,
            'publish_status' => WorkPublishStatus::Published->value,
            'vote_count' => $voteCount,
        ]);
    }
}
