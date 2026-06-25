<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\SpecialNeedDifficulties;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class SpecialNeedDifficultiesApiTest extends TestCase
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

    public function test_can_list_SpecialNeedDifficulties()
    {
        if (SpecialNeedDifficulties::count() === 0) {
            SpecialNeedDifficulties::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/special-need-difficulties');

        $response->assertStatus(200);
    }

    public function test_can_create_SpecialNeedDifficulties()
    {
        $record = SpecialNeedDifficulties::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/special-need-difficulties', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_SpecialNeedDifficulties()
    {
        $record = SpecialNeedDifficulties::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/special-need-difficulties/' . $record->id);

        $response->assertStatus(200);
    }


    public function test_can_update_SpecialNeedDifficulties()
    {
        $record = SpecialNeedDifficulties::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/special-need-difficulties/' . $record->id, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_SpecialNeedDifficulties()
    {
        $record = SpecialNeedDifficulties::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/special-need-difficulties/' . $record->id);

        $response->assertStatus(204);
    }
}
