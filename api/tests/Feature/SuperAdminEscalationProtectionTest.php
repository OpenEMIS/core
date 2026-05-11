<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\Api5\SecurityUsers;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * POCOR-9697 — Regression tests for the super_admin mass-assignment escalation.
 *
 * Before this ticket, any authenticated API caller could become super_admin
 * (or promote anyone else) by sending `super_admin: 1` to the v4 or v5 user
 * write endpoints. This file pins down the three layers of the fix:
 *
 *   1. v4 UsersAddRequest rejects super_admin outright (422).
 *   2. v4 UserRepository never copies super_admin into the DB write.
 *   3. v5 SecurityUsers models drop super_admin from $fillable, so it cannot
 *      slip through mass-assignment on POST/PUT against /api/v5/security-users.
 *
 * And, as a paired bonus, that plaintext passwords sent to either endpoint
 * land in security_users.password as a bcrypt hash, not as cleartext.
 */
class SuperAdminEscalationProtectionTest extends TestCase
{
    use DatabaseTransactions;

    protected $token;

    protected function setUp(): void
    {
        parent::setUp();

        // SecurityUsers id=2 is the shared test JWT subject across the suite.
        $user = SecurityUsers::where('id', 2)->first();
        if (!$user) {
            $this->markTestSkipped('Seeded test user id=2 not found.');
            return;
        }
        $this->token = JWTAuth::fromUser($user);
    }

    /**
     * Layer 1: prohibited rule on UsersAddRequest. Sending super_admin
     * in the body must fail validation with 422 — no DB row written.
     */
    public function test_v4_addUsers_rejects_super_admin_with_422(): void
    {
        $username = 'pocor9697_v4_prohibit_' . uniqid();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v4/users', [
            'first_name'    => 'Esc',
            'last_name'     => 'Alation',
            'gender_id'     => 1,
            'date_of_birth' => '2000-01-01',
            'username'      => $username,
            'password'      => 'someplain',
            'super_admin'   => 1,
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseMissing('security_users', ['username' => $username]);
    }

    /**
     * Layer 2: even if the validation rule were dropped, UserRepository
     * must not copy super_admin into the row it inserts. We bypass the
     * FormRequest by going straight at the repository with raw params.
     */
    public function test_v4_user_repository_drops_super_admin_on_create(): void
    {
        $username = 'pocor9697_v4_drop_' . uniqid();

        // Authenticate so JWTAuth::user() inside setUserData works.
        $user = SecurityUsers::where('id', 2)->first();
        JWTAuth::setToken($this->token);
        JWTAuth::authenticate();

        $repo = app(\App\Repositories\UserRepository::class);
        $payload = [
            'first_name'    => 'Drop',
            'last_name'     => 'Super',
            'gender_id'     => 1,
            'date_of_birth' => '2000-01-01',
            'username'      => $username,
            'password'      => 'someplain',
            'super_admin'   => 1,
        ];

        $userArr = $repo->setUserData($payload);
        $this->assertArrayNotHasKey(
            'super_admin',
            $userArr,
            'UserRepository::setUserData must never copy super_admin from input.'
        );
    }

    /**
     * Layer 3a: v5 create endpoint must drop super_admin via fillable.
     * Caller has v5 SecurityUsers permission (user id=2), so the request
     * itself succeeds — but the stored row must still have super_admin = 0.
     */
    public function test_v5_security_users_create_strips_super_admin(): void
    {
        $username = 'pocor9697_v5_create_' . uniqid();

        $payload = SecurityUsers::factory()->make([
            'username'    => $username,
            'super_admin' => 1,
        ])->toArray();
        // Inject super_admin explicitly in case factory drops non-fillable.
        $payload['super_admin'] = 1;

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/security-users', $payload);

        $this->assertContains(
            $response->getStatusCode(),
            [200, 201],
            'Expected v5 create to succeed for an authorised caller.'
        );

        $row = DB::table('security_users')->where('username', $username)->first();
        $this->assertNotNull($row, 'Created row not found.');
        $this->assertSame(0, (int) $row->super_admin,
            'v5 must never persist super_admin from the request body.');
    }

    /**
     * Layer 3b: v5 update must not promote an existing user.
     */
    public function test_v5_security_users_update_does_not_promote(): void
    {
        $target = SecurityUsers::factory()->create(['super_admin' => 0]);

        // Factory's super_admin=0 may be silently dropped (it's no longer
        // fillable). Force it explicitly so the precondition is real.
        DB::table('security_users')->where('id', $target->id)->update(['super_admin' => 0]);

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/security-users/' . $target->id, [
            'id'          => $target->id,
            'super_admin' => 1,
        ]);

        $this->assertContains($response->getStatusCode(), [200, 204]);

        $stored = DB::table('security_users')->where('id', $target->id)->value('super_admin');
        $this->assertSame(0, (int) $stored,
            'v5 update must never elevate an existing user to super_admin.');
    }

    /**
     * Bonus: plaintext passwords sent to v4 must land hashed in DB.
     */
    public function test_v4_addUsers_hashes_plaintext_password(): void
    {
        $username = 'pocor9697_v4_hash_' . uniqid();
        $plaintext = 'p0cor9697-plain';

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v4/users', [
            'first_name'    => 'Hash',
            'last_name'     => 'Me',
            'gender_id'     => 1,
            'date_of_birth' => '2000-01-01',
            'username'      => $username,
            'password'      => $plaintext,
        ]);

        $response->assertStatus(200);

        $stored = DB::table('security_users')->where('username', $username)->value('password');
        $this->assertNotEquals($plaintext, $stored,
            'Plaintext password must not be persisted.');
        $this->assertTrue(Hash::check($plaintext, $stored),
            'Stored password must be a valid bcrypt hash of the plaintext.');
    }

    /**
     * Layer 4: v4 GET /api/v4/users/{id} must not leak super_admin or the
     * password hash in its response. Both fields were previously dumped
     * verbatim through a manually-built response array in UserService.
     */
    public function test_v4_get_user_response_hides_super_admin_and_password(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v4/users/2');

        $response->assertStatus(200);
        $data = $response->json('data') ?? [];

        $this->assertArrayNotHasKey('super_admin', $data,
            'v4 GET /users/{id} must not include super_admin in the response.');
        $this->assertArrayNotHasKey('password', $data,
            'v4 GET /users/{id} must not include password in the response.');
    }

    /**
     * Bonus: plaintext passwords sent to v5 must land hashed in DB
     * (via the setPasswordAttribute mutator on Api5\SecurityUsers).
     */
    public function test_v5_security_users_hashes_plaintext_password(): void
    {
        $username = 'pocor9697_v5_hash_' . uniqid();
        $plaintext = 'v5plain-pocor9697';

        $payload = SecurityUsers::factory()->make([
            'username' => $username,
            'password' => $plaintext,
        ])->toArray();
        $payload['password'] = $plaintext; // factory may have already mutated it

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/security-users', $payload);

        $this->assertContains($response->getStatusCode(), [200, 201]);

        $stored = DB::table('security_users')->where('username', $username)->value('password');
        $this->assertNotNull($stored, 'Created row not found.');
        $this->assertNotEquals($plaintext, $stored, 'v5 must not persist plaintext.');
        $this->assertTrue(Hash::check($plaintext, $stored),
            'v5-stored password must be a valid bcrypt hash of the plaintext.');
    }
}
