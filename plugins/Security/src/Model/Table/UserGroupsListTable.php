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
use App\Model\Table\ControllerActionTable;
use Cake\I18n\Time;
use Cake\Utility\Text;


class UserGroupsListTable extends ControllerActionTable
{
    use MessagesTrait;
    use HtmlTrait;

    public function initialize(array $config)
    {
        $this->table('security_group_users');
        parent::initialize($config);

        // $this->belongsToMany('Users', [
        //     'className' => 'Security.Users',
        //     'joinTable' => 'security_group_users',
        //     'foreignKey' => 'security_group_id',
        //     'targetForeignKey' => 'security_user_id',
        //     'through' => 'Security.SecurityGroupUsers',
        //     'dependent' => true
        // ]);

        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'security_user_id']);
        $this->belongsTo('SecurityRoles', ['className' => 'Security.SecurityRoles', 'foreignKey' => 'security_role_id']);

        $this->addBehavior('OpenEmis.Autocomplete');  
        $this->addBehavior('User.AdvancedNameSearch');
        $this->toggle('view', false);
        $this->toggle('edit', true); //POCOR-7323
        $this->toggle('search', true);
        $this->toggle('add', true);

        $this->setDeleteStrategy('restrict');
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.ajaxUserAutocomplete'] = 'ajaxUserAutocomplete';
        return $events;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {

        $this->field('security_group_id', [
            'visible' => false]);
        $this->field('security_user_id', [
            'visible' => ['index' => true, 'view' => true, 'edit' => true, 'add' => true]]);      
        $this->field('security_role_id', ['source_model' => 'Security.SecurityRoles']);

        $this->setFieldOrder([
            'security_user_id','security_role_id'
        ]);

        //POCOR-7175 start
        $toolbarButtons = $extra['toolbarButtons'];
        $extra['toolbarButtons']['back'] = [
            'url' => [
                'plugin' => 'Security',
                'controller' => 'Securities',
                'action' => 'UserGroups',
                '0' => 'index',
            ],
            'type' => 'button',
            'label' => '<i class="fa kd-back"></i>',
            'attr' => [
                'class' => 'btn btn-xs btn-default',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false,
                'title' => __('Back')
            ]
        ]; //POCOR-7175 end
    }

    /** Start POCOR 7323 */
    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        $buttons['remove'] = [
            'url' => [
                'plugin' => 'Security',
                'controller' => 'Securities',
                'action' => 'UserGroupsList',
                '0' => 'remove',
                '_ext'=>'',
                'userGroupId'=> $buttons['edit']['url']['userGroupId'],
                '1' =>$buttons['edit']['url']['1']
            ],
            'type' => 'button',
            'label' => '<i class="fa kd-trash"></i> Delete',
            'attr' => [
                'class' => 'btn btn-xs btn-default',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false,
                'title' => __('Delete')
            ]
        ];
        unset($buttons['edit']);
        return $buttons;
    }
    /** End POCOR 7323 */

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('openemis_no', [
            'visible' => true]);
        $this->setFieldOrder(['openemis_no','security_user_id', 'security_role_id']);
    }
    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $userGroupId = $this->request->query['userGroupId']; 
        $query->contain(['Users','SecurityRoles'])
        ->where([$this->aliasField('security_group_id')=>$userGroupId])
        ->order([$this->aliasField('created DESC')]);

        //POCOR-7175 start
        $queryParams = $this->request->query;
        $search = $this->getSearchKey();

        // CUSTOM SEACH - 
        $extra['auto_search'] = false; // it will append an AND
        if (!empty($search)) {
            $query->find('byUserNameRole', ['search' => $search]);
        }
        //POCOR-7175 end

    }

    public function onUpdateFieldSecurityRoleId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $attr['type'] = 'select';
            $attr['options'] = TableRegistry::get('Security.SecurityRoles')->getSystemRolesList();
        }

        return $attr;
    }

    public function onGetOpenemisNo(Event $event, Entity $entity)
    {
        return $entity->user->openemis_no;
    }

    public function onUpdateFieldSecurityUserId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $attr['type'] = 'autocomplete';
            $attr['target'] = ['key' => 'security_user_id', 'name' => $this->aliasField('security_user_id')];
            $attr['noResults'] = __('No Guardian found.');
            $attr['attr'] = ['placeholder' => __('OpenEMIS ID, Identity Number or Name')];
            $action = 'UserGroupsList';
            if ($this->controller->name == 'Securities') {
                $action = 'UserGroupsList';
            }
            $attr['url'] = ['controller' => $this->controller->name, 'action' => $action, 'ajaxUserAutocomplete'];

            $requestData = $this->request->data;
            if (isset($requestData) && !empty($requestData[$this->alias()]['security_user_id'])) {
                $guardianId = $requestData[$this->alias()]['security_user_id'];
                $guardianName = $this->Users->get($guardianId)->name_with_id;

                $attr['attr']['value'] = $guardianName;
            }

            $iconSave = '<i class="fa fa-check"></i> ' . __('Save');
            $iconAdd = '<i class="fa kd-add"></i> ' . __('Create New');
            $attr['onNoResults'] = "$('.btn-save').html('" . $iconAdd . "').val('new')";
            $attr['onBeforeSearch'] = "$('.btn-save').html('" . $iconSave . "').val('save')";
            $attr['onSelect'] = "$('#reload').click();";
        } 
        return $attr;
    }

    public function ajaxUserAutocomplete()
    {
        $this->controller->autoRender = false;
        $this->ControllerAction->autoRender = false;

        if ($this->request->is(['ajax'])) {
            $term = $this->request->query['term'];

            $UserIdentitiesTable = TableRegistry::get('User.Identities');

            $query = $this->Users
                ->find()
                ->select([
                    $this->Users->aliasField('openemis_no'),
                    $this->Users->aliasField('first_name'),
                    $this->Users->aliasField('middle_name'),
                    $this->Users->aliasField('third_name'),
                    $this->Users->aliasField('last_name'),
                    $this->Users->aliasField('preferred_name'),
                    $this->Users->aliasField('id')
                ])
                ->leftJoin(
                    [$UserIdentitiesTable->alias() => $UserIdentitiesTable->table()],
                    [
                        $UserIdentitiesTable->aliasField('security_user_id') . ' = ' . $this->Users->aliasField('id')
                    ]
                )
                ->group([
                    $this->Users->aliasField('id')
                ])
                ->limit(100);

            $term = trim($term);
            if (!empty($term)) {
                $query = $this->addSearchConditions($query, ['alias' => 'Users', 'searchTerm' => $term, 'OR' => ['`Identities`.number LIKE ' => $term . '%']]);
            }

            $list = $query->all();

            $data = [];
            foreach ($list as $obj) {
                $label = sprintf('%s - %s', $obj->openemis_no, $obj->name);
                $data[] = ['label' => $label, 'value' => $obj->id];
            }

            echo json_encode($data);
            die;
        }
    }

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options) 
    {
        $SecurityGroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
        $userGroupId = $this->request->query['userGroupId'];    
        $entity->security_group_id = $userGroupId;
    }

    //POCOR-7175
    public function findByUserNameRole(Query $query, array $options)
    {
        if (array_key_exists('search', $options)) {
            $search = $options['search'];
            $query
            ->join([
                [
                    'table' => 'security_users', 'alias' => 'Users', 'type' => 'LEFT',
                    'conditions' => ['security_users.id = ' . $this->aliasField('security_user_id')]
                ],
                [
                    'table' => 'security_roles', 'alias' => 'SecurityRoles', 'type' => 'LEFT',
                    'conditions' => [
                        'security_roles.id = ' . $this->aliasField('security_role_id')]
                ],
                
            ])
            ->where([
                    'OR' => [
                        ['Users.openemis_no LIKE' => '%' . $search . '%'],
                        ['Users.first_name LIKE' => '%' . $search . '%'],
                        ['Users.last_name LIKE' => '%' . $search . '%'],
                        ['Users.middle_name LIKE' => '%' . $search . '%'],
                        ['Users.third_name LIKE' => '%' . $search . '%'],
                        ['SecurityRoles.name LIKE' => '%' . $search . '%']
                    ]
                ]
            );
        }

        return $query;
    }

    // Start POCOR-7342

    public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        $securityGroup = TableRegistry::get('security_groups');
        $securityGroupData = $securityGroup->find()
        ->where([$securityGroup->aliasField('id') =>$entity->security_group_id])
        ->first();
        
        $securityGroupUsersTbl = TableRegistry::get('security_group_users');
        $securityGroupUsers = $securityGroupUsersTbl->find()
        ->where([
            $securityGroupUsersTbl->aliasField('security_group_id') => $entity->security_group_id,
        ])->count();

        $extra['associatedRecords'][] = ['model' => 'Security User', 'count' => $securityGroupUsers, 'title'=>$securityGroupData->name];
        $entity->showDeletedValueAs = $securityGroupData->name;
    }

    // End POCOR-7342
    
}
