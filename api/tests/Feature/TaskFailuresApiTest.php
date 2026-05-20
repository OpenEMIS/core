<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\TaskFailures;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;

//POCOR-9694
class TaskFailuresApiTest extends TestCase
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

    public function test_can_list_TaskFailures()
    {
        if (TaskFailures::count() === 0) {
            TaskFailures::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/task-failures');

        $response->assertStatus(200);
    }

    public function test_can_create_TaskFailures()
    {
        $record = TaskFailures::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/task-failures', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_TaskFailures()
    {
        $record = TaskFailures::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/task-failures/' . $record->id);

        $response->assertStatus(200);
    }

    public function test_can_update_TaskFailures()
    {
        $record = TaskFailures::factory()->create();
        $updatedData = [
            'id' => $record->id,
            'retry_allowed' => false,
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/task-failures/' . $record->id, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_TaskFailures()
    {
        $record = TaskFailures::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/task-failures/' . $record->id);

        $response->assertStatus(204);
    }
}
