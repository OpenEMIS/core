<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\TrainingProviders;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class TrainingProvidersApiTest extends TestCase
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

    public function test_can_list_TrainingProviders()
    {
        if (TrainingProviders::count() === 0) {
            TrainingProviders::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/training-providers');

        $response->assertStatus(200);
    }

    public function test_can_create_TrainingProviders()
    {
        $record = TrainingProviders::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/training-providers', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_TrainingProviders()
    {
        $record = TrainingProviders::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/training-providers/' . $record->id);

        $response->assertStatus(200);
    }


    public function test_can_update_TrainingProviders()
    {
        $record = TrainingProviders::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/training-providers/' . $record->id, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_TrainingProviders()
    {
        $record = TrainingProviders::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/training-providers/' . $record->id);

        $response->assertStatus(204);
    }
}
