# Webhook Troubleshooting Guide

> **Feature:** POCOR-9257

---

## Diagnostic Flowchart

```
"My webhook is not working"
          │
          ▼
Is there a row in webhook_queue after the triggering action?
          │
    No ───┴─── Yes
    │               │
    ▼               ▼
[A] Not queuing  Is status 0 (pending) or -1 (failed)?
                    │               │
               status=0         status=-1
                    │               │
                    ▼               ▼
              [B] Not         [C] All retries
              processing       exhausted
```

---

## [A] Not Queuing — Nothing Appears in `webhook_queue`

Check each of these in order:

### 1. Is the webhook rule configured and active?

```sql
SELECT w.id, w.event_key, w.url, w.status,
       ci.name AS config_item, ci.value AS config_item_active
FROM webhooks w
JOIN config_items ci ON ci.id = w.external_data_source_id
WHERE w.event_key = 'your_event_key';
```

Both `w.status = 1` AND `ci.value = 1` must be true. If not, activate them in the admin UI.

### 2. Does the event key match?

The event key in the webhook rule must exactly match what the model generates. Case-sensitive, no trailing spaces.

CakePHP: check the `addBehavior('WebhookQueue', ['entity_create' => '...'])` call.
Laravel: check `$webhookEventPrefix` or the auto-generated key (table name singularized + `_action`).

```sql
-- See what event keys are being generated:
SELECT event_key, COUNT(*) AS count
FROM webhook_queue
WHERE created >= NOW() - INTERVAL 1 HOUR
GROUP BY event_key;
```

### 3. Is the table wired up?

CakePHP — check the table has `addBehavior('WebhookQueue', ...)` in `initialize()`.
Laravel — check the model has `use WebhookQueueTrait` and `$webhookEvents` defined.

### 4. Was `skip_callbacks` set?

CakePHP: imports and bulk operations set `$options['skip_callbacks'] = true`, which bypasses webhook queuing intentionally.

### 5. Is there a PHP error swallowing the queue call?

```bash
tail -50 /var/www/html/emis/core/logs/hin-error.log | grep "WebhookQueue\|Exception"
tail -50 /var/www/html/emis/core/api/storage/logs/laravel.log | grep "WebhookQueueTrait\|Exception"
```

---

## [B] Queued but Not Processing

### 1. Is the scheduler running?

```bash
crontab -l | grep artisan
ps aux | grep "schedule:run"
```

If no cron entry, add it:
```bash
* * * * * cd /var/www/html/emis/core/api && php artisan schedule:run >> /dev/null 2>&1
```

### 2. Manually run the processor

```bash
docker exec poe-application /bin/sh -c \
  "cd /var/www/html/emis/core/api && php artisan webhooks:process --once"
```

Watch for errors in the output.

### 3. Check `available_at`

```sql
SELECT id, event_key, available_at, next_retry_at, status, retry_count
FROM webhook_queue
WHERE status = 0
ORDER BY created DESC LIMIT 10;
```

If `available_at` is in the future, the entry is intentionally delayed (should not normally happen for first attempts — only retries).

### 4. Stalled `status = 1` entries

If the processor crashed, entries may be stuck in PROCESSING:

```sql
-- Check for stalled entries
SELECT id, event_key, status, modified
FROM webhook_queue
WHERE status = 1;

-- Reset stalled entries
UPDATE webhook_queue
SET status = 0
WHERE status = 1
  AND modified < NOW() - INTERVAL 5 MINUTE;
```

### 5. `WEBHOOK_ENABLED` setting

```bash
grep WEBHOOK_ENABLED /var/www/html/emis/core/api/.env
```

Must be `true` or absent. If set to `false`, change it and run `php artisan config:cache`.

---

## [C] All Retries Exhausted (status = -1)

### 1. Find the error

```sql
-- Check last error on the failed queue entry
SELECT id, event_key, target_url, retry_count, last_error, modified
FROM webhook_queue
WHERE status = -1
ORDER BY modified DESC LIMIT 10;

-- Check webhook_logs for the full delivery history
SELECT l.id, l.retry_attempt, l.response_status, l.error_message, l.duration_ms, l.created
FROM webhook_logs l
WHERE l.webhook_queue_id = <queue_id>
ORDER BY l.retry_attempt ASC;
```

