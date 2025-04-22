<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\ScholarshipLoans;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class ScholarshipLoansApiTest extends TestCase
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

    public function test_can_list_ScholarshipLoans()
    {
        if (ScholarshipLoans::count() === 0) {
            ScholarshipLoans::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/scholarship-loans');

        $response->assertStatus(200);
    }

    public function test_can_create_ScholarshipLoans()
    {
        $record = ScholarshipLoans::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/scholarship-loans', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_ScholarshipLoans()
    {
        $record = ScholarshipLoans::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/scholarship-loans/' . $record->scholarship_id);

        $response->assertStatus(200);
    }


    public function test_can_update_ScholarshipLoans()
    {
        $record = ScholarshipLoans::factory()->create();
        $updatedData = [
            'scholarship_id' => $record->scholarship_id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/scholarship-loans/' . $record->scholarship_id, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_ScholarshipLoans()
    {
        $record = ScholarshipLoans::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/scholarship-loans/' . $record->scholarship_id);

        $response->assertStatus(204);
    }
}
