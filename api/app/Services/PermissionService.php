<?php

namespace App\Services;

use App\Models\SecurityGroupUsers;
use App\Models\SecurityRoleFunction;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Log;

class PermissionService
{
    /**
     * Check if the user has permission to access the given action
     *
     * @param string $modelName
     * @param string $action
     * @return bool
     */
    public function checkPermission($modelName, $action): bool
    {
        $user = JWTAuth::user();
        if (!$user) {
            return false; // No user found, deny access
        }

        if ($user->super_admin ?? 0) {
            return true; // Super admin has all permissions
        }

        $userId = $user->id;
        $roleIds = SecurityGroupUsers::where('security_user_id', $userId)
            ->pluck('security_role_id')
            ->unique()
            ->toArray();

        if (empty($roleIds)) {
            return false; // User has no roles assigned
        }
        $requiredPermission = "$modelName.$action";
        $roleFunctions = SecurityRoleFunction::join('security_functions', 'security_functions.id', '=', 'security_role_functions.security_function_id')
            ->select(
                'security_role_functions._view',
                'security_role_functions._edit',
                'security_role_functions._add',
                'security_role_functions._delete',
                'security_role_functions._execute',
                'security_role_id',
                'security_function_id',
                'security_functions.name',
                'security_functions.controller',
                'security_functions.module',
                'security_functions.category',
                'security_functions._view as security_function_view',
                'security_functions._edit as security_function_edit',
                'security_functions._add as security_function_add',
                'security_functions._delete as security_function_delete',
                'security_functions._execute as security_function_execute'
            )
            ->whereIn('security_role_id', $roleIds)
            ->where(function ($query) use ($modelName, $action) {
                $query->where('security_functions.controller', $modelName)
                    ->where(function ($query) use ($action) {
                        $query->where('security_functions._view', "$action")
                            ->orWhere('security_functions._edit', "$action")
                            ->orWhere('security_functions._add', "$action")
                            ->orWhere('security_functions._delete', "$action")
                            ->orWhere('security_functions._execute', "$action")
                            ->orWhere('security_functions._view', 'LIKE', "%|$action")
                            ->orWhere('security_functions._edit', 'LIKE', "%|$action")
                            ->orWhere('security_functions._add', 'LIKE', "%|$action")
                            ->orWhere('security_functions._delete', 'LIKE', "%|$action")
                            ->orWhere('security_functions._execute', 'LIKE', "%|$action")
                            ->orWhere('security_functions._view', 'LIKE', "%|$action|%")
                            ->orWhere('security_functions._edit', 'LIKE', "%|$action|%")
                            ->orWhere('security_functions._add', 'LIKE', "%|$action|%")
                            ->orWhere('security_functions._delete', 'LIKE', "%|$action|%")
                            ->orWhere('security_functions._execute', 'LIKE', "%|$action|%")
                            ->orWhere('security_functions._view', 'LIKE', "$action|%")
                            ->orWhere('security_functions._edit', 'LIKE', "$action|%")
                            ->orWhere('security_functions._add', 'LIKE', "$action|%")
                            ->orWhere('security_functions._delete', 'LIKE', "$action|%")
                            ->orWhere('security_functions._execute', 'LIKE', "$action|%")
                        ;
                    })
                    ->orWhere(function ($query) use ($modelName, $action) {
                        $query->where('security_functions._view', 'LIKE', "%$modelName.$action%")
                            ->orWhere('security_functions._edit', 'LIKE', "%$modelName.$action%")
                            ->orWhere('security_functions._add', 'LIKE', "%$modelName.$action%")
                            ->orWhere('security_functions._delete', 'LIKE', "%$modelName.$action%")
                            ->orWhere('security_functions._execute', 'LIKE', "%$modelName.$action%");
                    });
            })
            ->get()
            ->toArray();
        Log::info("Role functions: " . print_r($roleFunctions,true));
        // Normalize and filter the array in PHP
        foreach ($roleFunctions as $roleFunction) {
            $actions = [
                'security_function_view',
                'security_function_edit',
                'security_function_add',
                'security_function_delete',
                'security_function_execute'
            ];

            foreach ($actions as $actionType) {
                if (!isset($roleFunction[$actionType])) {
                    continue;
                }

//                Log::info("Checking $actionType: " . $roleFunction[$actionType]);

                // Normalize permission string into an array
                $permissions = array_map('trim', explode('|', $roleFunction[$actionType]));
//                Log::info("Permissions: " . json_encode($permissions));

                // Check exact match
                if (in_array($action, $permissions, true) || in_array("$modelName.$action", $permissions, true)) {
//                    Log::info("Access granted for $modelName:$action");
                    return true;
                }

                // Additional check: Sometimes `controller` field might be a better match
                if (isset($roleFunction['controller'])) {
                    $controller = $roleFunction['controller'];
                    if (in_array("$controller.$action", $permissions, true)) {
//                        Log::info("Access granted for $controller:$action");
                        return true;
                    }
                }
            }

//            Log::info("Access denied for $modelName:$action");
            return false;
        };
        return false;
    }
}
