<?php
namespace Security\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use Cake\Datasource\Exception\RecordNotFoundException;
use App\Model\Table\AppTable;
use App\Model\Traits\MessagesTrait;
use App\Model\Traits\HtmlTrait;

class SystemGroupsTable extends AppTable
{
    use MessagesTrait;
    use HtmlTrait;

    public function initialize(array $config)
    {
        $this->table('security_groups');
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

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $newEvent = [
            'Model.Institutions.afterSave' => 'institutionAfterSave'
        ];
        $events = array_merge($events, $newEvent);
        return $events;
    }

    public function beforeAction(Event $event)
    {
        $controller = $this->controller;
        $tabElements = [
            'UserGroups' => [
                'url' => ['plugin' => $controller->plugin, 'controller' => $controller->name, 'action' => 'UserGroups'],
                'text' => $this->getMessage('UserGroups.tabTitle')
            ],
            $this->alias() => [
                'url' => ['plugin' => $controller->plugin, 'controller' => $controller->name, 'action' => $this->alias()],
                'text' => $this->getMessage($this->aliasField('tabTitle'))
            ]
        ];
        $tabElements = $this->controller->TabPermission->checkTabPermission($tabElements);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());

        $roleOptions = $this->Roles->find('list')->toArray();
        $this->ControllerAction->field('users', [
            'type' => 'user_table',
            'valueClass' => 'table-full-width',
            'roleOptions' => $roleOptions,
            'visible' => ['index' => false, 'view' => true, 'edit' => true]
        ]);

        $this->ControllerAction->setFieldOrder(['name', 'users']);
    }

    public function indexBeforeAction(Event $event)
    {
        $this->ControllerAction->field('no_of_users', ['visible' => ['index' => true]]);
        $this->ControllerAction->setFieldOrder(['name', 'no_of_users']);
    }

    public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options)
    {
        $queryParams = $request->query;

        $query->find('inInstitutions');

        if (!array_key_exists('sort', $queryParams) && !array_key_exists('direction', $queryParams)) {
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

    public function viewAfterAction(Event $event, Entity $entity)
    {
        $this->request->data[$this->alias()]['security_group_id'] = $entity->id;
    }

    public function onGetUserTableElement(Event $event, $action, $entity, $attr, $options = [])
    {
        $tableHeaders = [__('OpenEMIS ID'), __('Name'), __('Role')];
        $tableCells = [];
        $alias = $this->alias();
        $key = 'users';

        if ($action == 'index') {
            // not showing
        } else if ($action == 'view') {
            $roleOptions = $attr['roleOptions'];
            $associated = $entity->extractOriginal([$key]);
            if (!empty($associated[$key])) {
                foreach ($associated[$key] as $i => $obj) {
                    $rowData = [];
                    $rowData[] = $event->subject()->Html->link($obj->openemis_no, [
                        'plugin' => 'Directory',
                        'controller' => 'Directories',
                        'action' => 'Directories',
                        'view',
                        $this->paramsEncode(['id' => $obj->id])
                    ]);
                    $rowData[] = $obj->name;
                    $roleId = $obj->_joinData->security_role_id;

                    if (array_key_exists($roleId, $roleOptions)) {
                        $rowData[] = $roleOptions[$roleId];
                    } else {
                        $this->log(__METHOD__ . ': Orphan record found for role id: ' . $roleId, 'debug');
                        $rowData[] = '';
                    }
                    $tableCells[] = $rowData;
                }
            }
        }
        $attr['tableHeaders'] = $tableHeaders;
        $attr['tableCells'] = $tableCells;

        return $event->subject()->renderElement('Security.Groups/' . $key, ['attr' => $attr]);
    }

    public function findInInstitutions(Query $query, array $options)
    {
        $query->innerJoin(['Institutions' => 'institutions'], ['Institutions.security_group_id = SystemGroups.id']);
        return $query;
    }

    public function onGetNoOfUsers(Event $event, Entity $entity)
    {
        $id = $entity->id;

        $GroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
        $count = $GroupUsers->findAllBySecurityGroupId($id)->count();

        return $count;
    }
}
