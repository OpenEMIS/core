<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\SummaryProgrammeSectorGenders;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class SummaryProgrammeSectorGendersApiTest extends TestCase
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

    public function test_can_list_SummaryProgrammeSectorGenders()
    {
        if (SummaryProgrammeSectorGenders::count() === 0) {
            SummaryProgrammeSectorGenders::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/summary-programme-sector-genders');

        $response->assertStatus(200);
    }

    public function test_can_create_SummaryProgrammeSectorGenders()
    {
        $record = SummaryProgrammeSectorGenders::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/summary-programme-sector-genders', $data);

        $response->assertStatus(201);
    }


    public function test_can_view_ByID_SummaryProgrammeSectorGenders()
    {
        $record = SummaryProgrammeSectorGenders::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/summary-programme-sector-genders/' . $record->academic_period_id);

        $response->assertStatus(405);
    }

    public function test_can_view_SummaryProgrammeSectorGenders()
    {
        $record = SummaryProgrammeSectorGenders::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/summary-programme-sector-genders/' . 'academic_period_id/' . $record->academic_period_id . '/created/' . $record->created);

        $response->assertStatus(200);
    }

    public function test_can_update_SummaryProgrammeSectorGenders()
    {
        $record = SummaryProgrammeSectorGenders::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/summary-programme-sector-genders/' . $record->academic_period_id, $updatedData);

        $response->assertStatus(405);
    }

    public function test_can_delete_SummaryProgrammeSectorGenders()
    {
        $record = SummaryProgrammeSectorGenders::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/summary-programme-sector-genders/' . $record->academic_period_id);

        $response->assertStatus(405);
    }
}
