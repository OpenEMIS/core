<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\IdpSaml;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class IdpSamlApiTest extends TestCase
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

    public function test_can_list_IdpSaml()
    {
        if (IdpSaml::count() === 0) {
            IdpSaml::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/idp-saml');

        $response->assertStatus(200);
    }

    public function test_can_create_IdpSaml()
    {
        $record = IdpSaml::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/idp-saml', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_IdpSaml()
    {
        $record = IdpSaml::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/idp-saml/' . $record->system_authentication_id);

        $response->assertStatus(200);
    }


    public function test_can_update_IdpSaml()
    {
        $record = IdpSaml::factory()->create();
        $updatedData = [
            'system_authentication_id' => $record->system_authentication_id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/idp-saml/' . $record->system_authentication_id, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_IdpSaml()
    {
        $record = IdpSaml::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/idp-saml/' . $record->system_authentication_id);

        $response->assertStatus(204);
    }
}
