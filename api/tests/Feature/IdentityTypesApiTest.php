<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\IdentityTypes;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class IdentityTypesApiTest extends TestCase
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

    public function test_can_list_IdentityTypes()
    {
        if (IdentityTypes::count() === 0) {
            IdentityTypes::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/identity-types');

        $response->assertStatus(200);
    }

    public function test_can_create_IdentityTypes()
    {
        $record = IdentityTypes::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/identity-types', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_IdentityTypes()
    {
        $record = IdentityTypes::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/identity-types/' . $record->id);

        $response->assertStatus(200);
    }


    public function test_can_update_IdentityTypes()
    {
        $record = IdentityTypes::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/identity-types/' . $record->id, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_IdentityTypes()
    {
        $record = IdentityTypes::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/identity-types/' . $record->id);

        $response->assertStatus(204);
    }
}