### 2. Common error causes and fixes

| Error | Cause | Fix |
|-------|-------|-----|
| `cURL error 6: Could not resolve host` | DNS failure | Check DNS from server: `nslookup your-domain.com` |
| `cURL error 7: Connection refused` | Endpoint not reachable | Verify endpoint URL and firewall rules |
| `cURL error 28: Operation timed out` | Endpoint too slow | Increase `WEBHOOK_TIMEOUT` in `.env` |
| `SSL certificate verify failed` | Self-signed cert | Set `WEBHOOK_VERIFY_SSL=false` in `.env` (dev only) |
| `HTTP 401: Unauthorized` | Auth credentials wrong | Verify bearer token / API key |
| `HTTP 404: Not Found` | Wrong URL | Verify endpoint URL in webhook admin |
| `HTTP 500: Internal Server Error` | Receiver crashed | Check receiver application logs |

### 3. Resend a failed webhook

```sql
UPDATE webhook_queue
SET status = 0, retry_count = 0, next_retry_at = NULL, last_error = NULL
WHERE id = <queue_id>;
```

Then trigger the processor:
```bash
docker exec poe-application /bin/sh -c \
  "cd /var/www/html/emis/core/api && php artisan webhooks:process --once"
```

### 4. Bulk resend all failures for an event

```sql
UPDATE webhook_queue
SET status = 0, retry_count = 0, next_retry_at = NULL, last_error = NULL
WHERE status = -1 AND event_key = 'institution_update';
```

---

## Payload / Placeholder Issues

### Placeholder not replaced (appears as `${field_name}` in sent payload)

1. The field name must exist as a **top-level key** in the entity data at queue time.
2. Inspect an actual queued payload:
   ```sql
   SELECT payload FROM webhook_queue
   WHERE event_key = 'your_event_key'
   ORDER BY created DESC LIMIT 1;
   ```
3. The available keys are the JSON object's top-level property names.

### Relations not in payload

Laravel: add the relation name to `$webhookRelations` in the model.
CakePHP: add the association alias to the `'contain' => [...]` parameter in `addBehavior('WebhookQueue', ...)`.

---

## Performance Issues

### Queue depth growing faster than delivery

```sql
-- Check current backlog
SELECT COUNT(*) FROM webhook_queue WHERE status = 0;

-- Check processing rate over last hour
SELECT DATE_FORMAT(created, '%Y-%m-%d %H:%i') AS minute,
       COUNT(*) AS sent
FROM webhook_logs
WHERE success = 1
  AND created >= NOW() - INTERVAL 1 HOUR
GROUP BY minute
ORDER BY minute;
```

If the backlog is growing, the endpoint is too slow. Options:
- Increase `WEBHOOK_BATCH_SIZE` in `.env`
- Reduce `WEBHOOK_TIMEOUT` (fail faster on slow endpoints)
- Investigate the endpoint performance

### Large payloads

If `body_template` is empty, the full entity JSON is sent. For large entities with many fields or relations, this can be hundreds of KB per request. Use a `body_template` to send only the fields the receiver needs.

---

## Log Reference

| Log prefix | Source |
|------------|--------|
| `[WebhookQueueTrait]` | Laravel model — queuing events |
| `[WebhookQueue]` | CakePHP — `WebhookQueueTable::queueWebhook()` |
| `[WebhookQueue]` | CakePHP — `WebhookQueueBehavior` |
| `[ProcessWebhookQueue]` | Delivery processor (batch level) |
| `[WebhookSender]` | HTTP request/response details |
| `[WebhookScheduler]` | Laravel Kernel scheduler failures |

### Useful log searches

```bash
# All webhook activity
grep -i webhook /var/www/html/emis/core/api/storage/logs/laravel.log | tail -50

# Only errors
grep -E "\[WebhookSender\]|\[ProcessWebhookQueue\].*✗" \
  /var/www/html/emis/core/api/storage/logs/laravel.log | tail -20

# CakePHP webhook errors
grep -i webhook /var/www/html/emis/core/logs/hin-error.log | tail -20
```
