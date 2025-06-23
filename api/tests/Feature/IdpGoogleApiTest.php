<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\IdpGoogle;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class IdpGoogleApiTest extends TestCase
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

    public function test_can_list_IdpGoogle()
    {
        if (IdpGoogle::count() === 0) {
            IdpGoogle::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/idp-google');

        $response->assertStatus(200);
    }

    public function test_can_create_IdpGoogle()
    {
        $record = IdpGoogle::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/idp-google', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_IdpGoogle()
    {
        $record = IdpGoogle::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/idp-google/' . $record->system_authentication_id);

        $response->assertStatus(200);
    }


    public function test_can_update_IdpGoogle()
    {
        $record = IdpGoogle::factory()->create();
        $updatedData = [
            'client_secret' => $record->client_secret,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/idp-google/' . $record->system_authentication_id, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_IdpGoogle()
    {
        $record = IdpGoogle::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/idp-google/' . $record->system_authentication_id);

        $response->assertStatus(204);
    }
}
