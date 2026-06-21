<?php

namespace Tests\Feature\Admin;

use App\Filament\Exports\LotteryRecordExporter;
use App\Filament\Exports\UsersExporter;
use App\Filament\Exports\WorkExporter;
use App\Providers\Filament\AdminPanelProvider;
use App\Filament\Widgets\AdminOverviewStats;
use App\Models\LotteryRecord;
use App\Models\UploadedFile;
use App\Models\User;
use App\Models\Work;
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
            'city' => 'Shanghai',
            'work_address_code' => 'SHA-01',
            'password' => 'secret',
        ]);

        $this->assertSame('Preferred Name', AdminDisplay::preferredName($user));
        $this->assertSame('Shanghai', $user->city);
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

    public function test_admin_display_formats_prize_claim_type(): void
    {
        $this->assertSame('邮寄', AdminDisplay::claimType('shipping'));
        $this->assertSame('现场领取', AdminDisplay::claimType('pickup'));
        $this->assertSame('未填写', AdminDisplay::claimType(null));
    }

    public function test_admin_exports_default_to_csv_only(): void
    {
        $exporter = new UsersExporter(new \Filament\Actions\Exports\Models\Export, [], []);

        $this->assertSame([ExportFormat::Csv], $exporter->getFormats());
    }

    public function test_admin_exports_run_synchronously(): void
    {
        $exporter = new UsersExporter(new \Filament\Actions\Exports\Models\Export, [], []);

        $this->assertSame('sync', $exporter->getJobConnection());
    }

    public function test_admin_panel_disables_database_notifications(): void
    {
        $source = file_get_contents((string) (new \ReflectionClass(AdminPanelProvider::class))->getFileName());

        $this->assertStringNotContainsString('databaseNotifications', $source);
    }

    public function test_work_exporter_contains_complete_identifier_columns(): void
    {
        $exporter = new WorkExporter(new \Filament\Actions\Exports\Models\Export, [], []);
        $columnNames = collect($exporter->getColumns())
            ->map(fn ($column) => $column->getName())
            ->all();

        $this->assertContains('id', $columnNames);
        $this->assertContains('user_id', $columnNames);
        $this->assertContains('cover_file_id', $columnNames);
        $this->assertContains('content_file_id', $columnNames);
    }

    public function test_lottery_record_exporter_contains_claim_and_user_contact_columns(): void
    {
        $exporter = new LotteryRecordExporter(new \Filament\Actions\Exports\Models\Export, [], []);
        $columnNames = collect($exporter->getColumns())
            ->map(fn ($column) => $column->getName())
            ->all();

        $this->assertContains('prizeClaim.claim_type', $columnNames);
        $this->assertContains('prizeClaim.receiver_name', $columnNames);
        $this->assertContains('prizeClaim.receiver_phone', $columnNames);
        $this->assertContains('prizeClaim.receiver_address', $columnNames);
        $this->assertContains('prizeClaim.pickup_address', $columnNames);
        $this->assertContains('user.city', $columnNames);
        $this->assertContains('user.work_address_code', $columnNames);
        $this->assertContains('user.email', $columnNames);
        $this->assertContains('user.employee_no', $columnNames);
    }

    public function test_lottery_claim_type_column_uses_admin_display_formatter(): void
    {
        $tableSource = file_get_contents(base_path('app/Filament/Resources/LotteryRecords/Tables/LotteryRecordsTable.php'));
        $infolistSource = file_get_contents(base_path('app/Filament/Resources/LotteryRecords/Schemas/LotteryRecordInfolist.php'));
        $tableClaimTypeBlock = str($tableSource)
            ->after("TextColumn::make('prizeClaim.claim_type')")
            ->before("TextColumn::make('prizeClaim.receiver_name')")
            ->toString();
        $infolistClaimTypeBlock = str($infolistSource)
            ->after("TextEntry::make('prizeClaim.claim_type')")
            ->before("TextEntry::make('prizeClaim.receiver_name')")
            ->toString();

        $this->assertStringContainsString('AdminDisplay::claimType', $tableClaimTypeBlock);
        $this->assertStringContainsString('AdminDisplay::claimType', $infolistClaimTypeBlock);

        $this->assertStringContainsString("AdminDisplay::claimValue(\$record, 'pickup_address')", $tableSource);
        $this->assertStringContainsString("AdminDisplay::claimValue(\$record, 'pickup_address')", $infolistSource);
    }

    public function test_admin_claim_display_falls_back_to_user_claim_preference(): void
    {
        $user = User::factory()->create([
            'claim_type' => 'shipping',
            'receiver_name' => 'Fallback Receiver',
            'receiver_phone' => '13900000000',
            'receiver_address' => '山西省 晋城市 泽州县 1231231',
            'pickup_address' => '7.16 北京-望京',
        ]);
        $record = LotteryRecord::query()->create([
            'user_id' => $user->id,
            'source_type' => 'manual',
            'result_status' => 'won',
        ])->load(['user', 'prizeClaim']);

        $this->assertSame('邮寄', AdminDisplay::claimTypeForRecord($record));
        $this->assertSame('Fallback Receiver', AdminDisplay::claimValue($record, 'receiver_name'));
        $this->assertSame('13900000000', AdminDisplay::claimValue($record, 'receiver_phone'));
        $this->assertSame('山西省 晋城市 泽州县 1231231', AdminDisplay::claimValue($record, 'receiver_address'));
        $this->assertSame('7.16 北京-望京', AdminDisplay::claimValue($record, 'pickup_address'));
    }

    public function test_admin_overview_hides_removed_summary_cards(): void
    {
        $source = file_get_contents((string) (new \ReflectionClass(AdminOverviewStats::class))->getFileName());

        $this->assertStringContainsString("Stat::make('上传作品人数'", $source);
        $this->assertStringNotContainsString("Stat::make('今日游戏次数'", $source);
        $this->assertStringNotContainsString("Stat::make('今日登录人数'", $source);
        $this->assertStringContainsString("Stat::make('今日上传作品'", $source);
        $this->assertStringContainsString("Stat::make('可领取阳光普照数'", $source);
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

        $createResponse = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/admin/employees', [
                'name' => 'Preferred Name',
                'employeeNo' => 'E9100',
                'email' => 'employee-api@example.com',
                'city' => 'Shanghai',
                'workAddressCode' => 'SHA-01',
            ])
            ->assertOk()
            ->assertJsonPath('data.preferredName', 'Preferred Name')
            ->assertJsonPath('data.name', 'Preferred Name')
            ->assertJsonPath('data.city', 'Shanghai')
            ->assertJsonPath('data.workAddressCode', 'SHA-01');

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->patchJson('/api/v1/admin/employees/'.$createResponse->json('data.id'), [
                'city' => 'Beijing',
                'workAddressCode' => 'BJS-02',
            ])
            ->assertOk()
            ->assertJsonPath('data.city', 'Beijing')
            ->assertJsonPath('data.workAddressCode', 'BJS-02');
    }

    public function test_work_admin_table_contains_city_email_and_city_code_columns(): void
    {
        $source = file_get_contents(base_path('app/Filament/Resources/Works/Tables/WorksTable.php'));

        $this->assertStringContainsString("TextColumn::make('user.email')", $source);
        $this->assertStringContainsString("TextColumn::make('user.city')", $source);
        $this->assertStringContainsString("->label('城市')", $source);
        $this->assertStringContainsString("TextColumn::make('user.work_address_code')", $source);
        $this->assertStringContainsString("->label('code（城市code）')", $source);
    }

    public function test_work_admin_table_contains_city_and_city_code_filters(): void
    {
        $source = file_get_contents(base_path('app/Filament/Resources/Works/Tables/WorksTable.php'));

        $this->assertStringContainsString("SelectFilter::make('city')", $source);
        $this->assertStringContainsString("->where('city', \$data['value'])", $source);
        $this->assertStringContainsString("SelectFilter::make('work_address_code')", $source);
        $this->assertStringContainsString("->where('work_address_code', \$data['value'])", $source);
        $this->assertStringContainsString("whereHas('user'", $source);
    }

    public function test_work_exporter_contains_city_email_and_city_code_columns(): void
    {
        $exporter = new WorkExporter(new \Filament\Actions\Exports\Models\Export, [], []);
        $columnNames = collect($exporter->getColumns())
            ->map(fn ($column) => $column->getName())
            ->all();

        $this->assertContains('user.email', $columnNames);
        $this->assertContains('user.city', $columnNames);
        $this->assertContains('user.work_address_code', $columnNames);
    }

    public function test_bulk_approve_updates_selected_works_to_published_state(): void
    {
        $user = User::factory()->create();
        $first = Work::query()->create([
            'user_id' => $user->id,
            'type' => 'traditional',
            'group' => 'employee',
            'title' => 'Work A',
            'description' => 'A',
            'audit_status' => 'submitted',
            'publish_status' => 'hidden',
        ]);
        $second = Work::query()->create([
            'user_id' => $user->id,
            'type' => 'ai',
            'group' => 'employee',
            'title' => 'Work B',
            'description' => 'B',
            'audit_status' => 'under_review',
            'publish_status' => 'hidden',
        ]);

        Work::query()
            ->whereKey([$first->id, $second->id])
            ->update([
                'audit_status' => 'published',
                'publish_status' => 'published',
                'reviewed_at' => now(),
            ]);

        $this->assertDatabaseHas('works', [
            'id' => $first->id,
            'audit_status' => 'published',
            'publish_status' => 'published',
        ]);
        $this->assertDatabaseHas('works', [
            'id' => $second->id,
            'audit_status' => 'published',
            'publish_status' => 'published',
        ]);
    }
}
