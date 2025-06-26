<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\CurricularPositions;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class CurricularPositionsApiTest extends TestCase
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

    public function test_can_list_CurricularPositions()
    {
        if (CurricularPositions::count() === 0) {
            CurricularPositions::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/curricular-positions');

        $response->assertStatus(200);
    }

    public function test_can_create_CurricularPositions()
    {
        $record = CurricularPositions::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/curricular-positions', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_CurricularPositions()
    {
        $record = CurricularPositions::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/curricular-positions/' . $record->id);

        $response->assertStatus(200);
    }


    public function test_can_update_CurricularPositions()
    {
        $record = CurricularPositions::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/curricular-positions/' . $record->id, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_CurricularPositions()
    {
        $record = CurricularPositions::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/curricular-positions/' . $record->id);

        $response->assertStatus(204);
    }
}
