<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\InstitutionStudentVisitRequests;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class InstitutionStudentVisitRequestsApiTest extends TestCase
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

    public function test_can_list_InstitutionStudentVisitRequests()
    {
        if (InstitutionStudentVisitRequests::count() === 0) {
            InstitutionStudentVisitRequests::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/institution-student-visit-requests');

        $response->assertStatus(200);
    }

    public function test_can_create_InstitutionStudentVisitRequests()
    {
        $record = InstitutionStudentVisitRequests::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/institution-student-visit-requests', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_InstitutionStudentVisitRequests()
    {
        $record = InstitutionStudentVisitRequests::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/institution-student-visit-requests/' . $record->id);

        $response->assertStatus(200);
    }


    public function test_can_update_InstitutionStudentVisitRequests()
    {
        $record = InstitutionStudentVisitRequests::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/institution-student-visit-requests/' . $record->id, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_InstitutionStudentVisitRequests()
    {
        $record = InstitutionStudentVisitRequests::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/institution-student-visit-requests/' . $record->id);

        $response->assertStatus(204);
    }
}
