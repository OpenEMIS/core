<?php

namespace System\Model\Table;

use ArrayObject;
use Cake\Utility\Inflector;
use InvalidArgumentException;
use Cake\Event\Event;
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

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $header = __(Inflector::humanize(Inflector::underscore($this->getAlias())));
        $this->controller->set('contentHeader', $header);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
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
    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $userId = $this->Auth->user('id');
        $isSuperAdmin = $this->Auth->user('super_admin'); // true/false
        $readStatus = $this->request->getQuery('notice_status'); // '1', '0', or null

        // Non-superadmin users: filter by assigned notice IDs
        if (!$isSuperAdmin) {
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

            $query->where([
                $this->aliasField('id IN') => $assignedNoticeIds,
                $this->aliasField('status') => 1
            ]);
            $query->leftJoin(
                ['SecurityUserNotices' => 'security_user_notices'],
                [
                    'SecurityUserNotices.notice_id = Notices.id',
                    'SecurityUserNotices.security_user_id' => $userId
                ]
            );
        }

        // Apply read/unread filter ONLY if user is not superadmin
        if (!$isSuperAdmin) {
            if ($readStatus === '1') {
                $query->where(['SecurityUserNotices.id IS NOT' => null]); // Read
            } elseif ($readStatus === '0') {
                $query->where(['SecurityUserNotices.id IS' => null]); // Unread
            }
        }else{
            if ($readStatus === '0') {
                $query->where(['1 = 0']); // if user is superadmin, no unread message for user.
                return;
            } else {
                $query->where([$this->aliasField('status') => 1]); 
            }
        }
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        if ($field == 'status') {
            return __('Enable');
        }if ($field == 'notice_status') {
            return __('Status');
        } else if ($field == 'created_user_id') {
            return __('Created By');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
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


    public function onGetStatus(Event $event, Entity $entity)
    {
        if($entity->status == 1){
            return 'Enable';
        }else{
            return 'Disable';
        }
    }

    public function onGetNoticeStatus(Event $event, Entity $entity)
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

    public function onGetSecurityRoleId(Event $event, Entity $entity)
    {
        $table = TableRegistry::get('Security.SecurityRoles');
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
