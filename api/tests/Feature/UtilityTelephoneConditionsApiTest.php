<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\UtilityTelephoneConditions;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class UtilityTelephoneConditionsApiTest extends TestCase
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

    public function test_can_list_UtilityTelephoneConditions()
    {
        if (UtilityTelephoneConditions::count() === 0) {
            UtilityTelephoneConditions::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/utility-telephone-conditions');

        $response->assertStatus(200);
    }

    public function test_can_create_UtilityTelephoneConditions()
    {
        $record = UtilityTelephoneConditions::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/utility-telephone-conditions', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_UtilityTelephoneConditions()
    {
        $record = UtilityTelephoneConditions::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/utility-telephone-conditions/' . $record->id);

        $response->assertStatus(200);
    }


    public function test_can_update_UtilityTelephoneConditions()
    {
        $record = UtilityTelephoneConditions::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/utility-telephone-conditions/' . $record->id, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_UtilityTelephoneConditions()
    {
        $record = UtilityTelephoneConditions::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/utility-telephone-conditions/' . $record->id);

        $response->assertStatus(204);
    }
}
