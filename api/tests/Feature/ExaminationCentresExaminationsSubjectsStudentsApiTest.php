<?php

namespace Tests\Feature;
use Tests\Traits\PrimaryKeyStringTrait;


use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\ExaminationCentresExaminationsSubjectsStudents;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class ExaminationCentresExaminationsSubjectsStudentsApiTest extends TestCase
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

    public function test_can_list_ExaminationCentresExaminationsSubjectsStudents()
    {
        if (ExaminationCentresExaminationsSubjectsStudents::count() === 0) {
            ExaminationCentresExaminationsSubjectsStudents::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/examination-centres-examinations-subjects-students');

        $response->assertStatus(200);
    }

    public function test_can_create_ExaminationCentresExaminationsSubjectsStudents()
    {
        $record = ExaminationCentresExaminationsSubjectsStudents::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/examination-centres-examinations-subjects-students', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_ExaminationCentresExaminationsSubjectsStudents()
    {
        $record = ExaminationCentresExaminationsSubjectsStudents::factory()->create();

        $keyString = $this->getPrimaryKeyString($record);
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/examination-centres-examinations-subjects-students' . $keyString);

        $response->assertStatus(200);
    }


    public function test_can_update_ExaminationCentresExaminationsSubjectsStudents()
    {
        $record = ExaminationCentresExaminationsSubjectsStudents::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];

        $keyString = $this->getPrimaryKeyString($record);
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/examination-centres-examinations-subjects-students' . $keyString, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_ExaminationCentresExaminationsSubjectsStudents()
    {
        $record = ExaminationCentresExaminationsSubjectsStudents::factory()->create();

        $keyString = $this->getPrimaryKeyString($record);
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/examination-centres-examinations-subjects-students' . $keyString);

        $response->assertStatus(204);
    }
}
