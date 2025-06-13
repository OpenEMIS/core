<?php
namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\Event\Event;
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

        $query
            ->select([
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
            ])
            ->enableHydration(false); 

        if (!$isSuperAdmin && $userId) {
            // Get notice IDs assigned to user's security group
            $usersGroup = TableRegistry::getTableLocator()->get('Security.SecurityGroupUsers');
            $assignedNoticeIdsQuery = $usersGroup->find()
                ->select(['notice_id' => 'NoticeRoles.notice_id'])
                ->innerJoin(
                    ['NoticeRoles' => 'notice_roles'],
                    ['SecurityGroupUsers.security_role_id = NoticeRoles.security_role_id']
                )
                ->where(['SecurityGroupUsers.security_user_id' => $userId])
                ->enableHydration(false);

            $assignedNoticeIds = array_column($assignedNoticeIdsQuery->toArray(), 'notice_id');

            if (!empty($assignedNoticeIds)) {
                $query->where([
                    $this->aliasField('id IN') => $assignedNoticeIds,
                    $this->aliasField('status') => 1
                ])->order([
                    $this->aliasField('created') => 'DESC']);
            } else {
                $query->where(['1 = 0'])->order([
                    $this->aliasField('created') => 'DESC']);
            }
        } else {
            // Super admin get all active notices in dashboard
            $query->where([
                $this->aliasField('status') => 1
            ])->order([
            $this->aliasField('created') => 'DESC']);
        }

        return $query;
    }

}
