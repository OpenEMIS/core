<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\Tasks;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;

//POCOR-9694
class TasksApiTest extends TestCase
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

    public function test_can_list_Tasks()
    {
        if (Tasks::count() === 0) {
            Tasks::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/tasks');

        $response->assertStatus(200);
    }

    public function test_can_create_Tasks()
    {
        $record = Tasks::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/tasks', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_Tasks()
    {
        $record = Tasks::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/tasks/' . $record->id);

        $response->assertStatus(200);
    }

    public function test_can_update_Tasks()
    {
        $record = Tasks::factory()->create();
        $updatedData = [
            'id' => $record->id,
            'status' => Tasks::STATUS_DONE,
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/tasks/' . $record->id, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_Tasks()
    {
        $record = Tasks::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/tasks/' . $record->id);

        $response->assertStatus(204);
    }
}
