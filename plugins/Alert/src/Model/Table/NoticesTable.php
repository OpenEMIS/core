<?php

namespace Alert\Model\Table;

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
use Cake\Collection\Collection;
use Cake\Utility\Text;
use Cake\Utility\Security;

use Cake\Validation\Validator;
class NoticesTable extends ControllerActionTable
{
    private $fieldsOrder = ['created', 'message'];
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->belongsToMany('SecurityRoles', [
            'className' => 'Security.SecurityRoles',
            'joinTable' => 'notice_roles',
            'foreignKey' => 'notice_id',
            'targetForeignKey' => 'security_role_id',
            'through' => 'Alert.NoticeRoles',
            'dependent' => true,
            'cascadeCallbacks' => true
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

    public function validationDefault(Validator $validator): Validator
    {
        $validator->setProvider('custom', $this);
        $validator = parent::validationDefault($validator);
        return $validator
            ->requirePresence('subject')
            ->requirePresence('message')
            ->requirePresence('status');
    }

    public function addEditBeforeAction(EventInterface $event, ArrayObject $extra) {
        $this->field('security_role_id', ['entity' => $entity, 'visible' => true]);
        $this->field('status', ['entity' => $entity, 'visible' => true]);
        $this->field('notice_status', ['entity' => $entity, 'visible' => false]);
        $this->field('subject', ['entity' => $entity, 'visible' => true]);
        $this->field('message', ['entity' => $entity, 'visible' => true]);
        $this->setFieldOrder(['status', 'security_role_id', 'subject', 'message']);
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('created', ['visible' => true, 'sort' => true]);
        $this->field('created_user_id', ['visible' => true, 'sort' => false]);
        $this->field('notice_status', ['visible' => false]);
        $this->field('message', ['sort' => false,'visible' => false,]);
        $this->field('subject', ['sort' => false]);
        $this->field('status', ['sort' => false]);
        $this->setFieldOrder(['subject', 'status', 'created_user_id','created']);

    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $queryParams = $this->request->getQuery();
        if (!isset($queryParams['sort'])) {
            $query->order(
                [$this->aliasField('created') => 'DESC',
                    $this->aliasField('modified') => 'DESC']);
        }

    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        if ($field == 'status') {
            return __('Enable');
        } else if ($field == 'created_user_id') {
            return __('Created By');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

   public function onUpdateFieldStatus(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $status = [1 => 'Enable', 0 => 'Disable'];
        if ($action == 'add') {
            $attr['options'] = $status;
            $attr['type'] = 'select';
            $attr['onChangeReload'] = true;
            $attr['attr']['required'] = true;
            
        }elseif($action == 'edit'){
            $getRecordId = $this->getQueryString();
            $statusVal = $this->find()->where(['id' => $getRecordId['id']])->first()->status;
            $attr['options'] = $status;
            $attr['value'] = $statusVal;
            $attr['type'] = 'select';
            $attr['attr']['required'] = true;
            $attr['attr']['value'] = $statusVal;
        }

        return $attr;
    }
    public function onUpdateFieldSecurityRoleId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $roleOptions = $this->SecurityRoles
            ->find('list')
            ->select([
                $this->SecurityRoles->aliasField($this->SecurityRoles->getPrimaryKey()),
                $this->SecurityRoles->aliasField('name')
            ])
            ->find('visible')
            ->find('order')
            ->toArray();

        $attr['type'] = 'chosenSelect';
        $attr['options'] = $roleOptions;

        if ($action === 'edit') {
            $getRecordId = $this->getQueryString();
            $noticeRoleTable = TableRegistry::getTableLocator()->get('Alert.NoticeRoles');
            
            $roleIds = $noticeRoleTable->find()
                ->select(['security_role_id'])
                ->where(['notice_id' => $getRecordId['id']])
                ->extract('security_role_id')
                ->toList();

            if (!empty($roleIds)) {
                $selectedIds = $this->SecurityRoles
                    ->find()
                    ->select(['id'])
                    ->where(['id IN' => $roleIds])
                    ->extract('id')
                    ->toList();

                $attr['attr']['value'] = $selectedIds;
            }
        }

        return $attr;
    }

    

    public function addAfterSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        if (!empty($entity->get('security_role_id')['_ids'])) {
            $NoticeRolesTable = TableRegistry::getTableLocator()->get('Alert.NoticeRoles');
            
            foreach ($entity->get('security_role_id')['_ids'] as $roleId) {
                $roleData = [
                    'id' =>   Text::uuid(), 
                    'notice_id' => $entity->id,
                    'security_role_id' => $roleId
                ];
                $noticeRole = $NoticeRolesTable->newEntity($roleData);
                $NoticeRolesTable->save($noticeRole);
            }
        }
    }

    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('status', ['entity' => $entity]);
        $this->field('notice_status', ['visible' => false]);
        $this->field('security_role_id');
        $this->field('subject');
        $this->field('message', [
            'type' => 'element',
            'element' => 'Alert.Alert/notice',
        ]);

       $this->setFieldOrder(['status', 'security_role_id', 'subject', 'message']);
    }

    public function onGetStatus(EventInterface $event, Entity $entity)
    {
        if($entity->status == 1){
            return 'Enable';
        }else{
            return 'Disable';
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

    public function editAfterSave(EventInterface $event, Entity $entity, ArrayObject $requestData, ArrayObject $options)
    {
        $id = $entity->id;
        $NoticeRolesTable = TableRegistry::getTableLocator()->get('Alert.NoticeRoles');

        // Delete existing entries related to this notice
        $existingRecords = $NoticeRolesTable->find()->where(['notice_id' => $id])->all();
        foreach ($existingRecords as $record) {
            $NoticeRolesTable->delete($record);
        }

        // Save new notice_roles
        if (!empty($entity->get('security_role_id')['_ids'])) {
            foreach ($entity->get('security_role_id')['_ids'] as $roleId) {
                $roleData = [
                    'id' => Text::uuid(), 
                    'notice_id' => $id,
                    'security_role_id' => $roleId
                ];
                $newNoticeRole = $NoticeRolesTable->newEntity($roleData);
                $NoticeRolesTable->save($newNoticeRole);
            }
        }
    }
}
