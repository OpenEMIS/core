<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\InstitutionClassSubjects;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class InstitutionClassSubjectsApiTest extends TestCase
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

    public function test_can_list_InstitutionClassSubjects()
    {
        if (InstitutionClassSubjects::count() === 0) {
            InstitutionClassSubjects::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/institution-class-subjects');

        $response->assertStatus(200);
    }

    public function test_can_create_InstitutionClassSubjects()
    {
        $record = InstitutionClassSubjects::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/institution-class-subjects', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_InstitutionClassSubjects()
    {
        $record = InstitutionClassSubjects::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/institution-class-subjects/' . $record->id);

        $response->assertStatus(200);
    }


    public function test_can_update_InstitutionClassSubjects()
    {
        $record = InstitutionClassSubjects::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/institution-class-subjects/' . $record->id, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_InstitutionClassSubjects()
    {
        $record = InstitutionClassSubjects::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/institution-class-subjects/' . $record->id);

        $response->assertStatus(204);
    }
}
