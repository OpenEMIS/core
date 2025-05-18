<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\SummaryAreaInstitutionGradeAttendances;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class SummaryAreaInstitutionGradeAttendancesApiTest extends TestCase
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

    public function test_can_list_SummaryAreaInstitutionGradeAttendances()
    {
        if (SummaryAreaInstitutionGradeAttendances::count() === 0) {
            SummaryAreaInstitutionGradeAttendances::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/summary-area-institution-grade-attendances');

        $response->assertStatus(200);
    }

    public function test_can_create_SummaryAreaInstitutionGradeAttendances()
    {
        $record = SummaryAreaInstitutionGradeAttendances::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/summary-area-institution-grade-attendances', $data);

        $response->assertStatus(201);
    }


    public function test_can_view_ByID_SummaryAreaInstitutionGradeAttendances()
    {
        $record = SummaryAreaInstitutionGradeAttendances::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/summary-area-institution-grade-attendances/' . $record->academic_period_id);

        $response->assertStatus(405);
    }

    public function test_can_view_SummaryAreaInstitutionGradeAttendances()
    {
        $record = SummaryAreaInstitutionGradeAttendances::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/summary-area-institution-grade-attendances/' . 'academic_period_id/' . $record->academic_period_id . '/created/' . $record->created);

        $response->assertStatus(200);
    }

    public function test_can_update_SummaryAreaInstitutionGradeAttendances()
    {
        $record = SummaryAreaInstitutionGradeAttendances::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/summary-area-institution-grade-attendances/' . $record->academic_period_id, $updatedData);

        $response->assertStatus(405);
    }

    public function test_can_delete_SummaryAreaInstitutionGradeAttendances()
    {
        $record = SummaryAreaInstitutionGradeAttendances::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/summary-area-institution-grade-attendances/' . $record->academic_period_id);

        $response->assertStatus(405);
    }
}
