<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\CustomTableCells;
use App\Models\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class CustomTableCellsApiTest extends TestCase
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

    public function test_can_list_CustomTableCells()
    {
        if (CustomTableCells::count() === 0) {
            CustomTableCells::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/custom-table-cells');

        $response->assertStatus(200);
    }

    public function test_can_create_CustomTableCells()
    {
        $record = CustomTableCells::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/custom-table-cells', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_CustomTableCells()
    {
        $record = CustomTableCells::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/custom-table-cells/' . $record->id);

        $response->assertStatus(200);
    }


    public function test_can_update_CustomTableCells()
    {
        $record = CustomTableCells::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/custom-table-cells/' . $record->id, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_CustomTableCells()
    {
        $record = CustomTableCells::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/custom-table-cells/' . $record->id);

        $response->assertStatus(204);
    }
}
