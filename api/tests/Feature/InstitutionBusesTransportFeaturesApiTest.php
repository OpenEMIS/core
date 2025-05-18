<?php

namespace Tests\Feature;
use Tests\Traits\PrimaryKeyStringTrait;


use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\InstitutionBusesTransportFeatures;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class InstitutionBusesTransportFeaturesApiTest extends TestCase
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

    public function test_can_list_InstitutionBusesTransportFeatures()
    {
        if (InstitutionBusesTransportFeatures::count() === 0) {
            InstitutionBusesTransportFeatures::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/institution-buses-transport-features');

        $response->assertStatus(200);
    }

    public function test_can_create_InstitutionBusesTransportFeatures()
    {
        $record = InstitutionBusesTransportFeatures::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/institution-buses-transport-features', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_InstitutionBusesTransportFeatures()
    {
        $record = InstitutionBusesTransportFeatures::factory()->create();
        $keyString = $this->getPrimaryKeyString($record);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/institution-buses-transport-features' . $keyString);

        $response->assertStatus(200);
    }


    public function test_can_update_InstitutionBusesTransportFeatures()
    {
        $record = InstitutionBusesTransportFeatures::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $keyString = $this->getPrimaryKeyString($record);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/institution-buses-transport-features' . $keyString, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_InstitutionBusesTransportFeatures()
    {
        $record = InstitutionBusesTransportFeatures::factory()->create();
        $keyString = $this->getPrimaryKeyString($record);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/institution-buses-transport-features' . $keyString);

        $response->assertStatus(204);
    }
}
