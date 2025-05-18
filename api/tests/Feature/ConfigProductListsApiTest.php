<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\ConfigProductLists;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class ConfigProductListsApiTest extends TestCase
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

    public function test_can_list_ConfigProductLists()
    {
        if (ConfigProductLists::count() === 0) {
            ConfigProductLists::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/config-product-lists');

        $response->assertStatus(200);
    }

    public function test_can_create_ConfigProductLists()
    {
        $record = ConfigProductLists::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/config-product-lists', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_ConfigProductLists()
    {
        $record = ConfigProductLists::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/config-product-lists/' . $record->id);

        $response->assertStatus(200);
    }


    public function test_can_update_ConfigProductLists()
    {
        $record = ConfigProductLists::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/config-product-lists/' . $record->id, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_ConfigProductLists()
    {
        $record = ConfigProductLists::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/config-product-lists/' . $record->id);

        $response->assertStatus(204);
    }
}
