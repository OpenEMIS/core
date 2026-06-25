<?php

namespace App\Services;

use App\Models\SecurityGroupUsers;
use App\Models\SecurityRoleFunction;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon; // POCOR-9085


class PermissionService
{
    protected $user;
    protected $roleIds = [];
    protected $groupIds = []; // POCOR-9352
    protected $institutionIds = [];
    protected $allowAllInstitutions = 0;

    // POCOR-8966

    public function __construct()
    {

        $this->user = JWTAuth::user();
        if ($this->user) {
            $this->loadUserPermissions();
        }
    }

    private function loadUserPermissions()
    {
        $userId = $this->user->id;
        // POCOR-8966 start
        // If user is super admin, override permissions
        if ($this->user->super_admin ?? 0) {
            $this->allowAllInstitutions = 1;
            return;
        }
        // POCOR-8966 end
        $this->roleIds = SecurityGroupUsers::where('security_user_id', $userId)
            ->pluck('security_role_id')
            ->unique()
            ->toArray();
        $this->groupIds = SecurityGroupUsers::where('security_user_id', $userId) // POCOR-9352
            ->pluck('security_group_id')
            ->unique()
            ->toArray();

        $securityGroupUsers = SecurityGroupUsers::join('security_groups', 'security_groups.id', '=', 'security_group_users.security_group_id')
            ->join('security_group_institutions', 'security_group_institutions.security_group_id', '=', 'security_groups.id')
            ->where('security_group_users.security_user_id', $userId)
            ->select(
                'security_group_users.security_group_id',
                'security_group_users.security_role_id',
                'security_group_institutions.institution_id'
            )
            ->get();

        $this->institutionIds = $securityGroupUsers->pluck('institution_id')->unique()->toArray();


        // Ensure uniqueness
        $this->institutionIds = array_unique($this->institutionIds);

        // Fetch additional institution permissions
        $groupAreaInstitutions = $this->getGroupAreaInstitutions($this->groupIds); // POCOR-9352
        $this->allowAllInstitutions = $groupAreaInstitutions['allowAllInstitutions'] ?? 0;
        $this->institutionIds = array_unique(array_merge($this->institutionIds, $groupAreaInstitutions['institutionIds'] ?? []));

    }

    public function checkPermission($modelName, $action): bool
    {
        // POCOR-9092 autoloaded cache
        if (!$this->user) {
            $this->user = JWTAuth::user();
            if ($this->user) {
                $this->loadUserPermissions();
            }
        }

        $user = $this->user;
        if (!$user) {
            return false;
        }

        if ($user->super_admin ?? 0) {
            return true;
        }

        $cacheKey = "permissions:user:{$user->id}";
        $cacheTTL = 600; // seconds (10 min)

        $permissions = Cache::get($cacheKey);

        if (empty($permissions)) {
            // Log::info("Permissions cache miss for user {$user->id}. Reloading from DB.");
            $permissions = $this->loadPermissionsFromDb($user->id);

            if (!empty($permissions)) {
                Cache::put($cacheKey, $permissions, $cacheTTL);
            } else {
                // Log::warning("No permissions found for user {$user->id} after DB reload.");
                return false;
            }
        }

        return $this->hasPermission($permissions, $modelName, $action);
    }


