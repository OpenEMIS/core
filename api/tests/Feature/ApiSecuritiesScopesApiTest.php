<?php

namespace Tests\Feature;
use Tests\Traits\PrimaryKeyStringTrait;


use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\ApiSecuritiesScopes;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class ApiSecuritiesScopesApiTest extends TestCase
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

    public function test_can_list_ApiSecuritiesScopes()
    {
        if (ApiSecuritiesScopes::count() === 0) {
            ApiSecuritiesScopes::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/api-securities-scopes');

        $response->assertStatus(200);
    }

    public function test_can_create_ApiSecuritiesScopes()
    {
        $record = ApiSecuritiesScopes::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/api-securities-scopes', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_ApiSecuritiesScopes()
    {
        $record = ApiSecuritiesScopes::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/api-securities-scopes/api_security_id/' . $record->api_security_id . '/api_scope_id/' . $record->api_scope_id);

        $response->assertStatus(200);
    }


    public function test_can_update_ApiSecuritiesScopes()
    {
        $record = ApiSecuritiesScopes::factory()->create();
        $updatedData = [
            'api_security_id' => $record->api_security_id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/api-securities-scopes/api_security_id/' . $record->api_security_id . '/api_scope_id/' . $record->api_scope_id, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_ApiSecuritiesScopes()
    {
        $record = ApiSecuritiesScopes::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/api-securities-scopes/api_security_id/' . $record->api_security_id . '/api_scope_id/' . $record->api_scope_id);

        $response->assertStatus(204);
    }
}
