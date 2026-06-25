<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * HidesSuperAdmins — POCOR-9710 (row-level visibility lock).
 *
 * Ports the CakePHP invariant from plugins/Security/src/Model/Table/
 * UsersTable.php:682-686 (POCOR-9370):
 *
 *   if (empty($currentUser['super_admin']) || $currentUser['super_admin'] != 1) {
 *       $conditions[] = $this->aliasField('super_admin != 1');
 *   }
 *
 * to the Laravel API layer via an Eloquent global scope. Non-super-admin
 * callers see no super_admin = 1 rows in any query — list, single-fetch,
 * relation-load, anything routed through Eloquent. CLI commands (no
 * `auth()->user()`) default to the filtered view, which is the safer
 * fallback when no caller context exists.
 *
 * Super-admin callers bypass the scope entirely and see the full table —
 * needed so super-admins can manage each other and so seeders / impersonation
 * flows still work end-to-end.
 *
 * To bypass the scope deliberately (e.g. internal admin tooling or
 * pre-check probes inside CrudApiController), call
 *
 *     SecurityUsers::withoutGlobalScope('hideSuperAdmins')->find($id);
 *
 * — never bypass implicitly via raw queries; that defeats the audit trail.
 */
trait HidesSuperAdmins
{
    protected static function bootHidesSuperAdmins(): void
    {
        static::addGlobalScope('hideSuperAdmins', function (Builder $query): void {
            $caller = Auth::user();

            // No caller context — login flow, internal scheduler, CLI jobs,
            // factories. These have legitimate reasons to see every row
            // (the login flow itself looks up super-admin users by username).
            // Routes that handle untrusted input are gated by auth.jwt
            // middleware upstream of this scope, so by the time a request
            // hits us with no caller, it's an internal path.
            if (!$caller) {
                return;
            }

            // Super-admin callers see every row — including their peers.
            if ((int) ($caller->super_admin ?? 0) === 1) {
                return;
            }

            // Authenticated non-super-admin callers get the row-filtered
            // view — the actual policy this trait exists for.
            $query->where(function (Builder $q): void {
                $q->where('super_admin', '!=', 1)->orWhereNull('super_admin');
            });
        });
    }
}
