<?php

namespace App\Models\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Facades\JWTAuth;

/**
 * UserActivityLog
 *
 * POCOR-9697 (Wave 3): mirror the CakePHP `user_activities` audit trail from
 * the Laravel API. Every create / update / delete of a user record fires an
 * insert into `user_activities` so that the existing Cake-side dashboard
 * (User → Activities) shows API-originated changes alongside UI-originated
 * ones.
 *
 * Shape (matches Cake schema verbatim — same dashboard reads both):
 *   model            'Users'
 *   model_reference  the security_users.id whose record changed
 *   field            the column name (or '*' for create/delete summary rows)
 *   field_type       'string' | 'integer' | 'record'
 *   old_value        previous value, truncated to 255 chars, '[REDACTED]' for
 *                    password / super_admin (the column type itself is varchar
 *                    255 — we never store the raw bcrypt hash there either)
 *   new_value        new value, same redaction policy
 *   operation        'create' | 'update' | 'delete'
 *   security_user_id the user the row is about (same as model_reference)
 *   created_user_id  the JWT caller, 0 when run from CLI / queue / seed
 *   created          now()
 *
 * Critical rules:
 *  1. NEVER persist password or super_admin values — only that they changed.
 *  2. NEVER let an audit-log failure break the underlying write — every
 *     insert is wrapped in try/catch and logged to the Laravel log on failure.
 *  3. CLI / queue / seed contexts have no JWT — fall back to created_user_id=0.
 */
trait UserActivityLog
{
    /**
     * Boot the trait — wire the Eloquent model events.
     */
    public static function bootUserActivityLog(): void
    {
        static::created(function ($model) {
            $model->logUserActivity('create');
        });

        static::updated(function ($model) {
            $model->logUserActivity('update');
        });

        static::deleted(function ($model) {
            $model->logUserActivity('delete');
        });
    }

    /**
     * Fields whose value must never be persisted into user_activities.
     * The row is still emitted so the dashboard sees the field changed;
     * the value is masked.
     */
    protected function userActivityRedactedFields(): array
    {
        return ['password', 'super_admin'];
    }

    /**
     * Fields whose changes should never produce an audit row at all
     * (low-signal noise like the audit-trail columns themselves).
     */
    protected function userActivityIgnoredFields(): array
    {
        return ['modified', 'modified_user_id', 'last_login', 'failed_logins'];
    }

    /**
     * Main dispatcher — called from the model events.
     */
    public function logUserActivity(string $operation): void
    {
        try {
            $callerId = $this->resolveAuditCallerId();
            $targetId = (int) $this->getKey();
            if ($targetId <= 0) {
                return; // nothing to audit if we don't know which user
            }

            if ($operation === 'update') {
                $this->logUpdateDirtyFields($targetId, $callerId);
                return;
            }

            // create / delete — single summary row
            $this->insertUserActivityRow([
                'model'            => 'Users',
                'model_reference'  => $targetId,
                'field'            => '*',
                'field_type'       => 'record',
                'old_value'        => null,
                'new_value'        => null,
                'operation'        => $operation,
                'security_user_id' => $targetId,
                'created_user_id'  => $callerId,
                'created'          => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // Audit failures must never break the underlying write.
            Log::warning('[POCOR-9697 UserActivityLog] insert failed: ' . $e->getMessage(), [
                'operation' => $operation,
                'model'     => static::class,
                'id'        => $this->getKey(),
            ]);
        }
    }

    /**
     * Emit one row per dirty field on update.
     */
    protected function logUpdateDirtyFields(int $targetId, int $callerId): void
    {
        $dirty   = $this->getDirty();
        $ignored = array_flip($this->userActivityIgnoredFields());
        $redact  = array_flip($this->userActivityRedactedFields());
        $now     = date('Y-m-d H:i:s');

        foreach ($dirty as $field => $newValue) {
            if (isset($ignored[$field])) {
                continue;
            }

            $oldValue = $this->getOriginal($field);
            $isRedacted = isset($redact[$field]);

            $this->insertUserActivityRow([
                'model'            => 'Users',
                'model_reference'  => $targetId,
                'field'            => $field,
                'field_type'       => $this->inferFieldType($newValue),
                'old_value'        => $isRedacted ? '[REDACTED]' : $this->stringifyForAudit($oldValue),
                'new_value'        => $isRedacted ? '[REDACTED]' : $this->stringifyForAudit($newValue),
                'operation'        => 'update',
                'security_user_id' => $targetId,
                'created_user_id'  => $callerId,
                'created'          => $now,
            ]);
        }
    }

    /**
     * Resolve the JWT caller id, falling back to 0 when no request context
     * (CLI, queue worker, factory in tests' setUp).
     */
    protected function resolveAuditCallerId(): int
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if ($user && isset($user->id)) {
                return (int) $user->id;
            }
        } catch (\Throwable $e) {
            // no JWT in this context — fall through to system fallback
        }

        // Fallback: whoever the model has stamped as the actor.
        $modified = $this->getAttribute('modified_user_id');
        if ($modified !== null && $modified !== '') {
            return (int) $modified;
        }
        $created = $this->getAttribute('created_user_id');
        if ($created !== null && $created !== '') {
            return (int) $created;
        }

        return 0;
    }

