<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\Counsellings;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class CounsellingsApiTest extends TestCase
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

    public function test_can_list_Counsellings()
    {
        if (Counsellings::count() === 0) {
            Counsellings::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/counsellings');

        $response->assertStatus(200);
    }

    public function test_can_create_Counsellings()
    {
        $record = Counsellings::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/counsellings', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_Counsellings()
    {
        $record = Counsellings::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/counsellings/' . $record->id);

        $response->assertStatus(200);
    }


    public function test_can_update_Counsellings()
    {
        $record = Counsellings::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/counsellings/' . $record->id, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_Counsellings()
    {
        $record = Counsellings::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/counsellings/' . $record->id);

        $response->assertStatus(204);
    }
}
