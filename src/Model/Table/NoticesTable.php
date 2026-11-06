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

        //POCOR-9863: notices are shown to every user whose role the notice is assigned to.
        //The extra "Notice Message" permission check was removed so that role assignment alone decides visibility.
        $query->where([$this->aliasField('status') => 1])
            ->order([$this->aliasField('created') => 'DESC']);

        if (!$isSuperAdmin) {
            $noticeIds = TableRegistry::getTableLocator()->get('Alert.NoticeRoles')->getVisibleNoticeIds($userId);
            if (empty($noticeIds)) {
                return $query->where(['1 = 0']);
            }
            $query->where([$this->aliasField('id IN') => $noticeIds]);
        }

        return $query;
    }


}
