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
use Cake\Datasource\ResultSetInterface;
use Cake\Collection\Collection;

//POCOR-7271
class SurveyFiltersTable extends ControllerActionTable
{
    
    public function initialize(array $config)
    {
        $this->table('survey_forms_filters');
        parent::initialize($config);
//      $this->belongsTo('CustomModules', ['className' => 'CustomField.CustomModules','foreignKey' => 'custom_module_id']);
        $this->belongsTo('SurveyForms', ['className' => 'Survey.SurveyForms', 'foreignKey' => 'survey_form_id']);

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

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.getSearchableFields'] = 'getSearchableFields';
        return $events;
    }

    public function getSearchableFields(Event $event, ArrayObject $searchableFields)
    {
        $searchableFields[] = 'survey_form_id';
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $search = $this->getSearchKey(); //POCOR-7271
        if (!empty($search)) {
            $query->find('bySurveyFilterData', ['search' => $search]);
        }

        //custom module option in toolbar
        $name = array('Institution > Overview','Institution > Students > Survey','Institution > Repeater > Survey');
        $CustomModules = TableRegistry::get('custom_modules');
        $moduleOptions =  $CustomModules
            ->find('list', ['keyField' => 'id', 'valueField' => 'code']) 
           ->where(['custom_modules.name IN' => $name])->toArray();

        if (!empty($moduleOptions)) {
            $moduleOptions = $moduleOptions;
          //  $selectedModule = $this->queryString('module', $moduleOptions);
            $moduleId = $this->request->query('survey_module_id');
            //$extra['toolbarButtons']['add']['url']['module'] = $selectedModule;
            $this->advancedSelectOptions($moduleOptions, $moduleId);
            $this->controller->set(compact('moduleOptions'));
        }
        // Survey form options toolbar
        $this->SurveyForms = TableRegistry::get('survey_forms');
        $surveyFormOptions = $this->SurveyForms
            ->find('list')
            ->order([
                $this->SurveyForms->aliasField('name')
            ])
            ->toArray();
        $surveyFormOptions = ['-1' => '-- '.__('All Survey Forms').' --'] + $surveyFormOptions;
        $surveyFormId = $this->request->query('survey_form_id');
        $this->advancedSelectOptions($surveyFormOptions, $surveyFormId);
     
        $extra['elements']['controls'] = ['name' => 'Survey.filter_rules_controls', 'data' => [], 'options' => [], 'order' => 2];
        $this->controller->set(compact('surveyFormOptions'));

        $tableProvider = TableRegistry::get('survey_filter_institution_providers');
        $institutionType = TableRegistry::get('survey_filter_institution_types');
        $areaEducation = TableRegistry::get('survey_filter_areas');
        $provider = TableRegistry::get('institution_providers');
        $type = TableRegistry::get('institution_types');
        $areas = TableRegistry::get('areas');
        $survey_forms = TableRegistry::get('survey_forms');
      
        if($surveyFormId == -1 && $moduleId == 1)
        {
            $query->select([$this->aliasField('id'), $this->aliasField('name'), $survey_forms->aliasField('name')])
                    ->leftJoin([$tableProvider->alias() => $tableProvider->table()],
                        [$tableProvider->aliasField('survey_filter_id').'='.$this->aliasField('id')])
                    ->leftJoin([$institutionType->alias() => $institutionType->table()],
                        [$institutionType->aliasField('survey_filter_id').'='.$this->aliasField('id')])
                    ->leftJoin([$areaEducation->alias() => $areaEducation->table()],
                        [$areaEducation->aliasField('survey_filter_id').'='.$this->aliasField('id')])
                    ->leftJoin([$survey_forms->alias() => $survey_forms->table()],
                        [$survey_forms->aliasField('id').'='.$this->aliasField('survey_form_id')])
                   ->where([$this->aliasField('name IS NOT') => ''])
                   ->group([$tableProvider->aliasField('survey_filter_id'),$institutionType->aliasField('survey_filter_id'),$areaEducation->aliasField('survey_filter_id')]);

        }else{
            $query->select([$this->aliasField('id'), $this->aliasField('name'), $survey_forms->aliasField('name')])
                    ->leftJoin([$tableProvider->alias() => $tableProvider->table()],
                        [$tableProvider->aliasField('survey_filter_id').'='.$this->aliasField('id')])
                    ->leftJoin([$institutionType->alias() => $institutionType->table()],
                        [$institutionType->aliasField('survey_filter_id').'='.$this->aliasField('id')])
                    ->leftJoin([$areaEducation->alias() => $areaEducation->table()],
                        [$areaEducation->aliasField('survey_filter_id').'='.$this->aliasField('id')])
                    ->leftJoin([$survey_forms->alias() => $survey_forms->table()],
                        [$survey_forms->aliasField('id').'='.$this->aliasField('survey_form_id')])
                    ->where([$this->aliasField('survey_form_id') => $surveyFormId,$this->aliasField('name IS NOT') => ''])
                    ->group([$tableProvider->aliasField('survey_filter_id'),$institutionType->aliasField('survey_filter_id'),$areaEducation->aliasField('survey_filter_id')]);

        }

    }

