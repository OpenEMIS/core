<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\SecurityUserSessions;
use App\Models\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class SecurityUserSessionsApiTest extends TestCase
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

    public function test_can_list_SecurityUserSessions()
    {
        if (SecurityUserSessions::count() === 0) {
            SecurityUserSessions::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/security-user-sessions');

        $response->assertStatus(200);
    }

    public function test_can_create_SecurityUserSessions()
    {
        $record = SecurityUserSessions::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/security-user-sessions', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_SecurityUserSessions()
    {
        $record = SecurityUserSessions::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/security-user-sessions/' . $record->id);

        $response->assertStatus(200);
    }


    public function test_can_update_SecurityUserSessions()
    {
        $record = SecurityUserSessions::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/security-user-sessions/' . $record->id, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_SecurityUserSessions()
    {
        $record = SecurityUserSessions::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/security-user-sessions/' . $record->id);

        $response->assertStatus(204);
    }
}
