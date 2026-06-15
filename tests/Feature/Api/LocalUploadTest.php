<?php

namespace Tests\Feature\Api;

use App\Enums\UploadUsageType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile as HttpUploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LocalUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_upload_file_to_local_public_disk(): void
    {
        Storage::fake('public');

        $user = User::query()->create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
            'employee_no' => 'E0001',
            'password' => 'unused',
            'status' => 'active',
        ]);

        $token = $this->postJson('/api/v1/auth/login', [
            'employeeNo' => $user->employee_no,
            'email' => $user->email,
        ])->json('data.token');

        $response = $this->withHeaders(['Authorization' => 'Bearer '.$token])
            ->post('/api/v1/uploads/local', [
                'usageType' => UploadUsageType::WorkContent->value,
                'file' => HttpUploadedFile::fake()->create('demo.mp4', 1024, 'video/mp4'),
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.usageType', UploadUsageType::WorkContent->value)
            ->assertJsonPath('data.mimeType', 'video/mp4');

        $this->assertDatabaseHas('uploaded_files', [
            'user_id' => $user->id,
            'disk' => 'public',
            'usage_type' => UploadUsageType::WorkContent->value,
            'is_committed' => false,
        ]);

        Storage::disk('public')->assertExists(
            str_replace('/storage/', '', parse_url($response->json('data.url'), PHP_URL_PATH))
        );
    }
}
