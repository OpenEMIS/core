<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\SummaryAreaProviderGradeSubjectResults;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class SummaryAreaProviderGradeSubjectResultsApiTest extends TestCase
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

    public function test_can_list_SummaryAreaProviderGradeSubjectResults()
    {
        if (SummaryAreaProviderGradeSubjectResults::count() === 0) {
            SummaryAreaProviderGradeSubjectResults::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/summary-area-provider-grade-subject-results');

        $response->assertStatus(200);
    }

    public function test_can_create_SummaryAreaProviderGradeSubjectResults()
    {
        $record = SummaryAreaProviderGradeSubjectResults::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/summary-area-provider-grade-subject-results', $data);

        $response->assertStatus(201);
    }


    public function test_can_view_ByID_SummaryAreaProviderGradeSubjectResults()
    {
        $record = SummaryAreaProviderGradeSubjectResults::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/summary-area-provider-grade-subject-results/' . $record->academic_period_id);

        $response->assertStatus(405);
    }

    public function test_can_view_SummaryAreaProviderGradeSubjectResults()
    {
        $record = SummaryAreaProviderGradeSubjectResults::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/summary-area-provider-grade-subject-results/' . 'academic_period_id/' . $record->academic_period_id . '/created/' . $record->created);

        $response->assertStatus(200);
    }

    public function test_can_update_SummaryAreaProviderGradeSubjectResults()
    {
        $record = SummaryAreaProviderGradeSubjectResults::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/summary-area-provider-grade-subject-results/' . $record->academic_period_id, $updatedData);

        $response->assertStatus(405);
    }

    public function test_can_delete_SummaryAreaProviderGradeSubjectResults()
    {
        $record = SummaryAreaProviderGradeSubjectResults::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/summary-area-provider-grade-subject-results/' . $record->academic_period_id);

        $response->assertStatus(405);
    }
}
