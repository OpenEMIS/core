<?php

namespace Tests\Feature;
use Tests\Traits\PrimaryKeyStringTrait;


use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\StaffPositionTitlesGrades;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class StaffPositionTitlesGradesApiTest extends TestCase
{
    use PrimaryKeyStringTrait;
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

    public function test_can_list_StaffPositionTitlesGrades()
    {
        if (StaffPositionTitlesGrades::count() === 0) {
            StaffPositionTitlesGrades::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/staff-position-titles-grades');

        $response->assertStatus(200);
    }

    public function test_can_create_StaffPositionTitlesGrades()
    {
        $record = StaffPositionTitlesGrades::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/staff-position-titles-grades', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_StaffPositionTitlesGrades()
    {
        $record = StaffPositionTitlesGrades::factory()->create();

        $keyString = $this->getPrimaryKeyString($record);
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/staff-position-titles-grades' . $keyString);

        $response->assertStatus(200);
    }


    public function test_can_update_StaffPositionTitlesGrades()
    {
        $record = StaffPositionTitlesGrades::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];

        $keyString = $this->getPrimaryKeyString($record);
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/staff-position-titles-grades' . $keyString, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_StaffPositionTitlesGrades()
    {
        $record = StaffPositionTitlesGrades::factory()->create();

        $keyString = $this->getPrimaryKeyString($record);
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/staff-position-titles-grades' . $keyString);

        $response->assertStatus(204);
    }
}
