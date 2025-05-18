<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\AppraisalPeriods;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class AppraisalPeriodsApiTest extends TestCase
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

    public function test_can_list_AppraisalPeriods()
    {
        if (AppraisalPeriods::count() === 0) {
            AppraisalPeriods::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/appraisal-periods');

        $response->assertStatus(200);
    }

    public function test_can_create_AppraisalPeriods()
    {
        $record = AppraisalPeriods::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/appraisal-periods', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_AppraisalPeriods()
    {
        $record = AppraisalPeriods::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/appraisal-periods/' . $record->id);

        $response->assertStatus(200);
    }


    public function test_can_update_AppraisalPeriods()
    {
        $record = AppraisalPeriods::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/appraisal-periods/' . $record->id, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_AppraisalPeriods()
    {
        $record = AppraisalPeriods::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/appraisal-periods/' . $record->id);

        $response->assertStatus(204);
    }
}
