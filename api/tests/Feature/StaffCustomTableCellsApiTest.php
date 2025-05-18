<?php

namespace Tests\Feature;
use Tests\Traits\PrimaryKeyStringTrait;


use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\StaffCustomTableCells;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class StaffCustomTableCellsApiTest extends TestCase
{
    use PrimaryKeyStringTrait;
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

    public function test_can_list_StaffCustomTableCells()
    {
        if (StaffCustomTableCells::count() === 0) {
            StaffCustomTableCells::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/staff-custom-table-cells');

        $response->assertStatus(200);
    }

    public function test_can_create_StaffCustomTableCells()
    {
        $record = StaffCustomTableCells::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/staff-custom-table-cells', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_StaffCustomTableCells()
    {
        $record = StaffCustomTableCells::factory()->create();
        $keyString = $this->getPrimaryKeyString($record);
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/staff-custom-table-cells' . $keyString);

        $response->assertStatus(200);
    }


    public function test_can_update_StaffCustomTableCells()
    {
        $record = StaffCustomTableCells::factory()->create();
        $keyString = $this->getPrimaryKeyString($record);
        $updatedData = [
            'text_value' => $record->text_value,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/staff-custom-table-cells' . $keyString, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_StaffCustomTableCells()
    {
        $record = StaffCustomTableCells::factory()->create();
        $keyString = $this->getPrimaryKeyString($record);
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/staff-custom-table-cells' . $keyString);

        $response->assertStatus(204);
    }

    public function getPrimaryKeyString($record)
    {
        $primaryKeys = $record->getKeyName();
        if (!is_array($primaryKeys)) {
            $primaryKeys = [$primaryKeys];
        }

        $keyString = '';
        foreach ($primaryKeys as $key) {
            $keyString .= "/$key/" . $record->$key;
        }

        return $keyString;
    }
}
