<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\StaffLicenses;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class StaffLicensesApiTest extends TestCase
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

    public function test_can_list_StaffLicenses()
    {
        if (StaffLicenses::count() === 0) {
            StaffLicenses::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/staff-licenses');

        $response->assertStatus(200);
    }

    public function test_can_create_StaffLicenses()
    {
        $record = StaffLicenses::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/staff-licenses', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_StaffLicenses()
    {
        $record = StaffLicenses::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/staff-licenses/' . $record->id);

        $response->assertStatus(200);
    }


    public function test_can_update_StaffLicenses()
    {
        $record = StaffLicenses::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/staff-licenses/' . $record->id, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_StaffLicenses()
    {
        $record = StaffLicenses::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/staff-licenses/' . $record->id);

        $response->assertStatus(204);
    }
}
