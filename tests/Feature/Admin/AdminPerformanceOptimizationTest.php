<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\GameRecords\Schemas\GameRecordForm;
use App\Filament\Resources\LotteryRecords\Schemas\LotteryRecordForm;
use App\Filament\Resources\QuotaApplications\Schemas\QuotaApplicationForm;
use App\Filament\Resources\RegistrationProfiles\Schemas\RegistrationProfileForm;
use App\Filament\Resources\Works\Schemas\WorkForm;
use App\Models\User;
use App\Services\Admin\AdminStatisticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class AdminPerformanceOptimizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_statistics_overview_is_cached_between_calls(): void
    {
        Cache::flush();

        User::factory()->create();

        DB::enableQueryLog();

        app(AdminStatisticsService::class)->overview();
        $firstQueryCount = count(DB::getQueryLog());

        DB::flushQueryLog();

        app(AdminStatisticsService::class)->overview();
        $secondQueryCount = count(DB::getQueryLog());

        $this->assertGreaterThan(0, $firstQueryCount);
        $this->assertSame(0, $secondQueryCount);
    }

    public function test_api_token_last_used_at_is_not_written_on_every_request(): void
    {
        $issuedAt = now();
        $this->travelTo($issuedAt);

        $user = User::factory()->create([
            'status' => 'active',
            'password' => 'unused',
        ]);

        $token = $user->createToken('h5-api')->plainTextToken;
        [$tokenId] = explode('|', $token, 2);

        PersonalAccessToken::query()
            ->whereKey($tokenId)
            ->update(['last_used_at' => $issuedAt]);

        $this->travelTo($issuedAt->copy()->addMinute());

        DB::enableQueryLog();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me')
            ->assertOk();

        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $tokenId,
            'last_used_at' => $issuedAt->format('Y-m-d H:i:s'),
        ]);

        $lastUsedUpdates = collect(DB::getQueryLog())
            ->filter(fn (array $query): bool => str_contains(strtolower($query['query']), 'update "personal_access_tokens"') ||
                str_contains(strtolower($query['query']), 'update `personal_access_tokens`'))
            ->count();

        $this->assertSame(0, $lastUsedUpdates);

        $this->travelBack();
    }

    public function test_api_token_last_used_at_is_written_after_throttle_window(): void
    {
        $issuedAt = now();
        $this->travelTo($issuedAt);

        $user = User::factory()->create([
            'status' => 'active',
            'password' => 'unused',
        ]);

        $token = $user->createToken('h5-api')->plainTextToken;
        [$tokenId] = explode('|', $token, 2);

        PersonalAccessToken::query()
            ->whereKey($tokenId)
            ->update(['last_used_at' => $issuedAt]);

        $this->travelTo($issuedAt->copy()->addMinutes(6));

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/auth/me')
            ->assertOk();

        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $tokenId,
            'last_used_at' => $issuedAt->copy()->addMinutes(6)->format('Y-m-d H:i:s'),
        ]);

        $this->travelBack();
    }

    public function test_large_relationship_selects_are_not_preloaded(): void
    {
        foreach ($this->schemaClassesWithLargeRelationshipSelects() as $schemaClass) {
            $source = file_get_contents((string) (new \ReflectionClass($schemaClass))->getFileName());

            $this->assertStringNotContainsString('->preload()', $source, $schemaClass.' should not preload large relationship selects.');
        }
    }

    /**
     * @return list<class-string>
     */
    private function schemaClassesWithLargeRelationshipSelects(): array
    {
        return [
            WorkForm::class,
            RegistrationProfileForm::class,
            QuotaApplicationForm::class,
            GameRecordForm::class,
            LotteryRecordForm::class,
        ];
    }
}
