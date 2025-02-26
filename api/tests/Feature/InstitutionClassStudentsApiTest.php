<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\InstitutionClassStudents;
use App\Models\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class InstitutionClassStudentsApiTest extends TestCase
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

    public function test_can_list_InstitutionClassStudents()
    {
        if (InstitutionClassStudents::count() === 0) {
            InstitutionClassStudents::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/institution-class-students');

        $response->assertStatus(200);
    }

    public function test_can_create_InstitutionClassStudents()
    {
        $record = InstitutionClassStudents::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/institution-class-students', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_InstitutionClassStudents()
    {
        $record = InstitutionClassStudents::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/institution-class-students/' . $record->id);

        $response->assertStatus(200);
    }


    public function test_can_update_InstitutionClassStudents()
    {
        $record = InstitutionClassStudents::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/institution-class-students/' . $record->id, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_InstitutionClassStudents()
    {
        $record = InstitutionClassStudents::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/institution-class-students/' . $record->id);

        $response->assertStatus(204);
    }
}
