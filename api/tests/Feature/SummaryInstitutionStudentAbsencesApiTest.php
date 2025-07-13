<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\SummaryInstitutionStudentAbsences;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class SummaryInstitutionStudentAbsencesApiTest extends TestCase
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

    public function test_can_list_SummaryInstitutionStudentAbsences()
    {
        if (SummaryInstitutionStudentAbsences::count() === 0) {
            SummaryInstitutionStudentAbsences::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/summary-institution-student-absences');

        $response->assertStatus(200);
    }

    public function test_can_create_SummaryInstitutionStudentAbsences()
    {
        $record = SummaryInstitutionStudentAbsences::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/summary-institution-student-absences', $data);

        $response->assertStatus(201);
    }


    public function test_can_view_ByID_SummaryInstitutionStudentAbsences()
    {
        $record = SummaryInstitutionStudentAbsences::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/summary-institution-student-absences/' . $record->academic_period_id);

        $response->assertStatus(405);
    }

    public function test_can_view_SummaryInstitutionStudentAbsences()
    {
        $record = SummaryInstitutionStudentAbsences::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/summary-institution-student-absences/' . 'academic_period_id/' . $record->academic_period_id . '/created/' . $record->created);

        $response->assertStatus(200);
    }

    public function test_can_update_SummaryInstitutionStudentAbsences()
    {
        $record = SummaryInstitutionStudentAbsences::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/summary-institution-student-absences/' . $record->academic_period_id, $updatedData);

        $response->assertStatus(405);
    }

    public function test_can_delete_SummaryInstitutionStudentAbsences()
    {
        $record = SummaryInstitutionStudentAbsences::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/summary-institution-student-absences/' . $record->academic_period_id);

        $response->assertStatus(405);
    }
}
