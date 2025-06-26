<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\SurveyTableRows;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class SurveyTableRowsApiTest extends TestCase
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

    public function test_can_list_SurveyTableRows()
    {
        if (SurveyTableRows::count() === 0) {
            SurveyTableRows::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/survey-table-rows');

        $response->assertStatus(200);
    }

    public function test_can_create_SurveyTableRows()
    {
        $record = SurveyTableRows::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/survey-table-rows', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_SurveyTableRows()
    {
        $record = SurveyTableRows::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/survey-table-rows/' . $record->id);

        $response->assertStatus(200);
    }


    public function test_can_update_SurveyTableRows()
    {
        $record = SurveyTableRows::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/survey-table-rows/' . $record->id, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_SurveyTableRows()
    {
        $record = SurveyTableRows::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/survey-table-rows/' . $record->id);

        $response->assertStatus(204);
    }
}
