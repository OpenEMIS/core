<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\SystemPatches;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SystemPatchesApiTest extends TestCase
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

    public function test_can_list_SystemPatches()
    {
        if (SystemPatches::count() === 0) {
            SystemPatches::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/system-patches');

        $response->assertStatus(200);
    }

    public function test_can_create_SystemPatches()
    {
        $record = SystemPatches::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/system-patches', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_SystemPatches()
    {
        $record = SystemPatches::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/system-patches/' . $record->issue);

        $response->assertStatus(200);
    }


    public function test_can_update_SystemPatches()
    {
        $record = SystemPatches::factory()->create();
        $updatedData = [
            'created' => $record->created,
            // Add at least one field from schema to update
        ];
        Log::info($updatedData);
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/system-patches/' . $record->issue, $updatedData);
        Log::info($response->getContent());
        $response->assertStatus(200);
    }

    public function test_can_delete_SystemPatches()
    {
        $record = SystemPatches::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/system-patches/' . $record->issue);

        $response->assertStatus(204);
    }
}
