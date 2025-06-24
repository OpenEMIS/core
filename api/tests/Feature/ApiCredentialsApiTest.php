<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\ApiCredentials;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class ApiCredentialsApiTest extends TestCase
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

    public function test_can_list_ApiCredentials()
    {
        if (ApiCredentials::count() === 0) {
            ApiCredentials::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/api-credentials');

        $response->assertStatus(200);
    }

    public function test_can_create_ApiCredentials()
    {
        $record = ApiCredentials::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/api-credentials', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_ApiCredentials()
    {
        $record = ApiCredentials::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/api-credentials/' . $record->id);

        $response->assertStatus(200);
    }


    public function test_can_update_ApiCredentials()
    {
        $record = ApiCredentials::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/api-credentials/' . $record->id, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_ApiCredentials()
    {
        $record = ApiCredentials::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/api-credentials/' . $record->id);

        $response->assertStatus(204);
    }
}
