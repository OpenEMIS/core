<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\Phinxlog;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class PhinxlogApiTest extends TestCase
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

    public function test_can_list_Phinxlog()
    {
        if (Phinxlog::count() === 0) {
            Phinxlog::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/phinxlog');

        $response->assertStatus(200);
    }

    public function test_can_create_Phinxlog()
    {
        $record = Phinxlog::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/phinxlog', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_Phinxlog()
    {
        $record = Phinxlog::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/phinxlog/' . $record->version);

        $response->assertStatus(200);
    }


    public function test_can_update_Phinxlog()
    {
        $record = Phinxlog::factory()->create();
        $updatedData = [
            'version' => $record->version,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/phinxlog/' . $record->version, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_Phinxlog()
    {
        $record = Phinxlog::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/phinxlog/' . $record->version);

        $response->assertStatus(204);
    }
}
