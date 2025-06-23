<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\SummaryIscedSectors;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class SummaryIscedSectorsApiTest extends TestCase
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

    public function test_can_list_SummaryIscedSectors()
    {
        if (SummaryIscedSectors::count() === 0) {
            SummaryIscedSectors::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/summary-isced-sectors');

        $response->assertStatus(200);
    }

    public function test_can_create_SummaryIscedSectors()
    {
        $record = SummaryIscedSectors::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/summary-isced-sectors', $data);

        $response->assertStatus(201);
    }


    public function test_can_view_ByID_SummaryIscedSectors()
    {
        $record = SummaryIscedSectors::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/summary-isced-sectors/' . $record->academic_period_id);

        $response->assertStatus(405);
    }

    public function test_can_view_SummaryIscedSectors()
    {
        $record = SummaryIscedSectors::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/summary-isced-sectors/' . 'academic_period_id/' . $record->academic_period_id . '/created/' . $record->created);

        $response->assertStatus(200);
    }

    public function test_can_update_SummaryIscedSectors()
    {
        $record = SummaryIscedSectors::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/summary-isced-sectors/' . $record->academic_period_id, $updatedData);

        $response->assertStatus(405);
    }

    public function test_can_delete_SummaryIscedSectors()
    {
        $record = SummaryIscedSectors::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/summary-isced-sectors/' . $record->academic_period_id);

        $response->assertStatus(405);
    }
}
