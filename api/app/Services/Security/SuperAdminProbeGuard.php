<?php

namespace App\Services\Security;

use App\Models\Api5\SecurityUsers as Api5SecurityUsers;
use App\Models\SecurityUsers;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * POCOR-9710 — security-users probe detection + password carve-out.
 *
 * The HidesSuperAdmins global scope (App\Models\Concerns\HidesSuperAdmins)
 * already does the actual row hiding: non-super-admin callers cannot see
 * super_admin = 1 rows in any Eloquent query. This service adds the *audit
 * trail* on top — when a caller's query is shaped specifically to fetch
 * super-admins, we log it so ops can grep enumeration attempts.
 *
 * Fingerprint (Khindol 2026-05-19): run the caller's filter twice against
 * the SCOPE-BYPASSED table — once unconstrained, once narrowed to
 * `super_admin = 1`. If both counts are equal AND > 0, every match is a
 * super-admin → the caller is fishing. For single-fetch (`/security-users/5`
 * or `/api/v4/users/5`) the same idea reduces to "does this one id resolve
 * to a super-admin row?".
 *
 * Applies symmetrically to:
 *   - /api/v5/security-users     (CrudApiController, v5)
 *   - /api/v4/users              (UserController, v4)
 *
 * Q1 carve-out: non-super-admin callers cannot set or update `password` via
 * the generic CRUD path. Strip silently + log; never echo the field name
 * back (anti-fingerprinting, per feedback_silent_security_rejections.md).
 */
class SuperAdminProbeGuard
{
    public static function isSuperAdmin(?Authenticatable $caller): bool
    {
        return $caller && (int) ($caller->super_admin ?? 0) === 1;
    }

    /**
     * Single-fetch / single-target probe.
     *
     * @param class-string $modelClass App\Models\SecurityUsers or Api5\SecurityUsers.
     * @param array        $segments   URL segments after the resource key.
     * @return bool True iff the target id resolves to a super_admin = 1 row.
     */
    public function probesSingleSuperAdminTarget(string $modelClass, array $segments): bool
    {
        if (!$this->modelIsSecurityUsers($modelClass)) {
            return false;
        }
        $id = $this->extractSingleId($segments);
        if ($id === null) {
            return false;
        }
        return $this->idIsSuperAdmin($modelClass, $id);
    }

    public function idIsSuperAdmin(string $modelClass, int $id): bool
    {
        if (!$this->modelIsSecurityUsers($modelClass)) {
            return false;
        }
        return $modelClass::withoutGlobalScope('hideSuperAdmins')
            ->where('id', $id)
            ->where('super_admin', 1)
            ->exists();
    }

    /**
     * List-probe two-count fingerprint. Builder must be SCOPE-BYPASSED with
     * the caller's filters already applied, so we are comparing what THIS
     * caller asked for, not the whole table.
     */
    public function probesOnlySuperAdmins(Builder $unscopedFilteredQuery): bool
    {
        $countAll = (clone $unscopedFilteredQuery)->count();
        if ($countAll === 0) {
            return false;
        }
        $countSuper = (clone $unscopedFilteredQuery)->where('super_admin', 1)->count();
        return $countAll === $countSuper;
    }

    public function logProbe(Request $request, ?Authenticatable $caller, array $extra = []): void
    {
        Log::warning('POCOR-9710: security-users probe detected', array_merge([
            'endpoint'        => $request->path(),
            'method'          => $request->method(),
            'caller_id'       => $caller?->id,
            'caller_username' => $caller?->username ?? null,
            'ip'              => $request->ip(),
            'query'           => $request->query(),
        ], $extra));
    }

    /**
     * Q1 — silent-strip password when caller isn't super-admin.
     * Mutator on the model still hashes anything that survives, so this
     * is an extra defense layer, not the only one.
     */
    public function stripPasswordIfNotSuperAdmin(array $data, ?Authenticatable $caller, Request $request): array
    {
        if (!array_key_exists('password', $data) || self::isSuperAdmin($caller)) {
            return $data;
        }
        Log::warning('POCOR-9710: password supplied by non-super-admin — silently stripped', [
            'endpoint'  => $request->path(),
            'method'    => $request->method(),
            'caller_id' => $caller?->id,
            'ip'        => $request->ip(),
        ]);
        unset($data['password']);
        return $data;
    }

    private function modelIsSecurityUsers(string $modelClass): bool
    {
        return is_a($modelClass, SecurityUsers::class, true)
            || is_a($modelClass, Api5SecurityUsers::class, true);
    }

    private function extractSingleId(array $segments): ?int
    {
        if (count($segments) === 1 && ctype_digit((string) $segments[0])) {
            return (int) $segments[0];
        }
        return null;
    }
}
