<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\InstitutionCustomFormsFields;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class InstitutionCustomFormsFieldsApiTest extends TestCase
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

    public function test_can_list_InstitutionCustomFormsFields()
    {
        if (InstitutionCustomFormsFields::count() === 0) {
            InstitutionCustomFormsFields::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/institution-custom-forms-fields');

        $response->assertStatus(200);
    }

    public function test_can_create_InstitutionCustomFormsFields()
    {
        $record = InstitutionCustomFormsFields::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/institution-custom-forms-fields', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_InstitutionCustomFormsFields()
    {
        $record = InstitutionCustomFormsFields::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/institution-custom-forms-fields/' . $record->id);

        $response->assertStatus(200);
    }


    public function test_can_update_InstitutionCustomFormsFields()
    {
        $record = InstitutionCustomFormsFields::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/institution-custom-forms-fields/' . $record->id, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_InstitutionCustomFormsFields()
    {
        $record = InstitutionCustomFormsFields::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/institution-custom-forms-fields/' . $record->id);

        $response->assertStatus(204);
    }
}
