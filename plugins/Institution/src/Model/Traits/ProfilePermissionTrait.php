<?php
namespace Institution\Model\Traits;

use Cake\ORM\TableRegistry;

/**
 * ProfilePermissionTrait
 *
 * Reusable permission check for all four profile table types
 * (Institution, Staff, Student, Class).
 *
 * Reads security_role_functions._execute for the current user's roles
 * in the current institution's security group.  If the user belongs to
 * multiple roles the union is used — any role with _execute=1 grants
 * the permission (POCOR-9598).
 *
 * The function is looked up by name + controller so the check is portable
 * across country deployments that may have different auto-increment IDs
 * in security_functions (POCOR-9598).
 */
//POCOR-9598: start - centralised security_role_functions execute-permission check for all profile tables
trait ProfilePermissionTrait
{
    /**
     * Per-request cache: [ "controller:name" => bool ]
     * Populated on first call, reused for every subsequent row on the same page.
     */
    private array $_profilePermCache = [];

    /**
     * Returns true if the currently logged-in user has _execute=1
     * for the given security function (looked up by name + controller)
     * via any of their roles in the current institution's security group.
     *
     * Lookup by name+controller instead of hardcoded ID makes the check
     * portable across deployments where auto_increment IDs may differ.
     *
     * @param  string  $functionName   security_functions.name
     * @param  string  $controller     security_functions.controller
     * @return bool
     */
    protected function hasProfileFunctionPermission(string $functionName, string $controller): bool
    {
        $cacheKey = $controller . ':' . $functionName; //POCOR-9598: key by name+controller, not by ID

        if (array_key_exists($cacheKey, $this->_profilePermCache)) {
            return $this->_profilePermCache[$cacheKey];
        }

        // Super-admin bypasses all permission checks
        if ($this->Session->read('Auth.User.super_admin') == 1) {
            return $this->_profilePermCache[$cacheKey] = true;
        }

        // Resolve the function ID by name + controller — portable across country deployments
        $SecurityFunctions = TableRegistry::getTableLocator()->get('Security.SecurityFunctions'); //POCOR-9598: dynamic lookup instead of hardcoded ID constant
        $funcRecord = $SecurityFunctions->find()
            ->where(['name' => $functionName, 'controller' => $controller])
            ->select(['id'])
            ->first();

        if (empty($funcRecord)) {
            return $this->_profilePermCache[$cacheKey] = false;
        }

        $functionId = $funcRecord->id;

        $userId        = $this->Session->read('Auth.User.id');
        $institutionId = $this->getInstitutionID();

        // Guard: no institution context means no permission
        if (empty($institutionId)) { //POCOR-9598: guard against null institutionId to prevent RecordNotFoundException
            return $this->_profilePermCache[$cacheKey] = false;
        }

        // Resolve the institution's security group
        $Institutions     = TableRegistry::getTableLocator()->get('Institution.Institutions');
        $institution      = $Institutions->get($institutionId, ['fields' => ['security_group_id']]);
        $securityGroupId  = $institution->security_group_id;

        // Collect all role IDs the user holds in this institution's group
        $SecurityGroupUsers = TableRegistry::getTableLocator()->get('Security.SecurityGroupUsers');
        $roleIds = $SecurityGroupUsers
            ->find()
            ->where([
                'security_group_id' => $securityGroupId,
                'security_user_id'  => $userId,
            ])
            ->extract('security_role_id')
            ->toList();

        if (empty($roleIds)) {
            return $this->_profilePermCache[$cacheKey] = false;
        }

        // Grant if ANY of the user's roles has _execute=1 for this function
        $SecurityRoleFunctions = TableRegistry::getTableLocator()->get('Security.SecurityRoleFunctions');
        $result = $SecurityRoleFunctions->exists([
            'security_function_id' => $functionId,
            '_execute'             => 1,
            'security_role_id IN'  => $roleIds,
        ]);

        return $this->_profilePermCache[$cacheKey] = $result;
    }
}
//POCOR-9598: end
