<?php


namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\StudentCustomTableCells;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tests\Traits\PrimaryKeyStringTrait;
use Carbon\Carbon;

class StudentCustomTableCellsApiTest extends TestCase
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

    public function test_can_list_StudentCustomTableCells()
    {
        if (StudentCustomTableCells::count() === 0) {
            StudentCustomTableCells::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/student-custom-table-cells');

        $response->assertStatus(200);
    }

    public function test_can_create_StudentCustomTableCells()
    {
        $record = StudentCustomTableCells::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/student-custom-table-cells', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_StudentCustomTableCells()
    {
        $record = StudentCustomTableCells::factory()->create();
        $keyString = $this->getPrimaryKeyString($record);
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/student-custom-table-cells' . $keyString);

        $response->assertStatus(200);
    }


    public function test_can_update_StudentCustomTableCells()
    {
        $record = StudentCustomTableCells::factory()->create();
        $keyString = $this->getPrimaryKeyString($record);
        $updatedData = [
            'text_value' => $record->text_value,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/student-custom-table-cells' . $keyString, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_StudentCustomTableCells()
    {
        $record = StudentCustomTableCells::factory()->create();
        $keyString = $this->getPrimaryKeyString($record);
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/student-custom-table-cells' . $keyString);

        $response->assertStatus(204);
    }


}
