<?php

namespace Tests\Feature;
use Tests\Traits\PrimaryKeyStringTrait;
use Tests\Feature\Concerns\BootstrapsPocor9509AlertApiData;


use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\Api5\InstitutionStudentAbsenceDetails;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class InstitutionStudentAbsenceDetailsApiTest extends TestCase
{
    use PrimaryKeyStringTrait;
    use BootstrapsPocor9509AlertApiData;
    use WithFaker;

    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bootPocor9509AlertApiData(); //POCOR-9509: bootstrap minimal schema/data for alert-related API tests

        $user = TestSecurityUser::where('id', 2)->first();
        if (!$user) {
            $this->markTestSkipped('User with id 2 not found.');
            return;
        }
        $this->token = JWTAuth::fromUser($user);
    }

    public function test_can_list_InstitutionStudentAbsenceDetails()
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/institution-student-absence-details');

        $response->assertStatus(200);
    }

    public function test_can_create_InstitutionStudentAbsenceDetails()
    {
        $data = $this->pocor9509AbsencePayload([
            'date' => '2026-04-16',
        ]); //POCOR-9509: avoid deep factory dependencies in sparse test DB

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/institution-student-absence-details', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_InstitutionStudentAbsenceDetails()
    {
        $record = InstitutionStudentAbsenceDetails::create($this->pocor9509AbsencePayload()); //POCOR-9509: create a deterministic record without factory-only dependencies

        $keyString = $this->getPrimaryKeyString($record);
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/institution-student-absence-details' . $keyString);

        $response->assertStatus(200);
    }


    public function test_can_update_InstitutionStudentAbsenceDetails()
    {
        $record = InstitutionStudentAbsenceDetails::create($this->pocor9509AbsencePayload([
            'date' => '2026-04-17',
        ])); //POCOR-9509: create a deterministic record without factory-only dependencies
        $updatedData = [
            'student_id' => $record->student_id,
            'comment' => 'POCOR-9509 updated absence test',
        ];

        $keyString = $this->getPrimaryKeyString($record);
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/institution-student-absence-details' . $keyString, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_InstitutionStudentAbsenceDetails()
    {
        $record = InstitutionStudentAbsenceDetails::create($this->pocor9509AbsencePayload([
            'date' => '2026-04-18',
        ])); //POCOR-9509: create a deterministic record without factory-only dependencies

        $keyString = $this->getPrimaryKeyString($record);
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/institution-student-absence-details' . $keyString);

        $response->assertStatus(204);
    }
}
