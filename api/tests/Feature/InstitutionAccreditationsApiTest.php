<?php

namespace Tests\Feature;

use App\Models\Api5\InstitutionAccreditations;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class InstitutionAccreditationsApiTest extends TestCase
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

    public function test_can_list_InstitutionAccreditations()
    {
        if (InstitutionAccreditations::count() === 0) {
            InstitutionAccreditations::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/institution-accreditations');

        $response->assertStatus(200);
    }

    public function test_can_create_InstitutionAccreditations()
    {
        $record = InstitutionAccreditations::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/institution-accreditations', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_InstitutionAccreditations()
    {
        $record = InstitutionAccreditations::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/institution-accreditations/' . $record->id);

        $response->assertStatus(200);
    }

    public function test_can_update_InstitutionAccreditations()
    {
        $record = InstitutionAccreditations::factory()->create();
        $updatedData = [
            'id' => $record->id,
            'status' => 'revoked',
            'qualification_level' => 'Advanced Diploma',
        ];

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/institution-accreditations/' . $record->id, $updatedData);

        $response->assertStatus(200);
        $this->assertDatabaseHas('institution_accreditations', [
            'id' => $record->id,
            'status' => 'revoked',
            'qualification_level' => 'Advanced Diploma',
        ]);
    }

    public function test_can_delete_InstitutionAccreditations()
    {
        $record = InstitutionAccreditations::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/institution-accreditations/' . $record->id);

        $response->assertStatus(204);
    }
}
