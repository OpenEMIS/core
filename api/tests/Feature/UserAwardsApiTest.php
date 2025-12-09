<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\UserAwards;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class UserAwardsApiTest extends TestCase
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

    public function test_can_list_UserAwards()
    {
        if (UserAwards::count() === 0) {
            UserAwards::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/user-awards');

        $response->assertStatus(200);
    }

    public function test_can_create_UserAwards()
    {
        $record = UserAwards::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/user-awards', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_UserAwards()
    {
        $record = UserAwards::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/user-awards/' . $record->id);

        $response->assertStatus(200);
    }


    public function test_can_update_UserAwards()
    {
        $record = UserAwards::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/user-awards/' . $record->id, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_UserAwards()
    {
        $record = UserAwards::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/user-awards/' . $record->id);

        $response->assertStatus(204);
    }
}
