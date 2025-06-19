<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use App\Services\PermissionService;
use Tymon\JWTAuth\Facades\JWTAuth;

trait InstitutionScope
{
    protected static function bootInstitutionScope()
    {
        // Check if the model's table has an 'institution_id' column before doing anything
        $model = new static(); // Create an instance of the model
        if (!Schema::hasColumn($model->getTable(), 'institution_id')) {
            return; // No institution_id column, so no filtering needed
        }

        static::addGlobalScope('institutionAccess', function (Builder $query) {
            $user = JWTAuth::user();
            if (!$user) {
                return;
            }

            if ($user->super_admin ?? 0) {
                return; // Super admins can access everything
            }

            $permissionService = app(PermissionService::class);
            $allowedInstitutions = $permissionService->getInstitutionIds();

            if (!$permissionService->getAllowAllInstitutions()) {
                $query->whereIn('institution_id', $allowedInstitutions);
            }
        });
    }
}
