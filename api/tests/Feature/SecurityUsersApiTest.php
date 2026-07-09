<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\SecurityUsers;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class SecurityUsersApiTest extends TestCase
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

    public function test_can_list_SecurityUsers()
    {
        if (SecurityUsers::count() === 0) {
            SecurityUsers::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/security-users');

        $response->assertStatus(200);
    }

    public function test_can_create_SecurityUsers()
    {
        $record = SecurityUsers::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/security-users', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_SecurityUsers()
    {
        $record = SecurityUsers::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/security-users/' . $record->id);

        $response->assertStatus(200);
    }


    public function test_can_update_SecurityUsers()
    {
        $record = SecurityUsers::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/security-users/' . $record->id, $updatedData);

        $response->assertStatus(200);
    }

    //POCOR-9697: hard-delete of security_users is schema-blocked — the audit
    //trail `user_activities.security_user_id` FK is ON DELETE RESTRICT, and
    //UserActivityLog writes one row per create/edit. The CRUD endpoint must
    //surface this as 403, not 204. Production uses soft-delete via `status`.
    public function test_delete_SecurityUsers_blocked_by_audit_trail()
    {
        $record = SecurityUsers::factory()->create(); //POCOR-9697: factory create writes a user_activities row
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/security-users/' . $record->id);

        $response->assertStatus(403);
    }

    //POCOR-9590: drift detection — Synced user edited in any General field flips to Not Synced
    public function test_sync_status_drifts_from_1_to_2_on_general_field_edit()
    {
        $record = SecurityUsers::factory()->create([
            'sync_status' => SecurityUsers::SYNC_STATUS_SYNCED,
            'external_reference' => null, //POCOR-9590: prevent inception-sync from overriding status
            'first_name' => 'OldName',
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/security-users/' . $record->id, [
            'id' => $record->id,
            'first_name' => 'NewName',
        ]);

        $response->assertStatus(200);
        $this->assertSame(SecurityUsers::SYNC_STATUS_DRIFTED, (int)$record->fresh()->sync_status);
    }

    //POCOR-9590: Local user editing themselves stays Local
    public function test_sync_status_stays_0_when_local_user_edited()
    {
        $record = SecurityUsers::factory()->create([
            'sync_status' => SecurityUsers::SYNC_STATUS_LOCAL,
            'external_reference' => null, //POCOR-9590: prevent inception-sync from overriding status
            'first_name' => 'OldName',
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/security-users/' . $record->id, [
            'id' => $record->id,
            'first_name' => 'NewName',
        ]);

        $response->assertStatus(200);
        $this->assertSame(SecurityUsers::SYNC_STATUS_LOCAL, (int)$record->fresh()->sync_status);
    }

    //POCOR-9590: Not Synced (drifted) stays Not Synced on further edits
    public function test_sync_status_stays_2_when_not_synced_user_edited()
    {
        $record = SecurityUsers::factory()->create([
            'sync_status' => SecurityUsers::SYNC_STATUS_DRIFTED,
            'external_reference' => null, //POCOR-9590: prevent inception-sync from overriding status to 1
            'first_name' => 'OldName',
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/security-users/' . $record->id, [
            'id' => $record->id,
            'first_name' => 'NewName',
        ]);

        $response->assertStatus(200);
        $this->assertSame(SecurityUsers::SYNC_STATUS_DRIFTED, (int)$record->fresh()->sync_status);
    }

    //POCOR-9590: Synced user edited in a non-General field (e.g. email) keeps status at 1
    public function test_sync_status_stays_1_when_non_general_field_edited()
    {
        $record = SecurityUsers::factory()->create([
            'sync_status' => SecurityUsers::SYNC_STATUS_SYNCED,
            'external_reference' => null, //POCOR-9590: prevent inception-sync from overriding status
            'email' => 'old@example.com',
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/security-users/' . $record->id, [
            'id' => $record->id,
            'email' => 'new@example.com',
        ]);

        $response->assertStatus(200);
        $this->assertSame(SecurityUsers::SYNC_STATUS_SYNCED, (int)$record->fresh()->sync_status);
    }

    //POCOR-9590: inception sync — new user created with external_reference starts at SYNCED
    public function test_sync_status_set_to_1_on_create_with_external_reference()
    {
        $record = SecurityUsers::factory()->make([
            'external_reference' => 'EXT-' . $this->faker->uuid,
            'sync_status' => SecurityUsers::SYNC_STATUS_LOCAL, //POCOR-9590: saving event overrides to SYNCED
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/security-users', $record->toArray());

        $response->assertStatus(201);
        $created = SecurityUsers::where('external_reference', $record->external_reference)->first();
        $this->assertNotNull($created);
        $this->assertSame(SecurityUsers::SYNC_STATUS_SYNCED, (int)$created->sync_status);
    }
}
