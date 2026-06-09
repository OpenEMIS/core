<?php

namespace Tests\Feature;

use Tests\Feature\Concerns\BootstrapsPocor9509AlertApiData;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\Api5\InstitutionStudents;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class InstitutionStudentsApiTest extends TestCase
{
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

    public function test_can_list_InstitutionStudents()
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/institution-students');

        $response->assertStatus(200);
    }

    public function test_can_create_InstitutionStudents()
    {
        $data = $this->pocor9509InstitutionStudentPayload(); //POCOR-9509: avoid deep factory dependencies in sparse test DB

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/institution-students', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_InstitutionStudents()
    {
        $record = InstitutionStudents::create($this->pocor9509InstitutionStudentPayload()); //POCOR-9509: create a deterministic record without factory-only dependencies
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/institution-students/' . $record->id);

        $response->assertStatus(200);
    }


    public function test_can_update_InstitutionStudents()
    {
        $record = InstitutionStudents::create($this->pocor9509InstitutionStudentPayload([
            'previous_institution_student_id' => 'PREV-2',
        ])); //POCOR-9509: create a deterministic record without factory-only dependencies
        $updatedData = [
            'id' => $record->id,
            'previous_institution_student_id' => 'PREV-UPDATED',
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/institution-students/' . $record->id, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_InstitutionStudents()
    {
        $record = InstitutionStudents::create($this->pocor9509InstitutionStudentPayload([
            'previous_institution_student_id' => 'PREV-3',
        ])); //POCOR-9509: create a deterministic record without factory-only dependencies
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/institution-students/' . $record->id);

        $response->assertStatus(204);
    }
}
