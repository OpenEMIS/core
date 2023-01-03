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
            'name','institutions','area_id'
        ]);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('no_of_users', ['visible' => ['index' => true]]);
        $this->setFieldOrder(['name', 'no_of_users','institutions']);
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

    public function onGetAreaId(Event $event, Entity $entity)
    {
        if ($this->action == 'index') {
            $areaName = $entity->Areas['name'];
            // Getting the system value for the area
            $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
            $areaLevel = $ConfigItems->value('institution_area_level_id');

            // Getting the current area id
            $areaId = $entity->area_id;
            try {
                if ($areaId > 0) {
                    $path = $this->Areas
                    ->find('path', ['for' => $areaId])
                    ->contain('AreaLevels')
                    ->toArray();

                    foreach ($path as $value) {
                        if ($value['area_level']['level'] == $areaLevel) {
                            $areaName = $value['name'];
                        }
                    }
                }
            } catch (InvalidPrimaryKeyException $ex) {
                $this->log($ex->getMessage(), 'error');
            }
            return $areaName;
        }
        return $areaName;
        // return $entity->area_id;;
    }

    public function onUpdateFieldInstitutionId(Event $event, array $attr, $action, Request $request)
    {
            if($action == 'edit'){
                $MealsProgrammeId = $this->paramsDecode($request->params['pass']['1']);

                $MealInstitutionProgrammes = TableRegistry::get('Meal.MealInstitutionProgrammes');
                $result = $MealInstitutionProgrammes
                    ->find()
                    ->select([$MealInstitutionProgrammes->aliasField('area_id')])
                    ->where(['meal_programme_id' => $MealsProgrammeId['id']])
                    ->all();
                
                foreach($result AS $AreaData){
                    $AreaDataArr[] = $AreaData->area_id;
                }
                if(!empty($request->data)){
                    $areaId = array_unique($request->data['UserGroups']['area_id']['_ids']);
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
                }else{
                    $areaId = array_unique($AreaDataArr);
                }
            }elseif($action == 'add'){
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
                    $InstitutionsTable->aliasField('area_id IN') => $AreaArray,
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
            } 

            else{
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
        $attr['attr']['required'] = true;
        
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
        $SecurityGroupInstitutions = TableRegistry::get('Security.SecurityGroupInstitutions');
        $SecurityGroupId = $this->paramsDecode($this->request->params['pass'][1]);
        $institutions = TableRegistry::get('institutions');
        $SecurityGroupInstitutionsData = $SecurityGroupInstitutions->find()
            ->select([
                 $institutions->aliasField('name')
             ])
            ->leftJoin([$institutions->alias() => $institutions->table()],[
                $SecurityGroupInstitutions->aliasField('institution_id = ').$institutions->aliasField('id')
            ])
            ->where([$SecurityGroupInstitutions->aliasField('security_group_id')=>$SecurityGroupId['id']])
            ->toArray();

        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) use($SecurityGroupInstitutionsData) {
                return $results->map(function ($row) use($SecurityGroupInstitutionsData){
                    $item = [];
                    foreach ($SecurityGroupInstitutionsData as $key => $InstitutionsData) {
                         $item[] = $InstitutionsData->institutions['name'];
                        
                    }
                    $row['institution_id'] = implode(",",$item);
                    $SecurityGroupAreas = TableRegistry::get('Security.SecurityGroupAreas');
                    $areas = TableRegistry::get('areas');

                    $SecurityGroupAreasData = $SecurityGroupAreas->find()
                    ->select([
                         $areas->aliasField('name')
                     ])
                    ->leftJoin([$areas->alias() => $areas->table()],[
                        $SecurityGroupAreas->aliasField('area_id = ').$areas->aliasField('id')
                    ])
                    ->where([$SecurityGroupAreas->aliasField('security_group_id')=>$row->id])
                    ->toArray();

                    $areasData = [];
                    foreach ($SecurityGroupAreasData as $key => $AreasData) {
                         $areasData[] = $AreasData->areas['name'];
                        
                    }
                    $row['area_administrative_id'] = implode(",",$areasData);

            // echo "<pre>"; print_r($row); die();
                    return $row ;

                });
            });

    
       // $query->contain(['Institutions']);
    }
}
