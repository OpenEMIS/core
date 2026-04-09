<?php

namespace Tests\Feature;

use App\Models\Api5\InstitutionRegistrations;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class InstitutionRegistrationsApiTest extends TestCase
{
    use DatabaseTransactions;
    use WithFaker;

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

    public function test_can_list_InstitutionRegistrations()
    {
        if (InstitutionRegistrations::count() === 0) {
            InstitutionRegistrations::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/institution-registrations');

        $response->assertStatus(200);
    }

    public function test_can_create_InstitutionRegistrations()
    {
        $record = InstitutionRegistrations::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/institution-registrations', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_InstitutionRegistrations()
    {
        $record = InstitutionRegistrations::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/institution-registrations/' . $record->id);

        $response->assertStatus(200);
    }

    public function test_can_update_InstitutionRegistrations()
    {
        $record = InstitutionRegistrations::factory()->create();
        $updatedData = [
            'id' => $record->id,
            'status' => 'expired',
            'decision_reference' => 'DEC-POCOR-9610-UPDATED',
        ];

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/institution-registrations/' . $record->id, $updatedData);

        $response->assertStatus(200);
        $this->assertDatabaseHas('institution_registrations', [
            'id' => $record->id,
            'status' => 'expired',
            'decision_reference' => 'DEC-POCOR-9610-UPDATED',
        ]);
    }

    public function test_can_delete_InstitutionRegistrations()
    {
        $record = InstitutionRegistrations::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/institution-registrations/' . $record->id);

        $response->assertStatus(204);
    }
}
