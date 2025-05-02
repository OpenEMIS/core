<?php

namespace Tests\Feature;
use Tests\Traits\PrimaryKeyStringTrait;


use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\AssessmentItemsGradingTypes;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class AssessmentItemsGradingTypesApiTest extends TestCase
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

    public function test_can_list_AssessmentItemsGradingTypes()
    {
        if (AssessmentItemsGradingTypes::count() === 0) {
            AssessmentItemsGradingTypes::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/assessment-items-grading-types');

        $response->assertStatus(200);
    }

    public function test_can_create_AssessmentItemsGradingTypes()
    {
        $record = AssessmentItemsGradingTypes::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/assessment-items-grading-types', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_AssessmentItemsGradingTypes()
    {
        $record = AssessmentItemsGradingTypes::factory()->create();
        $keyString = $this->getPrimaryKeyString($record);
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/assessment-items-grading-types' . $keyString);

        $response->assertStatus(200);
    }


    public function test_can_update_AssessmentItemsGradingTypes()
    {
        $record = AssessmentItemsGradingTypes::factory()->create();
        $keyString = $this->getPrimaryKeyString($record);
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/assessment-items-grading-types' . $keyString, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_AssessmentItemsGradingTypes()
    {
        $record = AssessmentItemsGradingTypes::factory()->create();
        $keyString = $this->getPrimaryKeyString($record);
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/assessment-items-grading-types' . $keyString);

        $response->assertStatus(204);
    }
}
