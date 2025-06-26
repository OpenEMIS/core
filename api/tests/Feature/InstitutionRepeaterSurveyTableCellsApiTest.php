<?php

namespace Tests\Feature;
use Tests\Traits\PrimaryKeyStringTrait;


use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\InstitutionRepeaterSurveyTableCells;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class InstitutionRepeaterSurveyTableCellsApiTest extends TestCase
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

    public function test_can_list_InstitutionRepeaterSurveyTableCells()
    {
        if (InstitutionRepeaterSurveyTableCells::count() === 0) {
            InstitutionRepeaterSurveyTableCells::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/institution-repeater-survey-table-cells');

        $response->assertStatus(200);
    }

    public function test_can_create_InstitutionRepeaterSurveyTableCells()
    {
        $record = InstitutionRepeaterSurveyTableCells::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/institution-repeater-survey-table-cells', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_InstitutionRepeaterSurveyTableCells()
    {
        $record = InstitutionRepeaterSurveyTableCells::factory()->create();
        $keyString = $this->getPrimaryKeyString($record);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/institution-repeater-survey-table-cells' . $keyString);

        $response->assertStatus(200);
    }


    public function test_can_update_InstitutionRepeaterSurveyTableCells()
    {
        $record = InstitutionRepeaterSurveyTableCells::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $keyString = $this->getPrimaryKeyString($record);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/institution-repeater-survey-table-cells' . $keyString, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_InstitutionRepeaterSurveyTableCells()
    {
        $record = InstitutionRepeaterSurveyTableCells::factory()->create();
        $keyString = $this->getPrimaryKeyString($record);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/institution-repeater-survey-table-cells' . $keyString);

        $response->assertStatus(204);
    }
}
