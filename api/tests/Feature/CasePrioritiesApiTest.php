<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\CasePriorities;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class CasePrioritiesApiTest extends TestCase
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

    public function test_can_list_CasePriorities()
    {
        if (CasePriorities::count() === 0) {
            CasePriorities::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/case-priorities');

        $response->assertStatus(200);
    }

    public function test_can_create_CasePriorities()
    {
        $record = CasePriorities::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/case-priorities', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_CasePriorities()
    {
        $record = CasePriorities::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/case-priorities/' . $record->id);

        $response->assertStatus(200);
    }


    public function test_can_update_CasePriorities()
    {
        $record = CasePriorities::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/case-priorities/' . $record->id, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_CasePriorities()
    {
        $record = CasePriorities::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/case-priorities/' . $record->id);

        $response->assertStatus(204);
    }
}
