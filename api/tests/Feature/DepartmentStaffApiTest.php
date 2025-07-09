<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\DepartmentStaff;
use App\Models\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;

class DepartmentStaffApiTest extends TestCase
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

    public function test_can_list_DepartmentStaff()
    {
        if (DepartmentStaff::count() === 0) {
            DepartmentStaff::factory()->count(3)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/department-staff');

        $response->assertStatus(200);
    }

    public function test_can_create_DepartmentStaff()
    {
        $record = DepartmentStaff::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/department-staff', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_DepartmentStaff()
    {
        $record = DepartmentStaff::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/department-staff/' . $record->id);

        $response->assertStatus(200);
    }


    public function test_can_update_DepartmentStaff()
    {
        $record = DepartmentStaff::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/department-staff/' . $record->id, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_DepartmentStaff()
    {
        $record = DepartmentStaff::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/department-staff/' . $record->id);

        $response->assertStatus(204);
    }
}
