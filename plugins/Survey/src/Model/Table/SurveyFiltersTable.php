<?php
namespace Survey\Model\Table;

use ArrayObject;
use App\Model\Table\ControllerActionTable;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\Network\Request;
use Cake\Event\Event;
//use Cake\I18n\Time;
use Cake\Validation\Validator;
use Cake\Utility\Inflector;
use Cake\Utility\Text;
use Cake\Log\Log;

//POCOR-7271
class SurveyFiltersTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('survey_forms_filters');
        parent::initialize($config);
        //$this->belongsTo('CustomModules', ['className' => 'CustomField.CustomModules']);
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Rules' => ['index']
        ]);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        
      $this->field('custom_module_id',['visible' => false]);
        $this->field('survey_form_id', ['visible' => true]);
        $this->field('name', ['visible' => true]);
        $this->field('institution_type_id', ['visible' => true]);
        $this->field('institution_provider_id', ['visible' => true]);
       $this->field('area_education_id', ['visible' => true]);

    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $name = array('Institution > Overview','Institution > Students > Survey','Institution > Repeater > Survey');
        $CustomModules = TableRegistry::get('custom_modules');
        $moduleOptions =  $CustomModules
            ->find('list', ['keyField' => 'id', 'valueField' => 'code']) 
           ->where(['custom_modules.name IN' => $name]);

        $surveyForm = TableRegistry::get('survey_forms');
        $surveyFormOption = $surveyForm->find('list', ['keyField' => 'id', 'valueField' => 'name']);
        if (!empty($moduleOptions)) {
            $selectedModule = $this->queryString('module', $moduleOptions);
            $selectedModuleSecond = $this->queryString('form', $surveyFormOption);
            $extra['toolbarButtons']['add']['url']['module'] = $selectedModule;
            $extra['toolbarButtons']['add']['url']['form'] = $selectedModuleSecond;
           // $this->advancedSelectOptions($moduleOptions, $selectedModule);

            //$query->where([$this->aliasField('custom_module_id') => $selectedModule]);

            //Add controls filter to index page
          //  $toolbarElements = ['name' => 'CustomField.controls', 'data' => [], 'options' => [], 'order' => 1];
            $extra['elements']['controls'] = [
            'name' => 'CustomField.controls',
            'data' => [
                'module' => $selectedModule,
                'form' => $selectedModuleSecond,
            ],
            'options' => [],
            'order' => 1
        ];


           // $extra['elements']['controls'] = $toolbarElements;
            $this->controller->set(compact('moduleOptions','surveyFormOption'));
        }

        $SurveyFiltersTable = TableRegistry::get('survey_filters');

        /*$query
        ->find()->select(['institution_type_id' => $SurveyFiltersTable->aliasField('institution_type_id'),
            'institution_provider_id' => $SurveyFiltersTable->aliasField('institution_provider_id'),'area_education_id' => $SurveyFiltersTable->aliasField('area_education_id')])
        ->leftJoin([$SurveyFiltersTable->alias() => $SurveyFiltersTable->table()],
                    [ $SurveyFiltersTable->aliasField('survey_filter_id').'='.$this->aliasField('id') ]);

        $this->field('custom_module_id',['visible' => false]);
        $this->field('survey_form_id', ['visible' => true]);
        $this->field('name', ['visible' => true]);
        $this->field('institution_type_id', ['visible' => true]);
        $this->field('institution_provider_id', ['visible' => true]);
       $this->field('area_education_id', ['visible' => true]);
*/
        
    }

    public function addEditBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('custom_module_id', ['type' => 'select']);
        $this->field('survey_form_id', ['type' => 'select']);
        $this->field('name', ['visible' => true,]);

        $typeOptions = $this->getInstitutionType();
        $this->fields['institution_type_id']['options'] = [0 => __('All Institution Type')] + $typeOptions;
        $this->field('institution_type_id', [
            'type' => 'chosenSelect',
            'attr' => ['label' => __('Institution Type')],
            'visible' => ['index' => true, 'view' => true, 'edit' => true, 'add' => true]
        ]);
        $institutionProvider = $this->getInstitutionProvider();
        $this->fields['institution_provider_id']['options'] =  [0 => __('All Institution Provider')] + $institutionProvider;
        $this->field('institution_provider_id', [
            'type' => 'chosenSelect',
            'attr' => ['label' => __('Institution Provider')],
            'visible' => ['index' => true, 'view' => true, 'edit' => true, 'add' => true]
        ]);

        $areaEducationId = $this->getAreaEducation();
        $this->fields['area_education_id']['options'] = [0 => __('All Areas Education')] + $areaEducationId;
        $this->field('area_education_id', [
            'type' => 'chosenSelect',
            'attr' => ['label' => __('Area Education')],
            'visible' => ['index' => true, 'view' => true, 'edit' => true, 'add' => true]
        ]);
        
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('name', [
                'unique' => [
                    'rule' => ['validateUnique', ['scope' => 'custom_module_id']],
                    'provider' => 'table',
                    'message' => 'This name already exists in the system'
                ]
            ])
            ->add('institution_type_id', 'ruleNotEmpty', [
                'rule' => function ($value, $context) {
                    if (empty($value)) {
                        return false;
                    } elseif (isset($value['_ids']) && empty($value['_ids'])) {
                        return false;
                    }

                    return true;
                }
            ])
            ->add('institution_provider_id', 'ruleNotEmpty', [
                'rule' => function ($value, $context) {
                    if (empty($value)) {
                        return false;
                    } elseif (isset($value['_ids']) && empty($value['_ids'])) {
                        return false;
                    }

                    return true;
                }
            ])
            ->add('area_education_id', 'ruleNotEmpty', [
                'rule' => function ($value, $context) {
                    if (empty($value)) {
                        return false;
                    } elseif (isset($value['_ids']) && empty($value['_ids'])) {
                        return false;
                    }

                    return true;
                }
            ]);

    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
       
        $institution_type_id = [];
        $institution_provider_id = [];
        $area_education_id = [];
        if (array_key_exists('institution_type_id', $data) && array_key_exists('_ids', $data['institution_type_id']) && !empty($data['institution_type_id']['_ids'])) {
            foreach ($data['institution_type_id']['_ids'] as $institution_type) {
                $institution_type_id[] = [
                    'institution_type_id' => $institution_type
                ];
            }
        }
        $data['institution_type_id'] = $institution_type_id;

        if (array_key_exists('institution_provider_id', $data) && array_key_exists('_ids', $data['institution_provider_id']) && !empty($data['institution_provider_id']['_ids'])) {
            foreach ($data['institution_provider_id']['_ids'] as $institution_provider) {
                $institution_provider_id[] = [
                    'institution_provider_id' => $institution_provider
                ];
            }
        }

        $data['institution_provider_id'] = $institution_provider_id;

        if (array_key_exists('area_education_id', $data) && array_key_exists('_ids', $data['area_education_id']) && !empty($data['area_education_id']['_ids'])) {
            foreach ($data['area_education_id']['_ids'] as $area_education) {
                $area_education_id[] = [
                    'institution_type_id' => $area_education
                ];
            }
        }

        $data['area_education_id'] = $area_education_id;
    }


    public function onUpdateFieldCustomModuleId(Event $event, array $attr, $action, Request $request)
    {
        $name = array('Institution > Overview','Institution > Students > Survey','Institution > Repeater > Survey');
        $CustomModules = TableRegistry::get('custom_modules');
        $moduleOptions =  $CustomModules
            ->find('list', ['keyField' => 'id', 'valueField' => 'code']) 
           ->where(['custom_modules.name IN' => $name]);
        if ($action == 'edit'){
            $attr['visible'] = true;
            $attr['type'] = 'readonly';
        }else{
            $attr['type'] = 'select';
            $attr['options'] = $moduleOptions;
            $attr['select'] = true;
            $attr['onChangeReload'] = 'changeModule';
            return $attr;
        }
    }

    public function onUpdateFieldSurveyFormId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit'){
            $attr['visible'] = true;
            $attr['type'] = 'readonly';
        }else{
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

    public function getInstitutionType()
    {
        $TypesTable = TableRegistry::get('Institution.Types');
        $typeOptions = $TypesTable
            ->find('list', ['keyField' => 'id', 'valueField' => 'name']) 
            ->find('visible')
            ->find('order')
            ->toArray();
        return $typeOptions;
    }

    public function getInstitutionProvider()
    {
        $providerTable = TableRegistry::get('institution_providers');
        $providerOptions = $providerTable
            ->find('list', ['keyField' => 'id', 'valueField' => 'name']) 
            ->where(['visible' => 1])
            ->toArray();
        return $providerOptions;
    }

    public function getAreaEducation()
    {
        $Areas = TableRegistry::get('Area.Areas');
        $AreasEducationOptions = $Areas
            ->find('list', ['keyField' => 'id', 'valueField' => 'name']) 
            ->where(['visible' => 1])
            ->toArray();
            return $AreasEducationOptions ;
    }

    
    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
       echo "<pre>"; print_r($entity);die;
       $SurveyFiltersTable = TableRegistry::get('survey_filters');
        if ($entity->isNew()) {
            $countType = count($entity->institution_type_id);
            $countProvider = count($entity->institution_provider_id);
            $countArea = count($entity->area_education_id);
            
            if(($countType >= 2) || ($countProvider >= 2) || ($countArea >= 2)){
                /*foreach(){

                }*/
            }else{
                $entity = $SurveyFiltersTable->newEntity([
                        'survey_filter_id' =>$entity->id,
                        'institution_type_id'=>$entity->institution_type_id,
                        'institution_provider_id'=>$entity->institution_provider_id,
                        'area_education_id'=> $entity->area_education_id,
                        'modified'=>$this->Auth->user('id'),
                        'modified_user_id'=>date('Y-m-d h:i:s'),
                        'created_user_id'=>$this->Auth->user('id'),
                        'created'=> date('Y-m-d h:i:s')
                    ]);
               $saveData =  $SurveyFiltersTable->save($entity);
            }

            
        }
    }

   /* public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        //POCOR-7263::Start
        $AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $Institutions = TableRegistry::get('Institution.Institutions');
        $InstitutionSurveyT = TableRegistry::get('institution_surveys');
        $InstitutionTypesT = TableRegistry::get('institution_types');
        $SurveyFormsFilters = TableRegistry::get('survey_forms_filters');
        $currentAcademicPeriodId = $AcademicPeriod->AcademicPeriods->getCurrent();
        if($entity->custom_filter_selection == 1){
            foreach($entity['custom_filters'] as $k=>$customFilter){
                $insTypeId = $customFilter->id;
                $surveyFormId = $customFilter['_joinData']['survey_form_id'];
                $insList = $Institutions->find('all',['conditions'=>['institution_type_id'=>$insTypeId]])->toArray();
                foreach($insList as $kk=>$ins){
                    $alreatExist = $InstitutionSurveyT->find('all',['conditions'=> [
                        'academic_period_id'=>$currentAcademicPeriodId,
                        'survey_form_id'=>$surveyFormId,
                        'institution_id'=> $ins->id
                    ]])->first();
                    if(empty($alreatExist)){
                        $entity = $InstitutionSurveyT->newEntity([
                            'status_id' =>1,
                            'academic_period_id'=>$currentAcademicPeriodId,
                            'survey_form_id'=>$surveyFormId,
                            'institution_id'=> $ins->id,
                            'assignee_id'=>0,
                            'created_user_id'=>$this->Auth->user('id'),
                            'created'=> date('Y-m-d h:i:s')
                        ]);
                        $InstitutionSurveyT->save($entity);
                    }
                    
                }
            }
        }else{
            $surveyFormId = $entity->id;
            $insList = $Institutions->find('all')->toArray();
            foreach($insList as $kk=>$ins){
                $alreatExist = $InstitutionSurveyT->find('all',['conditions'=> [
                    'academic_period_id'=>$currentAcademicPeriodId,
                    'survey_form_id'=>$surveyFormId,
                    'institution_id'=> $ins->id
                ]])->first();
                if(empty($alreatExist)){
                    $entity = $InstitutionSurveyT->newEntity([
                        'status_id' =>1,
                        'academic_period_id'=>$currentAcademicPeriodId,
                        'survey_form_id'=>$surveyFormId,
                        'institution_id'=> $ins->id,
                        'assignee_id'=>0,
                        'created_user_id'=>$this->Auth->user('id'),
                        'created'=> date('Y-m-d h:i:s')
                    ]);
                    if($saveSurvey = $InstitutionSurveyT->save($entity)){ 
                        $InstitutionTypes = $InstitutionTypesT->find('all')->toArray();
                        foreach($InstitutionTypes as $ki => $InstitutionType){
                            $exixtData = $SurveyFormsFilters->find('all',['conditions'=>['survey_form_id'=>$saveSurvey->survey_form_id, 'survey_filter_id' => $InstitutionType->id ]])->first();
                            if(empty($exixtData)){
                                $surveyFormFilterData = [
                                    'survey_form_id' => $saveSurvey->survey_form_id,
                                    'survey_filter_id' => $InstitutionType->id
                                ];
                                $surveyFormFilterEntity = $SurveyFormsFilters->newEntity($surveyFormFilterData);
                                if ($SurveyFormsFilters->save($surveyFormFilterEntity)) {
                                } else {
                                    Log::write('debug', $surveyFormFilterEntity->errors());
                                }
                            }
                            
                        }
                    }
                }
            }

            
            
        }
        //POCOR-7263::End
      //  $this->setAllCustomFilter($entity);
    }*/



}
