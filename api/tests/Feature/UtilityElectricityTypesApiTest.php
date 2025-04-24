<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\UtilityElectricityTypes;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class UtilityElectricityTypesApiTest extends TestCase
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

    public function test_can_list_UtilityElectricityTypes()
    {
        if (UtilityElectricityTypes::count() === 0) {
            UtilityElectricityTypes::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/utility-electricity-types');

        $response->assertStatus(200);
    }

    public function test_can_create_UtilityElectricityTypes()
    {
        $record = UtilityElectricityTypes::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/utility-electricity-types', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_UtilityElectricityTypes()
    {
        $record = UtilityElectricityTypes::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/utility-electricity-types/' . $record->id);

        $response->assertStatus(200);
    }


    public function test_can_update_UtilityElectricityTypes()
    {
        $record = UtilityElectricityTypes::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/utility-electricity-types/' . $record->id, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_UtilityElectricityTypes()
    {
        $record = UtilityElectricityTypes::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/utility-electricity-types/' . $record->id);

        $response->assertStatus(204);
    }
}
