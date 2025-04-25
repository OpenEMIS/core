<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\OpenemisTemps;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class OpenemisTempsApiTest extends TestCase
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

    public function test_can_list_OpenemisTemps()
    {
        if (OpenemisTemps::count() === 0) {
            OpenemisTemps::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/openemis-temps');

        $response->assertStatus(200);
    }

    public function test_can_create_OpenemisTemps()
    {
        $record = OpenemisTemps::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/openemis-temps', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_OpenemisTemps()
    {
        $record = OpenemisTemps::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/openemis-temps/' . $record->id);

        $response->assertStatus(200);
    }


    public function test_can_update_OpenemisTemps()
    {
        $record = OpenemisTemps::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/openemis-temps/' . $record->id, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_OpenemisTemps()
    {
        $record = OpenemisTemps::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/openemis-temps/' . $record->id);

        $response->assertStatus(204);
    }
}
