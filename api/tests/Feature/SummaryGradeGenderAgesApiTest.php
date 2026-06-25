<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\SummaryGradeGenderAges;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class SummaryGradeGenderAgesApiTest extends TestCase
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

    public function test_can_list_SummaryGradeGenderAges()
    {
        if (SummaryGradeGenderAges::count() === 0) {
            SummaryGradeGenderAges::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/summary-grade-gender-ages');

        $response->assertStatus(200);
    }

    public function test_can_create_SummaryGradeGenderAges()
    {
        $record = SummaryGradeGenderAges::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/summary-grade-gender-ages', $data);

        $response->assertStatus(201);
    }


    public function test_can_view_ByID_SummaryGradeGenderAges()
    {
        $record = SummaryGradeGenderAges::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/summary-grade-gender-ages/' . $record->academic_period_id);

        $response->assertStatus(405);
    }

    public function test_can_view_SummaryGradeGenderAges()
    {
        $record = SummaryGradeGenderAges::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/summary-grade-gender-ages/' . 'academic_period_id/' . $record->academic_period_id . '/created/' . $record->created);

        $response->assertStatus(200);
    }

    public function test_can_update_SummaryGradeGenderAges()
    {
        $record = SummaryGradeGenderAges::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/summary-grade-gender-ages/' . $record->academic_period_id, $updatedData);

        $response->assertStatus(405);
    }

    public function test_can_delete_SummaryGradeGenderAges()
    {
        $record = SummaryGradeGenderAges::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/summary-grade-gender-ages/' . $record->academic_period_id);

        $response->assertStatus(405);
    }
}
