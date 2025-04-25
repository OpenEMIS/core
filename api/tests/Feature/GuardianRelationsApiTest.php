<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\GuardianRelations;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class GuardianRelationsApiTest extends TestCase
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

    public function test_can_list_GuardianRelations()
    {
        if (GuardianRelations::count() === 0) {
            GuardianRelations::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/guardian-relations');

        $response->assertStatus(200);
    }

    public function test_can_create_GuardianRelations()
    {
        $record = GuardianRelations::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/guardian-relations', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_GuardianRelations()
    {
        $record = GuardianRelations::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/guardian-relations/' . $record->id);

        $response->assertStatus(200);
    }


    public function test_can_update_GuardianRelations()
    {
        $record = GuardianRelations::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/guardian-relations/' . $record->id, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_GuardianRelations()
    {
        $record = GuardianRelations::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/guardian-relations/' . $record->id);

        $response->assertStatus(204);
    }
}
