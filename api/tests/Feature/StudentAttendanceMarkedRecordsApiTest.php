<?php

namespace Tests\Feature;
use Tests\Traits\PrimaryKeyStringTrait;


use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\StudentAttendanceMarkedRecords;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class StudentAttendanceMarkedRecordsApiTest extends TestCase
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

    public function test_can_list_StudentAttendanceMarkedRecords()
    {
        if (StudentAttendanceMarkedRecords::count() === 0) {
            StudentAttendanceMarkedRecords::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/student-attendance-marked-records');

        $response->assertStatus(200);
    }

    public function test_can_create_StudentAttendanceMarkedRecords()
    {
        $record = StudentAttendanceMarkedRecords::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/student-attendance-marked-records', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_StudentAttendanceMarkedRecords()
    {
        $record = StudentAttendanceMarkedRecords::factory()->create();

        $keyString = $this->getPrimaryKeyString($record);
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/student-attendance-marked-records' . $keyString);

        $response->assertStatus(200);
    }


    public function test_can_update_StudentAttendanceMarkedRecords()
    {
        $record = StudentAttendanceMarkedRecords::factory()->create();
        $updatedData = [
            'institution_id' => $record->institution_id,
            // Add at least one field from schema to update
        ];

        $keyString = $this->getPrimaryKeyString($record);
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/student-attendance-marked-records' . $keyString, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_StudentAttendanceMarkedRecords()
    {
        $record = StudentAttendanceMarkedRecords::factory()->create();

        $keyString = $this->getPrimaryKeyString($record);
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/student-attendance-marked-records' . $keyString);

        $response->assertStatus(204);
    }
}
