<?php

namespace Tests\Feature;
use Tests\Traits\PrimaryKeyStringTrait;


use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\InstitutionStudentAbsenceDetails;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class InstitutionStudentAbsenceDetailsApiTest extends TestCase
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

    public function test_can_list_InstitutionStudentAbsenceDetails()
    {
        if (InstitutionStudentAbsenceDetails::count() === 0) {
            InstitutionStudentAbsenceDetails::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/institution-student-absence-details');

        $response->assertStatus(200);
    }

    public function test_can_create_InstitutionStudentAbsenceDetails()
    {
        $record = InstitutionStudentAbsenceDetails::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/institution-student-absence-details', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_InstitutionStudentAbsenceDetails()
    {
        $record = InstitutionStudentAbsenceDetails::factory()->create();

        $keyString = $this->getPrimaryKeyString($record);
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/institution-student-absence-details' . $keyString);

        $response->assertStatus(200);
    }


    public function test_can_update_InstitutionStudentAbsenceDetails()
    {
        $record = InstitutionStudentAbsenceDetails::factory()->create();
        $updatedData = [
            'student_id' => $record->student_id,
            // Add at least one field from schema to update
        ];

        $keyString = $this->getPrimaryKeyString($record);
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/institution-student-absence-details' . $keyString, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_InstitutionStudentAbsenceDetails()
    {
        $record = InstitutionStudentAbsenceDetails::factory()->create();

        $keyString = $this->getPrimaryKeyString($record);
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/institution-student-absence-details' . $keyString);

        $response->assertStatus(204);
    }
}
