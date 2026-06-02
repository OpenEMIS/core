<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\TaskJobs;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;

//POCOR-9694
class TaskJobsApiTest extends TestCase
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

    public function test_can_list_TaskJobs()
    {
        if (TaskJobs::count() === 0) {
            TaskJobs::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/task-jobs');

        $response->assertStatus(200);
    }

    public function test_can_create_TaskJobs()
    {
        $record = TaskJobs::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/task-jobs', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_TaskJobs()
    {
        $record = TaskJobs::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/task-jobs/' . $record->id);

        $response->assertStatus(200);
    }

    public function test_can_update_TaskJobs()
    {
        $record = TaskJobs::factory()->create();
        $updatedData = [
            'id' => $record->id,
            'status' => TaskJobs::STATUS_DONE,
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/task-jobs/' . $record->id, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_TaskJobs()
    {
        $record = TaskJobs::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/task-jobs/' . $record->id);

        $response->assertStatus(204);
    }
}
