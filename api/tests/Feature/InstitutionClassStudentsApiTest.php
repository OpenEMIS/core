<?php

namespace Tests\Feature;

use Tests\Traits\PrimaryKeyStringTrait;
use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\InstitutionClassStudents;
use App\Models\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;

class InstitutionClassStudentsApiTest extends TestCase
{
    use PrimaryKeyStringTrait;
    use DatabaseTransactions, WithFaker;

    protected $token;
    protected $username;

    protected function setUp(): void
    {
        parent::setUp();

        // Get the username from the environment variable or use a default admin username
        $this->username = env('TEST_USERNAME', 'admin');

        // Check if cache reset is enabled
        $resetCache = env('RESET_CACHE', false);

        if ($resetCache) {
            // Clear the cache
            \Cache::flush();
//            Log::info("Cache cleared.");
        }

        $user = $this->getUserByUsername($this->username);
//        Log::info("User: " . print_r($user->id, true));
        if (!$user) {
            $this->markTestSkipped("User with username {$this->username} not found.");
            return;
        }
        $this->token = JWTAuth::fromUser($user);
//        Log::info("Token: " . print_r($this->token, true));
    }

    private function getUserByUsername($username)
    {
        return TestSecurityUser::where('username', $username)->first();
    }

    public function test_can_list_InstitutionClassStudents()
    {
        if (InstitutionClassStudents::count() === 0) {
            InstitutionClassStudents::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/institution-class-students');

        $response->assertStatus(200);
    }

    public function test_can_create_InstitutionClassStudents()
    {
        $record = InstitutionClassStudents::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/institution-class-students', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_InstitutionClassStudents()
    {
        $record = InstitutionClassStudents::factory()->create();

        $keyString = $this->getPrimaryKeyString($record);
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/institution-class-students' . $keyString);

        $response->assertStatus(200);
    }


    public function test_can_update_InstitutionClassStudents()
    {
        $record = InstitutionClassStudents::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];

        $keyString = $this->getPrimaryKeyString($record);
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/institution-class-students' . $keyString, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_InstitutionClassStudents()
    {
        $record = InstitutionClassStudents::factory()->create();

        $keyString = $this->getPrimaryKeyString($record);
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/institution-class-students' . $keyString);

        $response->assertStatus(204);
    }
}
