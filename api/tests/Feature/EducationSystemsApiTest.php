<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\EducationSystems;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class EducationSystemsApiTest extends TestCase
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

    public function test_can_list_EducationSystems()
    {
        if (EducationSystems::count() === 0) {
            EducationSystems::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/education-systems');

        $response->assertStatus(200);
    }

    public function test_can_create_EducationSystems()
    {
        $record = EducationSystems::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/education-systems', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_EducationSystems()
    {
        $record = EducationSystems::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/education-systems/' . $record->id);

        $response->assertStatus(200);
    }


    public function test_can_update_EducationSystems()
    {
        $record = EducationSystems::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/education-systems/' . $record->id, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_EducationSystems()
    {
        $record = EducationSystems::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/education-systems/' . $record->id);

        $response->assertStatus(204);
    }
}
