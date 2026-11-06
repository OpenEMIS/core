<?php
namespace Alert\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Event\EventInterface;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;

class NoticeRolesTable extends ControllerActionTable
{

    public function initialize(array $config): void
    {
        parent::initialize($config);
        
        $this->belongsTo('Notices', [
            'className' => 'Alert.Notices',
        ]);

        $this->belongsTo('SecurityRoles', [
            'className' => 'Security.SecurityRoles',
        ]);

    }

    /**
     * POCOR-9863: single rule for who can see a notice.
     * A notice is visible to a user when it is enabled (status = 1) and assigned
     * to at least one of the security roles the user holds in any security group.
     *
     * @param int|null $userId
     * @return int[] notice ids
     */
    public function getVisibleNoticeIds($userId): array
    {
        if (empty($userId)) {
            return [];
        }
        $rows = $this->find()
            ->select(['notice_id' => $this->aliasField('notice_id')])
            ->distinct()
            ->innerJoin(
                ['SecurityGroupUsers' => 'security_group_users'],
                ['SecurityGroupUsers.security_role_id = ' . $this->aliasField('security_role_id')]
            )
            ->innerJoin(
                ['VisibleNotices' => 'notices'],
                ['VisibleNotices.id = ' . $this->aliasField('notice_id')]
            )
            ->where([
                'SecurityGroupUsers.security_user_id' => $userId,
                'VisibleNotices.status' => 1,
            ])
            ->enableHydration(false)
            ->toArray();

        return array_values(array_unique(array_map('intval', array_column($rows, 'notice_id'))));
    }
}
