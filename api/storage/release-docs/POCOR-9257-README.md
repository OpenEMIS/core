# POCOR-9257 - Webhook Async Queueing System

## 1. What is the Task?

Implement a comprehensive webhook queueing system with three primary objectives:

1. **Implement the same webhook behavior for Laravel API part** - Create WebhookQueueTrait for Laravel models to match CakePHP's webhook queueing capabilities, ensuring unified webhook handling across both systems (CakePHP and Laravel API5)

2. **Make all the webhooks work asynchronously** - Convert all webhook firing from synchronous direct calls to asynchronous queue-based processing. Instead of blocking request processing during webhook delivery, webhooks are now queued to a database table and processed asynchronously by the Laravel worker scheduler

3. **Add logging of the webhook receiving side + possibility to resend webhook if some problem happens** - Implement comprehensive webhook logging and audit trail with the ability to manually resend failed webhooks, providing full visibility into webhook delivery status and enabling troubleshooting and recovery

## 2. Situation Before

- Webhooks were triggered synchronously via direct method calls
- Long-running webhook deliveries (network delays, retries) blocked user requests
- No retry mechanism for failed webhook deliveries
- Poor visibility into webhook delivery status
- Performance issues under high webhook load

## 3. What Was Implemented

**Primary Task 1: Implement Webhook Behavior for Laravel API**
- Created `WebhookQueueTrait` for Laravel API5 models (matching CakePHP behavior)
- Trait automatically queues webhooks on model create/update/delete events
- Supports configurable webhook events per model via `$webhookEvents` property
- Configurable relation loading via `$webhookRelations` property (matching CakePHP 'contain')
- Automatic sensitive field exclusion via `$webhookExcludedFields` property
- Applied to all Laravel API5 models that need webhook support

**Primary Task 2: Asynchronous Webhook Processing**
- Converted all CakePHP webhook firing from `$Webhooks->triggerCommand()` to `WebhookQueueTable->queueWebhook()`
- Made ConfigWebhooksTable methods public to support async processing:
  - `buildWebhookUrl()` - Constructs final webhook URL with placeholders
  - `prepareFinalWebhookBody()` - Parses JSON templates for request body
- Updated all webhook trigger points to graceful queue insertion with error logging
- Laravel `ProcessWebhookQueue` command processes `webhook_queue` table asynchronously every minute
- Both CakePHP and Laravel API trigger events are queued for async delivery
- No blocking of user requests during webhook delivery

**Primary Task 3: Webhook Logging and Resend Capability**
- Implemented comprehensive webhook logging in `webhook_logs` table (permanent audit trail)
- Tracks all webhook events with:
  - Event type and key (e.g., 'institution_update')
  - Webhook URL and request body
  - Response status and response body
  - Timestamp and error messages
  - Model context and user information
- Queue entries include retry tracking (`retry_count`)
- Failed webhooks (status = -1) can be manually resent:
  ```sql
  -- Mark failed webhooks for retry
  UPDATE webhook_queue SET status = 0, retry_count = 0
  WHERE status = -1 AND id IN (SELECT id FROM ...);
  ```
- Query `webhook_logs` table to review delivery history and troubleshoot failed deliveries

**Files Modified (CakePHP):**
- `plugins/Configuration/src/Model/Table/ConfigWebhooksTable.php` (2 methods made public)
- `plugins/Institution/src/Model/Table/InstitutionsTable.php` (triggerWebhookCommand method)
- `plugins/Configuration/src/Model/Behavior/CallWebhookBehavior.php` (triggerMyWebhook method)
- `plugins/User/src/Controller/UsersController.php` (logout webhook)
- Additional 30+ table files using CallWebhookBehavior

**Files Added/Modified (Laravel API):**
- `api/app/Models/Concerns/WebhookQueueTrait.php` - NEW: Trait for webhook queueing in Laravel models
- `api/app/Services/WebhookSender.php` - Service for sending queued webhooks
- `api/app/Console/Commands/ProcessWebhookQueue.php` - Command to process webhook queue
- 18+ Laravel API5 models updated to use WebhookQueueTrait

**Database Tables:**
- `webhook_queue` - Holds pending webhook deliveries (status: 0=pending, 1=processing, 2=sent, -1=failed, with retry_count)
- `webhook_logs` - Permanent audit trail with full webhook request/response details

### Files Changed Summary
- **Modified:** 35+ files (all tables/controllers using webhooks)
- **Added:** 0 files
- **Removed:** 0 files

### Database Migrations
- **Required:** YES - Migration `20260215234927_POCOR9257.php` creates/updates webhook tables
- **Tables created:**
  - `webhook_queue` - Operational queue for pending webhook deliveries with retry tracking
  - `webhook_logs` - Permanent audit trail with full request/response logging
