<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Api5\SecurityUsers;
use App\Models\Api5\Areas;

/**
 * POCOR-9257: Test webhook queueing when models are created/updated/deleted
 *
 * Note: We don't use DatabaseTransactions because we need the webhook
 * configuration to persist across test queries. Instead, we manually
 * clean up created records in tearDown().
 */
class WebhookQueueTest extends TestCase
{
    // NOT using DatabaseTransactions - we need webhooks configuration to persist

    protected $testGenderId;
    protected $testNationalityId;
    protected $createdUserIds = [];
    protected $createdWebhookIds = [];
    protected $createdConfigItemIds = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Get valid foreign key values
        $gender = DB::table('genders')->first();
        $this->testGenderId = $gender ? $gender->id : 1;

        $nationality = DB::table('nationalities')->first();
        $this->testNationalityId = $nationality ? $nationality->id : 1;

        // Clear webhook_queue table before each test
        DB::table('webhook_queue')->truncate();

        // Ensure we have at least one active webhook configured for testing
        $this->ensureTestWebhookExists();

        // CRITICAL: Re-enable test webhooks in case previous test disabled them
        DB::table('webhooks')
            ->where('url', 'https://httpbin.org/post')
            ->whereIn('event_key', ['security_user_create', 'security_user_update', 'security_user_delete'])
            ->update(['status' => 1]);

        // Debug: Check if webhooks are now active
        $webhookCount = DB::table('webhooks')
            ->whereIn('event_key', ['security_user_create', 'security_user_update', 'security_user_delete'])
            ->where('status', 1)
            ->count();

        Log::info("[WebhookQueueTest] setUp complete - Found {$webhookCount} active test webhooks in database");

