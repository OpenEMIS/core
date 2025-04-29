<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\InfrastructureWashSanitationQualities;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class InfrastructureWashSanitationQualitiesApiTest extends TestCase
{
    use DatabaseTransactions, WithFaker;

    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        $user = TestSecurityUser::where('id', 2)->first();
        if (!$user) {
            $this->markTestSkipped('User with id 2 not found.');
            return;
        }
        $this->token = JWTAuth::fromUser($user);
    }

    public function test_can_list_InfrastructureWashSanitationQualities()
    {
        if (InfrastructureWashSanitationQualities::count() === 0) {
            InfrastructureWashSanitationQualities::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/infrastructure-wash-sanitation-qualities');

        $response->assertStatus(200);
    }

    public function test_can_create_InfrastructureWashSanitationQualities()
    {
        $record = InfrastructureWashSanitationQualities::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/infrastructure-wash-sanitation-qualities', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_InfrastructureWashSanitationQualities()
    {
        $record = InfrastructureWashSanitationQualities::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/infrastructure-wash-sanitation-qualities/' . $record->id);

        $response->assertStatus(200);
    }


    public function test_can_update_InfrastructureWashSanitationQualities()
    {
        $record = InfrastructureWashSanitationQualities::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/infrastructure-wash-sanitation-qualities/' . $record->id, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_InfrastructureWashSanitationQualities()
    {
        $record = InfrastructureWashSanitationQualities::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/infrastructure-wash-sanitation-qualities/' . $record->id);

        $response->assertStatus(204);
    }
}
