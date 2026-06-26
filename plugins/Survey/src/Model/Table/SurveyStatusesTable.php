<?php
namespace Survey\Model\Table;

use ArrayObject;
use App\Model\Table\ControllerActionTable;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\Http\ServerRequest;
use Cake\Event\EventInterface;
use Cake\Validation\Validator; //POCOR-9703
use Cake\I18n\Time;

class SurveyStatusesTable extends ControllerActionTable
{
    private $_contain = ['AcademicPeriods'];

    public function initialize(array $config): void
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

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.getSearchableFields'] = 'getSearchableFields';
        return $events;
    }

    public function beforeAction(EventInterface $event)
    {
        $this->field('academic_period_level');
        $this->field('academic_periods');

        $this->setFieldOrder([
            'survey_form_id', 'survey_filter_id', 'date_enabled', 'date_disabled', 'academic_period_level', 'academic_periods'
        ]);
    }
    //POCOR-9703::Start - Validation for survey_filter_id field
    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->requirePresence('survey_filter_id', 'create')
            ->notEmptyString(
                'survey_filter_id',
                __('Survey Filter is required')
            );

        return $validator;
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $serverRequest = $this->request;
        $query->contain($this->_contain);

        // search
        /*$search = $this->getSearchKey();
        if (!empty($search)) {
            $query->find('bySurveyFilter', ['search' => $search]);
        }*/

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
        $name = array('Institution > Overview','Institution > Students > Survey','Institution > Repeater > Survey','Institution > Staff > Survey');
        $CustomModules = TableRegistry::getTableLocator()->get('CustomField.CustomModules');
        $moduleOptions =  $CustomModules
            ->find('list', ['keyField' => 'id', 'valueField' => 'code'])
           ->where([$CustomModules->aliasField('name IN') => $name])->toArray();

        if (!empty($moduleOptions)) {
            $moduleOptions = $moduleOptions;

            $moduleId = $serverRequest->getquery('survey_module_id');
            $this->advancedSelectOptions($moduleOptions, $moduleId);
            $this->controller->set(compact('moduleOptions'));
        }

        // Survey form options
        $SurveyForms = TableRegistry::getTableLocator()->get('Survey.SurveyForms');
        $surveyFormOptions = $SurveyForms
            ->find('list')
            ->where([$SurveyForms->aliasField('custom_module_id') => $moduleId])
            ->order([
                $SurveyForms->aliasField('name')
            ])
            ->toArray();
        $surveyFormOptions = ['-1' => '-- '.__('All Survey Form').' --'] + $surveyFormOptions;
        $surveyFormId = $serverRequest->getquery('survey_form_id');
        $this->advancedSelectOptions($surveyFormOptions, $surveyFormId);
        $this->controller->set(compact('surveyFormOptions'));

        // survey filter options toolbar
        $this->SurveyFilters = TableRegistry::getTableLocator()->get('Survey.SurveyFormsFilters');
        if($surveyFormId != -1){
            $surveyFilterOptions = $this->SurveyFilters
                ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
                ->where([$this->SurveyFilters->aliasField('survey_form_id') => $surveyFormId,$this->SurveyFilters->aliasField('name IS NOT') => ''])
                ->order([
                    $this->SurveyFilters->aliasField('name')
                ])
                ->toArray();
        }else{
            $surveyFilterOptions = $this->SurveyFilters
                ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
                ->where([$this->SurveyFilters->aliasField('name IS NOT') => ''])
                ->order([
                    $this->SurveyFilters->aliasField('name')
                ])
                ->toArray();
        }
        $surveyFilterOptions = ['-1' => '-- '.__('All Survey Filter').' --'] + $surveyFilterOptions;
        $surveyFilterId = $serverRequest->getquery('survey_filter_id');
        $this->advancedSelectOptions($surveyFilterOptions, $surveyFilterId);

        $extra['elements']['controls'] = ['name' => 'Survey.survey_status', 'data' => [], 'options' => [], 'order' => 3];
        $this->controller->set(compact('surveyFilterOptions'));
        $form  = TableRegistry::getTableLocator()->get('Survey.SurveyForms');
        $filter  = TableRegistry::getTableLocator()->get('Survey.SurveyFormsFilters');
        if($moduleId == 1 && $surveyFormId == -1 && $surveyFilterId == -1){
             $query;
        }elseif($moduleId == 1 && $surveyFormId != -1 && $surveyFilterId == -1){
             $query
                ->select([
                            $this->aliasField('id'),
                            $this->aliasField('date_disabled'),
                            $this->aliasField('date_enabled'),
                        ])
                ->leftJoin([$form->getAlias() => $form->getTable()],
                        [$form->aliasField('id').'='.$this->aliasField('survey_form_id') ])
                ->leftJoin([$filter->getAlias() => $filter->getTable()],
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
                ->leftJoin([$form->getAlias() => $form->getTable()],
                        [$form->aliasField('id').'='.$this->aliasField('survey_form_id') ])
                ->leftJoin([$filter->getAlias() => $filter->getTable()],
                        [$filter->aliasField('id').'='.$this->aliasField('survey_filter_id') ])
                ->where([$this->aliasField('survey_filter_id') =>$surveyFilterId]);
        }else{
            $query
                ->select([
                            $this->aliasField('id'),
                            $this->aliasField('date_disabled'),
                            $this->aliasField('date_enabled'),
                        ])
                ->leftJoin([$form->getAlias() => $form->getTable()],
                        [$form->aliasField('id').'='.$this->aliasField('survey_form_id') ])
                ->leftJoin([$filter->getAlias() => $filter->getTable()],
                        [$filter->aliasField('id').'='.$this->aliasField('survey_filter_id') ])
                ->where([$this->aliasField('survey_form_id') =>$surveyFormId,
                    $this->aliasField('survey_filter_id') =>$surveyFilterId,
                    $form->aliasField('custom_module_id') =>$moduleId

                ]);
        }

    }

    public function getSearchableFields(EventInterface $event, ArrayObject $searchableFields)
    {
        $searchableFields[] = 'survey_form_id';
    }

    public function viewEditBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $query->contain($this->_contain);
    }

    //Change in POCOR-7021
    public function addBeforeAction(EventInterface $event, ArrayObject $extra)
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

    //POCOR-8096::Start
    public function deleteBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $data = $this->paramsDecode($this->request->getData('primaryKey'));
        $surveyStatusId = $data['id'];
        $surveyStatusData = $this->get($surveyStatusId);
        $AcademicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
        $apData = $AcademicPeriods->find('all',['conditions'=>['start_date'=> $surveyStatusData->date_enabled, 'end_date'=> $surveyStatusData->date_disabled  ]])->first();
        $surveyStatusPeriods = TableRegistry::getTableLocator()->get('Survey.SurveyStatusPeriods');
        $surveyStatusPeriodsData = $surveyStatusPeriods->find('all', ['conditions' => ['survey_status_id' => $surveyStatusData->id]])->toArray();
        foreach($surveyStatusPeriodsData as $surveyStatusPeriodsData1){
            $apId = $surveyStatusPeriodsData1->academic_period_id;
            $insSurveyTbl = TableRegistry::getTableLocator()->get('Institution.InstitutionSurveys');
            $insSurveyData = $insSurveyTbl->find('all', ['conditions' =>['survey_form_id'=> $surveyStatusData->survey_form_id, 'academic_period_id'=> $apId]])->toArray();

            foreach($insSurveyData as $insSurvey1){
                $institutionSurveyTableCellsTbl = TableRegistry::getTableLocator()->get('Institution.InstitutionSurveyTableCells');
                $institutionSurveyAnswersTbl = TableRegistry::getTableLocator()->get('Institution.InstitutionSurveyAnswers');
                $institutionStudentSurveysTbl = TableRegistry::getTableLocator()->get('Student.StudentSurveys');
                $institutionStaffSurveysTbl = TableRegistry::getTableLocator()->get('Staff.StaffSurveys');
                $institutionRepeaterSurveysTbl = TableRegistry::getTableLocator()->get('InstitutionRepeater.RepeaterSurveys');

                $institution_repeater_survey_answers_tbl = TableRegistry::getTableLocator()->get('InstitutionRepeater.RepeaterSurveyAnswers');
                $institution_staff_survey_answers_tbl = TableRegistry::getTableLocator()->get('Staff.StaffSurveyAnswers');
                $institution_student_survey_answers_tbl = TableRegistry::getTableLocator()->get('Student.StudentSurveyAnswers');

                $institution_repeater_survey_table_cells_tbl = TableRegistry::getTableLocator()->get('InstitutionRepeater.RepeaterSurveyTableCells');
                $institution_staff_survey_table_cells_tbl = TableRegistry::getTableLocator()->get('Staff.StaffSurveyTableCells');
                $institution_student_survey_table_cells_tbl = TableRegistry::getTableLocator()->get('Student.StudentSurveyTableCells');

                $institutionSurveyTableCells = $institutionSurveyTableCellsTbl->find('all',['conditions' =>['institution_survey_id' => $insSurvey1->id]])->first();
                if(!empty($institutionSurveyTableCells)){
                    $this->Alert->error('general.survey_already_used', ['reset' => true]);
                    $url = $this->url('index');
                    $event->stopPropagation();
                    return $this->controller->redirect($url);
                }
                $institutionSurveyAnswers = $institutionSurveyAnswersTbl->find('all',['conditions' =>['institution_survey_id' => $insSurvey1->id]])->first();
                if(!empty($institutionSurveyAnswers)){
                    $this->Alert->error('general.survey_already_used', ['reset' => true]);
                    $url = $this->url('index');
                    $event->stopPropagation();
                    return $this->controller->redirect($url);
                }
                $institutionStudentSurvey = $institutionStudentSurveysTbl->find('all',['conditions' =>['parent_form_id' => $insSurvey1->survey_form_id, 'academic_period_id' => $apId]])->first();
                if(!empty($institutionStudentSurvey)){
                    $this->Alert->error('general.survey_already_used', ['reset' => true]);
                    $url = $this->url('index');
                    $event->stopPropagation();
                    return $this->controller->redirect($url);
                }
                $institutionStaffSurvey = $institutionStaffSurveysTbl->find('all',['conditions' =>['parent_form_id' => $insSurvey1->survey_form_id, 'academic_period_id' => $apId]])->first();
                if(!empty($institutionStaffSurvey)){
                    $this->Alert->error('general.survey_already_used', ['reset' => true]);
                    $url = $this->url('index');
                    $event->stopPropagation();
                    return $this->controller->redirect($url);
                }
                $institutionRepeaterSurvey = $institutionRepeaterSurveysTbl->find('all',['conditions' =>['parent_form_id' => $insSurvey1->survey_form_id, 'academic_period_id' => $apId]])->first();
                if(!empty($institutionRepeaterSurvey)){
                    $this->Alert->error('general.survey_already_used', ['reset' => true]);
                    $url = $this->url('index');
                    $event->stopPropagation();
                    return $this->controller->redirect($url);
                }

                /***************************** Other tables */
                $institutionRepeaterSurveyData = $institutionRepeaterSurveysTbl->find('all',['conditions' =>['parent_form_id' => $insSurvey1->survey_form_id, 'academic_period_id' => $apId]])->toArray();
                foreach($institutionRepeaterSurveyData as $institutionRepeaterSurveyData1){
                    $institution_repeater_survey_answers_data = $institution_repeater_survey_answers_tbl->find('all',['conditions' =>['institution_repeater_survey_id' => $institutionRepeaterSurveyData1->id]])->first();
                    if(!empty($institution_repeater_survey_answers_data)){
                        $this->Alert->error('general.survey_already_used', ['reset' => true]);
                        $url = $this->url('index');
                        $event->stopPropagation();
                        return $this->controller->redirect($url);
                    }
                }


                $institutionStaffSurveyData = $institutionStaffSurveysTbl->find('all',['conditions' =>['parent_form_id' => $insSurvey1->survey_form_id, 'academic_period_id' => $apId]])->toArray();
                foreach($institutionStaffSurveyData as $institutionStaffSurveyData1){
                    $institution_staff_survey_answers_data = $institution_staff_survey_answers_tbl->find('all',['conditions' =>['institution_staff_survey_id' => $institutionStaffSurveyData1->id]])->first();
                    if(!empty($institution_staff_survey_answers_data)){
                        $this->Alert->error('general.survey_already_used', ['reset' => true]);
                        $url = $this->url('index');
                        $event->stopPropagation();
                        return $this->controller->redirect($url);
                    }
                }

                $institutionStudentSurveysData = $institutionStudentSurveysTbl->find('all',['conditions' =>['parent_form_id' => $insSurvey1->survey_form_id, 'academic_period_id' => $apId]])->toArray();
                foreach($institutionStudentSurveysData as $institutionStudentSurveysData1){
                    $institution_student_survey_answers_data = $institution_student_survey_answers_tbl->find('all',['conditions' =>['institution_student_survey_id' => $institutionStudentSurveysData1->id]])->first();
                    if(!empty($institution_student_survey_answers_data)){
                        $this->Alert->error('general.survey_already_used', ['reset' => true]);
                        $url = $this->url('index');
                        $event->stopPropagation();
                        return $this->controller->redirect($url);
                    }
                }
                /********** */
                $institutionRepeaterSurveyDataa = $institutionRepeaterSurveysTbl->find('all',['conditions' =>['parent_form_id' => $insSurvey1->survey_form_id, 'academic_period_id' => $apId]])->toArray();
                foreach($institutionRepeaterSurveyDataa as $institutionRepeaterSurveyDataa1){
                    $institution_repeater_survey_table_cells_data = $institution_repeater_survey_table_cells_tbl->find('all',['conditions' =>['institution_repeater_survey_id' => $institutionRepeaterSurveyDataa1->id]])->first();
                    if(!empty($institution_repeater_survey_table_cells_data)){
                        $this->Alert->error('general.survey_already_used', ['reset' => true]);
                        $url = $this->url('index');
                        $event->stopPropagation();
                        return $this->controller->redirect($url);
                    }
                }


                $institutionStaffSurveyDataa = $institutionStaffSurveysTbl->find('all',['conditions' =>['parent_form_id' => $insSurvey1->survey_form_id, 'academic_period_id' => $apId]])->toArray();
                foreach($institutionStaffSurveyDataa as $institutionStaffSurveyDataa1){
                    $institution_staff_survey_table_cells_data = $institution_staff_survey_table_cells_tbl->find('all',['conditions' =>['institution_staff_survey_id' => $institutionStaffSurveyDataa1->id]])->first();
                    if(!empty($institution_staff_survey_table_cells_data)){
                        $this->Alert->error('general.survey_already_used', ['reset' => true]);
                        $url = $this->url('index');
                        $event->stopPropagation();
                        return $this->controller->redirect($url);
                    }
                }

                $institutionStudentSurveysDataa = $institutionStudentSurveysTbl->find('all',['conditions' =>['parent_form_id' => $insSurvey1->survey_form_id, 'academic_period_id' => $apId]])->toArray();
                foreach($institutionStudentSurveysDataa as $institutionStudentSurveysDataa1){
                    $institution_student_survey_table_cells_data = $institution_student_survey_table_cells_tbl->find('all',['conditions' =>['institution_student_survey_id' => $institutionStudentSurveysDataa1->id]])->first();
                    if(!empty($institution_student_survey_table_cells_data)){
                        $this->Alert->error('general.survey_already_used', ['reset' => true]);
                        $url = $this->url('index');
                        $event->stopPropagation();
                        return $this->controller->redirect($url);
                    }
                }
            }
            foreach($insSurveyData as $insSurvey11){
                $insSurveyTbl->delete($insSurvey11);
            }
        }
	}
    //POCOR-8096::End
    /***

    /**
       / POCOR-7021 readonly in edit page
    */
    public function editBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        list(, , $formOptions) = array_values($this->getSelectOptions());

        $this->fields['survey_form_id']['type'] = 'readonly';
        $this->fields['survey_form_id']['options'] = $formOptions;

        $this->field('survey_filter_id', ['type' => 'select']); //POCOR-7271

        $this->setFieldOrder([
            'survey_form_id','survey_filter_id','date_enabled', 'date_disabled','academic_period_id']);
    }

    // public function onUpdateFieldAcademicPeriodLevel(EventInterface $event, array $attr, $action, Request $request)
    public function onUpdateFieldAcademicPeriodLevel(EventInterface $event, array $attr, $action)
    {
        $AcademicPeriodLevels = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriodLevels');
        $levelOptions = $AcademicPeriodLevels->getList()->toArray();

        $attr['options'] = $levelOptions;
        $attr['onChangeReload'] = 'changePeriod';
        if ($action != 'add') {
            $attr['visible'] = false;
        }
        return $attr;
    }

    // public function onUpdateFieldAcademicPeriods(EventInterface $event, array $attr, $action, Request $request)
    public function onUpdateFieldAcademicPeriods(EventInterface $event, array $attr, $action)
    {
        $serverRequest = new ServerRequest();
        $selectedLevel = key($this->fields['academic_period_level']['options']);
        if ($serverRequest->is('post')) {
            $selectedLevel = $request->data($this->aliasField('academic_period_level'));

        }

        $AcademicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
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

    /*public function addOnInitialize(EventInterface $event, Entity $entity)
    {
        //Initialize field values
        list(, , , $selectedForm) = array_values($this->getSelectOptions());
        $entity->survey_form_id = $selectedForm;

        return $entity;
    }*/

    public function getSelectOptions()
    {
        //Return all required options and their key
        $query = $this->request->getQuery();
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
    public function afterSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        $SurveyFormsFilters = TableRegistry::getTableLocator()->get('Survey.SurveyFormsFilters');
        $Institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
        $Areas = TableRegistry::getTableLocator()->get('Area.Areas');// POCOR-7549
        $InstitutionSurveys = TableRegistry::getTableLocator()->get('Institution.InstitutionSurveys');
        $surveyfilterAreas = TableRegistry::getTableLocator()->get('Survey.SurveyFilterAreas');
        $filterInstitutionProviders = TableRegistry::getTableLocator()->get('Survey.SurveyFilterInstitutionProviders');
        $filterInstitutionTypes = TableRegistry::getTableLocator()->get('Survey.SurveyFilterInstitutionTypes');
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
                    //POCOR-7549 start
                    $areaEntity=$Areas->find()
                                     ->select([$Areas->aliasField('id')])
                                     ->where([$Areas->aliasField('parent_id')=>$value['area_education_id']])
                                     ->toArray();
                    foreach($areaEntity as $key=>$value){
                        $areaId[]=$value['id'];
                    }
                    // POCOR-7549 end
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
                                ['status_id' => 1,'academic_period_id'=>$periodObj->id,'survey_form_id' => $surveyFormId,'institution_id' => $instId,'assignee_id' => $surveyDataVal->assignee_id,'modified_user_id' => 1,'modified' => new Time('NOW')],   //POCOR-7359 //field
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
    public function onUpdateFieldSurveyFilterId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $surveyFormId = $this->request->getData('SurveyStatuses')['survey_form_id'];
        $formTable = TableRegistry::getTableLocator()->get('Survey.SurveyFormsFilters');
        if($surveyFormId == null){
            $form  = TableRegistry::getTableLocator()->get('Survey.SurveyForms');
            $surveyFormId = $form->find()->first()->id;
        }else{
          $surveyFormId = $surveyFormId;
        }
        if(!empty($this->request->getAttribute('params')['pass'][1])){
            $dataid = $this->paramsDecode($this->request->getAttribute('params')['pass'][1])['id'];
            $filerId = $this->find()->select(['survey_filter_id'])->where([$this->aliasField('id') => $dataid])
                        ->first();
        }
        if($action == 'edit'){
            $surveyFormsFilters = TableRegistry::getTableLocator()->get('Survey.SurveyFormsFilters');
            $dataVal = $surveyFormsFilters->find()->select(['name' =>$surveyFormsFilters->aliasField('name')])->where([$surveyFormsFilters->aliasField('id') => $filerId->survey_filter_id])->first();
            $attr['type'] = 'readonly';
            $attr['value'] = $filerId->survey_filter_id;
            $attr['attr']['value'] = $dataVal->name;
            return $attr;
        }elseif($action == 'add'){
            $filterOptions = $formTable
                ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
                ->where([$formTable->aliasField('survey_form_id') => $surveyFormId,$formTable->aliasField('name IS NOT') => ''])
                ->toArray();
            $attr['type'] = 'select';
            $attr['options'] = $filterOptions;
            $attr['select'] = false;
            $attr['null'] = false;
            $attr['required'] = 'required';
            $attr['attr']['required'] = 'required';
            $attr['onChangeReload'] = 'changeModule';
            return $attr;
        }

    }

    //POCOR-7271
    public function findBySurveyFilter(Query $query, array $options)
    {
        if (isset($options['search'])) {
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
    public function onUpdateFieldSurveyFormId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $formTable = TableRegistry::getTableLocator()->get('Survey.SurveyForms');
        $formOptions = $formTable
            ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
            ->toArray();
        $attr['type'] = 'select';
        $attr['options'] = $formOptions;
        $attr['select'] = false;
        $attr['onChangeReload'] = 'changeModule';
        return $attr;

    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'date_enabled') {
            return __('Date Enable');
        } elseif ($field == 'survey_form_id') {
            return __('Survey Form');
        } elseif ($field == 'survey_filter_id') {
            return __('Survey Filter');
        } elseif ($field == 'date_disabled') {
            return __('Date Disable');
        } elseif ($field == 'modified_user_id') {
            return __('Modified By');
        } elseif ($field == 'modified') {
            return __('Modified On');
        } elseif ($field == 'created_user_id') {
            return __('Created By');
        } elseif ($field == 'created') {
            return __('Created On');
        }elseif ($field == 'academic_periods') {
            return __('Academic Period');
        }elseif ($field == 'description') {
            return __('Description');
        }elseif ($field == 'params') {
            return __('Params');
        }else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

}
