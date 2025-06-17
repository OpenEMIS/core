<?php

namespace Tests\Feature;
use Tests\Traits\PrimaryKeyStringTrait;


use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\AppraisalFormsCriteriasScores;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class AppraisalFormsCriteriasScoresApiTest extends TestCase
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

    public function test_can_list_AppraisalFormsCriteriasScores()
    {
        if (AppraisalFormsCriteriasScores::count() === 0) {
            AppraisalFormsCriteriasScores::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/appraisal-forms-criterias-scores');

        $response->assertStatus(200);
    }

    public function test_can_create_AppraisalFormsCriteriasScores()
    {
        $record = AppraisalFormsCriteriasScores::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/appraisal-forms-criterias-scores', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_AppraisalFormsCriteriasScores()
    {
        $record = AppraisalFormsCriteriasScores::factory()->create();
        $keyString = $this->getPrimaryKeyString($record);
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/appraisal-forms-criterias-scores' . $keyString);

        $response->assertStatus(200);
    }


    public function test_can_update_AppraisalFormsCriteriasScores()
    {
        $record = AppraisalFormsCriteriasScores::factory()->create();
        $keyString = $this->getPrimaryKeyString($record);
        $updatedData = [
            'appraisal_form_id' => $record->appraisal_form_id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/appraisal-forms-criterias-scores' . $keyString, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_AppraisalFormsCriteriasScores()
    {
        $record = AppraisalFormsCriteriasScores::factory()->create();
        $keyString = $this->getPrimaryKeyString($record);
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/appraisal-forms-criterias-scores' . $keyString);

        $response->assertStatus(204);
    }
}