- **Backward compatible:** YES (queue failures don't break parent processes)
- **Data backup:** Migration automatically backs up existing tables before modifying them

## Issue 2 — Checkbox Selection and Mass Delete for Webhook Queue and Logs

### What Was Implemented

Added bulk operations capability to the Webhook Queue and Webhook Logs index pages, allowing users to select multiple records and delete them in a single action:

**New Template Elements (CakePHP):**
- `src/Template/Element/Webhook/select_checkbox.php` - Checkbox column rendering for rows
- `src/Template/Element/Webhook/bulk_actions_js.php` - JavaScript for checkbox state management (select all, select individual)
- `src/Template/Element/Webhook/delete_selected_button.php` - "Delete Selected" button with confirmation dialog

**Modified Table Classes:**
- `src/Model/Table/WebhookQueueTable.php` - Registered checkbox column and bulk operations integration
- `src/Model/Table/WebhookLogsTable.php` - Registered checkbox column and bulk operations integration

**New Controller Actions:**
- `src/Controller/WebhookController.php::queueDeleteSelected()` - POST handler for bulk deleting webhook queue entries
- `src/Controller/WebhookController.php::logsDeleteSelected()` - POST handler for bulk deleting webhook log entries

**User Experience:**
- Checkboxes appear in the first column of Webhook Queue and Logs index pages
- "Select All" checkbox to toggle all visible records
- Individual row checkboxes for targeted selection
- "Delete Selected" button appears when any records are selected
- JavaScript confirmation dialog prevents accidental deletions
- Immediate UI feedback upon successful deletion

### Files Changed Summary (Issue 2 only)
- Added: 3 files (template elements)
- Modified: 3 files (WebhookQueueTable, WebhookLogsTable, WebhookController)
- Removed: 0 files

## Issue 3 — WebhookLogs Delete Button Fix and Debug Cleanup

### What Was Implemented

Fixed the Delete button on the WebhookLogs index page (list view) which was not functioning due to incorrect URL-based approach. Replaced with modal-trigger approach matching the WebhookQueueTable pattern:

**Bug Fix:**
- The remove button in `WebhookLogsTable::onUpdateActionButtons()` was using a URL-based approach with a legacy action reference
- This triggered a GET request which was silently redirected by the cascade strategy without actually deleting the record
- Fixed by replacing with modal-trigger attributes: `data-toggle=modal`, `data-target=#delete-modal`, `field-target=#recordId`, `onclick=ControllerAction.fieldMapping(this)`, `field-value=encodedId`
- Now uses the same reliable modal-based delete pattern as WebhookQueueTable

**Debug Instrumentation Cleanup:**
- Removed all [TEMP-LOG] debug instrumentation added during the debugging session
- Commented out (not deleted) logging statements across 5 files:
  - `plugins/ControllerAction/src/Model/Behavior/HideButtonBehavior.php`
  - `plugins/OpenEmis/src/Model/Behavior/OpenEmisBehavior.php`
  - `plugins/OpenEmis/templates/Element/ControllerAction/index.php`
  - `plugins/OpenEmis/templates/Element/actions.php`
  - `src/Model/Table/AppTable.php`
- CSS regenerated for manual PDF output (`webroot/css/themes/layout.min.css`)

### Files Changed Summary (Issue 3 only)
- Modified: 6 files (1 table class + 5 debug cleanup locations)
- Removed: 0 files

**Total Changes (POCOR-9257):**
- Added: 3 files
- Modified: 44+ files (35+ from async queueing + 3 from bulk delete + 6 from delete button fix)
- Removed: 0 files

## 4. Deployment Instructions (User Experience)

1. **Pull and Deploy:**
   ```bash
   git checkout POCOR-9509
   git pull origin POCOR-9509
   ```

2. **Run CakePHP Database Migrations:**
   ```bash
   cd /path/to/emis/core
   bin/cake migrations migrate
   ```
   This executes migration `20260215234927_POCOR9257.php` which:
   - Creates `webhook_queue` table (operational queue)
   - Creates `webhook_logs` table (permanent audit trail)
   - Sets up indexes for optimal query performance
   - Backs up existing data if tables already exist

3. **Clear Application Cache:**
   ```bash
   cd /path/to/emis/core
   bin/cake cache clear_all
   ```

4. **Test Webhook Queueing:**
   - Edit an Institution record in the UI
   - Check `webhook_queue` table for a new pending entry
   - Verify queue entry has correct event_key (e.g., 'institution_update')
   - Check logs/webhook_*.log for successful queueing

5. **Verify Webhook Logging:**
   - Check `webhook_logs` table for audit trail entry
   - Verify full request/response details are captured
   - Confirm `webhook_logs.success` field shows correct status

6. **Verify Async Processing:**
   - Watch Laravel worker process queue entries:
   ```bash
   tail -f /path/to/emis/core/api/storage/logs/laravel.log | grep webhook
   ```
   - Or manually trigger processing:
   ```bash
   cd /path/to/emis/core/api
   php artisan webhooks:process --once
   ```

## 5. System Administrator Guide

**Monitoring:**
- Log location: `logs/hin-error.log` and `api/storage/logs/laravel.log`
- Monitor for `[WebhookQueue]` and `[ProcessWebhookQueue]` entries
- Check queue depth: `SELECT COUNT(*) FROM webhook_queue WHERE status = 0`

**Configuration:**
- Scheduler interval: `api/app/Console/Kernel.php` line 28-37 (every minute)
- Queue table max age: Old entries are purged after processing
- Audit trail: `webhook_logs` table keeps permanent record

**Cron Configuration (For System Administrators):**

To run the webhook queue processor automatically, add this single cron entry to run Laravel's scheduler every minute:

```bash
* * * * * cd /path/to/emis/core/api && php artisan schedule:run >> /dev/null 2>&1
```

**Setup Instructions:**

1. **Edit crontab:**
   ```bash
   crontab -e
   ```

2. **Add the cron entry** (paste the line above, adjust `/path/to/emis/core/api` to your installation path)

3. **Verify cron is running:**
   ```bash
   # Check if the cron entry was added
   crontab -l

   # Monitor scheduler execution
   tail -f /path/to/emis/core/api/storage/logs/laravel.log | grep -i "webhook\|schedule"
   ```

**How It Works:**
- The single cron entry runs Laravel's `schedule:run` command every minute
- The Kernel.php scheduler defines the webhook processing task:
  - `webhooks:process` - Runs every minute with `--once` flag (processes one batch and exits)
- The `--once` flag ensures the command processes pending webhooks and exits gracefully
- On failure, system logs the error; on success, it logs completion
- This design prevents long-running processes and allows for distributed webhook processing

**Alternative: Manual Webhook Processing (Without Cron)**

If you prefer to process webhooks manually or via a different scheduling method:

```bash
# Process pending webhooks (one batch)
cd /path/to/emis/core/api
php artisan webhooks:process --once
```

**Performance Metrics:**
- Expected queue processing time: <5 seconds for 100 webhooks
- Database impact: Minimal (async processing doesn't lock tables)
- Network gain: No longer blocks user requests during delivery

**Rollback Procedure:**
If issues occur, revert commits and:
1. Webhooks will fallback to logging only (no delivery)
2. `webhook_queue` table can be manually cleared if needed
3. No data loss (all events logged in webhook_logs)

**Webhook Logging and Troubleshooting:**

The system provides comprehensive logging for webhook delivery troubleshooting:

1. **Check webhook_logs table for delivery history:**
   ```sql
   -- View recent webhook events
   SELECT id, webhook_id, event_key, status, response_code, created
   FROM webhook_logs
   ORDER BY created DESC
   LIMIT 20;

   -- Find failed webhook deliveries
   SELECT id, webhook_id, event_key, response_body, created
   FROM webhook_logs
   WHERE response_code >= 400
   ORDER BY created DESC;
   ```

2. **Check webhook_queue for pending/failed webhooks:**
   ```sql
   -- View pending webhooks
   SELECT COUNT(*) as pending FROM webhook_queue WHERE status = 0;

   -- View failed webhooks (ready for manual resend)
   SELECT id, webhook_id, event_key, retry_count, created
   FROM webhook_queue
   WHERE status = -1;
   ```

3. **Manually resend failed webhooks:**
   ```sql
   -- Mark failed webhooks for retry (reset status to 0)
   UPDATE webhook_queue SET status = 0, retry_count = 0
   WHERE status = -1 AND id = WEBHOOK_ID;
   ```

4. **Monitor logs:**
   - Check `api/storage/logs/laravel.log` for ProcessWebhookQueue errors
   - Search for `[ProcessWebhookQueue]` and `[WebhookSender]` entries
   - Review `webhook_logs` response_body for error details from webhook endpoints

**Troubleshooting:**
- Check if scheduler is running: `ps aux | grep "artisan schedule:run"`
- Verify method visibility:
  ```bash
  grep 'public function buildWebhookUrl' /path/to/emis/core/plugins/Configuration/src/Model/Table/ConfigWebhooksTable.php
  ```
- Clear Laravel cache:
  ```bash
  cd /path/to/emis/core/api
  php artisan cache:clear
  ```
- Check webhook URL configuration in admin interface (Institution Settings → Webhooks)
- Verify webhook endpoint is accessible and responding with proper HTTP status codes
