<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\CustomRecords;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class CustomRecordsApiTest extends TestCase
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

    public function test_can_list_CustomRecords()
    {
        if (CustomRecords::count() === 0) {
            CustomRecords::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/custom-records');

        $response->assertStatus(200);
    }

    public function test_can_create_CustomRecords()
    {
        $record = CustomRecords::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/custom-records', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_CustomRecords()
    {
        $record = CustomRecords::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/custom-records/' . $record->id);

        $response->assertStatus(200);
    }


    public function test_can_update_CustomRecords()
    {
        $record = CustomRecords::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/custom-records/' . $record->id, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_CustomRecords()
    {
        $record = CustomRecords::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/custom-records/' . $record->id);

        $response->assertStatus(204);
    }
}
