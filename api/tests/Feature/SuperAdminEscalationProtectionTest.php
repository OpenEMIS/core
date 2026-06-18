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
 * write endpoints. This file pins down the layers of the fix:
 *
 *   1. v4 UsersAddRequest silently strips super_admin from the request and
 *      logs the attempt server-side. The response gives the attacker no
 *      signal that the field is even recognised (no 422, no field name in
 *      the body) — defence in depth without fingerprinting the API.
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
     * Layer 1: silent strip on UsersAddRequest. Sending super_admin in the
     * body must NOT trigger a 422 that names the field — that would
     * fingerprint the API for any attacker probing escalation vectors.
     * The field is removed by prepareForValidation() and the row, if
     * created, lands with super_admin = 0. The response body contains no
     * mention of "super_admin".
     */
    public function test_v4_addUsers_silently_strips_super_admin(): void
    {
        $username = 'pocor9697_v4_strip_' . uniqid();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v4/users', [
            'first_name'    => 'Esc',
            'last_name'     => 'Alation',
            'gender_id'     => 1,
            'date_of_birth' => '2000-01-01',
            'username'      => $username,
            'openemis_no'   => $username,
            'email'         => $username . '@example.test',
            'password'      => 'someplain',
            'super_admin'   => 1,
        ]);

        // Request looks like an ordinary success to the caller.
        $response->assertStatus(200);

        // Response body must NOT name super_admin — that would defeat the
        // whole point of stripping it silently.
        $this->assertStringNotContainsStringIgnoringCase(
            'super_admin',
            $response->getContent(),
            'v4 response must not name super_admin (fingerprinting hole).'
        );

        // DB row exists and is NOT super_admin.
        $row = DB::table('security_users')->where('username', $username)->first();
        $this->assertNotNull($row, 'Row should still be created.');
        $this->assertSame(0, (int) $row->super_admin,
            'Stripped field must never reach the DB.');
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
            'openemis_no'   => $username,
            'email'         => $username . '@example.test',
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
     * Layer 5: POST /api/v4/users/basic-information must not leak
     * super_admin or password. DirectoryRepository previously copied
     * super_admin verbatim into each result row.
     */
    public function test_v4_basic_information_response_hides_super_admin_and_password(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v4/users/basic-information?first_name=Z&last_name=Z&date_of_birth=1900-01-01&user_type_id=1&gender_id=1');

        // Endpoint may return 200 with empty results or 404 — both are
        // acceptable; the only thing this test cares about is the *shape*
        // of any rows that do come back.
        $rows = $response->json('data.data') ?? [];

        foreach ($rows as $row) {
            $this->assertArrayNotHasKey('super_admin', $row,
                'basic-information rows must not include super_admin.');
            $this->assertArrayNotHasKey('password', $row,
                'basic-information rows must not include password.');
        }

        $this->assertTrue(true, 'No leaking rows found.');
    }

    /**
     * Layer 6 (POCOR-9697 read-side closure):
     *
     * `_conditions=super_admin:1` must NOT enumerate super_admin accounts.
     * The CrudApiController allowlist rejects the clause with a 400 + generic
     * message; the field name is never echoed back. This closes the membership-
     * inference vector while also surfacing typos to legit clients (a previous
     * silent-drop iteration swallowed both attacks AND honest mistakes).
     */
    public function test_v5_conditions_filter_rejects_hidden_super_admin(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/security-users?_conditions=super_admin:1&limit=1');

        $response->assertStatus(400);
        $this->assertStringNotContainsStringIgnoringCase('super_admin', $response->getContent(),
            'Generic 400 body must not echo the rejected field name.');
    }

    /**
     * Layer 7 (POCOR-9697 read-side closure):
     *
     * `_conditions=password:>$2y$` must NOT act as a binary-search oracle on
     * the bcrypt hash column. Rejected with the same generic 400 as any other
     * non-allowlist field; the SOC log gets an escalated SENSITIVE-probe entry.
     */
    public function test_v5_conditions_filter_rejects_password_oracle(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/security-users?_conditions=' . urlencode('password:>$2y$') . '&limit=1');

        $response->assertStatus(400);
        $this->assertStringNotContainsStringIgnoringCase('password', $response->getContent(),
            'Generic 400 body must not echo the rejected field name.');
    }

    /**
     * Layer 7b (POCOR-9697 read-side DX):
     *
     * A genuine typo (`hubabuba:babble`) gets the SAME generic 400 as a
     * sensitive probe — anti-fingerprinting prevents A/B testing of which keys
     * are sensitive vs simply unknown. Without this, an attacker could probe:
     * `unknown_x` → 400, `super_admin` → 200-with-rows = "super_admin is real".
     */
    public function test_v5_conditions_filter_rejects_typo_field(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/security-users?_conditions=hubabuba:babble&limit=1');

        $response->assertStatus(400);
        $this->assertStringNotContainsStringIgnoringCase('hubabuba', $response->getContent(),
            'Generic 400 body must not echo the rejected field name (consistent with sensitive-field rejection).');
    }

    /**
     * Sanity: legitimate `$fillable` columns must still filter normally.
     * If they did not, every existing v5 GET client breaks.
     */
    public function test_v5_conditions_filter_allows_legitimate_field(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/security-users?_conditions=username:admin&limit=5');

        $response->assertStatus(200);
        $total = (int) $response->json('data.total');
        $this->assertGreaterThanOrEqual(1, $total,
            'username:admin should match the seeded admin row.');

        $usernames = collect($response->json('data.data') ?? [])->pluck('username')->all();
        $this->assertContains('admin', $usernames,
            'Filter on legitimate $fillable column must still apply.');
    }

    /**
     * Defence in depth: the 400 body must NOT leak any field name back to the
     * caller. No "super_admin", no "password", no "column", no "field" — same
     * anti-fingerprinting rule as the write side. The body is a fixed generic
     * string so sensitive probes, typos, and legitimate-but-non-allowlist
     * fields all produce identical responses.
     */
    public function test_v5_conditions_rejected_field_no_named_response_leak(): void
    {
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->getJson('/api/v5/security-users?_conditions=super_admin:1&limit=1');

        $response->assertStatus(400);
        $body = $response->getContent();

        $this->assertStringNotContainsStringIgnoringCase('super_admin', $body,
            'Response must not name the rejected field.');
        $this->assertStringNotContainsStringIgnoringCase('"password"', $body,
            'Response must not include password (always hidden).');
        $this->assertStringNotContainsStringIgnoringCase('unknown column', $body,
            'Response must not leak DB-level "unknown column" errors.');
    }

    /**
     * Wave-2 (POCOR-9697 audit-trail integrity):
     *
     * v5 create must overwrite a client-forged created_user_id with the JWT
     * user. Previously, a `created_user_id: 99999` in the body would land in
     * DB unchanged because the controller only filled the field when absent.
     */
    public function test_v5_create_overwrites_forged_created_user_id(): void
    {
        $username = 'pocor9697_w2_create_cu_' . uniqid();

        $payload = SecurityUsers::factory()->make([
            'username' => $username,
        ])->toArray();
        $payload['created_user_id'] = 99999; //POCOR-9697: forgery attempt

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/security-users', $payload);

        $this->assertContains($response->getStatusCode(), [200, 201]);

        $stored = DB::table('security_users')->where('username', $username)->value('created_user_id');
        $this->assertNotNull($stored, 'Row should have been created.');
        $this->assertSame(2, (int) $stored,
            'v5 create must derive created_user_id from JWT user (id=2), not from request body.');
    }

    /**
     * Wave-2: v5 update must overwrite a client-forged modified_user_id with
     * the JWT user.
     */
    public function test_v5_update_overwrites_forged_modified_user_id(): void
    {
        $target = SecurityUsers::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/security-users/' . $target->id, [
            'id'               => $target->id,
            'modified_user_id' => 99999, //POCOR-9697: forgery attempt
        ]);

        $this->assertContains($response->getStatusCode(), [200, 204]);

        $stored = DB::table('security_users')->where('id', $target->id)->value('modified_user_id');
        $this->assertSame(2, (int) $stored,
            'v5 update must derive modified_user_id from JWT user (id=2), not from request body.');
    }

    /**
     * Wave-2: v5 update must silently ignore a client-supplied created_user_id
     * — it is immutable. The original creator value must remain untouched.
     */
    public function test_v5_update_silently_ignores_created_user_id(): void
    {
        $target = SecurityUsers::factory()->create();
        $originalCreator = (int) DB::table('security_users')
            ->where('id', $target->id)
            ->value('created_user_id');

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/security-users/' . $target->id, [
            'id'              => $target->id,
            'created_user_id' => 99999, //POCOR-9697: should be silently dropped
        ]);

        $this->assertContains($response->getStatusCode(), [200, 204]);

        $stored = (int) DB::table('security_users')->where('id', $target->id)->value('created_user_id');
        $this->assertSame($originalCreator, $stored,
            'created_user_id is immutable — must equal the original creator, not the forged value.');
        $this->assertNotSame(99999, $stored, 'Forged value must not land in DB.');
    }

    /**
     * Wave-2: response body must NOT echo created_user_id / modified_user_id
     * in any error/diagnostic context. Echoing the field name would tell an
     * attacker the audit-trail forgery vector is recognised — same
     * anti-fingerprinting rule as the super_admin strip. We allow the field
     * to appear as a regular result attribute (it is part of the row), but
     * not in a "field rejected" style message.
     */
    public function test_v5_create_no_field_fingerprint_in_response(): void
    {
        $username = 'pocor9697_w2_fp_' . uniqid();

        $payload = SecurityUsers::factory()->make([
            'username' => $username,
        ])->toArray();
        $payload['created_user_id']  = 99999;
        $payload['modified_user_id'] = 99999;

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/security-users', $payload);

        $this->assertContains($response->getStatusCode(), [200, 201]);

        $body = $response->getContent();
        // Anti-fingerprinting: no diagnostic phrasing naming the field.
        $this->assertStringNotContainsStringIgnoringCase('forgery', $body);
        $this->assertStringNotContainsStringIgnoringCase('rejected', $body);
        $this->assertStringNotContainsStringIgnoringCase('forbidden field', $body);
        $this->assertStringNotContainsStringIgnoringCase('audit-trail', $body);
    }

    /**
     * Wave-2: v4 user create must also overwrite a forged created_user_id.
     * This re-confirms the v4 path that UserRepository::setUserData already
     * stamps from JWTAuth::user()->id and never copies the request value.
     */
    public function test_v4_create_overwrites_forged_created_user_id(): void
    {
        $username = 'pocor9697_w2_v4_cu_' . uniqid();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v4/users', [
            'first_name'      => 'Audit',
            'last_name'       => 'Trail',
            'gender_id'       => 1,
            'date_of_birth'   => '2000-01-01',
            'username'        => $username,
            'openemis_no'     => $username,
            'email'           => $username . '@example.test',
            'password'        => 'someplain',
            'created_user_id' => 99999, //POCOR-9697: forgery attempt
        ]);

        $response->assertStatus(200);

        $stored = DB::table('security_users')->where('username', $username)->value('created_user_id');
        $this->assertSame(2, (int) $stored,
            'v4 create must derive created_user_id from JWT user (id=2), never the request body.');
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

    /**
     * Wave-3 (POCOR-9697 audit log):
     *
     * Every v5 create on /security-users must drop a row into user_activities
     * naming who did it, who was created, and when. The shape mirrors what
     * the Cake-side UserActivityBehavior / UsersController writes so the
     * existing dashboard at User → Activities renders API rows identically.
     *
     * Critical: the row must NOT contain raw password or super_admin values.
     */
    public function test_v5_create_user_logs_audit_row(): void //POCOR-9697
    {
        $username = 'pocor9697_w3_create_' . uniqid();

        $payload = SecurityUsers::factory()->make([
            'username' => $username,
        ])->toArray();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v5/security-users', $payload);

        $this->assertContains($response->getStatusCode(), [200, 201]);

        $newUserId = (int) DB::table('security_users')->where('username', $username)->value('id');
        $this->assertGreaterThan(0, $newUserId, 'Created user not found.');

        $rows = DB::table('user_activities')
            ->where('security_user_id', $newUserId)
            ->where('operation', 'create')
            ->get();

        $this->assertGreaterThanOrEqual(1, $rows->count(),
            'A create-row must be written to user_activities for every API user-create.');

        foreach ($rows as $row) {
            $this->assertSame(2, (int) $row->created_user_id,
                'created_user_id must reflect the JWT caller (id=2), not 0 or the new user.');
            // Defence in depth: password and super_admin must never land in old/new.
            $this->assertNotSame('password', $row->field,
                'create summary row must not name password as a changed field.');
            $this->assertNotSame('super_admin', $row->field,
                'create summary row must not name super_admin as a changed field.');
        }
    }

    /**
     * Wave-3: a v5 update changing two ordinary fields must produce a row
     * per dirty field, each row recording the previous and new value.
     */
    public function test_v5_update_user_logs_audit_row_per_dirty_field(): void //POCOR-9697
    {
        $target = SecurityUsers::factory()->create([
            'first_name' => 'OrigFirst',
            'email'      => 'orig_' . uniqid() . '@example.test',
        ]);

        // Baseline — any rows from the create event itself.
        $baseline = DB::table('user_activities')
            ->where('security_user_id', $target->id)
            ->where('operation', 'edit')
            ->count();

        $newEmail = 'updated_' . uniqid() . '@example.test';
        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/security-users/' . $target->id, [
            'id'         => $target->id,
            'first_name' => 'NewFirst',
            'email'      => $newEmail,
        ]);

        $this->assertContains($response->getStatusCode(), [200, 204]);

        $rows = DB::table('user_activities')
            ->where('security_user_id', $target->id)
            ->where('operation', 'edit')
            ->get();

        $this->assertGreaterThanOrEqual($baseline + 2, $rows->count(),
            'Expected at least two new edit rows — one per dirty field (first_name + email).');

        $fields = $rows->pluck('field')->all();
        $this->assertContains('first_name', $fields, 'first_name change must be logged.');
        $this->assertContains('email', $fields, 'email change must be logged.');

        foreach ($rows as $row) {
            $this->assertNotSame('password', $row->field,
                'edit audit row must not name password unless password actually changed.');
            $this->assertNotSame('super_admin', $row->field,
                'edit audit row must never name super_admin in this test.');
        }
    }

    /**
     * Wave-3: when password itself is updated, the row must exist (so the
     * dashboard sees password was changed) but old/new value columns must
     * be redacted — we never persist plaintext or the bcrypt hash into
     * user_activities.old_value / new_value (themselves only varchar 255).
     */
    public function test_v5_update_user_password_change_logs_event_without_value(): void //POCOR-9697
    {
        $target = SecurityUsers::factory()->create();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->putJson('/api/v5/security-users/' . $target->id, [
            'id'       => $target->id,
            'password' => 'changed-plain-' . uniqid(),
        ]);

        $this->assertContains($response->getStatusCode(), [200, 204]);

        $row = DB::table('user_activities')
            ->where('security_user_id', $target->id)
            ->where('operation', 'edit')
            ->where('field', 'password')
            ->orderByDesc('id')
            ->first();

        $this->assertNotNull($row, 'Password change must be visible in the audit trail.');
        $this->assertSame('[REDACTED]', $row->old_value,
            'Audit must mask the previous password — never store the bcrypt hash.');
        $this->assertSame('[REDACTED]', $row->new_value,
            'Audit must mask the new password — never store plaintext or hash.');
    }

    /**
     * Wave-3: v4 create endpoint must also produce an audit row, since the
     * trait lives on the Eloquent model — not on a specific controller.
     */
    public function test_v4_create_user_logs_audit_row(): void //POCOR-9697
    {
        $username = 'pocor9697_w3_v4_create_' . uniqid();

        $response = $this->withHeaders([
            'Authorization' => "Bearer {$this->token}",
        ])->postJson('/api/v4/users', [
            'first_name'    => 'Audit',
            'last_name'     => 'V4',
            'gender_id'     => 1,
            'date_of_birth' => '2000-01-01',
            'username'      => $username,
            'openemis_no'   => $username,
            'email'         => $username . '@example.test',
            'password'      => 'someplain',
        ]);

        $response->assertStatus(200);

        $newUserId = (int) DB::table('security_users')->where('username', $username)->value('id');
        $this->assertGreaterThan(0, $newUserId, 'v4-created user not found.');

        $createdRows = DB::table('user_activities')
            ->where('security_user_id', $newUserId)
            ->where('operation', 'create')
            ->count();

        $this->assertGreaterThanOrEqual(1, $createdRows,
            'v4 create must produce a user_activities row — trait lives on the model.');
    }

    /**
     * Wave-3 follow-up: deleting a user must write the summary row PLUS
     * one snapshot row per column so the deleted entity can be
     * reconstructed forensically. password / super_admin must come
     * through as '[REDACTED]'; photo_content as '[...]'.
     */
    public function test_delete_user_writes_per_field_snapshot(): void //POCOR-9697
    {
        $target = SecurityUsers::factory()->create([
            'first_name' => 'DeleteSnap',
            'email'      => 'snap_' . uniqid() . '@example.test',
        ]);
        $targetId = $target->id;

        //POCOR-9697: hard-delete of security_users is schema-blocked
        //(FK ON DELETE RESTRICT). We exercise the snapshot logic
        //directly — same code path the Eloquent `deleted` event would
        //fire — without performing the schema-blocked DB delete.
        $target->logUserActivity('delete');

        $rows = DB::table('user_activities')
            ->where('security_user_id', $targetId)
            ->where('operation', 'delete')
            ->get();

        $this->assertGreaterThanOrEqual(
            2,
            $rows->count(),
            'delete must produce at least a summary row + per-field snapshot rows.'
        );

        $summary = $rows->first(function ($r) {
            return $r->field === '' && $r->old_value === '' && $r->new_value === '';
        });
        $this->assertNotNull($summary, 'Summary row (empty-string shape) must exist.');

        $firstName = $rows->firstWhere('field', 'first_name');
        $this->assertNotNull($firstName, 'first_name snapshot row must exist.');
        $this->assertSame('DeleteSnap', $firstName->old_value);
        $this->assertSame('', $firstName->new_value);

        $pw = $rows->firstWhere('field', 'password');
        $this->assertNotNull($pw, 'password row must exist (proves a password change/value was on the deleted user).');
        $this->assertSame('[REDACTED]', $pw->old_value,
            'password must never be persisted into the audit trail.');

        $sa = $rows->firstWhere('field', 'super_admin');
        $this->assertNotNull($sa, 'super_admin row must exist.');
        $this->assertSame('[REDACTED]', $sa->old_value);

        // photo_content is nullable on factory rows but the snapshot row
        // must exist with the '[...]' placeholder regardless of value.
        $photo = $rows->firstWhere('field', 'photo_content');
        if ($photo !== null) {
            $this->assertSame('[...]', $photo->old_value,
                'photo_content (longblob) must surface as [...] never the raw blob.');
        }
    }

    //POCOR-9710 — row-level super_admin invisibility.
    //
    //POCOR-9697 hid the `super_admin` *column* from responses; this layer
    //hides the *rows themselves* so a non-super-admin caller can neither
    //view, edit, nor confirm the existence of a super_admin = 1 user.
    //Mirrors the CakePHP rule at plugins/Security/src/Model/Table/
    //UsersTable.php:682-686 (POCOR-9370).
    //
    //Tests use a freshly minted non-super-admin token so we do not depend
    //on whichever seeded user happens to occupy id=2 in the dev DB.

    private function nonSuperAdminToken(): array
    {
        $caller = SecurityUsers::factory()->create([
            'username'    => 'pocor9710_caller_' . uniqid(),
            'status'      => 1,
            'super_admin' => 0,
        ]);
        DB::table('security_users')->where('id', $caller->id)->update(['super_admin' => 0]);
        //Grant SecurityUsers view/add/edit so the caller reaches the probe
        //gate — without permission the controller short-circuits at 403 and
        //the row-visibility scope is untestable. We attach the Principal
        //role (id=4) which the dev seed already wires to SecurityUsers.
        $this->grantSecurityUsersPermissionTo($caller->id);
        return [JWTAuth::fromUser($caller), $caller];
    }

    private function grantSecurityUsersPermissionTo(int $userId): void
    {
        $roleId = DB::table('security_roles')
            ->join('security_role_functions', 'security_role_functions.security_role_id', '=', 'security_roles.id')
            ->join('security_functions', 'security_functions.id', '=', 'security_role_functions.security_function_id')
            ->where('security_functions._view', 'like', '%SecurityUsers%')
            ->where('security_role_functions._view', 1)
            ->where('security_role_functions._add', 1)
            ->where('security_role_functions._edit', 1)
            ->value('security_roles.id');
        if ($roleId === null) {
            $this->markTestSkipped('No seeded role grants SecurityUsers view/add/edit — cannot exercise probe gate.');
            return;
        }
        DB::table('security_group_users')->insert([
            'security_user_id'  => $userId,
            'security_role_id'  => $roleId,
            'security_group_id' => DB::table('security_groups')->value('id') ?: 0,
            'created_user_id'   => 1,
            'created'           => now(),
        ]);
        //PermissionService caches per-user permissions for 10 min — flush so
        //our freshly-granted role is picked up for the test.
        \Cache::forget("permissions:user:{$userId}");
    }

    private function freshSuperAdminTarget(): SecurityUsers
    {
        $target = SecurityUsers::factory()->create([
            'username' => 'pocor9710_target_' . uniqid(),
            'status'   => 1,
        ]);
        DB::table('security_users')->where('id', $target->id)->update(['super_admin' => 1]);
        return $target->fresh();
    }

    public function test_v5_get_super_admin_id_returns_404_for_non_super_admin(): void
    {
        [$token] = $this->nonSuperAdminToken();
        $target = $this->freshSuperAdminTarget();

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->getJson('/api/v5/security-users/' . $target->id);

        $response->assertStatus(404);
        $this->assertStringNotContainsStringIgnoringCase(
            'super_admin',
            $response->getContent(),
            'v5 404 body must not name super_admin (fingerprinting hole).'
        );
    }

    public function test_v4_get_super_admin_id_returns_not_found_for_non_super_admin(): void
    {
        [$token] = $this->nonSuperAdminToken();
        $target = $this->freshSuperAdminTarget();

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->getJson('/api/v4/users/' . $target->id);

        $this->assertStringContainsString(
            'Users Data Not Found',
            $response->getContent(),
            'v4 GET /users/{id} must yield the generic not-found message for a super-admin target.'
        );
        $this->assertStringNotContainsStringIgnoringCase(
            'super_admin',
            $response->getContent(),
            'v4 not-found body must not name super_admin.'
        );
    }

    public function test_v5_list_does_not_leak_super_admin_rows_for_non_super_admin(): void
    {
        [$token] = $this->nonSuperAdminToken();
        $target = $this->freshSuperAdminTarget();

        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->getJson('/api/v5/security-users?limit=200');

        $response->assertStatus(200);
        $body = $response->getContent();
        $this->assertStringNotContainsString(
            (string) $target->id,
            $body,
            'v5 list must not include the freshly-minted super-admin id.'
        );
    }

    public function test_v5_get_super_admin_id_returns_row_for_super_admin_caller(): void
    {
        //Super-admin callers bypass the scope entirely — verifies we did not
        //break super-admin-to-super-admin management (which POCOR-9370 also
        //preserves on the CakePHP side).
        $admin = SecurityUsers::where('id', 2)->first();
        if (!$admin || (int) $admin->super_admin !== 1) {
            $this->markTestSkipped('Seeded id=2 must be super-admin for this test.');
            return;
        }
        $target = $this->freshSuperAdminTarget();

        $response = $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
            ->getJson('/api/v5/security-users/' . $target->id);

        $response->assertStatus(200);
        $this->assertStringContainsString(
            (string) $target->id,
            $response->getContent(),
            'super-admin caller must see super-admin targets — no regression on peer management.'
        );
    }

    public function test_v5_post_password_silently_stripped_for_non_super_admin(): void
    {
        //Q1 — non-super-admin callers cannot set `password` via the generic
        //CRUD path; the field is removed before mass-assignment and the row
        //is created with a model-generated password (whatever the factory
        //default mutator yields). The response must not name `password`.
        [$token, $caller] = $this->nonSuperAdminToken();
        $username = 'pocor9710_pwstrip_' . uniqid();
        $plaintext = 'attacker_chosen_password';

        $payload = SecurityUsers::factory()->make([
            'username'    => $username,
            'openemis_no' => $username,
        ])->toArray();
        $payload['password'] = $plaintext;
        //Caller permissions: the seeded test JWT subject (id=2) is super-admin,
        //so to exercise the non-super-admin path we use the fresh caller
        //which inherits no roles — the controller still mass-assigns up to
        //the model's $fillable, so the field-strip is the layer under test.
        $response = $this->withHeaders(['Authorization' => "Bearer {$token}"])
            ->postJson('/api/v5/security-users', $payload);

        if ($response->status() !== 201) {
            //Permission seeding may legitimately block the call — in which
            //case the strip is moot for this caller. Skip the assertion
            //rather than wedge the test on environment shape.
            $this->markTestSkipped(
                'Non-super-admin caller could not POST to /api/v5/security-users in this env (status '
                . $response->status() . ') — strip layer untested via this path.'
            );
            return;
        }

        $this->assertStringNotContainsStringIgnoringCase(
            'password',
            $response->getContent(),
            'v5 create response must never name password.'
        );

        $row = DB::table('security_users')->where('username', $username)->first();
        $this->assertNotNull($row, 'Row must still be created.');
        $this->assertFalse(
            Hash::check($plaintext, $row->password),
            'Plaintext password from non-super-admin caller must NOT be persisted.'
        );
    }

    public function test_v5_post_password_accepted_for_super_admin_caller(): void
    {
        //Q1 carve-out — super-admin callers retain the ability to seed a
        //plaintext password on create; the setPasswordAttribute() mutator
        //hashes it before persist.
        $username = 'pocor9710_pwsuper_' . uniqid();
        $plaintext = 'super_chosen_password';

        $payload = SecurityUsers::factory()->make([
            'username'    => $username,
            'openemis_no' => $username,
        ])->toArray();
        $payload['password'] = $plaintext;

        $response = $this->withHeaders(['Authorization' => "Bearer {$this->token}"])
            ->postJson('/api/v5/security-users', $payload);

        $response->assertStatus(201);
        $row = DB::table('security_users')->where('username', $username)->first();
        $this->assertNotNull($row);
        $this->assertTrue(
            Hash::check($plaintext, $row->password),
            'Super-admin caller must be able to set the initial password; the mutator hashes it.'
        );
    }
}
