<?php

namespace Tests\Feature;
use Tests\Traits\PrimaryKeyStringTrait;


use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\SecurityRoleFunctions;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class SecurityRoleFunctionsApiTest extends TestCase
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

    public function test_can_list_SecurityRoleFunctions()
    {
        if (SecurityRoleFunctions::count() === 0) {
            SecurityRoleFunctions::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/security-role-functions');

        $response->assertStatus(200);
    }

    public function test_can_create_SecurityRoleFunctions()
    {
        $record = SecurityRoleFunctions::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/security-role-functions', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_SecurityRoleFunctions()
    {
        $record = SecurityRoleFunctions::factory()->create();
        $keyString = $this->getPrimaryKeyString($record);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/security-role-functions' . $keyString);

        $response->assertStatus(200);
    }


    public function test_can_update_SecurityRoleFunctions()
    {
        $record = SecurityRoleFunctions::factory()->create();
        $keyString = $this->getPrimaryKeyString($record);

        $updatedData = [
            'security_role_id' => $record->security_role_id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/security-role-functions' . $keyString, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_SecurityRoleFunctions()
    {
        $record = SecurityRoleFunctions::factory()->create();
        $keyString = $this->getPrimaryKeyString($record);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/security-role-functions' . $keyString);

        $response->assertStatus(204);
    }
}