    private function loadPermissionsFromDb($userId): array
    {
        $roleIds = SecurityGroupUsers::where('security_user_id', $userId)
            ->pluck('security_role_id')
            ->unique()
            ->toArray();

        if (empty($roleIds)) {
            return [];
        }

        $roleFunctions = SecurityRoleFunction::join('security_functions', 'security_functions.id', '=', 'security_role_functions.security_function_id')
            ->select(
                'security_role_functions._view',
                'security_role_functions._edit',
                'security_role_functions._add',
                'security_role_functions._delete',
                'security_role_functions._execute',
                'security_functions.module',
                'security_functions._view as security_function_view',
                'security_functions._edit as security_function_edit',
                'security_functions._add as security_function_add',
                'security_functions._delete as security_function_delete',
                'security_functions._execute as security_function_execute'
            )
            ->whereIn('security_role_id', $roleIds)
            ->where(function ($query) {
                $query->where('security_role_functions._view', 1)
                    ->orWhere('security_role_functions._edit', 1)
                    ->orWhere('security_role_functions._add', 1)
                    ->orWhere('security_role_functions._delete', 1)
                    ->orWhere('security_role_functions._execute', 1);
            })
            ->get()->toArray();


        $permissions = [];
//        Log::info("Role IDS: " . print_r($roleIds,true));
//        Log::info("Role Functions: " . print_r($roleFunctions,true));
        foreach ($roleFunctions as $roleFunction) {
            foreach (['_view', '_edit', '_add', '_delete', '_execute'] as $perm) {
//                Log::info("Checking permission: $perm");
//                Log::info("Role Function: " . print_r($roleFunction["perm"],true));
                if ($roleFunction["$perm"]) { // If role has permission (boolean 1)
                    $permissionList = explode('|', $roleFunction["security_function$perm"] ?? '');
                    $permissionList = array_filter(array_map('trim', $permissionList));

                    // 🔹 Merge instead of replace
                    if (!isset($permissions[$roleFunction["module"]][$perm])) {
                        $permissions[$roleFunction["module"]][$perm] = [];
                    }
                    $permissions[$roleFunction["module"]][$perm] = array_unique(
                        array_merge($permissions[$roleFunction["module"]][$perm], $permissionList)
                    );
                }
            }
        }


        return $permissions;
    }

    private function hasPermission($permissions, $modelName, $action): bool
    {
        foreach ($permissions as $module => $permTypes) {
            foreach (['_view', '_edit', '_add', '_delete', '_execute'] as $perm) {
                if (isset($permTypes[$perm])) {
                    $permValues = $permTypes[$perm];
                    // POCOR-8966 start
                    // 🔹 Check for specific model-based permission like "InstitutionStudents.view"
                    if (in_array("$modelName.$action", $permValues, true)) {
                        return true;
                    }
                    // POCOR-8966 end
                }
            }
        }
        return false;
    }


    public function getInstitutionIds()
    {
        return $this->institutionIds;
    }

    public function getAllowAllInstitutions()
    {
        return $this->allowAllInstitutions;
    }

    function getGroupAreaInstitutions(array $groupIds): array
    {
        try {
//            Log::debug(print_r([$groupIds],true));
            if (empty($groupIds)) return ['allowAllInstitutions' => 0, 'institutionIds' => []];

            $groupAreas = DB::table('security_group_areas')
                ->whereIn('security_group_id', $groupIds)
                ->pluck('area_id')
                ->toArray();

            if (in_array(1, $groupAreas, true)) {
                return ['allowAllInstitutions' => 1, 'institutionIds' => []];
            }

            $areaIds = $this->getChildrenIdFromDb($groupAreas);

            $institutionIds = DB::table('institutions')
                ->whereIn('area_id', $areaIds)
                ->pluck('id')
                ->toArray();
//            Log::debug(print_r([$groupIds, $groupAreas, $areaIds, $institutionIds],true));
            return ['allowAllInstitutions' => 0, 'institutionIds' => $institutionIds];
        } catch (\Exception $e) {
            Log::error("Error in getGroupAreaInstitutions: " . $e->getMessage());
            return ['allowAllInstitutions' => 0, 'institutionIds' => []];
        }
    }

    // POCOR-9352
    function getChildrenIdFromDb(array $parentAreaIds): array
    {
        $seen = array_values(array_unique($parentAreaIds));
        $frontier = $seen;

        while (!empty($frontier)) {
            $children = DB::table('areas')
                ->whereIn('parent_id', $frontier)
                ->pluck('id')
                ->toArray();

            if (empty($children)) {
                break;
            }

            $children = array_values(array_unique($children));
            $new = array_values(array_diff($children, $seen));

            if (empty($new)) {
                break; // больше нечего расширять — выходим
            }

            $seen = array_merge($seen, $new);
            $frontier = $new;
        }

        return array_values(array_unique($seen));
    }
}