    public function addEditBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('custom_module_id', ['type' => 'select']);
        $this->field('survey_form_id', ['type' => 'select']);
        $this->field('name', ['visible' => true,]);

        $typeOptions = $this->getInstitutionType();
        $this->fields['institution_type_id']['options'] = [-1 => __('All Institution Type')] + $typeOptions;
        $this->field('institution_type_id', [
            'type' => 'chosenSelect',
            'attr' => ['label' => __('Institution Type'),'required'=>true], //POCOR-7548
            'visible' => ['index' => true, 'view' => true, 'edit' => true, 'add' => true]
        ]);
        $institutionProvider = $this->getInstitutionProvider();
        $this->fields['institution_provider_id']['options'] =  [-1 => __('All Institution Provider')] + $institutionProvider;
        $this->field('institution_provider_id', [
            'type' => 'chosenSelect',
            'attr' => ['label' => __('Institution Provider'),'required'=>true], //POCOR-7548
            'visible' => ['index' => true, 'view' => true, 'edit' => true, 'add' => true]
        ]);

        $areaEducationId = $this->getAreaEducation();
        $this->fields['area_education_id']['options'] = [-1 => __('All Areas Education')] + $areaEducationId;
        $this->field('area_education_id', [
            'type' => 'chosenSelect',
            'attr' => ['label' => __('Area Education'),'required'=>true], //POCOR-7548
            'visible' => ['index' => true, 'view' => true, 'edit' => true, 'add' => true]
        ]);
        
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('institution_provider_id', ['visible' => true]);	//POCOR-7548
        $this->field('institution_type_id', ['visible' => true]);	//POCOR-7548
        $this->field('area_education_id', ['visible' => true]);  //POCOR-7548
        $filterId = $entity->id;
        session_start();
        $_SESSION["surveyFilterId"] = $filterId;
        //POCOR-7548
        $this->setFieldOrder([	
            'custom_module_id', 'survey_form_id', 'name', 'date_disabled', 'institution_provider_id', 'institution_type_id','area_education_id'	
        ]);
        //POCOR-7548
    }

    public function editBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('custom_module_id', ['type' => 'readonly']);
        $this->field('survey_form_id', ['type' => 'select']);
        $this->field('custom_module_id', ['visible' => true]);
        $this->field('survey_form_id', ['visible' => true]);
        $this->field('name', ['visible' => true]);
        $this->setFieldOrder([
            'custom_module_id', 'survey_form_id', 'name', 'date_disabled', 'institution_provider_id', 'institution_type_id','area_education_id'
        ]);
    }

    public function editBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $entity->survey_filter_id = $_SESSION['surveyFilterId'];
        $filterId = $entity->survey_filter_id;
        $tableProvider = TableRegistry::get('survey_filter_institution_providers');
        $institutionType = TableRegistry::get('survey_filter_institution_types');
        $areaEducation = TableRegistry::get('survey_filter_areas');

        $providerResult = $tableProvider->find()->select(['institution_provider_id'])
                        ->where([$tableProvider->aliasField('survey_filter_id') => $filterId])
                        ;
        $institutionTypeResult = $institutionType->find()->select(['institution_type_id'])
                                ->where([$institutionType->aliasField('survey_filter_id') => $filterId])
                                ->toArray();
        $areaEducationResult = $areaEducation->find()->select(['area_education_id'])
                                ->where([$areaEducation->aliasField('survey_filter_id') => $filterId])
                                ->toArray();
        
        $provider = [];
        if(!empty($providerResult)){
            foreach($providerResult as $key => $value){
                $provider[$key] = ['id' => $value['institution_provider_id']]; 
            }
        }

        $type = [];
        if(!empty($institutionTypeResult)){
            foreach($institutionTypeResult as $key => $value){
                $type[$key] = ['id' => $value['institution_type_id']]; 
            }
        }
        $areaEducation = [];
        if(!empty($areaEducationResult)){
            foreach($areaEducationResult as $key => $value){
                $areaEducation[$key] = ['id' => $value['area_education_id']]; 
            }
        }

        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) use($provider,$type,$areaEducation) {
            return $results->map(function ($row) use($provider,$type,$areaEducation) {
                $row['institution_provider_id'] = $provider;
                $row['institution_type_id'] = $type;
                $row['area_education_id'] = $areaEducation;
                return $row;
            });
        });
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
                    if (empty($value[0]['institution_type_id'])) {
                        return false;
                    } elseif (isset($value[0]['institution_type_id']) && empty($value[0]['institution_type_id'])) {
                        
                        return false;
                    }

                    return true;
                }
            ])
            ->add('institution_provider_id', 'ruleNotEmpty', [
                'rule' => function ($value, $context) {
                    if (empty($value[0]['institution_provider_id'])) {
                        return false;
                    } elseif (isset($value[0]['institution_provider_id']) && empty($value[0]['institution_provider_id'])) {
                        return false;
                    }

                    return true;
                }
            ])
            ->add('area_education_id', 'ruleNotEmpty', [
                'rule' => function ($value, $context) {
                    if (empty($value[0]['area_education_id'])) {
                        return false;
                    } elseif (isset($value[0]['area_education_id']) && empty($value[0]['area_education_id'])) {
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
        if (array_key_exists('institution_type_id', $data) && !empty($data['institution_type_id'])) {
            foreach ($data['institution_type_id'] as $institution_type) {
                $institution_type_id[] = [
                    'institution_type_id' => $institution_type
                ];
            }
        }
        $data['institution_type_id'] = $institution_type_id;

        if (array_key_exists('institution_provider_id', $data) && !empty($data['institution_provider_id'])) {
            foreach ($data['institution_provider_id'] as $institution_provider) {
                $institution_provider_id[] = [
                    'institution_provider_id' => $institution_provider
                ];
            }
        }

        $data['institution_provider_id'] = $institution_provider_id;

        if (array_key_exists('area_education_id', $data) && !empty($data['area_education_id'])) {
            foreach ($data['area_education_id'] as $area_education) {
                $area_education_id[] = [
                    'area_education_id' => $area_education
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

        if(!empty($this->request->pass[1])){
            $dataid = $this->paramsDecode($this->request->pass[1])['id'];
            $filerID  = $dataid ;
            $CustomModules = TableRegistry::get('survey_forms_filters');
            $CustomModulesdata = $CustomModules->find()->select(['custom_module_id'])
                                ->where([$CustomModules->aliasField('id') =>  $filerID])->first();
         }

        if ($action == 'edit'){
            $CustomModules = TableRegistry::get('custom_modules');
            $data = $CustomModules->find()->select(['code' =>$CustomModules->aliasField('code')])->where([$CustomModules->aliasField('id') => $CustomModulesdata->custom_module_id])->first();
            $attr['type'] = 'readonly';
            $attr['value'] = $CustomModulesdata->custom_module_id;
            $attr['attr']['value'] = $data->code;
            return $attr;
        }elseif($action == 'add'){
            $attr['type'] = 'select';
            $attr['options'] = $moduleOptions;
            $attr['select'] = true;
            $attr['onChangeReload'] = 'changeModule';
            return $attr;
        }
    }

    public function onUpdateFieldSurveyFormId(Event $event, array $attr, $action, Request $request)
    {
        $CustomModules = $request->data['SurveyFilters']['custom_module_id'];
        if($CustomModules==null){
            $CustomModules = 1;
        }else{
          $CustomModules = $CustomModules;  
        }
        if(!empty($this->request->pass[1])){
            $dataid = $this->paramsDecode($this->request->pass[1])['id'];
            $filerID  = $dataid ;
            $formTable = TableRegistry::get('survey_forms_filters');
            $formTabledata = $formTable->find()->select(['survey_form_id'])
                                ->where([$formTable->aliasField('id') =>  $filerID])->first();
         }
        if ($action == 'edit'){
            $forms = TableRegistry::get('survey_forms');
            $data = $forms->find()->select(['name' =>$forms->aliasField('name')])->where([$forms->aliasField('id') => $formTabledata->survey_form_id])->first();
            $attr['type'] = 'readonly';
            $attr['value'] = $formTabledata->survey_form_id;
            $attr['attr']['value'] = $data->name;
            return $attr;
        }else{
            $formTable = TableRegistry::get('survey_forms');
            $formOptions = $formTable
                ->find('list', ['keyField' => 'id', 'valueField' => 'name']) 
                ->where([$formTable->aliasField('custom_module_id') => $CustomModules])
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

    public function onGetCustomModuleId(Event $event, Entity $entity)
    {
        $CustomModules = TableRegistry::get('custom_modules');
        $data = $CustomModules->find()->select(['code' =>$CustomModules->aliasField('code')])->where([$CustomModules->aliasField('id') => $entity->custom_module_id])->first();
       return $data->code;
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

    
    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {  
        if ($entity->isNew()) {
                $entity = $this->newEntity([
                    'name' =>$entity->name,
                    'survey_form_id' =>$entity->survey_form_id,
                    'custom_module_id' =>$entity->custom_module_id,
                    'modified'=>$this->Auth->user('id'),
                    'modified_user_id'=>date('Y-m-d h:i:s'),
                    'created_user_id'=>$this->Auth->user('id'),
                    'created'=> date('Y-m-d h:i:s')
                ]);
               $saveData =  $this->save($entity);

        }else{
            $updatedata =   $this->updateAll(
                                ['name' => $entity->name,'survey_form_id'=>$entity->survey_form_id,'custom_module_id' => $entity->custom_module_id, 'modified'=>$this->Auth->user('id'),
                    'modified_user_id'=>date('Y-m-d h:i:s')],  
                                [
                                 'id' => $entity->id, 
                                ]
                                );
        }

    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $type = $entity->institution_type_id[0]['institution_type_id'];
        $provider = $entity->institution_provider_id[0]['institution_provider_id'];
        $areaEducation = $entity->area_education_id[0]['area_education_id']; //POCOR-7548
        $filterId = $entity->id;
        $institutionProvider = TableRegistry::get('survey_filter_institution_providers');
        $institutionType = TableRegistry::get('survey_filter_institution_types');
        $surveyarea = TableRegistry::get('survey_filter_areas');
        $checkProvider = $institutionProvider->find()->where(['survey_filter_id' => $filterId])->toArray();
        $checkType = $institutionType->find()->where(['survey_filter_id' => $filterId])->toArray();
        $checkArea = $surveyarea->find()->where(['survey_filter_id' => $filterId])->toArray();
        if($checkProvider !=null){
            $institutionProvider->deleteAll(['survey_filter_id' => $filterId]);
        }
        if($checkType !=null){
            $institutionType->deleteAll(['survey_filter_id' => $filterId]);
        }
        if($checkArea !=null){
            $surveyarea->deleteAll(['survey_filter_id' => $filterId]);
        }
        foreach($type as $value){
            $entity = $institutionType->newEntity([
                    'survey_filter_id' =>$filterId,
                    'institution_type_id' =>$value,
                    'created_user_id'=>$this->Auth->user('id'),
                    'created'=> date('Y-m-d h:i:s')
                ]);
            $saveData =  $institutionType->save($entity);
        }

        foreach($provider as $value){
            $entity = $institutionProvider->newEntity([
                    'survey_filter_id' =>$filterId,
                    'institution_provider_id' =>$value,
                    'created_user_id'=>$this->Auth->user('id'),
                    'created'=> date('Y-m-d h:i:s')
                ]);
            $saveData =  $institutionProvider->save($entity);
        }

        foreach($areaEducation as $value){
            $entity = $surveyarea->newEntity([
                    'survey_filter_id' =>$filterId,
                    'area_education_id' =>$value,
                    'created_user_id'=>$this->Auth->user('id'),
                    'created'=> date('Y-m-d h:i:s')
                ]);
            $saveData =  $surveyarea->save($entity);
        }
        
    }

    public function onGetInstitutionTypeId(Event $event, Entity $entity)
    {
        $typedata = [];
        $filterId = $entity->id;
        $type = TableRegistry::get('institution_types');
        $surveyInstitutionTypes = TableRegistry::get('survey_filter_institution_types');
        $InstitutionTypesData = $surveyInstitutionTypes->find()
                                ->where([$surveyInstitutionTypes->aliasField('survey_filter_id') => $filterId])->first()->institution_type_id;
        if($InstitutionTypesData != -1){  
            $data = $surveyInstitutionTypes->find()->select(['id'=> $type->aliasField('id'),
                            'name' => $type->aliasField('name')])
                            ->leftJoin([$type->alias() => $type->table()],
                            [$type->aliasField('id').'='.$surveyInstitutionTypes->aliasField('institution_type_id') ])
                            ->where([$surveyInstitutionTypes->aliasField('survey_filter_id') => $filterId]);         
            foreach($data as $key => $value){

                $typedata[] = $value->name;
            }               
            return implode(', ', $typedata);
        }elseif($InstitutionTypesData == -1){
            $institutionType = 'All Institution Type';
            return $institutionType;
        }elseif($InstitutionTypesData == NULL){
            $institutionType = '';
            return $institutionType;
        }
    }

    public function onGetInstitutionProviderId(Event $event, Entity $entity)
    {
        $result = [];
        $filterId = $entity->id;
        $institutionProviders = TableRegistry::get('institution_providers');
        $surveyinstitutionProviders = TableRegistry::get('survey_filter_institution_providers');
        $institutionProvidersData = $surveyinstitutionProviders->find()
                                ->where([$surveyinstitutionProviders->aliasField('survey_filter_id') => $filterId])->first()->institution_provider_id;
        if($institutionProvidersData != -1){  
            $data = $surveyinstitutionProviders->find()->select(['id'=> $institutionProviders->aliasField('id'),
                            'name' => $institutionProviders->aliasField('name')])
                            ->leftJoin([$institutionProviders->alias() => $institutionProviders->table()],
                            [$institutionProviders->aliasField('id').'='.$surveyinstitutionProviders->aliasField('institution_provider_id') ])
                            ->where([$surveyinstitutionProviders->aliasField('survey_filter_id') => $filterId]);         
            foreach($data as $key => $value){

                $result[] = $value->name;
            }               
            return implode(', ', $result);
        }elseif($institutionProvidersData == -1){
            $institutionProvider = 'All Institution Provider';
            return $institutionProvider;
        }elseif($institutionProvidersData == NULL){
            $institutionProvider = '';
            return $institutionProvider;
        }
    }

    public function onGetAreaEducationId(Event $event, Entity $entity)
    {
        $result = [];
        $filterId = $entity->id;
        $areaEducation = TableRegistry::get('areas');
        $surveyAreaEducation = TableRegistry::get('survey_filter_areas');
        $areaeducationData = $surveyAreaEducation->find()
                                ->where([$surveyAreaEducation->aliasField('survey_filter_id') => $filterId])->first()->area_education_id;
        if($areaeducationData != -1){  
            $data = $surveyAreaEducation->find()->select(['id'=> $areaEducation->aliasField('id'),
                            'name' => $areaEducation->aliasField('name')])
                            ->leftJoin([$areaEducation->alias() => $areaEducation->table()],
                            [$areaEducation->aliasField('id').'='.$surveyAreaEducation->aliasField('area_education_id') ])
                            ->where([$surveyAreaEducation->aliasField('survey_filter_id') => $filterId]);         
            foreach($data as $key => $value){

                $result[] = $value->name;
            }               
            return implode(', ', $result);
        }elseif($areaeducationData == NULL){
            $educations = '';
            return $educations;
        }elseif($areaeducationData == -1){
            $educations = 'All Area Education';
            return $educations;
        }
    }

    public function beforeDelete(Event $event, Entity $entity, ArrayObject $extra)
    {
        $filterId = $entity->id;
        $status = TableRegistry::get('survey_statuses');
        $surveyFilterAreas = TableRegistry::get('survey_filter_areas');
        $surveyFilterInstitutionProviders = TableRegistry::get('survey_filter_institution_providers');
        $surveyFilterInstitutionTypes = TableRegistry::get('survey_filter_institution_types');
        $checkstatus = $status->find()->where([$status->aliasField('survey_filter_id') => $filterId])->toArray();
        $checkFilterAreas = $surveyFilterAreas->find()->where([$surveyFilterAreas->aliasField('survey_filter_id') => $filterId])->toArray();
        $checksurveyProviders = $surveyFilterInstitutionProviders->find()->where([$surveyFilterInstitutionProviders->aliasField('survey_filter_id') => $filterId])->toArray();
        $checkInstitutionTypes = $surveyFilterInstitutionTypes->find()->where([$surveyFilterInstitutionTypes->aliasField('survey_filter_id') => $filterId])->toArray();
        if(!empty($checkstatus)){
            $message = __('Survey Filter is  associated with Other Data');
            $this->Alert->error($message, ['type' => 'string', 'reset' => true]);
            $event->stopPropagation();
        }elseif(empty($checkstatus) || (!empty($checkFilterAreas)) || (!empty($checksurveyProviders)) || (!empty($checkInstitutionTypes))){
            $deletAreaSurvey =  $surveyFilterAreas->deleteAll(['survey_filter_id' => $filterId]);
            $deletInstitutionProvidersSurvey =  $surveyFilterInstitutionProviders->deleteAll(['survey_filter_id' => $filterId]);
            $deletInstitutionTypesSurvey =  $surveyFilterInstitutionTypes->deleteAll(['survey_filter_id' => $filterId]);
                   
        }

    }

    //POCOR-7271
    public function findBySurveyFilterData(Query $query, array $options)
    {

        if (array_key_exists('search', $options)) {
            $search = $options['search'];
            $query
            ->join([
                [
                    'table' => 'survey_forms', 'alias' => 'SurveyForms', 'type' => 'INNER',
                    'conditions' => ['SurveyForms.id = ' . $this->aliasField('survey_form_id')]
                ],
                [
                    'table' => 'survey_filter_institution_providers', 'alias' => 'SurveyFilterInstitutionProviders', 'type' => 'LEFT',
                    'conditions' => ['SurveyFilterInstitutionProviders.survey_filter_id = ' . $this->aliasField('id')]
                ],
                [
                    'table' => 'institution_providers', 'alias' => 'InstitutionProviders', 'type' => 'LEFT',
                    'conditions' => [
                        'InstitutionProviders.id = ' . 'SurveyFilterInstitutionProviders.survey_filter_id',
                    ]
                ],
            ])
            ->where([
                    'OR' => [
                       [$this->aliasField('name').' LIKE' => '%' . $search . '%'],
                        ['SurveyForms.name LIKE' => '%' . $search . '%'],
                        ['InstitutionProviders.name LIKE' => '%' . $search . '%'],
                    ]
                ]
            );
        }
        return $query;
    }
    //POCOR-7611 start
    public function onUpdateFieldInstitutionProviderId(Event $event, array $attr, $action, Request $request)
    {  
        if($action=="edit"){
        if(!empty($request->params['pass'][1])){
        $data=$this->paramsDecode($request->params['pass'][1]);
        $filterId=$data['id'];
        }
        $institutionProviders = TableRegistry::get('institution_providers');
        $surveyinstitutionProviders = TableRegistry::get('survey_filter_institution_providers');
        $institutionProvidersData = $surveyinstitutionProviders->find()
        ->where([$surveyinstitutionProviders->aliasField('survey_filter_id') => $filterId])
        ->first()
        ->institution_provider_id;
        $result=[];
        if($institutionProvidersData != -1){  
            $data = $surveyinstitutionProviders->find()->select(['id'=> $institutionProviders->aliasField('id'),
                            'name' => $institutionProviders->aliasField('name')])
                            ->leftJoin([$institutionProviders->alias() => $institutionProviders->table()],
                            [$institutionProviders->aliasField('id').'='.$surveyinstitutionProviders->aliasField('institution_provider_id') ])
                            ->where([$surveyinstitutionProviders->aliasField('survey_filter_id') => $filterId]);         
            foreach($data as $key => $value){
               $result[] = $value->name;
            }               
        }elseif($institutionProvidersData == -1){
            $result[] = 'All Institution Provider';
           
        }elseif($institutionProvidersData == NULL){
            $result = '';
           
        }
        $attr['type'] = 'readonly';
        $attr['attr']['value'] = implode(', ', $result);;
           return $attr; 
        }
    }
    public function onUpdateFieldInstitutionTypeId(Event $event, array $attr, $action, Request $request)
    { 
        if($action=="edit"){
            if(!empty($request->params['pass'][1])){
            $data=$this->paramsDecode($request->params['pass'][1]);
            $filterId=$data['id'];
            }
        $typedata = [];
        $type = TableRegistry::get('institution_types');
        $surveyInstitutionTypes = TableRegistry::get('survey_filter_institution_types');
        $InstitutionTypesData = $surveyInstitutionTypes->find()
                                ->where([$surveyInstitutionTypes->aliasField('survey_filter_id') => $filterId])->first()->institution_type_id;
        if($InstitutionTypesData != -1){  
            $data = $surveyInstitutionTypes->find()->select(['id'=> $type->aliasField('id'),
                            'name' => $type->aliasField('name')])
                            ->leftJoin([$type->alias() => $type->table()],
                            [$type->aliasField('id').'='.$surveyInstitutionTypes->aliasField('institution_type_id') ])
                            ->where([$surveyInstitutionTypes->aliasField('survey_filter_id') => $filterId]);         
            foreach($data as $key => $value){

                $typedata[] = $value->name;
            }               
           
        }elseif($InstitutionTypesData == -1){
            $typedata[] = 'All Institution Type';
           
        }elseif($InstitutionTypesData == NULL){
            $typedata[] = '';
        }
        $attr['type'] = 'readonly';
        $attr['attr']['value'] = implode(', ', $typedata);
           return $attr; 

    }
   }
   public function onUpdateFieldAreaEducationId(Event $event, array $attr, $action, Request $request)
   { 
       if($action=="edit"){
        $result = [];
        if(!empty($request->params['pass'][1])){
            $data=$this->paramsDecode($request->params['pass'][1]);
            $filterId=$data['id'];
            }
        $areaEducation = TableRegistry::get('areas');
        $surveyAreaEducation = TableRegistry::get('survey_filter_areas');
        $areaeducationData = $surveyAreaEducation->find()
                                ->where([$surveyAreaEducation->aliasField('survey_filter_id') => $filterId])->first()->area_education_id;
        if($areaeducationData != -1){  
            $data = $surveyAreaEducation->find()->select(['id'=> $areaEducation->aliasField('id'),
                            'name' => $areaEducation->aliasField('name')])
                            ->leftJoin([$areaEducation->alias() => $areaEducation->table()],
                            [$areaEducation->aliasField('id').'='.$surveyAreaEducation->aliasField('area_education_id') ])
                            ->where([$surveyAreaEducation->aliasField('survey_filter_id') => $filterId]);         
            foreach($data as $key => $value){

                $result[] = $value->name;
            }               
        }elseif($areaeducationData == NULL){
            $result[] = '';
        }elseif($areaeducationData == -1){
            $result[] = 'All Area Education';
        }
        $attr['type'] = 'readonly';
        $attr['attr']['value'] = implode(', ', $result);
           return $attr; 
     }
   }
    //POCOR-7611 end
}
