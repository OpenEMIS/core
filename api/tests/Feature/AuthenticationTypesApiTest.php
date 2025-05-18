<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\AuthenticationTypes;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class AuthenticationTypesApiTest extends TestCase
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

    public function test_can_list_AuthenticationTypes()
    {
        if (AuthenticationTypes::count() === 0) {
            AuthenticationTypes::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/authentication-types');

        $response->assertStatus(200);
    }

    public function test_can_create_AuthenticationTypes()
    {
        $record = AuthenticationTypes::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/authentication-types', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_AuthenticationTypes()
    {
        $record = AuthenticationTypes::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/authentication-types/' . $record->id);

        $response->assertStatus(200);
    }


    public function test_can_update_AuthenticationTypes()
    {
        $record = AuthenticationTypes::factory()->create();
        $updatedData = [
            'name' => $this->faker->name(),
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/authentication-types/' . $record->id, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_AuthenticationTypes()
    {
        $record = AuthenticationTypes::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/authentication-types/' . $record->id);

        $response->assertStatus(204);
    }
}
