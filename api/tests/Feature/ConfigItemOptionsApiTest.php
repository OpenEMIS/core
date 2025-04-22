<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\ConfigItemOptions;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class ConfigItemOptionsApiTest extends TestCase
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

    public function test_can_list_ConfigItemOptions()
    {
        if (ConfigItemOptions::count() === 0) {
            ConfigItemOptions::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/config-item-options');

        $response->assertStatus(200);
    }

    public function test_can_create_ConfigItemOptions()
    {
        $record = ConfigItemOptions::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/config-item-options', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_ConfigItemOptions()
    {
        $record = ConfigItemOptions::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/config-item-options/' . $record->id);

        $response->assertStatus(200);
    }


    public function test_can_update_ConfigItemOptions()
    {
        $record = ConfigItemOptions::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/config-item-options/' . $record->id, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_ConfigItemOptions()
    {
        $record = ConfigItemOptions::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/config-item-options/' . $record->id);

        $response->assertStatus(204);
    }
}