    /**
     * Direct INSERT — bypassing the UserActivities Eloquent model keeps this
     * trait independent of model-level side-effects and avoids any chance of
     * recursive audit when UserActivities itself gets touched.
     */
    protected function insertUserActivityRow(array $row): void
    {
        DB::table('user_activities')->insert($row);
    }

    protected function inferFieldType($value): string
    {
        if (is_int($value)) {
            return 'integer';
        }
        if (is_bool($value)) {
            return 'integer';
        }
        if (is_float($value)) {
            return 'decimal';
        }
        return 'string';
    }

    /**
     * Coerce a value to a string short enough for the varchar(255) column.
     */
    protected function stringifyForAudit($value): ?string
    {
        if ($value === null) {
            return null;
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
        }
        $value = (string) $value;
        if (strlen($value) > 255) {
            $value = substr($value, 0, 252) . '...';
        }
        return $value;
    }

    /**
     * Static entry-point for code paths that bypass Eloquent events — e.g.
     * v4 UserRepository::addUsers calls SecurityUsers::insert() (query
     * builder) which never fires `created`. Those paths can opt-in by calling
     * UserActivityLog::logExternalUserChange('create', $newUserId).
     *
     * The shape matches what the trait writes from Eloquent events so a
     * single dashboard query covers both origins.
     *
     * @param string $operation 'create' | 'update' | 'delete'
     * @param int    $targetUserId security_users.id whose row changed
     * @param array  $changedFields field => ['old' => ..., 'new' => ...] for 'update'
     *                              — ignored for create / delete
     */
    public static function logExternalUserChange(
        string $operation,
        int $targetUserId,
        array $changedFields = []
    ): void {
        try {
            if ($targetUserId <= 0) {
                return;
            }
            $callerId = self::resolveExternalAuditCallerId();
            $now      = date('Y-m-d H:i:s');
            $redact   = array_flip(['password', 'super_admin']);

            if ($operation === 'update' && !empty($changedFields)) {
                foreach ($changedFields as $field => $delta) {
                    $isRedacted = isset($redact[$field]);
                    DB::table('user_activities')->insert([
                        'model'            => 'Users',
                        'model_reference'  => $targetUserId,
                        'field'            => (string) $field,
                        'field_type'       => 'string',
                        'old_value'        => $isRedacted ? '[REDACTED]' : self::staticStringifyForAudit($delta['old'] ?? null),
                        'new_value'        => $isRedacted ? '[REDACTED]' : self::staticStringifyForAudit($delta['new'] ?? null),
                        'operation'        => 'update',
                        'security_user_id' => $targetUserId,
                        'created_user_id'  => $callerId,
                        'created'          => $now,
                    ]);
                }
                return;
            }

            DB::table('user_activities')->insert([
                'model'            => 'Users',
                'model_reference'  => $targetUserId,
                'field'            => '*',
                'field_type'       => 'record',
                'old_value'        => null,
                'new_value'        => null,
                'operation'        => $operation,
                'security_user_id' => $targetUserId,
                'created_user_id'  => $callerId,
                'created'          => $now,
            ]);
        } catch (\Throwable $e) {
            Log::warning('[POCOR-9697 UserActivityLog::logExternalUserChange] ' . $e->getMessage(), [
                'operation' => $operation,
                'target'    => $targetUserId,
            ]);
        }
    }

    protected static function resolveExternalAuditCallerId(): int
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
            if ($user && isset($user->id)) {
                return (int) $user->id;
            }
        } catch (\Throwable $e) {
            // CLI / queue context — fall through.
        }
        return 0;
    }

    protected static function staticStringifyForAudit($value): ?string
    {
        if ($value === null) {
            return null;
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
        }
        $value = (string) $value;
        if (strlen($value) > 255) {
            $value = substr($value, 0, 252) . '...';
        }
        return $value;
    }
}
