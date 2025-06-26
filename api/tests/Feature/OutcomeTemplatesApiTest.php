<?php

namespace Tests\Feature;
use Tests\Traits\PrimaryKeyStringTrait;


use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\OutcomeTemplates;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class OutcomeTemplatesApiTest extends TestCase
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

    public function test_can_list_OutcomeTemplates()
    {
        if (OutcomeTemplates::count() === 0) {
            OutcomeTemplates::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/outcome-templates');

        $response->assertStatus(200);
    }

    public function test_can_create_OutcomeTemplates()
    {
        $record = OutcomeTemplates::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/outcome-templates', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_OutcomeTemplates()
    {
        $record = OutcomeTemplates::factory()->create();

        $keyString = $this->getPrimaryKeyString($record);
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/outcome-templates' . $keyString);

        $response->assertStatus(200);
    }


    public function test_can_update_OutcomeTemplates()
    {
        $record = OutcomeTemplates::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];

        $keyString = $this->getPrimaryKeyString($record);
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/outcome-templates' . $keyString, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_OutcomeTemplates()
    {
        $record = OutcomeTemplates::factory()->create();

        $keyString = $this->getPrimaryKeyString($record);
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/outcome-templates' . $keyString);

        $response->assertStatus(204);
    }
}
