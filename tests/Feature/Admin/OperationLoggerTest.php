<?php

namespace Tests\Feature\Admin;

use App\Models\OperationLog;
use App\Models\User;
use App\Models\Work;
use App\Services\Admin\OperationLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperationLoggerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_records_admin_operation_with_target_payload_and_ip(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $work = Work::query()->create([
            'user_id' => $admin->id,
            'type' => 'traditional',
            'group' => 'employee',
            'title' => '测试作品',
            'audit_status' => 'submitted',
            'publish_status' => 'hidden',
            'vote_count' => 3,
        ]);

        $this->actingAs($admin);

        app(OperationLogger::class)->log('works', 'adjust_votes', $work, [
            'before' => 3,
            'after' => 8,
        ]);

        $this->assertDatabaseHas(OperationLog::class, [
            'user_id' => $admin->id,
            'module' => 'works',
            'action' => 'adjust_votes',
            'target_type' => Work::class,
            'target_id' => $work->id,
        ]);

        $this->assertSame([
            'before' => 3,
            'after' => 8,
        ], OperationLog::query()->firstOrFail()->payload);
    }
}
