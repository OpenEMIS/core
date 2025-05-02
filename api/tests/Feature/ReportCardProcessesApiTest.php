<?php

namespace Tests\Feature;
use Tests\Traits\PrimaryKeyStringTrait;


use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\ReportCardProcesses;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class ReportCardProcessesApiTest extends TestCase
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

    public function test_can_list_ReportCardProcesses()
    {
        if (ReportCardProcesses::count() === 0) {
            ReportCardProcesses::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/report-card-processes');

        $response->assertStatus(200);
    }

    public function test_can_create_ReportCardProcesses()
    {
        $record = ReportCardProcesses::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/report-card-processes', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_ReportCardProcesses()
    {
        $record = ReportCardProcesses::factory()->create();

        $keyString = $this->getPrimaryKeyString($record);
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/report-card-processes' . $keyString);

        $response->assertStatus(200);
    }


    public function test_can_update_ReportCardProcesses()
    {
        $record = ReportCardProcesses::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];

        $keyString = $this->getPrimaryKeyString($record);
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/report-card-processes' . $keyString, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_ReportCardProcesses()
    {
        $record = ReportCardProcesses::factory()->create();

        $keyString = $this->getPrimaryKeyString($record);
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/report-card-processes' . $keyString);

        $response->assertStatus(204);
    }
}
