<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\SummaryInstitutionNationalities;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class SummaryInstitutionNationalitiesApiTest extends TestCase
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

    public function test_can_list_SummaryInstitutionNationalities()
    {
        if (SummaryInstitutionNationalities::count() === 0) {
            SummaryInstitutionNationalities::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/summary-institution-nationalities');

        $response->assertStatus(200);
    }

    public function test_can_create_SummaryInstitutionNationalities()
    {
        $record = SummaryInstitutionNationalities::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/summary-institution-nationalities', $data);

        $response->assertStatus(201);
    }


    public function test_can_view_ByID_SummaryInstitutionNationalities()
    {
        $record = SummaryInstitutionNationalities::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/summary-institution-nationalities/' . $record->academic_period_id);

        $response->assertStatus(405);
    }

    public function test_can_view_SummaryInstitutionNationalities()
    {
        $record = SummaryInstitutionNationalities::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/summary-institution-nationalities/' . 'academic_period_id/' . $record->academic_period_id . '/created/' . $record->created);

        $response->assertStatus(200);
    }

    public function test_can_update_SummaryInstitutionNationalities()
    {
        $record = SummaryInstitutionNationalities::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/summary-institution-nationalities/' . $record->academic_period_id, $updatedData);

        $response->assertStatus(405);
    }

    public function test_can_delete_SummaryInstitutionNationalities()
    {
        $record = SummaryInstitutionNationalities::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/summary-institution-nationalities/' . $record->academic_period_id);

        $response->assertStatus(405);
    }
}
