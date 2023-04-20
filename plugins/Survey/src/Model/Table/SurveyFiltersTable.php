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
        $this->belongsTo('CustomModules', ['className' => 'CustomField.CustomModules','foreignKey' => 'custom_module_id']);
        $this->belongsTo('SurveyForms', ['className' => 'Survey.SurveyForms', 'foreignKey' => 'survey_form_id']);

        $this->hasMany('InstitutionProviders', ['className' => 'Institution.InstitutionProviders', 'foreignKey' => 'institution_provider_id']);
        $this->hasMany('InstitutionTypes', ['className' => 'Institution.InstitutionTypes', 'foreignKey' => 'institution_type_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('Areas', ['className' => 'Areas', 'foreignKey' => 'area_education_id', 'dependent' => true, 'cascadeCallbacks' => true]);

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
        //custom module option in toolbar
        $name = array('Institution > Overview','Institution > Students > Survey','Institution > Repeater > Survey');
        $CustomModules = TableRegistry::get('custom_modules');
        $moduleOptions =  $CustomModules
            ->find('list', ['keyField' => 'id', 'valueField' => 'code']) 
           ->where(['custom_modules.name IN' => $name])->toArray();

        if (!empty($moduleOptions)) {
            $moduleOptions = $moduleOptions;
            $selectedModule = $this->queryString('module', $moduleOptions);
            $moduleId = $this->request->query('survey_module_id');
            $extra['toolbarButtons']['add']['url']['module'] = $selectedModule;
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
        /*$query->find()->select(['id','name'])
                ->leftJoin([$tableProvider->alias() => $tableProvider->table()],
                    [ $tableProvider->aliasField('survey_filter_id').'='.$this->aliasField('id') ])
                ->leftJoin([$institutionType->alias() => $institutionType->table()],
                    [ $institutionType->aliasField('survey_filter_id').'='.$this->aliasField('id') ])
                ->leftJoin([$areaEducation->alias() => $areaEducation->table()],
                    [ $areaEducation->aliasField('survey_filter_id').'='.$this->aliasField('id') ])
                ->leftJoin([$provider->alias() => $provider->table()],
                    [ $provider->aliasField('id').'='.$tableProvider->aliasField('institution_provider_id') ])
                ->leftJoin([$type->alias() => $type->table()],
                    [ $type->aliasField('id').'='.$institutionType->aliasField('institution_type_id') ])
                ->leftJoin([$areas->alias() => $areas->table()],
                    [ $areas->aliasField('id').'='.$areaEducation->aliasField('area_education_id') ]);*/

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
        $data = $query->toArray();
        $filterId = $data[0]['id'];
        $tableProvider = TableRegistry::get('survey_filter_institution_providers');
        $institutionType = TableRegistry::get('survey_filter_institution_types');
        $areaEducation = TableRegistry::get('survey_filter_areas');

        $providerResult = $tableProvider->find()->select(['institution_provider_id'])
                        ->where([$tableProvider->aliasField('survey_filter_id') => $filterId])
                        ->toArray();
        $institutionTypeResult = $institutionType->find()->select(['institution_type_id'])
                                ->where([$institutionType->aliasField('survey_filter_id') => $filterId])
                                ->toArray();
        $areaEducationResult = $areaEducation->find()->select(['area_education_id'])
                                ->where([$areaEducation->aliasField('survey_filter_id') => $filterId])
                                ->toArray();
        
        $provider = [];
        if(!empty($providerResult)){
            foreach($providerResult as $key => $value){
                $provider[$key] = ['id' => $value->institution_provider_id]; 
            }
        }
        $type = [];
        if(!empty($institutionTypeResult)){
            foreach($institutionTypeResult as $key => $value){
                $type[$key] = ['id' => $value->institution_type_id]; 
            }
        }
        $areaEducation = [];
        if(!empty($areaEducationResult)){
            foreach($areaEducationResult as $key => $value){
                $areaEducation[$key] = ['id' => $value->area_education_id]; 
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

    public function onGetCustomModuleId(Event $event, Entity $entity)
    {
        $CustomModules = TableRegistry::get('custom_modules');
        $data = $CustomModules->find()->select(['code' =>$CustomModules->aliasField('code')])->where([$CustomModules->aliasField('id') => $entity->custom_module_id])->first();
       return $data->code;
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $type = $entity->institution_type_id[0]['institution_type_id'];
        $provider = $entity->institution_provider_id[0]['institution_provider_id'];
        $areaEducation = $entity->institution_provider_id[0]['institution_provider_id'];
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



}