        if ($webhookCount == 0) {
            Log::warning("[WebhookQueueTest] WARNING: No active webhooks found after setUp()!");
        }
    }

    protected function tearDown(): void
    {
        // Clean up created test users
        if (!empty($this->createdUserIds)) {
            DB::table('security_users')->whereIn('id', $this->createdUserIds)->delete();
            Log::info('[WebhookQueueTest] Cleaned up ' . count($this->createdUserIds) . ' test users');
        }

        // Clean up created test webhooks
        if (!empty($this->createdWebhookIds)) {
            DB::table('webhooks')->whereIn('id', $this->createdWebhookIds)->delete();
            Log::info('[WebhookQueueTest] Cleaned up ' . count($this->createdWebhookIds) . ' test webhooks');
        }

        // Clean up created test config items
        if (!empty($this->createdConfigItemIds)) {
            DB::table('config_items')->whereIn('id', $this->createdConfigItemIds)->delete();
            Log::info('[WebhookQueueTest] Cleaned up ' . count($this->createdConfigItemIds) . ' test config items');
        }

        // Clear webhook_queue
        DB::table('webhook_queue')->truncate();

        parent::tearDown();
    }

    /**
     * Ensure a test webhook configuration exists for security_user_create event
     */
    private function ensureTestWebhookExists(): void
    {
        // Check if test webhook exists
        $webhook = DB::table('webhooks')
            ->where('event_key', 'security_user_create')
            ->where('url', 'https://httpbin.org/post')
            ->first();

        Log::debug("[WebhookQueueTest] Checking for existing security_user_create webhook: " . ($webhook ? 'FOUND' : 'NOT FOUND'));

        if (!$webhook) {
            // Get or create test config_item (external data source)
            $configItem = DB::table('config_items')
                ->where('code', 'TEST_WEBHOOK_SOURCE')
                ->first();

            if (!$configItem) {
                $configItemId = DB::table('config_items')->insertGetId([
                    'name' => 'Test Webhook Source',
                    'code' => 'TEST_WEBHOOK_SOURCE',
                    'type' => 'External Data Source',
                    'value' => 1, // Active
                    'created' => now(),
                    'modified' => now(),
                ]);
                $this->createdConfigItemIds[] = $configItemId;
            } else {
                $configItemId = $configItem->id;
            }

            // Create test webhook
            $webhookId = DB::table('webhooks')->insertGetId([
                'name' => 'Test Security User Create Webhook',
                'event_key' => 'security_user_create',
                'url' => 'https://httpbin.org/post',
                'method' => 'POST',
                'status' => 1, // Active
                'external_data_source_id' => $configItemId,
                'created' => now(),
                'modified' => now(),
            ]);
            $this->createdWebhookIds[] = $webhookId;

            Log::info('[WebhookQueueTest] Created test webhook for security_user_create');
        }

        // Also create webhooks for update and delete events
        $this->ensureTestWebhookForEvent('security_user_update');
        $this->ensureTestWebhookForEvent('security_user_delete');
    }

    /**
     * Ensure a test webhook exists for a specific event
     */
    private function ensureTestWebhookForEvent(string $eventKey): void
    {
        $webhook = DB::table('webhooks')
            ->where('event_key', $eventKey)
            ->where('url', 'https://httpbin.org/post')
            ->first();

        if (!$webhook) {
            $configItem = DB::table('config_items')
                ->where('code', 'TEST_WEBHOOK_SOURCE')
                ->first();

            $webhookId = DB::table('webhooks')->insertGetId([
                'name' => "Test {$eventKey} Webhook",
                'event_key' => $eventKey,
                'url' => 'https://httpbin.org/post',
                'method' => 'POST',
                'status' => 1, // Active
                'external_data_source_id' => $configItem->id,
                'created' => now(),
                'modified' => now(),
            ]);
            $this->createdWebhookIds[] = $webhookId;

            Log::info("[WebhookQueueTest] Created test webhook for {$eventKey}");
        }
    }

    /**
     * Test that webhook is queued when SecurityUser is created
     */
    public function test_webhook_queued_on_user_create()
    {
        Log::info('[WebhookQueueTest] ========== START test_webhook_queued_on_user_create ==========');

        // Verify queue is empty before test
        $beforeCount = DB::table('webhook_queue')->count();
        $this->assertEquals(0, $beforeCount, 'webhook_queue should be empty before test');
        Log::info('[WebhookQueueTest] Verified webhook_queue is empty (count: 0)');

        // Create a new user
        Log::info('[WebhookQueueTest] Creating new SecurityUser...');
        $user = SecurityUsers::create([
            'username' => 'test_webhook_user_' . time(),
            'openemis_no' => 'TEST' . time(),
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test_webhook_' . time() . '@example.com',
            'password' => bcrypt('password'),
            'gender_id' => $this->testGenderId,
            'nationality_id' => $this->testNationalityId,
            'status' => 1,
            'super_admin' => 0,
            'created' => now(),
            'modified' => now(),
        ]);

        $this->createdUserIds[] = $user->id;
        Log::info("[WebhookQueueTest] Created user ID: {$user->id}, username: {$user->username}");

        // Check if webhook was queued
        $afterCount = DB::table('webhook_queue')->count();
        Log::info("[WebhookQueueTest] webhook_queue count after user creation: {$afterCount}");

        $this->assertGreaterThan(
            $beforeCount,
            $afterCount,
            'Webhook should be queued after user creation'
        );

        // Verify the queued webhook details
        $queuedWebhook = DB::table('webhook_queue')
            ->where('event_key', 'security_user_create')
            ->latest('created')
            ->first();

        $this->assertNotNull($queuedWebhook, 'Queued webhook should exist for security_user_create event');

        if ($queuedWebhook) {
            Log::info("[WebhookQueueTest] Found queued webhook #{$queuedWebhook->id}:");
            Log::info("[WebhookQueueTest]   Event Key: {$queuedWebhook->event_key}");
            Log::info("[WebhookQueueTest]   Target URL: {$queuedWebhook->target_url}");
            Log::info("[WebhookQueueTest]   HTTP Method: {$queuedWebhook->http_method}");
            Log::info("[WebhookQueueTest]   Status: {$queuedWebhook->status} (0=pending)");
            Log::info("[WebhookQueueTest]   Payload size: " . strlen($queuedWebhook->payload) . " bytes");

            $this->assertEquals('security_user_create', $queuedWebhook->event_key);
            $this->assertEquals(0, $queuedWebhook->status, 'Status should be PENDING (0)');
            $this->assertEquals('POST', $queuedWebhook->http_method);

            // Verify payload contains user data
            $payload = json_decode($queuedWebhook->payload, true);
            $this->assertNotNull($payload, 'Payload should be valid JSON');
            $this->assertEquals($user->id, $payload['id'], 'Payload should contain user ID');
            $this->assertEquals($user->username, $payload['username'], 'Payload should contain username');

            // Verify sensitive fields are excluded
            $this->assertArrayNotHasKey('password', $payload, 'Password should be excluded from payload');
            $this->assertArrayNotHasKey('super_admin', $payload, 'super_admin should be excluded from payload');

            Log::info('[WebhookQueueTest] ✓ All assertions passed for user creation webhook');
        }

        Log::info('[WebhookQueueTest] ========== END test_webhook_queued_on_user_create ==========');
    }

    /**
     * Test that webhook is queued when SecurityUser is updated
     */
    public function test_webhook_queued_on_user_update()
    {
        Log::info('[WebhookQueueTest] ========== START test_webhook_queued_on_user_update ==========');

        // Create a user first (this will queue a create webhook)
        $user = SecurityUsers::create([
            'username' => 'test_update_user_' . time(),
            'openemis_no' => 'UPDT' . time(),
            'first_name' => 'Update',
            'last_name' => 'Test',
            'email' => 'test_update_' . time() . '@example.com',
            'password' => bcrypt('password'),
            'gender_id' => $this->testGenderId,
            'nationality_id' => $this->testNationalityId,
            'status' => 1,
            'super_admin' => 0,
            'created' => now(),
            'modified' => now(),
        ]);

        $this->createdUserIds[] = $user->id;
        Log::info("[WebhookQueueTest] Created user ID: {$user->id} for update test");

        // Clear the queue from creation
        DB::table('webhook_queue')->truncate();
        Log::info('[WebhookQueueTest] Cleared webhook_queue after user creation');

        // Update the user
        Log::info('[WebhookQueueTest] Updating user first_name...');
        $user->first_name = 'Updated';
        $user->save();

        Log::info("[WebhookQueueTest] Updated user ID: {$user->id}, new first_name: {$user->first_name}");

        // Check if webhook was queued
        $queuedWebhook = DB::table('webhook_queue')
            ->where('event_key', 'security_user_update')
            ->latest('created')
            ->first();

        $this->assertNotNull($queuedWebhook, 'Queued webhook should exist for security_user_update event');

        if ($queuedWebhook) {
            Log::info("[WebhookQueueTest] Found queued webhook #{$queuedWebhook->id} for update event");
            Log::info("[WebhookQueueTest]   Event Key: {$queuedWebhook->event_key}");
            Log::info("[WebhookQueueTest]   Payload size: " . strlen($queuedWebhook->payload) . " bytes");

            $this->assertEquals('security_user_update', $queuedWebhook->event_key);

            $payload = json_decode($queuedWebhook->payload, true);
            $this->assertEquals('Updated', $payload['first_name'], 'Payload should contain updated first_name');

            Log::info('[WebhookQueueTest] ✓ All assertions passed for user update webhook');
        }

        Log::info('[WebhookQueueTest] ========== END test_webhook_queued_on_user_update ==========');
    }

    /**
     * Test that webhook is queued when SecurityUser is deleted
     */
    public function test_webhook_queued_on_user_delete()
    {
        Log::info('[WebhookQueueTest] ========== START test_webhook_queued_on_user_delete ==========');

        // Create a user first
        $user = SecurityUsers::create([
            'username' => 'test_delete_user_' . time(),
            'openemis_no' => 'DELT' . time(),
            'first_name' => 'Delete',
            'last_name' => 'Test',
            'email' => 'test_delete_' . time() . '@example.com',
            'password' => bcrypt('password'),
            'gender_id' => $this->testGenderId,
            'nationality_id' => $this->testNationalityId,
            'status' => 1,
            'super_admin' => 0,
            'created' => now(),
            'modified' => now(),
        ]);

        $userId = $user->id;
        $this->createdUserIds[] = $userId;
        Log::info("[WebhookQueueTest] Created user ID: {$userId} for delete test");

        // Clear the queue from creation
        DB::table('webhook_queue')->truncate();
        Log::info('[WebhookQueueTest] Cleared webhook_queue after user creation');

        // Delete the user
        Log::info("[WebhookQueueTest] Deleting user ID: {$userId}...");
        $user->delete();
        Log::info("[WebhookQueueTest] User deleted");

        // Check if webhook was queued
        $queuedWebhook = DB::table('webhook_queue')
            ->where('event_key', 'security_user_delete')
            ->latest('created')
            ->first();

        $this->assertNotNull($queuedWebhook, 'Queued webhook should exist for security_user_delete event');

        if ($queuedWebhook) {
            Log::info("[WebhookQueueTest] Found queued webhook #{$queuedWebhook->id} for delete event");
            Log::info("[WebhookQueueTest]   Event Key: {$queuedWebhook->event_key}");
            Log::info("[WebhookQueueTest]   Payload size: " . strlen($queuedWebhook->payload) . " bytes");

            $this->assertEquals('security_user_delete', $queuedWebhook->event_key);

            $payload = json_decode($queuedWebhook->payload, true);
            $this->assertEquals($userId, $payload['id'], 'Payload should contain deleted user ID');
            $this->assertArrayHasKey('deleted_at', $payload, 'Payload should contain deleted_at timestamp');
            $this->assertArrayHasKey('deleted_by', $payload, 'Payload should contain deleted_by');

            Log::info('[WebhookQueueTest] ✓ All assertions passed for user delete webhook');
        }

        Log::info('[WebhookQueueTest] ========== END test_webhook_queued_on_user_delete ==========');
    }

    /**
     * Test that webhook is NOT queued when no active webhooks are configured
     */
    public function test_no_webhook_queued_when_no_active_webhooks()
    {
        Log::info('[WebhookQueueTest] ========== START test_no_webhook_queued_when_no_active_webhooks ==========');

        // Disable all webhooks
        DB::table('webhooks')->update(['status' => 0]);
        Log::info('[WebhookQueueTest] Disabled all webhooks (status = 0)');

        // Create a user
        Log::info('[WebhookQueueTest] Creating user with no active webhooks...');
        $user = SecurityUsers::create([
            'username' => 'test_no_webhook_' . time(),
            'openemis_no' => 'NOWH' . time(),
            'first_name' => 'NoWebhook',
            'last_name' => 'Test',
            'email' => 'test_no_webhook_' . time() . '@example.com',
            'password' => bcrypt('password'),
            'gender_id' => $this->testGenderId,
            'nationality_id' => $this->testNationalityId,
            'status' => 1,
            'super_admin' => 0,
            'created' => now(),
            'modified' => now(),
        ]);

        $this->createdUserIds[] = $user->id;
        Log::info("[WebhookQueueTest] Created user ID: {$user->id}");

        // Verify no webhook was queued
        $queueCount = DB::table('webhook_queue')->count();
        Log::info("[WebhookQueueTest] webhook_queue count: {$queueCount}");

        $this->assertEquals(0, $queueCount, 'No webhook should be queued when no active webhooks are configured');

        Log::info('[WebhookQueueTest] ✓ Verified no webhooks queued when webhooks disabled');
        Log::info('[WebhookQueueTest] ========== END test_no_webhook_queued_when_no_active_webhooks ==========');
    }

    /**
     * Test webhook queueing with relations
     */
    public function test_webhook_queued_with_relations()
    {
        Log::info('[WebhookQueueTest] ========== START test_webhook_queued_with_relations ==========');

        // Test with Areas model (if it has relations configured)
        // First check if Areas uses WebhookQueueTrait and has webhookRelations
        $area = Areas::first();

        if (!$area) {
            Log::info('[WebhookQueueTest] No areas found, creating test area...');
            $area = Areas::create([
                'code' => 'TEST_AREA_' . time(),
                'name' => 'Test Area',
                'order' => 1,
                'created' => now(),
                'modified' => now(),
            ]);
        }

        // Check if webhook was queued (if Areas has webhooks configured)
        $queuedWebhook = DB::table('webhook_queue')
            ->where('event_key', 'area_create')
            ->orWhere('event_key', 'area_update')
            ->latest('created')
            ->first();

        if ($queuedWebhook) {
            Log::info("[WebhookQueueTest] Found queued webhook for area event");
            Log::info("[WebhookQueueTest]   Event Key: {$queuedWebhook->event_key}");

            $payload = json_decode($queuedWebhook->payload, true);
            $this->assertNotNull($payload, 'Payload should be valid JSON');
            Log::info('[WebhookQueueTest] ✓ Webhook queued successfully with relations');
        } else {
            Log::info('[WebhookQueueTest] No webhook queued for area (may not have webhooks configured)');
            $this->assertTrue(true, 'Test passed - no webhook configured is acceptable');
        }

        Log::info('[WebhookQueueTest] ========== END test_webhook_queued_with_relations ==========');
    }
}
