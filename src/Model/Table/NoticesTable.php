<?php
namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\Event\EventInterface;
use ArrayObject;
use App\Model\Table\ControllerActionTable;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;

class NoticesTable extends AppTable
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Dashboard' => ['index']
        ]);
    }

    //POCOR-7210
    public function findNotices(Query $query, array $options)
    {

        $userId = $_SESSION['Auth']['User']['id'] ?? null;
        $isSuperAdmin = $_SESSION['Auth']['User']['super_admin'] ?? false;
        $query->select([
            'id',
            'subject',
            'message',
            'created_user_id',
            'modified_user_id',
            'status',
            'created' => $query->func()->date_format([
                $this->aliasField('created') => 'literal',
                "'%M %d, %Y - %H:%i:%s'" => 'literal'
            ]),
            'modified' => $query->func()->date_format([
                $this->aliasField('modified') => 'literal',
                "'%M %d, %Y - %H:%i:%s'" => 'literal'
            ])
        ])->enableHydration(false);

        if (!$isSuperAdmin && $userId) {
            $usersGroup = TableRegistry::getTableLocator()->get('Security.SecurityGroupUsers');

            // Step 1: Get user's security role IDs
            $userRoleIdsQuery = $usersGroup->find()
                ->select(['security_role_id'])
                ->where(['security_user_id' => $userId])
                ->enableHydration(false);
            $userRoleIds = array_column($userRoleIdsQuery->toArray(), 'security_role_id');
            //POCOR-9429: This check is added to restrict users which don't have any roles assigned.
            if (empty($userRoleIds)) {
               return $query->where(['1 = 0']);
            }
            $havePermissionToView = TableRegistry::getTableLocator()->get('Security.SecurityRoleFunctions')->find()
                    ->leftJoin(['SecurityFunctions' => 'security_functions'], [
                        [
                            'SecurityFunctions.id = SecurityRoleFunctions.security_function_id',
                        ]
                    ])
                    ->where([
                        'SecurityFunctions.controller' => 'Systems',
                        'SecurityFunctions.name' => 'Notice Message',
                        'SecurityRoleFunctions.security_role_id IN'=> $userRoleIds,
                        'SecurityRoleFunctions._view' => 1,
                    ])
                    ->toArray();
            if (empty($havePermissionToView)) {
                // no permission to view any notice
                return $query->where(['1 = 0']);
            }

            // Step 2: Get notice IDs assigned to user's roles
            $noticeRoles = TableRegistry::getTableLocator()->get('Alert.NoticeRoles');
            $assignedNoticeIdsQuery = $noticeRoles->find()
                ->select(['notice_id'])
                ->where(['security_role_id IN' => $userRoleIds])
                ->enableHydration(false);
            $assignedNoticeIds = array_column($assignedNoticeIdsQuery->toArray(), 'notice_id');

            if (!empty($assignedNoticeIds)) {
                // Return assigned, active notices
                $query->where([
                    $this->aliasField('id IN') => $assignedNoticeIds,
                    $this->aliasField('status') => 1
                ])->order([
                    $this->aliasField('created') => 'DESC'
                ]);
            } else {
                // No notices assigned to user's roles
                $query->where(['1 = 0'])->order([
                    $this->aliasField('created') => 'DESC'
                ]);
            }
        } else {
            // Superadmin: show all active notices
            $query->where([
                $this->aliasField('status') => 1
            ])->order([
                $this->aliasField('created') => 'DESC'
            ]);
        }

        return $query;
    }


}
