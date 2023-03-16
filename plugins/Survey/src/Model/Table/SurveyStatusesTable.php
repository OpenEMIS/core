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
            'survey_form_id', 'date_enabled', 'date_disabled', 'academic_period_level', 'academic_periods'
        ]);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain($this->_contain);

        // search
        $search = $this->getSearchKey();
        if (!empty($search)) {
            $query
                ->matching('SurveyForms')
                ->where(['SurveyForms.name LIKE' => '%' . $search . '%']);
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
        list(, , $formOptions) = array_values($this->getSelectOptions());

        $this->fields['survey_form_id']['type'] = 'select';
        $this->fields['survey_form_id']['options'] = $formOptions;
    }
    /**
       / POCOR-7021 readonly in edit page
    */
    public function editBeforeAction(Event $event, ArrayObject $extra)
    {
        //Setup fields
        list(, , $formOptions) = array_values($this->getSelectOptions());

        $this->fields['survey_form_id']['type'] = 'readonly';
        $this->fields['survey_form_id']['options'] = $formOptions;
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
       // print_r($periodOptions);die;
        $attr['type'] = 'chosenSelect';
        $attr['options'] = $periodOptions;
        return $attr;
    }

    public function addOnInitialize(Event $event, Entity $entity)
    {
        //Initialize field values
        list(, , , $selectedForm) = array_values($this->getSelectOptions());
        $entity->survey_form_id = $selectedForm;

        return $entity;
    }

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

    /**POCOR-6676 starts - modified conditions to save record before add*/ 
    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $SurveyFormsFilters = TableRegistry::get('Survey.SurveyFormsFilters');
        $Institutions = TableRegistry::get('Institution.Institutions');
        $InstitutionSurveys = TableRegistry::get('Institution.InstitutionSurveys');
        $surveyFormId = $entity->survey_form_id;
        $SurveyFormsFilterObj = $SurveyFormsFilters->find()
                            ->where([$SurveyFormsFilters->aliasField('survey_form_id') => $surveyFormId])
                            ->toArray();
        $institutionTypeIds = [];
        if (!empty($SurveyFormsFilterObj)) {
            foreach ($SurveyFormsFilterObj as $value) {
                $institutionTypeIds[] = $value->survey_filter_id;
            }
        }
        if($institutionTypeIds[0]!=0) //POCOR-6976
        {
            $getInstitutionObj = $Institutions->find()
                            ->select([$Institutions->aliasField('id')])
                            ->where([$Institutions->aliasField('institution_type_id IN') => $institutionTypeIds])
                            ->toArray();
        }else{ // if institution type is 0 means its for all custom filter.//POCOR-6976
            $getInstitutionObj = $Institutions->find()
                            ->select([$Institutions->aliasField('id')])
                            ->toArray();
        }
        $institutionIds = [];
        if (!empty($getInstitutionObj)) {
            foreach ($getInstitutionObj as $val) {
                $institutionIds[] = $val->id;
            }
        }
        if (!empty($entity->academic_periods)) {
            foreach ($entity->academic_periods as $periodObj) {
                foreach ($institutionIds as $instId) {
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
                    }//POCOR-7177 remove else part because its working wrong.
                    //POCOR-7005 end conditon change for update record
                }
            }
        }
    }
    /**POCOR-6676 ends*/ 
}
