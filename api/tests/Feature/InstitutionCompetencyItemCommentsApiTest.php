<?php

namespace Tests\Feature;
use Tests\Traits\PrimaryKeyStringTrait;


use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\InstitutionCompetencyItemComments;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class InstitutionCompetencyItemCommentsApiTest extends TestCase
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

    public function test_can_list_InstitutionCompetencyItemComments()
    {
        if (InstitutionCompetencyItemComments::count() === 0) {
            InstitutionCompetencyItemComments::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/institution-competency-item-comments');

        $response->assertStatus(200);
    }

    public function test_can_create_InstitutionCompetencyItemComments()
    {
        $record = InstitutionCompetencyItemComments::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/institution-competency-item-comments', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_InstitutionCompetencyItemComments()
    {
        $record = InstitutionCompetencyItemComments::factory()->create();
        $keyString = $this->getPrimaryKeyString($record);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/institution-competency-item-comments' . $keyString);

        $response->assertStatus(200);
    }


    public function test_can_update_InstitutionCompetencyItemComments()
    {
        $record = InstitutionCompetencyItemComments::factory()->create();
        $keyString = $this->getPrimaryKeyString($record);

        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/institution-competency-item-comments' . $keyString, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_InstitutionCompetencyItemComments()
    {
        $record = InstitutionCompetencyItemComments::factory()->create();
        $keyString = $this->getPrimaryKeyString($record);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/institution-competency-item-comments' . $keyString);

        $response->assertStatus(204);
    }
}
