<?php

namespace Tests\Feature\Admin;

use App\Filament\Exports\UsersExporter;
use App\Models\UploadedFile;
use App\Models\User;
use App\Support\AdminDisplay;
use Filament\Actions\Exports\Enums\ExportFormat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTableEnhancementTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_admin_fields_are_fillable_and_preferred_name_uses_name(): void
    {
        $user = User::query()->create([
            'name' => 'Preferred Name',
            'email' => 'preferred@example.com',
            'employee_no' => 'E9001',
            'address' => 'Shanghai',
            'work_address_code' => 'SHA-01',
            'password' => 'secret',
        ]);

        $this->assertSame('Preferred Name', AdminDisplay::preferredName($user));
        $this->assertSame('Shanghai', $user->address);
        $this->assertSame('SHA-01', $user->work_address_code);
    }

    public function test_admin_display_detects_media_types(): void
    {
        $image = new UploadedFile(['mime_type' => 'image/png']);
        $audio = new UploadedFile(['mime_type' => 'audio/mpeg']);
        $video = new UploadedFile(['mime_type' => 'video/mp4']);

        $this->assertTrue(AdminDisplay::isImage($image));
        $this->assertTrue(AdminDisplay::isAudio($audio));
        $this->assertTrue(AdminDisplay::isVideo($video));
    }

    public function test_admin_exports_default_to_xlsx_only(): void
    {
        $exporter = new UsersExporter(new \Filament\Actions\Exports\Models\Export, [], []);

        $this->assertSame([ExportFormat::Xlsx], $exporter->getFormats());
    }

    public function test_admin_employee_api_returns_primary_employee_fields_and_work_address_code(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'nickname' => 'admin',
            'password' => 'unused',
        ]);
        $token = $this->postJson('/api/v1/auth/login', [
            'employeeNo' => $admin->employee_no,
            'email' => $admin->email,
            'nickname' => $admin->nickname,
        ])->json('data.token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/admin/employees', [
                'name' => 'Preferred Name',
                'employeeNo' => 'E9100',
                'email' => 'employee-api@example.com',
                'address' => 'Shanghai',
                'workAddressCode' => 'SHA-01',
            ])
            ->assertOk()
            ->assertJsonPath('data.preferredName', 'Preferred Name')
            ->assertJsonPath('data.name', 'Preferred Name')
            ->assertJsonPath('data.address', 'Shanghai')
            ->assertJsonPath('data.workAddressCode', 'SHA-01');
    }
}
