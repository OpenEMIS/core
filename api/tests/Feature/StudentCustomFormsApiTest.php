<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\StudentCustomForms;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class StudentCustomFormsApiTest extends TestCase
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

    public function test_can_list_StudentCustomForms()
    {
        if (StudentCustomForms::count() === 0) {
            StudentCustomForms::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/student-custom-forms');

        $response->assertStatus(200);
    }

    public function test_can_create_StudentCustomForms()
    {
        $record = StudentCustomForms::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/student-custom-forms', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_StudentCustomForms()
    {
        $record = StudentCustomForms::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/student-custom-forms/' . $record->id);

        $response->assertStatus(200);
    }


    public function test_can_update_StudentCustomForms()
    {
        $record = StudentCustomForms::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/student-custom-forms/' . $record->id, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_StudentCustomForms()
    {
        $record = StudentCustomForms::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/student-custom-forms/' . $record->id);

        $response->assertStatus(204);
    }
}
