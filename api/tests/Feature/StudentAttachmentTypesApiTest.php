<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\StudentAttachmentTypes;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class StudentAttachmentTypesApiTest extends TestCase
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

    public function test_can_list_StudentAttachmentTypes()
    {
        if (StudentAttachmentTypes::count() === 0) {
            StudentAttachmentTypes::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/student-attachment-types');

        $response->assertStatus(200);
    }

    public function test_can_create_StudentAttachmentTypes()
    {
        $record = StudentAttachmentTypes::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/student-attachment-types', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_StudentAttachmentTypes()
    {
        $record = StudentAttachmentTypes::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/student-attachment-types/' . $record->id);

        $response->assertStatus(200);
    }


    public function test_can_update_StudentAttachmentTypes()
    {
        $record = StudentAttachmentTypes::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/student-attachment-types/' . $record->id, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_StudentAttachmentTypes()
    {
        $record = StudentAttachmentTypes::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/student-attachment-types/' . $record->id);

        $response->assertStatus(204);
    }
}
