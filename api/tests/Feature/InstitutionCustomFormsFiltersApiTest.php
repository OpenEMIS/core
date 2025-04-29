<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\InstitutionCustomFormsFilters;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class InstitutionCustomFormsFiltersApiTest extends TestCase
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

    public function test_can_list_InstitutionCustomFormsFilters()
    {
        if (InstitutionCustomFormsFilters::count() === 0) {
            InstitutionCustomFormsFilters::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/institution-custom-forms-filters');

        $response->assertStatus(200);
    }

    public function test_can_create_InstitutionCustomFormsFilters()
    {
        $record = InstitutionCustomFormsFilters::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/institution-custom-forms-filters', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_InstitutionCustomFormsFilters()
    {
        $record = InstitutionCustomFormsFilters::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/institution-custom-forms-filters/' . $record->id);

        $response->assertStatus(200);
    }


    public function test_can_update_InstitutionCustomFormsFilters()
    {
        $record = InstitutionCustomFormsFilters::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/institution-custom-forms-filters/' . $record->id, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_InstitutionCustomFormsFilters()
    {
        $record = InstitutionCustomFormsFilters::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/institution-custom-forms-filters/' . $record->id);

        $response->assertStatus(204);
    }
}
