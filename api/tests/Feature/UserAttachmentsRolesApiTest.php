<?php

namespace Tests\Feature;
use Tests\Traits\PrimaryKeyStringTrait;


use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\UserAttachmentsRoles;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class UserAttachmentsRolesApiTest extends TestCase
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

    public function test_can_list_UserAttachmentsRoles()
    {
        if (UserAttachmentsRoles::count() === 0) {
            UserAttachmentsRoles::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/user-attachments-roles');

        $response->assertStatus(200);
    }

    public function test_can_create_UserAttachmentsRoles()
    {
        $record = UserAttachmentsRoles::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/user-attachments-roles', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_UserAttachmentsRoles()
    {
        $record = UserAttachmentsRoles::factory()->create();
        $keyString = $this->getPrimaryKeyString($record);
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/user-attachments-roles' . $keyString);

        $response->assertStatus(200);
    }


    public function test_can_update_UserAttachmentsRoles()
    {
        $record = UserAttachmentsRoles::factory()->create();
        $keyString = $this->getPrimaryKeyString($record);
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/user-attachments-roles' . $keyString, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_UserAttachmentsRoles()
    {
        $record = UserAttachmentsRoles::factory()->create();
        $keyString = $this->getPrimaryKeyString($record);
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/user-attachments-roles' . $keyString);

        $response->assertStatus(204);
    }
}
