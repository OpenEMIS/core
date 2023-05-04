<?php
namespace Survey\Model\Table;

use ArrayObject;
use App\Model\Table\ControllerActionTable;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\I18n\Time;

class SurveyStatusesTable extends ControllerActionTable
{
    private $_contain = ['AcademicPeriods'];

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('SurveyForms', ['className' => 'Survey.SurveyForms']);
        $this->belongsTo('SurveyFormsFilters', ['className' => 'Survey.SurveyFormsFilters','foreignKey' => 'survey_filter_id']);
        $this->hasMany('SurveyStatusPeriods', ['className' => 'Survey.SurveyStatusPeriods', 'foreignKey' => 'survey_status_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->belongsToMany('AcademicPeriods', [
            'className' => 'AcademicPeriod.AcademicPeriods',
            'joinTable' => 'survey_status_periods',
            'foreignKey' => 'survey_status_id',
            'targetForeignKey' => 'academic_period_id'
        ]);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.getSearchableFields'] = 'getSearchableFields';
        return $events;
    }

    public function beforeAction(Event $event)
    {
        $this->field('academic_period_level');
        $this->field('academic_periods');

        $this->setFieldOrder([
            'survey_form_id', 'survey_filter_id', 'date_enabled', 'date_disabled', 'academic_period_level', 'academic_periods'
        ]);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain($this->_contain);

        // search
        $search = $this->getSearchKey();
        if (!empty($search)) {
            $query->find('bySurveyFilter', ['search' => $search]);
        }

        // Start POCOR-5188
        $is_manual_exist = $this->getManualUrl('Administration','Status','Survey');       
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

        //custom module option in toolbar
        $name = array('Institution > Overview','Institution > Students > Survey','Institution > Repeater > Survey');
        $CustomModules = TableRegistry::get('custom_modules');
        $moduleOptions =  $CustomModules
            ->find('list', ['keyField' => 'id', 'valueField' => 'code']) 
           ->where(['custom_modules.name IN' => $name])->toArray();

        if (!empty($moduleOptions)) {
            $moduleOptions = $moduleOptions;
            $moduleId = $this->request->query('survey_module_id');
            $this->advancedSelectOptions($moduleOptions, $moduleId);
            $this->controller->set(compact('moduleOptions'));
        }

        // Survey form options
        $SurveyForms = TableRegistry::get('survey_forms');
        $surveyFormOptions = $SurveyForms
            ->find('list')
            ->where([$SurveyForms->aliasField('custom_module_id') => $moduleId])
            ->order([
                $SurveyForms->aliasField('name')
            ])
            ->toArray();
        $surveyFormOptions = ['-1' => '-- '.__('All Survey Form').' --'] + $surveyFormOptions;
        $surveyFormId = $this->request->query('survey_form_id');
        $this->advancedSelectOptions($surveyFormOptions, $surveyFormId);
        $this->controller->set(compact('surveyFormOptions'));

        // survey filter options toolbar
        $this->SurveyFilters = TableRegistry::get('survey_forms_filters');
        if($surveyFormId != -1){
            $surveyFilterOptions = $this->SurveyFilters
                ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
                ->where([$this->SurveyFilters->aliasField('survey_form_id') => $surveyFormId])
                ->order([
                    $this->SurveyFilters->aliasField('name')
                ])
                ->toArray();
        }else{
            $surveyFilterOptions = $this->SurveyFilters
                ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
                ->order([
                    $this->SurveyFilters->aliasField('name')
                ])
                ->toArray();
        }
        $surveyFilterOptions = ['-1' => '-- '.__('All Survey Filter').' --'] + $surveyFilterOptions;
        $surveyFilterId = $this->request->query('survey_filter_id');
        $this->advancedSelectOptions($surveyFilterOptions, $surveyFilterId);
     
        $extra['elements']['controls'] = ['name' => 'Survey.survey_status', 'data' => [], 'options' => [], 'order' => 3];
        $this->controller->set(compact('surveyFilterOptions'));
        $form  = TableRegistry::get('survey_forms');
        $filter  = TableRegistry::get('survey_forms_filters');
        if($moduleId == 1 && $surveyFormId == -1 && $surveyFilterId == -1){
             $query;
        }elseif($moduleId == 1 && $surveyFormId != -1 && $surveyFilterId == -1){
             $query
                ->select([
                            $this->aliasField('id'),
                            $this->aliasField('date_disabled'),
                            $this->aliasField('date_enabled'),
                        ])
                ->innerJoin([$form->alias() => $form->table()],
                        [$form->aliasField('id').'='.$this->aliasField('survey_form_id') ])
                ->innerJoin([$filter->alias() => $filter->table()],
                        [$filter->aliasField('id').'='.$this->aliasField('survey_filter_id') ])
                ->where([$this->aliasField('survey_form_id') =>$surveyFormId,
                    $form->aliasField('custom_module_id') =>$moduleId

                ]);
        }elseif($moduleId == 1 && $surveyFormId == -1 && $surveyFilterId != -1){
             $query
                ->select([
                            $this->aliasField('id'),
                            $this->aliasField('date_disabled'),
                            $this->aliasField('date_enabled'),
                        ])
                ->innerJoin([$form->alias() => $form->table()],
                        [$form->aliasField('id').'='.$this->aliasField('survey_form_id') ])
                ->innerJoin([$filter->alias() => $filter->table()],
                        [$filter->aliasField('id').'='.$this->aliasField('survey_filter_id') ])
                ->where([$this->aliasField('survey_filter_id') =>$surveyFilterId]);
        }else{
            $query
                ->select([
                            $this->aliasField('id'),
                            $this->aliasField('date_disabled'),
                            $this->aliasField('date_enabled'),
                        ])
                ->innerJoin([$form->alias() => $form->table()],
                        [$form->aliasField('id').'='.$this->aliasField('survey_form_id') ])
                ->innerJoin([$filter->alias() => $filter->table()],
                        [$filter->aliasField('id').'='.$this->aliasField('survey_filter_id') ])
                ->where([$this->aliasField('survey_form_id') =>$surveyFormId,
                    $this->aliasField('survey_filter_id') =>$surveyFilterId,
                    $form->aliasField('custom_module_id') =>$moduleId

                ]);
        }        

    }

    public function getSearchableFields(Event $event, ArrayObject $searchableFields)
    {
        $searchableFields[] = 'survey_form_id';
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain($this->_contain);
    }

    //Change in POCOR-7021
    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        //Setup fields
      //  list(, , $formOptions) = array_values($this->getSelectOptions());

        //$this->fields['survey_form_id']['type'] = 'select';
        //$this->fields['survey_form_id']['options'] = $formOptions;
        $this->field('survey_filter_id', ['visible' => true,]);//POCOR-7271
        $this->field('survey_form_id', ['visible' => true,]);//POCOR-7271
        $this->setFieldOrder([
            'survey_form_id', 'survey_filter_id','date_enabled','date_disabled', 'academic_period_level', 'academic_periods'
        ]);
    }
    /**
       / POCOR-7021 readonly in edit page
    */
    public function editBeforeAction(Event $event, ArrayObject $extra)
    {
        list(, , $formOptions) = array_values($this->getSelectOptions());

        $this->fields['survey_form_id']['type'] = 'readonly';
        $this->fields['survey_form_id']['options'] = $formOptions;

        $this->field('survey_filter_id', ['type' => 'select']); //POCOR-7271

        $this->setFieldOrder([
            'survey_form_id','survey_filter_id','date_enabled', 'date_disabled','academic_period_id']);
    }

    public function onUpdateFieldAcademicPeriodLevel(Event $event, array $attr, $action, Request $request)
    {
        $AcademicPeriodLevels = TableRegistry::get('AcademicPeriod.AcademicPeriodLevels');
        $levelOptions = $AcademicPeriodLevels->getList()->toArray();
        
        $attr['options'] = $levelOptions;
        $attr['onChangeReload'] = 'changePeriod';
        if ($action != 'add') {
            $attr['visible'] = false;
        }
        return $attr;
    }

    public function onUpdateFieldAcademicPeriods(Event $event, array $attr, $action, Request $request)
    {
        $selectedLevel = key($this->fields['academic_period_level']['options']);
        if ($request->is('post')) {
            $selectedLevel = $request->data($this->aliasField('academic_period_level'));
           
        }

        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $periodOptions = $AcademicPeriods
            ->find('list')
            ->find('visible')
            ->find('order')
            ->where([$AcademicPeriods->aliasField('academic_period_level_id') => $selectedLevel])
            ->toArray();
        $attr['type'] = 'chosenSelect';
        $attr['options'] = $periodOptions;
        return $attr;
    }

    /*public function addOnInitialize(Event $event, Entity $entity)
    {
        //Initialize field values
        list(, , , $selectedForm) = array_values($this->getSelectOptions());
        $entity->survey_form_id = $selectedForm;

        return $entity;
    }*/

    public function getSelectOptions()
    {
        //Return all required options and their key
        $query = $this->request->query;
        $CustomModules = $this->SurveyForms->CustomModules;
        $moduleOptions = $CustomModules
            ->find('list')
            ->where([$CustomModules->aliasField('parent_id') => 0])
            ->toArray();
        $selectedModule = isset($query['module']) ? $query['module'] : key($moduleOptions);

        $formOptions = $this->SurveyForms
            ->find('list')
            ->where([$this->SurveyForms->aliasField('custom_module_id') => $selectedModule])
            ->toArray();
        $selectedForm = isset($query['form']) ? $query['form'] : key($formOptions);

         

        return compact('moduleOptions', 'selectedModule', 'formOptions', 'selectedForm');
    }

    /**POCOR-6676 starts - modified conditions to save record before add
     * POCOR-7271 change condition based on new filters
    **/ 
    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
       //echo "<pre>"; print_r($entity);die;
        $SurveyFormsFilters = TableRegistry::get('Survey.SurveyFormsFilters');
        $Institutions = TableRegistry::get('Institution.Institutions');
        $InstitutionSurveys = TableRegistry::get('Institution.InstitutionSurveys');
        $surveyfilterAreas = TableRegistry::get('survey_filter_areas');
        $filterInstitutionProviders = TableRegistry::get('survey_filter_institution_providers');
        $filterInstitutionTypes = TableRegistry::get('survey_filter_institution_types');
        $surveyFormId = $entity->survey_form_id;
        $surveyFilterId = $entity->survey_filter_id;
        $where = [];
        $provider = $filterInstitutionProviders->find()->select(['institution_provider_id'])
                    ->where([$filterInstitutionProviders->aliasField('survey_filter_id') => $surveyFilterId])->toArray();

        $providerId = [];
        if(!empty($provider)){
            foreach($provider as $value){
                $institutionProviderId = $value['institution_provider_id'];
                if($institutionProviderId != -1){
                    $providerId[]  = $value['institution_provider_id'];
                    
                }
            }
            if($provider[0]['institution_provider_id'] != -1){
                $where[$Institutions->aliasField('institution_provider_id IN')] = $providerId;
            }
        }
        
        $type = $filterInstitutionTypes->find()->select(['institution_type_id'])
                ->where([$filterInstitutionTypes->aliasField('survey_filter_id') => $surveyFilterId])->toArray();
        $typsids = [];
        if(!empty($type)){
            foreach($type as $value){
                $institutionTypeId = $value['institution_type_id'];
                if($institutionTypeId != -1){
                   $typsids[]  = $value['institution_type_id'];
                }
            }
            if($type[0]['institution_type_id'] != -1){
                $where[$Institutions->aliasField('institution_type_id IN')] = $typsids;
            }
        }

        $area = $surveyfilterAreas->find()->select(['area_education_id'])
                ->where([$surveyfilterAreas->aliasField('survey_filter_id') => $surveyFilterId])->toArray();
        $areaId = [];
        if(!empty($area)){
            foreach($area as $value){
                $institutionAreaId = $value['area_education_id'];
                if($institutionAreaId != -1){
                    $areaId[] = $value['area_education_id'];
                }
            }

            if($area[0]['area_education_id'] != -1){
                $where[$Institutions->aliasField('area_id IN')] = $areaId;
            }
        }
        
        $getInstitutionObj = $Institutions->find()
                        ->select([$Institutions->aliasField('id')])
                        ->where($where)
                        ->toArray();
        //echo "<pre>"; print_r($getInstitutionObj);die;
        $institutionIds = [];
        if (!empty($getInstitutionObj)) {
            foreach ($getInstitutionObj as $val) {
                $institutionIds[] = $val->id;
            }
        }
        if (!empty($entity->academic_periods)) {
            foreach ($entity->academic_periods as $periodObj) {
                foreach ($institutionIds as $instId) {
                   // echo "<pre>"; print_r($periodObj);die;
                   // $InstitutionSurveys->deleteAll(['institution_id' => $instId, 'academic_period_id' => $periodObj->id, 'survey_form_id' => $surveyFormId]);
                    $surveyDataVal = $InstitutionSurveys->find()->where(['institution_id' => $instId, 'academic_period_id' => $periodObj->id, 'survey_form_id' => $surveyFormId])->first();
                    //POCOR-7005 start conditon change for update record
                    if(!empty($surveyDataVal)){
                        $update =   $InstitutionSurveys->updateAll(
                                ['status_id' => 1,'academic_period_id'=>$periodObj->id,'survey_form_id' => $surveyFormId,'institution_id' => $instId,'assignee_id' => 0,'modified_user_id' => 1,'modified' => new Time('NOW')],    //field
                                [
                                 'id' => $surveyDataVal['id'], //condition
                                ] 
                            );
                    }else{
                        $entity = $InstitutionSurveys->newEntity([
                            'status_id' =>1,
                            'academic_period_id'=>$periodObj->id,
                            'survey_form_id'=>$surveyFormId,
                            'institution_id'=> $instId,
                            'assignee_id'=>0,
                            'created_user_id'=>$this->Auth->user('id'),
                            'created'=> date('Y-m-d h:i:s')
                        ]);
                        $InstitutionSurveys->save($entity);
                    } //POCOR-7177 remove else part because its working wrong.
                    //POCOR-7271 add again new entity
                    //POCOR-7005 end conditon change for update record
                }
            }
        }
    }
    /**POCOR-6676 ends*/ 

    //POCOR-7271
    public function onUpdateFieldSurveyFilterId(Event $event, array $attr, $action, Request $request)
    {
        $surveyFormId = $request->data['SurveyStatuses']['survey_form_id'];
        $formTable = TableRegistry::get('survey_forms_filters');
        if($surveyFormId==null){
            $form  = TableRegistry::get('survey_forms');
            $surveyFormId = $form->find()->first()->id;
        }else{
          $surveyFormId = $surveyFormId;  
        }
        if(!empty($this->request->pass[1])){
            $dataid = $this->paramsDecode($this->request->pass[1])['id'];
            $filerId = $this->find()->select(['survey_filter_id'])->where([$this->aliasField('id') => $dataid])
                        ->first();
        }
        if($action == 'edit'){
            $surveyFormsFilters = TableRegistry::get('survey_forms_filters');
            $dataVal = $surveyFormsFilters->find()->select(['name' =>$surveyFormsFilters->aliasField('name')])->where([$surveyFormsFilters->aliasField('id') => $filerId->survey_filter_id])->first();
            $attr['type'] = 'readonly';
            $attr['value'] = $filerId->survey_filter_id;
            $attr['attr']['value'] = $dataVal->name;
            return $attr;
        }elseif($action == 'add'){
            $filterOptions = $formTable
                ->find('list', ['keyField' => 'id', 'valueField' => 'name']) 
                ->where([$formTable->aliasField('survey_form_id') => $surveyFormId])
                ->toArray();
            $attr['type'] = 'select';
            $attr['options'] = $filterOptions;
            $attr['select'] = false;
            $attr['onChangeReload'] = 'changeModule';
            return $attr;
        }
        
    }

    //POCOR-7271
    public function findBySurveyFilter(Query $query, array $options)
    {
        if (array_key_exists('search', $options)) {
            $search = $options['search'];
            $query
            ->join([
                [
                    'table' => 'survey_forms_filters', 'alias' => 'SurveyFormsFilters', 'type' => 'INNER',
                    'conditions' => ['SurveyFormsFilters.id = ' . $this->aliasField('survey_filter_id')]
                ],
                [
                    'table' => 'survey_forms', 'alias' => 'SurveyForms', 'type' => 'INNER',
                    'conditions' => ['SurveyForms.id = ' . $this->aliasField('survey_form_id')]
                ],
            ])
            ->where([
                    'OR' => [
                        ['SurveyFormsFilters.name LIKE' => '%' . $search . '%'],
                        ['SurveyForms.name LIKE' => '%' . $search . '%'],
                    ]
                ]
            );
        }

        return $query;
    }

    //POCOR-7271
    public function onUpdateFieldSurveyFormId(Event $event, array $attr, $action, Request $request)
    {
        $formTable = TableRegistry::get('survey_forms');
        $formOptions = $formTable
            ->find('list', ['keyField' => 'id', 'valueField' => 'name']) 
            ->toArray();
        $attr['type'] = 'select';
        $attr['options'] = $formOptions;
        $attr['select'] = false;
        $attr['onChangeReload'] = 'changeModule';
        return $attr;
         
    }

}
