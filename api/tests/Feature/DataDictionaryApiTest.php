<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\DataDictionary;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class DataDictionaryApiTest extends TestCase
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

    public function test_can_list_DataDictionary()
    {
        if (DataDictionary::count() === 0) {
            DataDictionary::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/data-dictionary');

        $response->assertStatus(200);
    }

    public function test_can_create_DataDictionary()
    {
        $record = DataDictionary::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/data-dictionary', $data);

        $response->assertStatus(201);
    }


    public function test_can_view_ByID_DataDictionary()
    {
        $record = DataDictionary::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/data-dictionary/' . $record->database_name);

        $response->assertStatus(405);
    }

    public function test_can_view_DataDictionary()
    {
        $record = DataDictionary::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/data-dictionary/' . 'database_name/' . $record->database_name . '/created/' . $record->created);

        $response->assertStatus(200);
    }

    public function test_can_update_DataDictionary()
    {
        $record = DataDictionary::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/data-dictionary/' . $record->database_name, $updatedData);

        $response->assertStatus(405);
    }

    public function test_can_delete_DataDictionary()
    {
        $record = DataDictionary::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/data-dictionary/' . $record->database_name);

        $response->assertStatus(405);
    }
}
