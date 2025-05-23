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
       $noticeStatusRead = [
            -1 => 'All',
             1 => 'Read',
             0 => 'Unread'
        ];
        $extra['noticeStatusRead'] = $noticeStatusRead;
        $extra['elements']['control'] = [
            'name' => 'System.notice_status_data',  // Field identifier
            'data' => [
                'notice_status' => $noticeStatusRead, 
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
        $isSuperAdmin = $this->Auth->user('super_admin');
        $roles = [];
        $roleNames = [];

        $usersGroup = TableRegistry::getTableLocator()->get('Security.SecurityGroupUsers');
        $noticeRoles = TableRegistry::getTableLocator()->get('Alert.NoticeRoles');
        $userNotices = TableRegistry::getTableLocator()->get('Alert.SecurityUserNotices');

        // 1. Get notice_ids based on user’s role
        $assignedNoticeIdsQuery = $usersGroup->find()
            ->select(['notice_id' => 'NoticeRoles.notice_id'])
            ->innerJoin(
                ['NoticeRoles' => 'notice_roles'],
                ['SecurityGroupUsers.security_role_id = NoticeRoles.security_role_id']
            )
            ->where(['SecurityGroupUsers.security_user_id IS' => $userId])
            ->enableHydration(false);

        $assignedNoticeIds = array_column($assignedNoticeIdsQuery->toArray(), 'notice_id');

        // Check if user has super role access
        if ($isSuperAdmin) {
            $query
                ->leftJoinWith('NoticeRoles')
                ->enableAutoFields(true)
                ->group(['notice_id']);
        }else {
            $query->where([$this->aliasField('id IN') => $assignedNoticeIds, $this->aliasField('status') => 1]);
        }

        // Default sort if not present
        $queryParams = $this->request->getQuery();
        if (!isset($queryParams['sort'])) {
            $query->order([
                $this->aliasField('created') => 'DESC',
                $this->aliasField('modified') => 'DESC'
            ]);
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
        $this->field('security_role_id');
        $this->field('subject');
        $this->field('message', [
            'type' => 'element',
            'element' => 'Alert.Alert/notice',
        ]);

       $this->setFieldOrder(['security_role_id', 'subject', 'message']);
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
        $userNoticesTable = TableRegistry::getTableLocator()->get('Alert.SecurityUserNotices');
        $loginUserId = $this->Auth->user()['id'];
        $exists = $userNoticesTable->find()
            ->where([
                'security_user_id IS' => $loginUserId,
                'notice_id IS' => $noticeId
            ]);

        $record = $exists->first();

        if (!$exists) {
            return 'Unread';
        }else{
            return 'Read';

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
