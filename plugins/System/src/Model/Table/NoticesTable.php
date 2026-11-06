<?php

namespace System\Model\Table;

use ArrayObject;
use Cake\Utility\Inflector;
use InvalidArgumentException;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Mailer\Email;
use Cake\I18n\Time;
use Cake\Http\Client;
use Cake\Log\Log;
use Cake\Http\ServerRequest;
use Cake\Http\Response;
use App\Model\Table\ControllerActionTable;

class NoticesTable extends ControllerActionTable
{
    private $fieldsOrder = ['created', 'message'];
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->toggle('view', true);
        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
        $this->hasMany('NoticeRoles', [
            'className' => 'Alert.NoticeRoles',
            'foreignKey' => 'notice_id',
            'dependent' => true,
            'cascadeCallbacks' => true,
        ]);
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        return $events;
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $header = __(Inflector::humanize(Inflector::underscore($this->getAlias())));
        $this->controller->set('contentHeader', $header);
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('created', ['visible' => true, 'sort' => true]);
        $this->field('created_user_id', ['visible' => true, 'sort' => false]);
        $this->field('message', ['sort' => false,'visible' => false,]);
        $this->field('subject', ['sort' => false]);
        $this->field('status', ['visible' => false,]);
        $this->field('notice_status', ['visible' => true,]);
        $this->setFieldOrder(['subject', 'notice_status', 'created_user_id','created']);
        $noticeOption = [
                -1 => 'All',
                 1 => 'Read',
                 0 => 'Unread'
        ];
        $noticeStatus = $this->request->getQuery('notice_status') ?? -1;
        $extra['noticeStatusRead'] = $noticeStatusRead;
        $extra['elements']['control'] = [
            'name' => 'System.notice_status_data',  // Field identifier
            'data' => [
                'noticeOption' => $noticeOption, 
                'noticeStatus' => $noticeStatus, 
            ],
            'options' => [], 
            'order' => 1     
        ];
        $toolbarButtonsArray = $extra['toolbarButtons']->getArrayCopy();
        $extra['toolbarButtons']->exchangeArray($toolbarButtonsArray);


    }
    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $userId = $this->Auth->user('id');
        $isSuperAdmin = $this->Auth->user('super_admin');
        $readStatus = $this->request->getQuery('notice_status');

        if (!$isSuperAdmin) {
            //POCOR-9863: show enabled notices assigned to any of the user's roles (no separate permission needed)
            $noticeIds = TableRegistry::getTableLocator()->get('Alert.NoticeRoles')->getVisibleNoticeIds($userId);

            if (empty($noticeIds)) {
                $query->where(['1 = 0']);
                $this->Alert->info(__('There are no records.'), [
                    'type' => 'string',
                    'reset' => true
                ]);
                $event->stopPropagation();

                return false;
            }

            $query->where([
                $this->aliasField('id IN') => $noticeIds,
                $this->aliasField('status') => 1
            ]);
            $query->leftJoin(
                ['SecurityUserNotices' => 'security_user_notices'],
                [
                    'SecurityUserNotices.notice_id = Notices.id',
                    'SecurityUserNotices.security_user_id IS' => $userId
                ]
            );
        }

        // Step 6: Read/unread filters
        if (!$isSuperAdmin) {
            if ($readStatus === '1') {
                $query->where(['SecurityUserNotices.id IS NOT' => null]);
            } elseif ($readStatus === '0') {
                $query->where(['SecurityUserNotices.id IS' => null]);
            }
        } else {
            if ($readStatus === '0') {
                $query->where(['1 = 0']);
                return;
            } else {
                $query->where([$this->aliasField('status') => 1]);
            }
        }
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        if ($field == 'status') {
            return __('Enable');
        }if ($field == 'notice_status') {
            return __('Status');
        } else if ($field == 'created_user_id') {
            return __('Created User');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    //POCOR-9863: non-admin users may only open notices that are addressed to one of their roles
    public function viewBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        if (!$this->Auth->user('super_admin')) {
            $noticeIds = TableRegistry::getTableLocator()->get('Alert.NoticeRoles')
                ->getVisibleNoticeIds($this->Auth->user('id'));
            $query->where(empty($noticeIds) ? ['1 = 0'] : [$this->aliasField('id IN') => $noticeIds]);
        }
    }

    public function viewAfterAction(EventInterface $event, Entity|bool|null $entity, ArrayObject $extra)
    {
        //POCOR-9863: viewBeforeQuery above can legitimately filter the record out (notice disabled,
        //or no longer assigned to any of the user's roles). ViewBehavior then passes a falsy entity
        //here and redirects to the index, so bail out before touching $entity.
        if (!$entity) {
            return;
        }

        $this->field('status', ['visible' => false]);
        $this->field('notice_status', ['visible' => true]);
        $this->field('security_role_id');
        $this->field('subject');
        $this->field('message', [
            'type' => 'element',
            'element' => 'Alert.Alert/notice',
        ]);

       $this->setFieldOrder(['security_role_id', 'subject', 'message','notice_status', 'status']);
       $this->saveNoticeStatus($entity);
    }

    private function saveNoticeStatus($entity)
    {
        $noticeId = $entity->id;
        $loginUserId = $this->Auth->user()['id'];
        $isSuperAdmin = $this->Auth->user('super_admin');
        if(!$isSuperAdmin){

            $userNoticesTable = TableRegistry::getTableLocator()->get('Alert.SecurityUserNotices');
            $exists = $userNoticesTable->find()
                ->where([
                    'security_user_id IS' => $loginUserId,
                    'notice_id IS' => $noticeId
                ]);

            $record = $exists->first();
           
            if (!$record) {
                // Create new record
                $userNotice = $userNoticesTable->newEntity([
                    'security_user_id' => $loginUserId,
                    'notice_id' => $noticeId
                ]);

                $userNoticesTable->save($userNotice);
            }
        }
    }


    public function onGetStatus(EventInterface $event, Entity $entity)
    {
        if($entity->status == 1){
            return 'Enable';
        }else{
            return 'Disable';
        }
    }

    public function onGetNoticeStatus(EventInterface $event, Entity $entity)
    {
         $isSuperAdmin = $this->Auth->user('super_admin');
        if($isSuperAdmin){
            return 'Read';
        }
        if($this->action == 'view'){
            $userNoticesTable = TableRegistry::getTableLocator()->get('Alert.SecurityUserNotices');
            $loginUserId = $this->Auth->user()['id'];
            $exists = $userNoticesTable->find()
                ->where([
                    'security_user_id IS' => $loginUserId,
                    'notice_id IS' => $entity->id
                ]);

            $record = $exists->first();

            if (!$exists) {
                return 'Unread';
            }else{
                return 'Read';

            }
        }else{
            $userId = $this->Auth->user('id');
            $noticeId = $entity->id;

            $usersGroup   = TableRegistry::getTableLocator()->get('Security.SecurityGroupUsers');
            $noticeRoles  = TableRegistry::getTableLocator()->get('Alert.NoticeRoles');
            $userNotices  = TableRegistry::getTableLocator()->get('Alert.SecurityUserNotices');

            // 1. Check if the notice is assigned to the user's roles
            $assigned = $usersGroup->find()
                ->innerJoin(
                    ['NoticeRoles' => 'notice_roles'],
                    ['SecurityGroupUsers.security_role_id = NoticeRoles.security_role_id']
                )
                ->where([
                    'SecurityGroupUsers.security_user_id' => $userId,
                    'NoticeRoles.notice_id' => $noticeId
                ])
                ->first();

            if (!$assigned) {
                return null; // Or 'Not Applicable'
            }

            // 2. Check if the user has seen the notice
            $seen = $userNotices->find()
                ->where([
                    'security_user_id' => $userId,
                    'notice_id' => $noticeId
                ])
                ->first();

            return $seen ? 'Read' : 'Unread';
        }
    }

    public function onGetSecurityRoleId(EventInterface $event, Entity $entity)
    {
        $table = TableRegistry::getTableLocator()->get('Security.SecurityRoles');
        $obj = [];
        $roles = TableRegistry::getTableLocator()->get('Alert.NoticeRoles')
                ->find()
                ->where(['notice_id' => $entity->id])
                ->contain(['SecurityRoles'])
                ->toArray();

        if ($roles) {
            foreach ($roles as $noticeRole) 
            {
                $role = $table->find()
                    ->select(['name'])
                    ->where(['id' => $noticeRole->security_role_id])
                    ->first();
                    
                if ($role) {
                    $obj[] = $role->name;
                }
            }
        }

        $values = !empty($obj) ? implode(', ', $obj) : __('');
        return $values;
    }

}
