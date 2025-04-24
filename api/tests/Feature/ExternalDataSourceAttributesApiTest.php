<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\ExternalDataSourceAttributes;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class ExternalDataSourceAttributesApiTest extends TestCase
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

    public function test_can_list_ExternalDataSourceAttributes()
    {
        if (ExternalDataSourceAttributes::count() === 0) {
            ExternalDataSourceAttributes::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/external-data-source-attributes');

        $response->assertStatus(200);
    }

    public function test_can_create_ExternalDataSourceAttributes()
    {
        $record = ExternalDataSourceAttributes::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/external-data-source-attributes', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_ExternalDataSourceAttributes()
    {
        $record = ExternalDataSourceAttributes::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/external-data-source-attributes/' . $record->id);

        $response->assertStatus(200);
    }


    public function test_can_update_ExternalDataSourceAttributes()
    {
        $record = ExternalDataSourceAttributes::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/external-data-source-attributes/' . $record->id, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_ExternalDataSourceAttributes()
    {
        $record = ExternalDataSourceAttributes::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/external-data-source-attributes/' . $record->id);

        $response->assertStatus(204);
    }
}
