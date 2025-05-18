<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\InfrastructureWashHygieneEducations;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class InfrastructureWashHygieneEducationsApiTest extends TestCase
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

    public function test_can_list_InfrastructureWashHygieneEducations()
    {
        if (InfrastructureWashHygieneEducations::count() === 0) {
            InfrastructureWashHygieneEducations::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/infrastructure-wash-hygiene-educations');

        $response->assertStatus(200);
    }

    public function test_can_create_InfrastructureWashHygieneEducations()
    {
        $record = InfrastructureWashHygieneEducations::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/infrastructure-wash-hygiene-educations', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_InfrastructureWashHygieneEducations()
    {
        $record = InfrastructureWashHygieneEducations::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/infrastructure-wash-hygiene-educations/' . $record->id);

        $response->assertStatus(200);
    }


    public function test_can_update_InfrastructureWashHygieneEducations()
    {
        $record = InfrastructureWashHygieneEducations::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/infrastructure-wash-hygiene-educations/' . $record->id, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_InfrastructureWashHygieneEducations()
    {
        $record = InfrastructureWashHygieneEducations::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/infrastructure-wash-hygiene-educations/' . $record->id);

        $response->assertStatus(204);
    }
}
