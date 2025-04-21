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
        $this->field('status', ['sort' => false]);
        $this->setFieldOrder(['subject', 'status', 'created_user_id','created']);

    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $queryParams = $this->request->getQuery();
        if (!isset($queryParams['sort'])) {
            $query->order(
                [$this->aliasField('created') => 'DESC',
                    $this->aliasField('modified') => 'DESC']);
        }

    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        if ($field == 'status') {
            return __('Enable');
        } else if ($field == 'created_user_id') {
            return __('Created By');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('status', ['entity' => $entity]);
        $this->field('security_role_id');
        $this->field('subject');
        $this->field('message', [
            'type' => 'element',
            'element' => 'Alert.Alert/notice',
        ]);

       $this->setFieldOrder(['status', 'security_role_id', 'subject', 'message']);
    }

    public function onGetStatus(Event $event, Entity $entity)
    {
        if($entity->status == 1){
            return 'Enable';
        }else{
            return 'Disable';
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
