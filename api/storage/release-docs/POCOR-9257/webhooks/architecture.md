# Webhook System Architecture

> **Feature:** POCOR-9257 — Async Webhook Queue

---

## Overview

The webhook system bridges OpenEMIS data events to external HTTP endpoints. It is built in two layers:

1. **Queuing layer** — intercepts data changes in CakePHP and Laravel and writes them to `webhook_queue`
2. **Delivery layer** — a background processor reads the queue and sends the HTTP requests

The two layers are completely decoupled. The queuing layer never makes network calls. The delivery layer never touches business logic.

---

## Queuing Layer

### CakePHP: `WebhookQueueBehavior`

Attached via `$this->addBehavior('WebhookQueue', [...])` in CakePHP table classes.

```php
$this->addBehavior('WebhookQueue', [
    'entity_create' => 'student_create',
    'entity_update' => 'student_update',
    'entity_delete' => 'student_delete',
    'table_alias'   => 'Institution.Students',
    'contain'       => [],
]);
```

**Hooks:**
- `afterSave` — fires `entity_create` (new) or `entity_update` (existing). Skips `InstitutionClasses` and `InstitutionSubjects` (legacy compatibility).
- `afterDelete` — fires `entity_delete`
- `afterFullSave` — fires same as afterSave (for complex multi-step saves)

**Skips** if `options['skip_callbacks']` is set (import operations).

### Laravel API5: `WebhookQueueTrait`

Mixed into Eloquent models via `use WebhookQueueTrait;`.

```php
class Institutions extends Model
{
    use WebhookQueueTrait;

    protected $webhookEvents = ['created', 'updated', 'deleted'];
    protected $webhookRelations = [];           // Optional: eager-load relations
    protected $webhookExcludedFields = [];      // Optional: extra fields to strip
    protected $webhookEventPrefix = null;       // Optional: override auto-generated key prefix
}
```

**Event key generation:**
```
// Default: singularize table name + action
institution_students  →  institution_student_create
security_users        →  security_user_update

// With prefix:
areas (prefix = 'area_education_')  →  area_education_delete
```

**Sensitive fields always excluded:** `password`, `super_admin`, `_content`, `remember_token`, plus any listed in `$webhookExcludedFields`.

---

## Queue Lookup

Both paths share identical logic for looking up which webhooks to fire:

```sql
SELECT w.id, w.url, w.query_template, w.body_template, w.method, w.event_key
FROM webhooks w
INNER JOIN config_items ci ON ci.id = w.external_data_source_id
WHERE w.event_key = :event_key
  AND w.status = 1        -- Active
  AND ci.value = 1        -- External data source Active
```

If no rows match, nothing is queued. No error is raised — absence of a configured webhook is normal.

---

## Template Resolution

At queue time (before writing to `webhook_queue`), placeholders in `query_template` and `body_template` are replaced:

```
Template:  {"id": "${id}", "school": "${institution_id}"}
Entity:    {id: 42, institution_id: 7, ...}
Result:    {"id": "42", "school": "7"}
```

**Placeholder syntax:** `${field_name}` — flat field access only. Replacement is done via regex: `/\$\{([a-zA-Z0-9_]+)\}/`.

Unknown placeholders are kept as-is.

If no `body_template` is set, the full entity array (with relations loaded) is serialized as JSON.

---

## Delivery Layer

### `ProcessWebhookQueue` (Artisan command)

**Signature:** `webhooks:process [--limit=100] [--once] [--max-retries=3]`

**Scheduling:** Laravel Kernel runs it every minute with `--once --withoutOverlapping()`.

**Processing loop:**
1. SELECT `status=0`, `available_at <= now()`, `(next_retry_at IS NULL OR <= now())`, ORDER BY `created ASC`, LIMIT `--limit`
2. For each entry, wrapped in `DB::transaction()`:
   - UPDATE `status = 1` (PROCESSING)
   - Call `WebhookSender::send()`
   - On success: UPDATE `status = 2`, write response fields, INSERT into `webhook_logs`
   - On failure: increment `retry_count`, set `next_retry_at` (exponential backoff), or set `status = -1` if exhausted, INSERT into `webhook_logs`
3. Continue batches until empty (unless `--once`)

**Transaction safety:** Each webhook is processed in its own transaction. A crash in one does not affect others.

### `WebhookSender`

HTTP client wrapper around Guzzle. Handles:
- All HTTP methods (GET/POST/PUT/PATCH/DELETE)
- Custom headers
- Auth header injection (bearer, basic, api_key, hmac)
- Response parsing (status, body, duration)
- Timeout enforcement (connect timeout + read timeout)
- Response body truncation (10,000 char limit)

---

## Data Flow Diagram

```
User action (UI or API)
        │
        ▼
┌──────────────────────────────────────────────────────┐
│              QUEUING LAYER                           │
│                                                      │
│  CakePHP Table         Laravel Eloquent Model        │
│  afterSave/Delete  OR  created/updated/deleted       │
│        │                      │                      │
│        ▼                      ▼                      │
│  ConfigWebhooks lookup ── DB::table('webhooks')      │
│  (event_key + status + config_item checks)           │
│        │                      │                      │
│        ▼                      ▼                      │
│  Resolve URL + body template placeholders            │
│        │                                             │
│        ▼                                             │
│  INSERT INTO webhook_queue (status=0, PENDING)      │
└──────────────────────────────────────────────────────┘
        │
        │  (async, every minute)
        ▼
┌──────────────────────────────────────────────────────┐
│              DELIVERY LAYER                          │
│                                                      │
│  ProcessWebhookQueue                                │
│        │                                             │
│        ▼                                             │
│  SELECT pending webhook_queue entries               │
│  (status=0, available_at<=now)                       │
│        │                                             │
│        ▼                                             │
│  UPDATE status=1 (PROCESSING)                        │
│        │                                             │
│        ▼                                             │
│  WebhookSender::send()  →  HTTP request              │
│        │                                             │
│   success?   ──Yes──►  status=2 (SENT)              │
│       │No                                            │
│        ▼                                             │
│  retry_count < max? ──Yes──► backoff + status=0      │
│       │No                                            │
│        ▼                                             │
│  status=-1 (FAILED)                                  │
│        │                                             │
│        ▼                                             │
│  INSERT INTO webhook_logs (audit trail)              │
└──────────────────────────────────────────────────────┘
```

---

## Graceful Degradation

All queuing code is wrapped in `try/catch(\Throwable $e)`. Any exception during webhook queuing:
- Is logged to the Laravel/CakePHP error log
- Does **not** throw an exception back to the caller
- Does **not** prevent the originating save/delete from completing

The webhook system is strictly additive — it cannot break a user's CRUD operation.
