<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\StaffSalaryTransactions;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class StaffSalaryTransactionsApiTest extends TestCase
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

    public function test_can_list_StaffSalaryTransactions()
    {
        if (StaffSalaryTransactions::count() === 0) {
            StaffSalaryTransactions::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/staff-salary-transactions');

        $response->assertStatus(200);
    }

    public function test_can_create_StaffSalaryTransactions()
    {
        $record = StaffSalaryTransactions::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/staff-salary-transactions', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_StaffSalaryTransactions()
    {
        $record = StaffSalaryTransactions::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/staff-salary-transactions/' . $record->id);

        $response->assertStatus(200);
    }


    public function test_can_update_StaffSalaryTransactions()
    {
        $record = StaffSalaryTransactions::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/staff-salary-transactions/' . $record->id, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_StaffSalaryTransactions()
    {
        $record = StaffSalaryTransactions::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/staff-salary-transactions/' . $record->id);

        $response->assertStatus(204);
    }
}
