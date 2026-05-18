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
 * insert into `user_activities` so the existing Cake-side dashboard
 * (User → Activities) renders API-originated rows next to UI-originated ones.
 *
 * --------------------------------------------------------------------------
 * Row shape — ports CakePHP `TrackActivityBehavior` (src/Model/Behavior/
 * TrackActivityBehavior.php) verbatim so a single dashboard query covers
 * both origins:
 *
 *   UPDATE  → one row per dirty field
 *             field       = column name
 *             field_type  = column type ('string' | 'integer' | 'decimal' …)
 *             old_value   = previous value (or '[REDACTED]' — see below)
 *             new_value   = new value      (or '[REDACTED]')
 *             operation   = 'edit'   ← matches Cake enum, not 'update'
 *
 *   DELETE  → one summary row, all four columns empty strings,
 *             operation   = 'delete'   (matches TrackActivityBehavior::afterDelete)
 *
 *   CREATE  → port of the missing CakePHP creation event (Cake's
 *             TrackActivityBehavior does not emit on create — but the
 *             dashboard already renders 'create' rows seeded by
 *             UsersController, so we adopt that shape).
 *             one summary row, all four columns empty strings,
 *             operation   = 'create'
 *
 * --------------------------------------------------------------------------
 * Logging policy — what fires a Laravel `Log::warning` / `Log::info`:
 *
 * We do NOT log normal CRUD activity (the `user_activities` row IS the audit
 * trail for that). The Laravel log is reserved for SUSPICIOUS or DANGEROUS
 * events that an SOC / ops engineer should be alertable on:
 *
 *   • `password` or `super_admin` value supplied in a request body
 *   • Filter probe targeting `password` / `super_admin` / `remember_token`
 *     (read-side enumeration attempt)
 *   • `created_user_id` / `modified_user_id` forgery in the payload
 *   • ACL denial against SecurityUsers endpoints
 *   • Audit-row INSERT itself failing (defensive try/catch — see this trait)
 *
 * Everything else is silent. The audit table records the WHAT; the Laravel
 * log records the SUSPICIOUS attempts only.
 *
 * --------------------------------------------------------------------------
 * Hard-delete of `security_users` is schema-blocked:
 *
 * `user_activities.security_user_id` has a FK to `security_users.id` with
 * `ON DELETE RESTRICT`. Any single `create` / `edit` row about a user
 * therefore blocks the hard-delete of that user — and the trait writes
 * one on every create / edit, so once a user has been touched they
 * cannot be hard-deleted. In practice OpenEMIS soft-deletes users via
 * the `status` column, which is unaffected by this FK; the production
 * `user_activities` table holds zero `delete` rows for this reason.
 *
 * The per-field delete-snapshot code below (`logDeleteSnapshot`) is
 * dormant infrastructure for `security_users` — it ships ready for the
 * day the FK is ever relaxed to `ON DELETE SET NULL`, or for any other
 * table that adopts this trait without that constraint. If the snapshot
 * branch fires under the current FK, the try/catch around the inserts
 * swallows the inevitable FK-violation without breaking the request.
 *
 * --------------------------------------------------------------------------
 * Critical rules:
 *  1. NEVER persist `password` or `super_admin` values — emit the row to
 *     prove the column changed, but force old/new to '[REDACTED]'. The
 *     column type itself is `varchar(255)` so a bcrypt hash would not even
 *     fit cleanly; the redaction guards the trail regardless.
 *  2. NEVER let an audit-log failure break the underlying write — every
 *     insert is wrapped in try/catch and surfaces to Laravel log on failure.
 *  3. CLI / queue / seed contexts have no JWT — fall back to
 *     `created_user_id = 0`.
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
     *
     * POCOR-9697: photo_content is `longblob` — storing it makes no sense
     * (binary garbage, blows the varchar(255) column). On edit we write
     * '[REDACTED]' to mark the change; if the field ever appears in a
     * delete-snapshot row, write '[...]' as the placeholder.
     */
    protected function userActivityRedactedFields(): array
    {
        return ['password', 'super_admin', 'photo_content'];
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

            $now = date('Y-m-d H:i:s');

            //POCOR-9697: create / delete — always emit the summary row first
            //(matches CakePHP TrackActivityBehavior::afterDelete shape: empty
            //strings for field / field_type / old_value / new_value). The
            //dashboard renders this as the "user was created / deleted" event.
            $this->insertUserActivityRow([
                'model'            => 'Users',
                'model_reference'  => $targetId,
                'field'            => '',
                'field_type'       => '',
                'old_value'        => '',
                'new_value'        => '',
                'operation'        => $operation, //'create' or 'delete'
                'security_user_id' => $targetId,
                'created_user_id'  => $callerId,
                'created'          => $now,
            ]);

            //POCOR-9697: on delete, additionally snapshot every column into
            //its own row so the deleted user can be reconstructed / inspected
            //post-mortem. password and super_admin stay '[REDACTED]';
            //photo_content (longblob) becomes '[...]'.
            if ($operation === 'delete') {
                $this->logDeleteSnapshot($targetId, $callerId, $now);
            }
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
                'old_value'        => $isRedacted ? $this->redactPlaceholder($field, 'edit') : $this->stringifyForAudit($oldValue),
                'new_value'        => $isRedacted ? $this->redactPlaceholder($field, 'edit') : $this->stringifyForAudit($newValue),
                'operation'        => 'edit', //POCOR-9697: 'edit' matches CakePHP TrackActivityBehavior — not 'update'
                'security_user_id' => $targetId,
                'created_user_id'  => $callerId,
                'created'          => $now,
            ]);
        }
    }

    /**
     * POCOR-9697: snapshot every column of the deleted entity into its own
     * audit row. Lets a forensic / undelete path reconstruct who the user
     * was. Skips the ignored list (audit timestamps, last_login, etc.) and
     * the primary key.
     */
    protected function logDeleteSnapshot(int $targetId, int $callerId, string $now): void
    {
        $ignored = array_flip($this->userActivityIgnoredFields());
        $redact  = array_flip($this->userActivityRedactedFields());

        foreach ($this->getAttributes() as $field => $value) {
            if ($field === $this->getKeyName() || isset($ignored[$field])) {
                continue;
            }

            $isRedacted = isset($redact[$field]);

            $this->insertUserActivityRow([
                'model'            => 'Users',
                'model_reference'  => $targetId,
                'field'            => $field,
                'field_type'       => $this->inferFieldType($value),
                'old_value'        => $isRedacted ? $this->redactPlaceholder($field, 'delete') : $this->stringifyForAudit($value),
                'new_value'        => '', //POCOR-9697: nothing remains after delete
                'operation'        => 'delete',
                'security_user_id' => $targetId,
                'created_user_id'  => $callerId,
                'created'          => $now,
            ]);
        }
    }

    /**
     * POCOR-9697: pick the placeholder for a redacted field.
     * - photo_content on a delete snapshot → '[...]' (binary, omitted)
     * - everything else (password, super_admin, photo_content on edit)
     *   → '[REDACTED]'
     */
    protected function redactPlaceholder(string $field, string $operation): string
    {
        if ($operation === 'delete' && $field === 'photo_content') {
            return '[...]';
        }
        return '[REDACTED]';
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
            //POCOR-9697: keep the static helper's redact list aligned with
            //the instance method's userActivityRedactedFields() — photo_content
            //is longblob, no value in storing it.
            $redact   = array_flip(['password', 'super_admin', 'photo_content']);

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
                        'operation'        => 'edit', //POCOR-9697: 'edit' matches CakePHP TrackActivityBehavior
                        'security_user_id' => $targetUserId,
                        'created_user_id'  => $callerId,
                        'created'          => $now,
                    ]);
                }
                return;
            }

            //POCOR-9697: create / delete via the query-builder path — emit
            //the summary row only. Per-field delete snapshot lives on the
            //Eloquent trait (logDeleteSnapshot) because it needs the entity
            //to read attribute values; query-builder callers don't have one.
            //If a query-builder caller needs the full snapshot, fetch the
            //row first with SecurityUsers::find() and call ->delete() so the
            //Eloquent `deleted` event fires.
            DB::table('user_activities')->insert([
                'model'            => 'Users',
                'model_reference'  => $targetUserId,
                'field'            => '',
                'field_type'       => '',
                'old_value'        => '',
                'new_value'        => '',
                'operation'        => $operation, //'create' or 'delete'
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
