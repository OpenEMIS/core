<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\FloorCustomFieldValues;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class FloorCustomFieldValuesApiTest extends TestCase
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

    public function test_can_list_FloorCustomFieldValues()
    {
        if (FloorCustomFieldValues::count() === 0) {
            FloorCustomFieldValues::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/floor-custom-field-values');

        $response->assertStatus(200);
    }

    public function test_can_create_FloorCustomFieldValues()
    {
        $record = FloorCustomFieldValues::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/floor-custom-field-values', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_FloorCustomFieldValues()
    {
        $record = FloorCustomFieldValues::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/floor-custom-field-values/' . $record->id);

        $response->assertStatus(200);
    }


    public function test_can_update_FloorCustomFieldValues()
    {
        $record = FloorCustomFieldValues::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/floor-custom-field-values/' . $record->id, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_FloorCustomFieldValues()
    {
        $record = FloorCustomFieldValues::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/floor-custom-field-values/' . $record->id);

        $response->assertStatus(204);
    }
}
