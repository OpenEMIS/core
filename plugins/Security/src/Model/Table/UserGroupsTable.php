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

        $this->field('area_id', ['title' => __('Area Education'), 'source_model' => 'Area.Areas', 'displayCountry' => false,'attr' => ['label' => __('Area Education')]]);

        $this->field('institution_id', [
            'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]]);      

        $this->setFieldOrder([
            'name','institution_id','area_id'
        ]);
    }

    /** Start POCOR 7213 */

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        $buttons['edit']['label'] =  '<i class="fa fa-edit"></i> Edit Group';
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
        $this->setFieldOrder(['name', 'no_of_users','institution_id']);

        // Start POCOR-5188
		$is_manual_exist = $this->getManualUrl('Administration','Groups','Security');       
		if(!empty($is_manual_exist)){
			$btnAttr = [
				'class' => 'btn btn-xs btn-default icon-big',
				'data-toggle' => 'tooltip',
				'data-placement' => 'bottom',
				'escape' => false,
				'target'=>'_blank'
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
                        [$this->aliasField('name').' LIKE' => '%'.$search.'%']
                    ]
                ]
            )
            ->group($this->aliasField('id'))
            ;
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

    public function onUpdateFieldAreaId(Event $event, array $attr, $action, Request $request)
    {
        $areaId = isset($request->data) ? $request->data['UserGroups']['area_id']['_ids'] : 0;
        $flag = 1;
        if(!isset($areaId[1])){
            $flag = 0;
        }else if(isset($areaId[1]) && $areaId[0] == '-1'){
            $flag = 1;
        }
        else{
            $flag = 0;
        }
        $Areas = TableRegistry::get('Area.Areas');
        
        $entity = $attr['entity'];

        if ($action == 'add' || $action == 'edit') {
            $areaOptions = $Areas
                ->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
                ->order([$Areas->aliasField('order')]);

            $attr['type'] = 'chosenSelect';
            $attr['attr']['multiple'] = true;
            // $attr['select'] = true;
            $areaOptionsList = $areaOptions->toArray();
            if (count($areaOptionsList) > 1) {
                if($flag == 0){
                    $attr['options'] = $areaOptions->toArray();
                }else{
                    $attr['options'] = $areaOptions->toArray();
                }
            }else{
                $attr['options'] = $areaOptions->toArray();
            }
            $attr['onChangeReload'] = true;
        } else {
            $attr['type'] = 'hidden';
        }
           
        return $attr;

    }
    public function onGetAreaAdministrativeId(Event $event, Entity $entity)
    {
        $SecurityGroupAreas = TableRegistry::get('Security.SecurityGroupAreas');
        $result = $SecurityGroupAreas
            ->find()
            ->select([$SecurityGroupAreas->aliasField('area_id')])
            ->where(['security_group_id' => $entity->id])
            ->all();
        
        foreach($result AS $AreaData){
            $areaArr[] = $AreaData->area_id;
        }

        $Areas = TableRegistry::get('Area.Areas');
        if(!empty($areaArr)){
            $AreasResult = $Areas
            ->find('list')
            ->where(['id IN' => $areaArr])
            ->toArray();
            foreach($AreasResult AS $AreaResultData){
                $AreaDataVal[] =  $AreaResultData;
            }
        }
        return (!empty($AreaDataVal))? implode(', ', $AreaDataVal): '';//POCOR-7254
    }

    /*
    * Function to show institution name on view page
    * @author Ehteram Ahmad <ehteram.ahmad@mail.valuecoders.com>
    * return string
    * @ticket POCOR-7187
    */

    public function onGetInstitutionId(Event $event, Entity $entity)
    {
        $SecurityGroupInstitutions = TableRegistry::get('Security.SecurityGroupInstitutions');
        $InstitutionsTable = TableRegistry::get('Institution.Institutions');
        $SecurityGroupInstitutionsData = $SecurityGroupInstitutions
                            ->find()
                            ->where(['security_group_id' => $entity->id])
                            ->first();
        $InstitutionsTableData = $InstitutionsTable
                            ->find()
                            ->where(['id' => $SecurityGroupInstitutionsData->institution_id])
                            ->first();
        return isset($InstitutionsTableData->name) ? $InstitutionsTableData->name : '';
    }


    public function onUpdateFieldInstitutionId(Event $event, array $attr, $action, Request $request)
    {
        if($action == 'add'){
            $areaId = isset($request->data) ? $request->data['UserGroups']['area_id']['_ids'] : 0;
            $string_version = implode(',', $areaId);
            $AreaT = TableRegistry::get('areas');                    
            //Level-1
            $AreaData = $AreaT->find('all',['fields'=>'id'])->where(['parent_id' => $string_version])->toArray();
            $childArea =[];
            $childAreaMain = [];
            $childArea3 = [];
            $childArea4 = [];
            foreach($AreaData as $kkk =>$AreaData11 ){
                $childArea[$kkk] = $AreaData11->id;
            }
            //level-2
            foreach($childArea as $kyy =>$AreaDatal2 ){ 
                $AreaDatas = $AreaT->find('all',['fields'=>'id'])->where(['parent_id' => $AreaDatal2])->toArray();
                foreach($AreaDatas as $ky =>$AreaDatal22 ){
                    $childAreaMain[$kyy.$ky] = $AreaDatal22->id;
                }
            }
            //level-3
            if(!empty($childAreaMain)){
                foreach($childAreaMain as $kyy =>$AreaDatal3 ){ 
                    $AreaDatass = $AreaT->find('all',['fields'=>'id'])->where(['parent_id' => $AreaDatal3])->toArray();
                    foreach($AreaDatass as $ky =>$AreaDatal222 ){
                        $childArea3[$kyy.$ky] = $AreaDatal222->id;
                    }
                }
            }
            //level-4
            if(!empty($childAreaMain)){
                foreach($childArea3 as $kyy =>$AreaDatal4 ){
                    $AreaDatasss = $AreaT->find('all',['fields'=>'id'])->where(['parent_id' => $AreaDatal4])->toArray();
                    foreach($AreaDatasss as $ky =>$AreaDatal44 ){
                        $childArea4[$kyy.$ky] = $AreaDatal44->id;
                    }
                }
            }
            
            $mergeArr = array_merge($childAreaMain,$childArea,$childArea3,$childArea4);
            array_push($mergeArr,$string_version);
            $mergeArr = array_unique($mergeArr);
            $finalIds = implode(',',$mergeArr);
            $areaId = explode(',',$finalIds);
        }else  { 
            $areaId = isset($request->data) ? $request->data['UserGroups']['area_id']['_ids'] : 0;
            //POCOR-6903: Start
            $AreaLevelsTable = TableRegistry::get('Area.AreaLevels');
            $AreaLevelsTableResult = $AreaLevelsTable
                            ->find('list')
                            ->toArray();
            $string_version = implode(',', $areaId);
            $AreaT = TableRegistry::get('areas');                    
            //Level-1
            $AreaData = $AreaT->find('all',['fields'=>'id'])->where(['parent_id' => $string_version])->toArray();
            $childArea =[];
            $childAreaMain = [];
            $childArea3 = [];
            $childArea4 = [];
            foreach($AreaData as $kkk =>$AreaData11 ){
                $childArea[$kkk] = $AreaData11->id;
            }
            //level-2
            foreach($childArea as $kyy =>$AreaDatal2 ){ 
                $AreaDatas = $AreaT->find('all',['fields'=>'id'])->where(['parent_id' => $AreaDatal2])->toArray();
                foreach($AreaDatas as $ky =>$AreaDatal22 ){
                    $childAreaMain[$kyy.$ky] = $AreaDatal22->id;
                }
            }
            //level-3
            if(!empty($childAreaMain)){
                foreach($childAreaMain as $kyy =>$AreaDatal3 ){ 
                    $AreaDatass = $AreaT->find('all',['fields'=>'id'])->where(['parent_id' => $AreaDatal3])->toArray();
                    foreach($AreaDatass as $ky =>$AreaDatal222 ){
                        $childArea3[$kyy.$ky] = $AreaDatal222->id;
                    }
                }
            }
            
            //level-4
            if(!empty($childAreaMain)){
                foreach($childArea3 as $kyy =>$AreaDatal4 ){
                    $AreaDatasss = $AreaT->find('all',['fields'=>'id'])->where(['parent_id' => $AreaDatal4])->toArray();
                    foreach($AreaDatasss as $ky =>$AreaDatal44 ){
                        $childArea4[$kyy.$ky] = $AreaDatal44->id;
                    }
                }
            }
            
            $mergeArr = array_merge($childAreaMain,$childArea,$childArea3,$childArea4);
            array_push($mergeArr,$string_version);
            $mergeArr = array_unique($mergeArr);
            $finalIds = implode(',',$mergeArr);
            $areaId = explode(',',$finalIds);
        }
        $InstitutionsId = isset($request->data) ? $request->data['UserGroups']['institution_id']['_ids'] : 0;
        $institutionList = [];
        $InstitutionsTable = TableRegistry::get('Institution.Institutions');
        $InstitutionStatusesTable = TableRegistry::get('Institution.Statuses');
        $activeStatus = $InstitutionStatusesTable->getIdByCode('ACTIVE');
        if(empty($InstitutionsId[1])){
            if ($areaId[0] == -1 && count($areaId) == 1) {
                $flag = 0;
            }else if($areaId[0] != -1 && count($areaId) >= 1){
                $flag = 1;
            }else{
                $flag = 1;
            }
        }else{
            $flag = 1;
        }
       
        if($areaId[0] != -1 || count($areaId) > 1){
            $AreaArray = [];
            $i=0;
            foreach ($areaId as $akey => $aval) {
                if($aval != -1){
                    $AreaArray[$i] = $aval;
                    $i++;
                }
            }
            $conditions = [
                //$InstitutionsTable->aliasField('area_id IN') => $AreaArray,//POCOR-7254
                $InstitutionsTable->aliasField('institution_status_id') => $activeStatus
            ];
        }else{ 
            $conditions = [$InstitutionsTable->aliasField('institution_status_id') => $activeStatus];
        }
        if ($areaId > 0) {
            $institutionQuery = $InstitutionsTable
            ->find('list', [
                'keyField' => 'id',
                'valueField' => 'code_name'
            ])
            ->where([
                $conditions
            ])
            ->order([
                $InstitutionsTable->aliasField('code') => 'ASC',
                $InstitutionsTable->aliasField('name') => 'ASC'
            ]);
        }else{
            $institutionQuery = $InstitutionsTable
            ->find('list', [
                'keyField' => 'id',
                'valueField' => 'code_name'
            ])
            ->where([
                $InstitutionsTable->aliasField('institution_status_id') => $activeStatus
            ])
            ->order([
                $InstitutionsTable->aliasField('code') => 'ASC',
                $InstitutionsTable->aliasField('name') => 'ASC'
            ]);
        }
        $institutionList = $institutionQuery->toArray();
            
        $attr['type'] = 'chosenSelect';
        $attr['onChangeReload'] = true;
        $attr['attr']['multiple'] = true;
        $attr['options'] = $institutionList;
        $attr['attr']['required'] = false;//POCOR-7254
        
        return $attr;
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
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

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options) 
    {
        $SecurityGroupInstitutions = TableRegistry::get('Security.SecurityGroupInstitutions');
        $dispatchTable = [];
        $dispatchTable[] = $SecurityGroupInstitutions;
        foreach ($dispatchTable as $model) {
            $model->dispatchEvent('Model.SecurityGroupInstitutions.afterSave', [$entity], $this);
        }
    }

    private function setupFields(Entity $entity = null) {
        $attr = [];
        if (!is_null($entity)) {
            $attr['attr'] = ['entity' => $entity];
        }
      
        $this->field('area_administrative_id', [    
            'attr' => [ 
                'label' => __('Area Education') 
            ],  
            'visible' => ['index' => false, 'view' => true, 'edit' => false, 'add' => true] 
        ]);
        $this->field('area_id', ['type' => 'areapicker', 'source_model' => 'Area.Areas', 'displayCountry' => false]);
        $this->field('institution_id', [
            'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]
        ]);
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        //POCOR-7187[START]
        $SecurityGroupInstitutions = TableRegistry::get('Security.SecurityGroupInstitutions');
        $SecurityGroupInstitutionsData = $SecurityGroupInstitutions
                            ->find()
                            ->where(['security_group_id' => $query->toArray()[0]->id])
                            ->first();
        if(!empty($SecurityGroupInstitutionsData)){ //POCOR-7187[END]
            $SecurityGroupId = $this->paramsDecode($this->request->params['pass'][1]);
            $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
                return $results->map(function ($row){
                    $SecurityGroupInstitutions = TableRegistry::get('Security.SecurityGroupInstitutions');

                    $institution = TableRegistry::get('institutions');
                    $SecurityGroupInstitutionsData = $SecurityGroupInstitutions->find()
                    // ->contain(['Institutions'])
                    ->select([
                        $institution->aliasField('id')
                    ])
                    ->leftJoin([$institution->alias() => $institution->table()],[
                        $SecurityGroupInstitutions->aliasField('institution_id = ').$institution->aliasField('id')
                    ])
                    ->where([$SecurityGroupInstitutions->aliasField('security_group_id')=>$row->id])
                    ->toArray();

                    if(!empty($SecurityGroupInstitutionsData)){
                        foreach($SecurityGroupInstitutionsData AS $institutionData){
                            $institutionArr[] = $institutionData->institutions['id'];
                        }

                        $Institutions = TableRegistry::get('Institution.Institutions');
                        $InstitutionsResult = $Institutions
                            ->find()
                            ->where(['id IN' => $institutionArr])
                            ->all();

                        foreach($InstitutionsResult AS $InstitutionsResultData){
                            $InstitutionsData[] =  $InstitutionsResultData;
                        }
                        $row['institution_id'] = $InstitutionsData;
                        
                        $SecurityGroupAreas = TableRegistry::get('Security.SecurityGroupAreas');
                        $areas = TableRegistry::get('areas');

                        $SecurityGroupAreasData = $SecurityGroupAreas->find()
                        ->select([
                            $areas->aliasField('id')
                        ])
                        ->leftJoin([$areas->alias() => $areas->table()],[
                            $SecurityGroupAreas->aliasField('area_id = ').$areas->aliasField('id')
                        ])
                        ->where([$SecurityGroupAreas->aliasField('security_group_id')=>$row->id])
                        ->toArray();
                        if ($SecurityGroupAreasData) {
                            foreach($SecurityGroupAreasData AS $AreaData){
                                $areaArr[] = $AreaData->areas['id'];
                            }
                            $Areas = TableRegistry::get('Area.Areas');
                            $AreasResult = $Areas
                                        ->find()
                                        ->where(['id IN' => $areaArr])
                                        ->all();

                            foreach($AreasResult AS $AreaResultData){
                                $AreaDataVal[] =  $AreaResultData;
                            }
                            $row['area_id'] = $AreaDataVal;
                        }
                        return $row ;
                    }
                });
            });  
        }  
    }

    public function editBeforeSave(Event $event, Entity $entity, ArrayObject $extra)
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
}
