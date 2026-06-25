<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\InfrastructureWashWaterFunctionalities;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class InfrastructureWashWaterFunctionalitiesApiTest extends TestCase
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

    public function test_can_list_InfrastructureWashWaterFunctionalities()
    {
        if (InfrastructureWashWaterFunctionalities::count() === 0) {
            InfrastructureWashWaterFunctionalities::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/infrastructure-wash-water-functionalities');

        $response->assertStatus(200);
    }

    public function test_can_create_InfrastructureWashWaterFunctionalities()
    {
        $record = InfrastructureWashWaterFunctionalities::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/infrastructure-wash-water-functionalities', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_InfrastructureWashWaterFunctionalities()
    {
        $record = InfrastructureWashWaterFunctionalities::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/infrastructure-wash-water-functionalities/' . $record->id);

        $response->assertStatus(200);
    }


    public function test_can_update_InfrastructureWashWaterFunctionalities()
    {
        $record = InfrastructureWashWaterFunctionalities::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/infrastructure-wash-water-functionalities/' . $record->id, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_InfrastructureWashWaterFunctionalities()
    {
        $record = InfrastructureWashWaterFunctionalities::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/infrastructure-wash-water-functionalities/' . $record->id);

        $response->assertStatus(204);
    }
}
