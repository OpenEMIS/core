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

class UserGroupsTable extends ControllerActionTable
{
    use MessagesTrait;
    use HtmlTrait;

    public function initialize(array $config)
    {
        $this->table('security_groups');
        parent::initialize($config);

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

        $this->belongsToMany('Institutions', [
            'className' => 'Institution.Institutions',
            'joinTable' => 'security_group_institutions',
            'foreignKey' => 'security_group_id',
            'targetForeignKey' => 'institution_id',
            'through' => 'Security.SecurityGroupInstitutions',
            'dependent' => true
        ]);


        $this->belongsToMany('Roles', [
            'className' => 'Security.SecurityRoles',
            'joinTable' => 'security_group_users',
            'foreignKey' => 'security_group_id',
            'targetForeignKey' => 'security_role_id',
            'through' => 'Security.SecurityGroupUsers',
            'dependent' => true
        ]);
        // $this->setDeleteStrategy('restrict');
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $newEvent = [
            'Model.SecurityGroupInstitutions.afterSave' => 'institutionAfterSave'
        ];

        $events = array_merge($events, $newEvent);
        return $events;
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $controller = $this->controller;
        $tabElements = [
            $this->alias() => [
                'url' => ['plugin' => $controller->plugin, 'controller' => $controller->name, 'action' => $this->alias()],
                'text' => $this->getMessage($this->aliasField('tabTitle'))
            ],
            'SystemGroups' => [
                'url' => ['plugin' => $controller->plugin, 'controller' => $controller->name, 'action' => 'SystemGroups'],
                'text' => $this->getMessage('SystemGroups.tabTitle')
            ]
        ];
        $tabElements = $this->controller->TabPermission->checkTabPermission($tabElements);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());

        $this->field('area_id', [
            'title' => __('Area Education'),
            'source_model' => 'Area.Areas',
            'displayCountry' => false,
            'attr' => ['label' => __('Area Education')]]);

        $this->field('institution_id', [
            'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]]);

        $this->setFieldOrder([
            'name', 'area_id', 'institution_id'
        ]);
    }

    /** Start POCOR 7213 */

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        $buttons['edit']['label'] = '<i class="fa fa-edit"></i> Edit Group';
        $manageUsersBtn = ['manage_users' => $buttons['view']];
        $manageUsersBtn['manage_users']['url'] = [
            'plugin' => 'Security',
            'controller' => 'Securities',
            'action' => 'UserGroupsList',
            'userGroupId' => $entity->id,
            'index'
        ];
        $manageUsersBtn['manage_users']['label'] = '<i class="fa fa-key"></i>' . __('Manage Users');
        $buttons = array_merge($manageUsersBtn, $buttons);
        return $buttons;
    }

    /** End POCOR 7213 */

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('no_of_users', ['visible' => ['index' => true]]);
        $this->setFieldOrder(['name', 'no_of_users', 'institution_id']);

        // Start POCOR-5188
        $is_manual_exist = $this->getManualUrl('Administration', 'Groups', 'Security');
        if (!empty($is_manual_exist)) {
            $btnAttr = [
                'class' => 'btn btn-xs btn-default icon-big',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false,
                'target' => '_blank'
            ];

            $helpBtn['url'] = $is_manual_exist['url'];
            $helpBtn['type'] = 'button';
            $helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
            $helpBtn['attr'] = $btnAttr;
            $helpBtn['attr']['title'] = __('Help');
            $extra['toolbarButtons']['help'] = $helpBtn;
        }
        // End POCOR-5188
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
        $queryParams = $this->request->query;
        $query->find('notInInstitutions');
        // filter groups by users permission
        if ($this->Auth->user('super_admin') != 1) {
            $userId = $this->Auth->user('id');
            $query->where([
                'OR' => [
                    'EXISTS (SELECT `id` FROM `security_group_users` WHERE `security_group_users`.`security_group_id` = `UserGroups`.`id` AND `security_group_users`.`security_user_id` = ' . $userId . ')',
                    'UserGroups.created_user_id' => $userId
                ]
            ]);
        }
        $extra['order'] = [$this->aliasField('name') => 'asc'];

        $search = $this->getSearchKey();

