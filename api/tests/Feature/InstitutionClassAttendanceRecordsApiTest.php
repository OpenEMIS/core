<?php

namespace Tests\Feature;
use Tests\Traits\PrimaryKeyStringTrait;


use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\InstitutionClassAttendanceRecords;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class InstitutionClassAttendanceRecordsApiTest extends TestCase
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

    public function test_can_list_InstitutionClassAttendanceRecords()
    {
        if (InstitutionClassAttendanceRecords::count() === 0) {
            InstitutionClassAttendanceRecords::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/institution-class-attendance-records');

        $response->assertStatus(200);
    }

    public function test_can_create_InstitutionClassAttendanceRecords()
    {
        $record = InstitutionClassAttendanceRecords::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/institution-class-attendance-records', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_InstitutionClassAttendanceRecords()
    {
        $record = InstitutionClassAttendanceRecords::factory()->create();

        $keyString = $this->getPrimaryKeyString($record);
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/institution-class-attendance-records' . $keyString);

        $response->assertStatus(200);
    }


    public function test_can_update_InstitutionClassAttendanceRecords()
    {
        $record = InstitutionClassAttendanceRecords::factory()->create();
        $updatedData = [
            'institution_class_id' => $record->institution_class_id,
            // Add at least one field from schema to update
        ];

        $keyString = $this->getPrimaryKeyString($record);
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/institution-class-attendance-records' . $keyString, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_InstitutionClassAttendanceRecords()
    {
        $record = InstitutionClassAttendanceRecords::factory()->create();

        $keyString = $this->getPrimaryKeyString($record);
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/institution-class-attendance-records' . $keyString);

        $response->assertStatus(204);
    }
}
