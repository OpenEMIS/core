<?php

namespace Tests\Feature;
use Tests\Traits\PrimaryKeyStringTrait;


use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\ExaminationCentresExaminationsInstitutions;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class ExaminationCentresExaminationsInstitutionsApiTest extends TestCase
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

    public function test_can_list_ExaminationCentresExaminationsInstitutions()
    {
        if (ExaminationCentresExaminationsInstitutions::count() === 0) {
            ExaminationCentresExaminationsInstitutions::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/examination-centres-examinations-institutions');

        $response->assertStatus(200);
    }

    public function test_can_create_ExaminationCentresExaminationsInstitutions()
    {
        $record = ExaminationCentresExaminationsInstitutions::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/examination-centres-examinations-institutions', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_ExaminationCentresExaminationsInstitutions()
    {
        $record = ExaminationCentresExaminationsInstitutions::factory()->create();
        $keyString = $this->getPrimaryKeyString($record);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",

        ])->getJson('/api/v5/examination-centres-examinations-institutions' . $keyString);

        $response->assertStatus(200);
    }


    public function test_can_update_ExaminationCentresExaminationsInstitutions()
    {
        $record = ExaminationCentresExaminationsInstitutions::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $keyString = $this->getPrimaryKeyString($record);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/examination-centres-examinations-institutions' . $keyString, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_ExaminationCentresExaminationsInstitutions()
    {
        $record = ExaminationCentresExaminationsInstitutions::factory()->create();
        $keyString = $this->getPrimaryKeyString($record);
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/examination-centres-examinations-institutions' . $keyString);

        $response->assertStatus(204);
    }
}
