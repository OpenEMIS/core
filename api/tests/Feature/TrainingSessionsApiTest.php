<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\TrainingSessions;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class TrainingSessionsApiTest extends TestCase
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

    public function test_can_list_TrainingSessions()
    {
        if (TrainingSessions::count() === 0) {
            TrainingSessions::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/training-sessions');

        $response->assertStatus(200);
    }

    public function test_can_create_TrainingSessions()
    {
        $record = TrainingSessions::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/training-sessions', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_TrainingSessions()
    {
        $record = TrainingSessions::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/training-sessions/' . $record->id);

        $response->assertStatus(200);
    }


    public function test_can_update_TrainingSessions()
    {
        $record = TrainingSessions::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/training-sessions/' . $record->id, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_TrainingSessions()
    {
        $record = TrainingSessions::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/training-sessions/' . $record->id);

        $response->assertStatus(204);
    }
}
