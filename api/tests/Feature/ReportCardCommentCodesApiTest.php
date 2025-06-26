<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\ReportCardCommentCodes;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class ReportCardCommentCodesApiTest extends TestCase
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

    public function test_can_list_ReportCardCommentCodes()
    {
        if (ReportCardCommentCodes::count() === 0) {
            ReportCardCommentCodes::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/report-card-comment-codes');

        $response->assertStatus(200);
    }

    public function test_can_create_ReportCardCommentCodes()
    {
        $record = ReportCardCommentCodes::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/report-card-comment-codes', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_ReportCardCommentCodes()
    {
        $record = ReportCardCommentCodes::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/report-card-comment-codes/' . $record->id);

        $response->assertStatus(200);
    }


    public function test_can_update_ReportCardCommentCodes()
    {
        $record = ReportCardCommentCodes::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/report-card-comment-codes/' . $record->id, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_ReportCardCommentCodes()
    {
        $record = ReportCardCommentCodes::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/report-card-comment-codes/' . $record->id);

        $response->assertStatus(204);
    }
}
