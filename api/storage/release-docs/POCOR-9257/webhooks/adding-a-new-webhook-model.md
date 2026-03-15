# Adding Webhook Support to a New Model

> **Feature:** POCOR-9257

This guide covers how to wire up webhook queuing for a new model in either the CakePHP or Laravel API5 layer.

---

## CakePHP: Attaching `WebhookQueueBehavior`

Open the table class (`src/Model/Table/YourTable.php` or `plugins/Plugin/src/Model/Table/YourTable.php`) and add the behavior in `initialize()`:

```php
public function initialize(array $config): void
{
    parent::initialize($config);

    // ... other behaviors ...

    // POCOR-9257: Queue webhooks for async delivery
    $this->addBehavior('WebhookQueue', [
        'entity_create' => 'your_event_create',  // event key for new records
        'entity_update' => 'your_event_update',  // event key for edits
        'entity_delete' => 'your_event_delete',  // event key for deletes
        'table_alias'   => 'Plugin.YourTable',   // CakePHP table alias for data serialization
        'contain'       => [],                   // Optional: ['Association1', 'Association2']
    ]);
}
```

### Choosing Event Keys

Event keys must match what is configured in the **Webhooks** admin screen. Use one of the predefined keys from `ConfigWebhooksTable::$eventKeyOptions`, or add a new one there.

Naming convention: `{entity_singular}_{action}` e.g. `staff_leave_create`, `assessment_update`.

### `contain` Parameter

If you want related data included in the webhook payload, list the association names:

```php
'contain' => ['Users', 'Institutions'],
```

The associations must be defined in the table's `initialize()` via `$this->belongsTo()`, `$this->hasMany()`, etc.

> **Note:** Including `contain` increases payload size and query cost. Only include associations that the external receiver needs.

---

## Laravel API5: Adding `WebhookQueueTrait`

Open the model (`api/app/Models/Api5/YourModel.php`) and add the trait:

```php
<?php
namespace App\Models\Api5;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\WebhookQueueTrait; //POCOR-9257: Async webhook queuing

class YourModel extends Model
{
    use HasFactory;
    use WebhookQueueTrait; //POCOR-9257: Queue webhooks on create/update/delete

    protected $table = 'your_table_name';

    // POCOR-9257: Which Eloquent events trigger webhook queuing
    protected $webhookEvents = ['created', 'updated', 'deleted'];

    // POCOR-9257: Optional - override auto-generated event key prefix
    // Default: singularized table name (e.g. 'your_table_name' → 'your_table_name_create')
    // Use this when the default key doesn't match what's in ConfigWebhooksTable
    // protected $webhookEventPrefix = 'custom_prefix_';

    // POCOR-9257: Optional - Eloquent relations to include in payload
    // protected $webhookRelations = ['relatedModel'];

    // POCOR-9257: Optional - additional fields to exclude from payload
    // protected $webhookExcludedFields = ['internal_field', 'temp_field'];

    // ... rest of model ...
}
```

### Event Key Generation

The trait auto-generates the event key:

```
table: your_table_name
action: create
→ event_key: your_table_name_create
```

For tables ending in `ies`:
```
table: categories → singular: category → category_create
```

For tables ending in `s`:
```
table: students → student → student_create
```

If this doesn't match the key configured in the webhook admin UI, set `$webhookEventPrefix`:
```php
protected $webhookEventPrefix = 'student_';
// → fires student_create, student_update, student_delete
```

### Selective Events

You don't have to fire all three events:

```php
// Only queue on create
protected $webhookEvents = ['created'];

// Only queue on create and delete
protected $webhookEvents = ['created', 'deleted'];
```

---

## Adding the Event Key to the Admin UI Dropdown

The admin UI event key dropdown is populated from `ConfigWebhooksTable::$eventKeyOptions`. To add a new key so admins can select it:

Open `plugins/Configuration/src/Model/Table/ConfigWebhooksTable.php` and add your key:

```php
private $eventKeyOptions = [
    // ... existing keys ...
    'your_event_create' => 'Your Entity Create',   //POCOR-XXXX: new event
    'your_event_update' => 'Your Entity Update',   //POCOR-XXXX: new event
    'your_event_delete' => 'Your Entity Delete',   //POCOR-XXXX: new event
];
```

---

## Verifying the Integration

1. **Trigger the event** (create/edit/delete a record in the affected model)
2. **Check the queue:**
   ```sql
   SELECT event_key, status, created FROM webhook_queue
   WHERE created >= NOW() - INTERVAL 5 MINUTE
   ORDER BY created DESC;
   ```
   If no entry appears, the queuing failed or no active webhook is configured for that event key.
3. **Check logs for queuing errors:**
   ```bash
   tail -100 /var/www/html/emis/core/api/storage/logs/laravel.log | grep "WebhookQueue\|WebhookQueueTrait"
   tail -100 /var/www/html/emis/core/logs/hin-error.log | grep "WebhookQueue"
   ```
4. **Manually process and check delivery:**
   ```bash
   docker exec poe-application /bin/sh -c \
     "cd /var/www/html/emis/core/api && php artisan webhooks:process --once"
   ```
