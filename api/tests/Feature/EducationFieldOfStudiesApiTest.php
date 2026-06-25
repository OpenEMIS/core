<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\EducationFieldOfStudies;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class EducationFieldOfStudiesApiTest extends TestCase
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

    public function test_can_list_EducationFieldOfStudies()
    {
        if (EducationFieldOfStudies::count() === 0) {
            EducationFieldOfStudies::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/education-field-of-studies');

        $response->assertStatus(200);
    }

    public function test_can_create_EducationFieldOfStudies()
    {
        $record = EducationFieldOfStudies::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/education-field-of-studies', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_EducationFieldOfStudies()
    {
        $record = EducationFieldOfStudies::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/education-field-of-studies/' . $record->id);

        $response->assertStatus(200);
    }


    public function test_can_update_EducationFieldOfStudies()
    {
        $record = EducationFieldOfStudies::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/education-field-of-studies/' . $record->id, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_EducationFieldOfStudies()
    {
        $record = EducationFieldOfStudies::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/education-field-of-studies/' . $record->id);

        $response->assertStatus(204);
    }
}
