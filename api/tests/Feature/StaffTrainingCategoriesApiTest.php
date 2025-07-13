<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\StaffTrainingCategories;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class StaffTrainingCategoriesApiTest extends TestCase
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

    public function test_can_list_StaffTrainingCategories()
    {
        if (StaffTrainingCategories::count() === 0) {
            StaffTrainingCategories::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/staff-training-categories');

        $response->assertStatus(200);
    }

    public function test_can_create_StaffTrainingCategories()
    {
        $record = StaffTrainingCategories::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/staff-training-categories', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_StaffTrainingCategories()
    {
        $record = StaffTrainingCategories::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/staff-training-categories/' . $record->id);

        $response->assertStatus(200);
    }


    public function test_can_update_StaffTrainingCategories()
    {
        $record = StaffTrainingCategories::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/staff-training-categories/' . $record->id, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_StaffTrainingCategories()
    {
        $record = StaffTrainingCategories::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/staff-training-categories/' . $record->id);

        $response->assertStatus(204);
    }
}
