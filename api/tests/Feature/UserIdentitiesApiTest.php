<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Models\Api5\UserIdentities;
use App\Models\Api5\SecurityUsers as TestSecurityUser;
use Tymon\JWTAuth\Facades\JWTAuth;
use Carbon\Carbon;

class UserIdentitiesApiTest extends TestCase
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

    public function test_can_list_UserIdentities()
    {
        if (UserIdentities::count() === 0) {
            UserIdentities::factory()->count(1)->create();
        }

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/user-identities');

        $response->assertStatus(200);
    }

    public function test_can_create_UserIdentities()
    {
        $record = UserIdentities::factory()->make();
        $data = $record->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/user-identities', $data);

        $response->assertStatus(201);
    }

    public function test_can_view_UserIdentities()
    {
        $record = UserIdentities::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/user-identities/' . $record->id);

        $response->assertStatus(200);
    }


    public function test_can_update_UserIdentities()
    {
        $record = UserIdentities::factory()->create();
        $updatedData = [
            'id' => $record->id,
            // Add at least one field from schema to update
        ];
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/user-identities/' . $record->id, $updatedData);

        $response->assertStatus(200);
    }

    public function test_can_delete_UserIdentities()
    {
        $record = UserIdentities::factory()->create();
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->deleteJson('/api/v5/user-identities/' . $record->id);

        $response->assertStatus(204);
    }

    /**
     * POCOR-9590 — number/identity_type_id on a row whose identity_type is registered as
     * an external-source lookup key (NIN for Seychelles in this test) must be immutable.
     * The endpoint still returns 200 (silent strip, no fingerprinting) but the row must not
     * have changed in the database.
     */
    public function test_external_lookup_identity_number_is_locked_against_update()
    {
        $identityTypeId = (int) \DB::table('identity_types')->where('name', 'LIKE', '%NIN%')->value('id');
        if (!$identityTypeId) {
            $this->markTestSkipped('No NIN identity_type in the test DB.');
            return;
        }

        $attrId = \DB::table('external_data_source_attributes')->insertGetId([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'external_data_source_type' => 'POCOR-9590-Test-Source',
            'attribute_field' => 'identity_type_id',
            'attribute_name'  => 'identity_type_id',
            'value' => (string) $identityTypeId,
            'created_user_id' => 1,
            'created' => Carbon::now(),
        ]);

        $original = '9700000001';
        $row = UserIdentities::factory()->create([
            'identity_type_id' => $identityTypeId,
            'number' => $original,
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/user-identities/' . $row->id, [
            'id' => $row->id,
            'number' => '9700000002',
        ]);

        $response->assertStatus(200);
        $this->assertSame(
            $original,
            \DB::table('user_identities')->where('id', $row->id)->value('number'),
            'external-lookup identity number must not be updatable via v5'
        );
    }

    /**
     * POCOR-9590 — control case: identity_type that is NOT an external lookup key remains
     * editable. Proves the lock is targeted, not a blanket block.
     */
    public function test_non_external_lookup_identity_number_still_updates()
    {
        $identityTypeId = (int) \DB::table('identity_types')->where('name', 'Passport')->value('id');
        if (!$identityTypeId) {
            $this->markTestSkipped('No Passport identity_type in the test DB.');
            return;
        }

        \DB::table('external_data_source_attributes')
            ->where('attribute_field', 'identity_type_id')
            ->where('value', (string) $identityTypeId)
            ->delete();

        $row = UserIdentities::factory()->create([
            'identity_type_id' => $identityTypeId,
            'number' => 'P-OLD-0001',
        ]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/user-identities/' . $row->id, [
            'id' => $row->id,
            'number' => 'P-NEW-0002',
        ]);

        $response->assertStatus(200);
        $this->assertSame(
            'P-NEW-0002',
            \DB::table('user_identities')->where('id', $row->id)->value('number'),
            'non-external-lookup identity number must remain editable'
        );
    }
}
