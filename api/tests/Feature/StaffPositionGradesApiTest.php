<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\StaffPositionGrades;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class StaffPositionGradesApiTest extends TestCase
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

    public function test_can_list_StaffPositionGrades()
    {
        if (StaffPositionGrades::count() === 0) {
            StaffPositionGrades::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/staff-position-grades');

        $response->assertStatus(200);
    }

    public function test_can_create_StaffPositionGrades()
    {
        $record = StaffPositionGrades::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/staff-position-grades', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_StaffPositionGrades()
    {
        $record = StaffPositionGrades::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/staff-position-grades/' . $record->id);

        $response->assertStatus(200);
    }


    public function test_can_update_StaffPositionGrades()
    {
        $record = StaffPositionGrades::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/staff-position-grades/' . $record->id, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_StaffPositionGrades()
    {
        $record = StaffPositionGrades::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/staff-position-grades/' . $record->id);

        $response->assertStatus(204);
    }
}