        // CUSTOM SEACH - Institution Code, Institution Name, Area Code and Area Name
        $extra['auto_search'] = false; // it will append an AND
        if (!empty($search)) {
            $query->find('byInstitutionAreaNameCode', ['search' => $search]);
        }
    }

    //POCOR-7168
    public function findByInstitutionAreaNameCode(Query $query, array $options)
    {
        if (array_key_exists('search', $options)) {
            $search = $options['search'];
            $query
                ->join([
                    [
                        'table' => 'security_group_institutions', 'alias' => 'SecurityGroupInstitutions', 'type' => 'LEFT',
                        'conditions' => ['SecurityGroupInstitutions.security_group_id = ' . $this->aliasField('id')]
                    ],
                    [
                        'table' => 'institutions', 'alias' => 'Institutions', 'type' => 'LEFT',
                        'conditions' => [
                            'Institutions.id = ' . 'SecurityGroupInstitutions.institution_id',
                        ]
                    ],
                    [
                        'table' => 'security_group_areas', 'alias' => 'SecurityGroupAreas', 'type' => 'LEFT',
                        'conditions' => ['SecurityGroupAreas.security_group_id = ' . $this->aliasField('id')]
                    ],
                    [
                        'table' => 'areas', 'alias' => 'Areas', 'type' => 'LEFT',
                        'conditions' => [
                            'Areas.id = ' . 'SecurityGroupAreas.area_id',
                        ]
                    ],
                ])
                ->where([
                        'OR' => [
                            ['Institutions.code LIKE' => '%' . $search . '%'],
                            ['Institutions.name LIKE' => '%' . $search . '%'],
                            ['Areas.code LIKE' => '%' . $search . '%'],
                            ['Areas.name LIKE' => '%' . $search . '%'],
                            [$this->aliasField('name') . ' LIKE' => '%' . $search . '%']
                        ]
                    ]
                )
                ->group($this->aliasField('id'));
        }

        return $query;
    }

    public function findNotInInstitutions(Query $query, array $options)
    {
        $query->where([
            'NOT EXISTS (SELECT `id` FROM `institutions` WHERE `security_group_id` = `UserGroups`.`id`)'
        ]);
        return $query;
    }

    public function editAfterAction(Event $event, Entity $entity)
    {
        $this->field('area_id', [
            'type' => 'chosenSelect',
            'attr' => [
                'label' => __('Area'),
            ],
            'entity' => $entity,
        ]);
        $this->setFieldOrder([
            'name', 'area_id', 'institution_id'
        ]);
    }


    public function onUpdateFieldAreaId(Event $event, array $attr, $action, Request $request)
    {
        $areaId = isset($request->data) ? $request->data['UserGroups']['area_id']['_ids'] : 0;
//        $this->log($attr, 'debug');
        $Areas = TableRegistry::get('Area.Areas');
        if ($action == 'add' || $action == 'edit') {
            $areaOptions = $Areas
                ->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
                ->order([$Areas->aliasField('order')]);
            $areaOptionsList = $areaOptions->toArray();
            $attr['type'] = 'chosenSelect';
            $attr['attr']['multiple'] = true;
//            $attr['select'] = true;
            $attr['options'] = $areaOptionsList;
            // $attr['onChangeReload'] = false;//POCOR-7744 //for removing area properly
        } else {
            $attr['type'] = 'hidden';
        }
        if ($action == 'edit') {
            $entity = $attr['entity'];
            if ($entity) {
                if ($areaId) {
                    $attr['value'] = $areaId;
                    $attr['attr']['value'] = $areaId;

                } else {
                    $attr['value'] = $this->getAreaIdList($entity);
                    $attr['attr']['value'] = $this->getAreaIdList($entity);
                }
            }
        }
        return $attr;

    }

    public function onGetAreaAdministrativeId(Event $event, Entity $entity)
    {
        return $this->getAreaList($entity);
    }

    /**
    * Function to show institution name on view page
    * @author Ehteram Ahmad <ehteram.ahmad@mail.valuecoders.com>
    * return string
    * @ticket POCOR-7187
    */
    public function onGetInstitutionId(Event $event, Entity $entity)
    {

        $SecurityGroupInstitutions = TableRegistry::get('security_group_institutions');
        $InstitutionsTable = TableRegistry::get('institutions');
        //POCOR-7331 start
        $SecurityGroupInstitutionsData = $SecurityGroupInstitutions
            ->find()
            ->where(['security_group_id' => $entity->id])
            ->toArray();
        if (empty($SecurityGroupInstitutionsData)) {
            $SecurityGroupInstitutionsData = [0];
        }
        $InstitutionsTableData = [];
        foreach ($SecurityGroupInstitutionsData as $value) {
            $record = $InstitutionsTable
                ->find()
                ->select($InstitutionsTable->aliasField('name'))
                ->where(['id' => $value['institution_id']])
                ->first();
            $InstitutionsTableData[] = $record;
        }

        return isset($InstitutionsTableData) ? $InstitutionsTableData : '';
        //POCOR-7331 ends
    }


    /**
     * @param Event $event
     * @param array $attr
     * @param $action
     * @param Request $request
     * @author Khindol Madraimov <khindol.madraimov@gmail.com>
     * @return array
     */
     public function onUpdateFieldInstitutionId(Event $event, array $attr, $action, Request $request)
    {
        $areaList = isset($request->data) ? $request->data['UserGroups']['area_id']['_ids'] : null;
        if ($action == 'edit') {
            $institutionsValuesList = isset($request->data) ? $request->data['UserGroups']['institution_id']['_ids'] : 0;
            if ($action == 'edit') {
                $entity = $attr['entity'];
                if ($entity) {
                    if ($institutionsValuesList) {
                        $attr['value'] = $institutionsValuesList;
                        $attr['attr']['value'] = $institutionsValuesList;

                    } else {
                        $attr['value'] = $this->getInstitutionIdList($entity);
                        $attr['attr']['value'] = $this->getInstitutionIdList($entity);
                    }
                }
            }

        }
        $institutionList = $this->getInstitutionOptions($areaList);
        $attr['type'] = 'chosenSelect';
        $attr['attr']['multiple'] = true;
        $attr['options'] = $institutionList;
        $attr['attr']['required'] = false;//POCOR-7254
        return $attr;
    }

    public
    function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
        $toolbarAttr = [
            'class' => 'btn btn-xs btn-default',
            'data-toggle' => 'tooltip',
            'data-placement' => 'bottom',
            'escape' => false
        ];

        $listUrl = [
            'plugin' => 'Security',
            'controller' => 'Securities',
            'action' => 'UserGroupsList',
            'userGroupId' => $entity->id,
            'index'
        ];

        $listButton['url'] = $listUrl;
        $listButton['type'] = 'button';
        $listButton['attr'] = $toolbarAttr;
        $listButton['label'] = '<i class="fa kd-lists"></i>';
        $listButton ['attr']['title'] = __('List');
        $extra['toolbarButtons']['list'] = $listButton;
    }

    public
    function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public
    function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $SecurityGroupInstitutions = TableRegistry::get('Security.SecurityGroupInstitutions');
        $dispatchTable = [];
        $dispatchTable[] = $SecurityGroupInstitutions;
        foreach ($dispatchTable as $model) {
            $model->dispatchEvent('Model.SecurityGroupInstitutions.afterSave', [$entity], $this);
        }
    }

    private function setupFields(Entity $entity = null)
    {
        $attr = [];
        if (!is_null($entity)) {
            $attr['attr'] = ['entity' => $entity];
        }

        $this->field('area_administrative_id', [
            'attr' => [
                'label' => __('Area Education')
            ],
            'visible' => [
                'index' => false,
                'view' => true,
                'edit' => false,
                'add' => true],
            'entity' => $entity
        ]);

        $this->field('area_id', [
            'type' => 'areapicker',
            'source_model' => 'Area.Areas',
            'displayCountry' => false]);

        $this->field('institution_id', [
            'visible' => ['index' => false,
                'view' => true,
                'edit' => true,
                'add' => true],
            'entity' => $entity
        ]);
        $this->setFieldOrder([
            'name', 'area_id', 'institution_id'
        ]);
    }

    public
    function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        //POCOR-7187[START]
        $SecurityGroupInstitutions = TableRegistry::get('Security.SecurityGroupInstitutions');
        $SecurityGroupInstitutionsData = $SecurityGroupInstitutions
            ->find()
            ->where(['security_group_id' => $query->toArray()[0]->id])
            ->first();
        if (!empty($SecurityGroupInstitutionsData)) { //POCOR-7187[END]
            $SecurityGroupId = $this->paramsDecode($this->request->params['pass'][1]);
            $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
                return $results->map(function ($row) {
                    $SecurityGroupInstitutions = TableRegistry::get('Security.SecurityGroupInstitutions');

                    $institution = TableRegistry::get('institutions');
                    $SecurityGroupInstitutionsData = $SecurityGroupInstitutions->find()
                        // ->contain(['Institutions'])
                        ->select([
                            $institution->aliasField('id')
                        ])
                        ->leftJoin([$institution->alias() => $institution->table()], [
                            $SecurityGroupInstitutions->aliasField('institution_id = ') . $institution->aliasField('id')
                        ])
                        ->where([$SecurityGroupInstitutions->aliasField('security_group_id') => $row->id])
                        ->toArray();

                    if (!empty($SecurityGroupInstitutionsData)) {
                        foreach ($SecurityGroupInstitutionsData AS $institutionData) {
                            $institutionArr[] = $institutionData->institutions['id'];
                        }

                        $Institutions = TableRegistry::get('Institution.Institutions');
                        $InstitutionsResult = $Institutions
                            ->find()
                            ->where(['id IN' => $institutionArr])
                            ->all();

                        foreach ($InstitutionsResult AS $InstitutionsResultData) {
                            $InstitutionsData[] = $InstitutionsResultData;
                        }
                        $row['institution_id'] = $InstitutionsData;

                        $SecurityGroupAreas = TableRegistry::get('Security.SecurityGroupAreas');
                        $areas = TableRegistry::get('areas');

                        $SecurityGroupAreasData = $SecurityGroupAreas->find()
                            ->select([
                                $areas->aliasField('id')
                            ])
                            ->leftJoin([$areas->alias() => $areas->table()], [
                                $SecurityGroupAreas->aliasField('area_id = ') . $areas->aliasField('id')
                            ])
                            ->where([$SecurityGroupAreas->aliasField('security_group_id') => $row->id])
                            ->toArray();
                        if ($SecurityGroupAreasData) {
                            foreach ($SecurityGroupAreasData AS $AreaData) {
                                $areaArr[] = $AreaData->areas['id'];
                            }
                            $Areas = TableRegistry::get('Area.Areas');
                            $AreasResult = $Areas
                                ->find()
                                ->where(['id IN' => $areaArr])
                                ->all();

                            foreach ($AreasResult AS $AreaResultData) {
                                $AreaDataVal[] = $AreaResultData;
                            }
                            $row['area_id'] = $AreaDataVal;
                        }
                        return $row;
                    }
                });
            });
        }
    }

    public
    function editBeforeSave(Event $event, Entity $entity, ArrayObject $extra)
    {
        $SecurityInstitutions = TableRegistry::get('Security.SecurityGroupInstitutions');
        $SecurityGroupAreas = TableRegistry::get('Security.SecurityGroupAreas');

        $conditions1 = [
            $SecurityInstitutions->aliasField('security_group_id') => $entity->id
        ];

        $SecurityInstitutions->deleteAll($conditions1);

        $conditions2 = [
            $SecurityGroupAreas->aliasField('security_group_id') => $entity->id
        ];
        $SecurityGroupAreas->deleteAll($conditions2);

        $SecurityInstitutions = TableRegistry::get('Security.SecurityGroupInstitutions');
        $SecurityGroupAreas = TableRegistry::get('Security.SecurityGroupAreas');
        if ($entity->institution_id['_ids']) {
            foreach ($entity->institution_id['_ids'] as $key => $value) {
                $securityInstitution = $SecurityInstitutions->newEntity([
                    'security_group_id' => $entity->id,
                    'institution_id' => $value
                ]);
                $SecurityInstitutions->save($securityInstitution);
            }
        }
        if ($entity->area_id['_ids']) {
            foreach ($entity->area_id['_ids'] as $key => $value) {
                $securityArea = $SecurityGroupAreas->newEntity([
                    'security_group_id' => $entity->id,
                    'area_id' => $value
                ]);
                $SecurityGroupAreas->save($securityArea);
            }
        }
    }

    /**
     * @param Entity $entity
     * @param array $areaArr
     * @param array $AreaDataVal
     * @return string
     */
    private
    function getAreaList(Entity $entity)
    {
        $areaArr = $this->getAreaIdList($entity);

        if (empty($areaArr)) {
            $areaArr = [0];
        }
        $AreaDataVal = [];
        $Areas = TableRegistry::get('Area.Areas');
        if (!empty($areaArr)) {
            $AreasResult = $Areas
                ->find('list')
                ->where(['id IN' => $areaArr])
                ->toArray();
            foreach ($AreasResult AS $AreaResultData) {
                $AreaDataVal[] = $AreaResultData;
            }
        }

        return (!empty($AreaDataVal)) ? implode(', ', $AreaDataVal) : "";//POCOR-7254
    }

    /**
     * @param Entity|null $entity
     * @return array
     * @author Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private
    function getAreaIdList(Entity $entity = null)
    {
        $areaArr = [];
        if ($entity) {
            $SecurityGroupAreas = TableRegistry::get('security_group_areas');
            $result = $SecurityGroupAreas
                ->find()
                ->select([$SecurityGroupAreas->aliasField('area_id')])
                ->where(['security_group_id' => $entity->id])
                ->all();
        }
        if (!$entity) {
            $Areas = TableRegistry::get('areas');
            $result = $Areas
                ->find()
                ->select(['area_id' => $Areas->aliasField('id')])
                ->all();
        }
        foreach ($result AS $AreaData) {
            $areaArr[] = $AreaData->area_id;
        }
        return $areaArr;
    }

    /**
     * @param Entity $entity
     * @return array
     * @author Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private
    function getInstitutionIdList(Entity $entity)
    {
        $institutionArr = [];
        if ($entity) {
            $SecurityGroupInstitutions = TableRegistry::get('security_group_institutions');
            $result = $SecurityGroupInstitutions
                ->find()
                ->select([$SecurityGroupInstitutions->aliasField('institution_id')])
                ->where(['security_group_id' => $entity->id])
                ->all();
        }
        foreach ($result AS $InstitutionData) {
            $institutionArr[] = $InstitutionData->institution_id;
        }
        return $institutionArr;
    }

    /**
     * attention! recursive function
     * @param $ids
     * @param $idArray
     * @return array
     * @author for a patch Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    public function getChildren($ids, $idArray) {

        $Areas = TableRegistry::get('Area.Areas');
        $result = $Areas->find()
            ->where([
                $Areas->aliasField('parent_id IN') => $ids
            ])
            ->toArray();
        foreach ($result as $key => $value) {
            $idArray[] = $value['id'];
            $idArray = $this->getChildren([$value['id']], $idArray);
        }
        return $idArray;
    }

    /**
     * @param null $areaList
     * @return array
     * @author Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private function getInstitutionOptions($areaList = null)
    {
        $Institutions = TableRegistry::get('Institution.Institutions');
        $InstitutionStatuses = TableRegistry::get('institution_statuses');
        $institutionQuery = $Institutions
            ->find('list', [
                'keyField' => 'id',
                'valueField' => 'code_name'
            ])
            ->innerJoin([$InstitutionStatuses->alias() => $InstitutionStatuses->table()],
                [$InstitutionStatuses->aliasField('id = ')
                    . $Institutions->aliasField('institution_status_id')])
            ->where([$InstitutionStatuses->aliasField('code') => 'ACTIVE'])
            ->order([
                $Institutions->aliasField('code') => 'ASC',
                $Institutions->aliasField('name') => 'ASC'
            ]);
        if($areaList){
            $areaIds = $areaList;
            $allgetArea = $this->getChildren($areaList, $areaIds);
            if(empty($allgetArea)){
                $allgetArea = [-1];
            }
            $allgetArea = array_unique($allgetArea);
            $institutionQuery->where([$Institutions->aliasField('area_id IN') => $allgetArea]);
        }
        $institutionList = $institutionQuery->toArray();

        return $institutionList;
    }
}
