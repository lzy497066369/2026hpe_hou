<?php

namespace Tests\Feature\Api;

use App\Enums\UploadUsageType;
use App\Enums\WorkAuditStatus;
use App\Enums\WorkGroup;
use App\Enums\WorkPublishStatus;
use App\Enums\WorkType;
use App\Models\User;
use App\Models\Work;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_api_endpoints_return_the_standard_envelope(): void
    {
        $user = User::query()->create([
            'name' => 'Demo User',
            'email' => 'demo@example.com',
            'employee_no' => 'E0001',
            'phone' => '13800000000',
            'password' => 'unused',
            'status' => 'active',
        ]);
        $author = User::query()->create([
            'name' => 'Author',
            'email' => 'author@example.com',
            'employee_no' => 'E0002',
            'password' => 'unused',
            'status' => 'active',
        ]);
        $work = Work::query()->create([
            'user_id' => $author->id,
            'type' => WorkType::Traditional->value,
            'group' => WorkGroup::Employee->value,
            'title' => 'Demo Work',
            'description' => 'Demo',
            'audit_status' => WorkAuditStatus::Published->value,
            'publish_status' => WorkPublishStatus::Published->value,
            'vote_count' => 0,
        ]);

        $token = $this->postJson('/api/v1/auth/login', [
            'employeeNo' => $user->employee_no,
            'email' => $user->email,
        ])->json('data.token');

        $headers = ['Authorization' => 'Bearer '.$token];

        $policy = $this->withHeaders($headers)->postJson('/api/v1/uploads/policy', [
            'usageType' => UploadUsageType::RegistrationMaterial->value,
        ]);
        $fileId = $policy->json('data.fileId');

        $recordId = $this->withHeaders($headers)
            ->postJson('/api/v1/lottery/draw')
            ->json('data.id');

        $endpoints = [
            ['POST', '/api/v1/auth/login', ['employeeNo' => 'E0001', 'email' => 'demo@example.com']],
            ['GET', '/api/v1/registration/profile', [], $headers],
            ['GET', '/api/v1/works?page=1&pageSize=10'],
            ['GET', '/api/v1/works/'.$work->id],
            ['POST', '/api/v1/votes', ['workId' => (string) $work->id], $headers],
            ['GET', '/api/v1/lottery/qualification', [], $headers],
            ['POST', '/api/v1/lottery/draw', [], $headers],
            ['POST', '/api/v1/lottery/records/'.$recordId.'/claim', [
                'claimType' => 'shipping',
                'receiverName' => 'Demo User',
                'receiverPhone' => '13800000000',
                'receiverAddress' => 'Shanghai',
            ], $headers],
            ['GET', '/api/v1/profile/summary', [], $headers],
            ['POST', '/api/v1/uploads/policy', ['usageType' => 'work_content'], $headers],
            ['POST', '/api/v1/uploads/complete', ['fileId' => $fileId], $headers],
        ];

        foreach ($endpoints as $endpoint) {
            [$method, $uri] = $endpoint;
            $payload = $endpoint[2] ?? [];
            $requestHeaders = $endpoint[3] ?? [];

            $response = $this->withHeaders($requestHeaders)->json($method, $uri, $payload);

            $response
                ->assertOk()
                ->assertJsonStructure([
                    'code',
                    'message',
                    'data',
                    'request_id',
                ])
                ->assertJsonPath('code', 0);
        }

        $worksResponse = $this->getJson('/api/v1/works?page=1&pageSize=10');
        $worksResponse
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'items',
                    'pagination' => ['page', 'pageSize', 'total', 'hasMore'],
                ],
            ]);
    }
}
