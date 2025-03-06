<?php

namespace App\Services;

use App\Models\SecurityGroupUsers;
use App\Models\SecurityRoleFunction;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Cache;



class PermissionService
{
    protected $user;
    protected $roleIds = [];
    protected $institutionIds = [];
    protected $allowAllInstitutions = 0;
    private array $commonViewModules = [
        'Areas',
        'Genders',
        'AreaAdministratives',
        'AreaLevels',
        'AcademicPeriods',
        'AcademicPeriodLevels',
        'ContactTypes'
        // Add more modules that should be always viewable
    ];

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
        $this->roleIds = SecurityGroupUsers::where('security_user_id', $userId)
            ->pluck('security_role_id')
            ->unique()
            ->toArray();

        $securityGroupUsers = SecurityGroupUsers::with('securityGroup', 'securityGroup.institutions')
            ->where('security_user_id', $userId)
            ->get();

        foreach ($securityGroupUsers as $sGU) {
            foreach ($sGU->securityGroup->institutions as $institution) {
                $this->institutionIds[] = $institution->institution_id;
            }
        }

        // Ensure uniqueness
        $this->institutionIds = array_unique($this->institutionIds);

        // Fetch additional institution permissions
        $groupAreaInstitutions = $this->getGroupAreaInstitutions($this->roleIds);
        $this->allowAllInstitutions = $groupAreaInstitutions['allowAllInstitutions'] ?? 0;
        $this->institutionIds = array_unique(array_merge($this->institutionIds, $groupAreaInstitutions['institutionIds'] ?? []));

        // If user is super admin, override permissions
        if ($this->user->super_admin ?? 0) {
            $this->allowAllInstitutions = 1;
        }
    }

    public function checkPermission($modelName, $action)
    {
        $user = JWTAuth::user();
        if (!$user) {
            return false;
        }

        if ($user->super_admin ?? 0) {
            return true; // Super admin has all permissions
        }

        $cacheKey = "permissions:user:{$user->id}";

        // Attempt to get cached permissions
        $permissions = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($user) {
            return $this->loadPermissionsFromDb($user->id);
        });

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
                'security_functions.module'
            )
            ->whereIn('security_role_id', $roleIds)
            ->get();

        $permissions = [];
        foreach ($roleFunctions as $roleFunction) {
            foreach (['_view', '_edit', '_add', '_delete', '_execute'] as $perm) {
                if (!empty($roleFunction->$perm)) {
                    $permissions[$roleFunction->module][$perm] = explode('|', $roleFunction->$perm);
                }
            }
        }

        return $permissions;
    }

    private function hasPermission($permissions, $modelName, $action): bool
    {
        if ($action === 'view' && in_array($modelName, $this->commonViewModules, true)) {
            return true;
        }
        foreach ($permissions as $module => $permTypes) {
            foreach (['_view', '_edit', '_add', '_delete', '_execute'] as $perm) {
                if (isset($permTypes[$perm])) {
                    $permValues = $permTypes[$perm];

                    // 🔹 Check for general permission like "Institutions.view"
                    if (in_array("$module.$action", $permValues, true)) {
                        return true;
                    }

                    // 🔹 Check for specific model-based permission like "InstitutionStudents.view"
                    if (in_array("$modelName.$action", $permValues, true)) {
                        return true;
                    }

                    // 🔹 Check if only "view", "edit", etc. exists (without module prefix)
//                    if (in_array($action, $permValues, true)) {
//                        return true;
//                    }
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

    private function getGroupAreaInstitutions($roleIds)
    {
        // Your logic to fetch group area institutions based on role IDs
        return [
            'allowAllInstitutions' => 0,
            'institutionIds' => [] // Replace with actual logic
        ];
    }
}
