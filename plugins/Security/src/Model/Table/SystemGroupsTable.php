<?php
namespace Security\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Http\ServerRequest;
use Cake\Datasource\Exception\RecordNotFoundException;
use App\Model\Table\AppTable;
use App\Model\Traits\MessagesTrait;
use App\Model\Traits\HtmlTrait;
use App\Model\Table\ControllerActionTable;
use Cake\Datasource\EntityInterface;

class SystemGroupsTable extends ControllerActionTable
{
    use MessagesTrait;
    use HtmlTrait;

    public function initialize(array $config): void
    {
        $this->setTable('security_groups');
        parent::initialize($config);

        $this->hasMany('Roles', ['className' => 'Security.SecurityRoles', 'dependent' => true]);
        $this->hasOne('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'security_group_id']);
        $this->belongsToMany('Users', [
            'className' => 'Security.Users',
            'joinTable' => 'security_group_users',
            'foreignKey' => 'security_group_id',
            'targetForeignKey' => 'security_user_id',
            'through' => 'Security.SecurityGroupUsers',
            'dependent' => true
        ]);

        $this->belongsToMany('Areas', [
            'className' => 'Area.Areas',
            'joinTable' => 'security_group_areas',
            'foreignKey' => 'security_group_id',
            'targetForeignKey' => 'area_id',
            'through' => 'Security.SecurityGroupAreas',
            'dependent' => true
        ]);

        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
    }

    public function institutionAfterSave(Event $event, Entity $entity)
    {
        if ($entity->isNew()) {
            $obj = $this->newEntity(['name' => $entity->code . ' - ' . $entity->name]);
            $securityGroup = $this->save($obj);
            if ($securityGroup) {
                $SecurityInstitutions = TableRegistry::get('Security.SecurityGroupInstitutions');
                // add the relationship of security group and institutions
                $securityInstitution = $SecurityInstitutions->newEntity([
                    'security_group_id' => $securityGroup->id,
                    'institution_id' => $entity->id
                ]);
                $SecurityInstitutions->save($securityInstitution);
                $entity->security_group_id = $securityGroup->id;
                $InstitutionsTable = $event->subject();
                if (!$InstitutionsTable->save($entity)) {
                    return false;
                }
            } else {
                return false;
            }
        } else {
            $securityGroupId = $entity->security_group_id;
            if (!empty($securityGroupId)) {
                $obj = $this->get($securityGroupId);
                if (is_object($obj)) {
                    $data = ['name' => $entity->code . ' - ' . $entity->name];
                    $obj = $this->patchEntity($obj, $data);
                    $securityGroup = $this->save($obj);
                    if (!$securityGroup) {
                        return false;
                    }
                }
            }
        }
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $newEvent = [
            'Model.Institutions.afterSave' => 'institutionAfterSave'
        ];
        $events = array_merge($events, $newEvent);
        return $events;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $controller = $this->controller;
        $tabElements = [
            'UserGroups' => [
                'url' => ['plugin' => $controller->getPlugin(), 'controller' => $controller->getName(), 'action' => 'UserGroups'],
                'text' => $this->getMessage('UserGroups.tabTitle')
            ],
            $this->getAlias() => [
                'url' => ['plugin' => $controller->getPlugin(), 'controller' => $controller->getName(), 'action' => $this->getAlias()],
                'text' => $this->getMessage($this->aliasField('tabTitle'))
            ]
        ];
        $tabElements = $this->controller->TabPermission->checkTabPermission($tabElements);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->getAlias());

        // $roleOptions = $this->Roles->find('list')->toArray();
        // $this->ControllerAction->field('users', [
        //     'type' => 'user_table',
        //     'valueClass' => 'table-full-width',
        //     'roleOptions' => $roleOptions,
        //     'visible' => ['index' => false, 'view' => true, 'edit' => true]
        // ]);

        $this->setFieldOrder(['name', 'users']); // POCOR-8446
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('no_of_users', ['visible' => ['index' => true]]);
        $this->setFieldOrder(['name', 'no_of_users','institution_id']);
    }

    public function onGetNoOfUsers(Event $event, Entity $entity)
    {
        $id = $entity->id;
        $GroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
        $count = $GroupUsers->findAllBySecurityGroupId($id)->count();
        return $count;
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $queryParams = $this->request->getQuery();
        $query->find('inInstitutions');

        if (!isset($queryParams['sort']) && !isset($queryParams['direction'])) {
            $query->order([$this->aliasField('name') => 'asc']);
        }
        // filter groups by users permission
        if ($this->Auth->user('super_admin') != 1) {
            $userId = $this->Auth->user('id');
            $SecurityGroupUsersTable = TableRegistry::get('Security.SecurityGroupUsers');
            $SecurityGroupUsers = $SecurityGroupUsersTable
                ->find('list')
                ->where([
                    $SecurityGroupUsersTable->aliasField('security_group_id') .' = ' .$this->aliasField('id'),
                    $SecurityGroupUsersTable->aliasField('security_user_id') => $userId
                ]);

            $query
                ->where([
                    'OR'=>[
                        'EXISTS ('.$SecurityGroupUsers->sql().')',
                        'Institutions.created_user_id' => $userId
                    ]
                ]);
        }
    }

    public function viewBeforeQuery(Event $event, Query $query)
    {
        $query->contain(['Users']);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->request->getData[$this->getAlias()]['security_group_id'] = $entity->id;
        $toolbarAttr = [
                    'class' => 'btn btn-xs btn-default',
                    'data-toggle' => 'tooltip',
                    'data-placement' => 'bottom',
                    'escape' => false
                ];

        $listUrl = [
            'plugin' => 'Security',
            'controller' => 'Securities',
            'action' => 'SystemGroupsList',
            'userGroupId' => $entity->id,
            'index',
            "?userGroupId=$entity->id"
        ];

        $listButton['url'] = $listUrl;
        $listButton['type'] = 'button';
        $listButton['attr'] = $toolbarAttr;
        $listButton['label'] = '<i class="fa kd-lists"></i>';
        $listButton ['attr']['title'] = __('List');
        $extra['toolbarButtons']['list'] = $listButton;
    }

    public function findInInstitutions(Query $query, array $options)
    {
        $query->innerJoin(['Institutions' => 'institutions'], ['Institutions.security_group_id = SystemGroups.id']);
        return $query;
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'custom_module_id') {
            return __('Custom Module');
        }elseif ($field == 'name') {
            return __('Name');
        } elseif ($field == 'modified_user_id') {
            return __('Modified By');
        } elseif ($field == 'modified') {
            return __('Modified On');
        } elseif ($field == 'created_user_id') {
            return __('Created By');
        } elseif ($field == 'created') {
            return __('Created On');
        }elseif ($field == 'no_of_users') {
            return __('No Of Users');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }


}
