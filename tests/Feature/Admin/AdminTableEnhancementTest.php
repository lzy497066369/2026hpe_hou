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

    public function test_user_admin_fields_are_fillable_and_preferred_name_uses_username(): void
    {
        $user = User::query()->create([
            'name' => 'Legal Name',
            'username' => 'Preferred Name',
            'email' => 'preferred@example.com',
            'employee_no' => 'E9001',
            'work_city' => 'Shanghai',
            'mail_code' => 'CN-SHA',
            'work_address_code' => 'SHA-01',
            'password' => 'secret',
        ]);

        $this->assertSame('Preferred Name', $user->username);
        $this->assertSame('Preferred Name', AdminDisplay::preferredName($user));
        $this->assertSame('Shanghai', $user->work_city);
        $this->assertSame('CN-SHA', $user->mail_code);
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

    public function test_admin_employee_api_returns_preferred_name_and_work_fields(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
            'password' => 'unused',
        ]);
        $token = $this->postJson('/api/v1/auth/login', [
            'employeeNo' => $admin->employee_no,
            'email' => $admin->email,
            'nickname' => $admin->nickname,
        ])->json('data.token');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/admin/employees', [
                'name' => 'Legal Name',
                'username' => 'Preferred Name',
                'employeeNo' => 'E9100',
                'email' => 'employee-api@example.com',
                'workCity' => 'Shanghai',
                'mailCode' => 'CN-SHA',
                'workAddressCode' => 'SHA-01',
            ])
            ->assertOk()
            ->assertJsonPath('data.username', 'Preferred Name')
            ->assertJsonPath('data.preferredName', 'Preferred Name')
            ->assertJsonPath('data.workCity', 'Shanghai')
            ->assertJsonPath('data.mailCode', 'CN-SHA')
            ->assertJsonPath('data.workAddressCode', 'SHA-01');
    }
}
