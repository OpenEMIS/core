<?php

namespace Tests\Feature;
use Tests\Traits\PrimaryKeyStringTrait;


use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\ExaminationCentreRoomsExaminationsInvigilators;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class ExaminationCentreRoomsExaminationsInvigilatorsApiTest extends TestCase
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

    public function test_can_list_ExaminationCentreRoomsExaminationsInvigilators()
    {
        if (ExaminationCentreRoomsExaminationsInvigilators::count() === 0) {
            ExaminationCentreRoomsExaminationsInvigilators::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/examination-centre-rooms-examinations-invigilators');

        $response->assertStatus(200);
    }

    public function test_can_create_ExaminationCentreRoomsExaminationsInvigilators()
    {
        $record = ExaminationCentreRoomsExaminationsInvigilators::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/examination-centre-rooms-examinations-invigilators', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_ExaminationCentreRoomsExaminationsInvigilators()
    {
        $record = ExaminationCentreRoomsExaminationsInvigilators::factory()->create();
        $keyString = $this->getPrimaryKeyString($record);
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/examination-centre-rooms-examinations-invigilators' . $keyString);

        $response->assertStatus(200);
    }


    public function test_can_update_ExaminationCentreRoomsExaminationsInvigilators()
    {
        $record = ExaminationCentreRoomsExaminationsInvigilators::factory()->create();
        $keyString = $this->getPrimaryKeyString($record);
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/examination-centre-rooms-examinations-invigilators' . $keyString, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_ExaminationCentreRoomsExaminationsInvigilators()
    {
        $record = ExaminationCentreRoomsExaminationsInvigilators::factory()->create();
        $keyString = $this->getPrimaryKeyString($record);
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/examination-centre-rooms-examinations-invigilators' . $keyString);

        $response->assertStatus(204);
    }
}
