<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\SummaryInstitutionGradeNationalities;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class SummaryInstitutionGradeNationalitiesApiTest extends TestCase
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

    public function test_can_list_SummaryInstitutionGradeNationalities()
    {
        if (SummaryInstitutionGradeNationalities::count() === 0) {
            SummaryInstitutionGradeNationalities::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/summary-institution-grade-nationalities');

        $response->assertStatus(200);
    }

    public function test_can_create_SummaryInstitutionGradeNationalities()
    {
        $record = SummaryInstitutionGradeNationalities::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/summary-institution-grade-nationalities', $data);

        $response->assertStatus(201);
    }


    public function test_can_view_ByID_SummaryInstitutionGradeNationalities()
    {
        $record = SummaryInstitutionGradeNationalities::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/summary-institution-grade-nationalities/' . $record->academic_period_id);

        $response->assertStatus(405);
    }

    public function test_can_view_SummaryInstitutionGradeNationalities()
    {
        $record = SummaryInstitutionGradeNationalities::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/summary-institution-grade-nationalities/' . 'academic_period_id/' . $record->academic_period_id . '/created/' . $record->created);

        $response->assertStatus(200);
    }

    public function test_can_update_SummaryInstitutionGradeNationalities()
    {
        $record = SummaryInstitutionGradeNationalities::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/summary-institution-grade-nationalities/' . $record->academic_period_id, $updatedData);

        $response->assertStatus(405);
    }

    public function test_can_delete_SummaryInstitutionGradeNationalities()
    {
        $record = SummaryInstitutionGradeNationalities::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/summary-institution-grade-nationalities/' . $record->academic_period_id);

        $response->assertStatus(405);
    }
}
