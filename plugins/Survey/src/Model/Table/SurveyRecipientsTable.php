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

//POCOR-7271
class SurveyRecipientsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('institution_surveys');
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods','foreignKey' => 'academic_period_id']);
        $this->belongsTo('SurveyForms', ['className' => 'Survey.SurveyForms','foreignKey' => 'survey_form_id']);
        parent::initialize($config);
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Rules' => ['index']
        ]);
        $this->toggle('add', false);
        $this->toggle('view', false);
        $this->toggle('edit', false);
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
            $moduleId = $this->request->query('survey_module_id');
            $this->advancedSelectOptions($moduleOptions, $moduleId);
            $this->controller->set(compact('moduleOptions'));
        }

        // Survey form options
        $this->SurveyForms = TableRegistry::get('survey_forms');
        $surveyFormOptions = $this->SurveyForms
            ->find('list')
            ->order([
                $this->SurveyForms->aliasField('name')
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
        $surveyFilterId = $this->request->query('survey_filter_id');
        $this->advancedSelectOptions($surveyFilterOptions, $surveyFilterId);
     
        $extra['elements']['controls'] = ['name' => 'Survey.survey_status', 'data' => [], 'options' => [], 'order' => 3];
        $this->controller->set(compact('surveyFilterOptions'));

        $institutions = TableRegistry::get('institutions');
        $surveyForm = TableRegistry::get('survey_forms');
        $SurveyFormFilters = TableRegistry::get('survey_forms_filters');
        $SurveyStatus=TableRegistry::get('survey_statuses'); //POCOR-7611 
        $SurveyAreas=TableRegistry::get('survey_filter_areas'); //POCOR-7611 
        $SurveyInstitutionProviders=TableRegistry::get('survey_filter_institution_providers');//POCOR-7611 
        $SurveyInstitutionTypes=TableRegistry::get('survey_filter_institution_types');//POCOR-7611 
        $this->field('institution_code',['visible' => true]);
        $this->field('institution_name', ['visible' => true]);
        $this->field('status_id', ['visible' => false]);
        $this->field('academic_period_id', ['visible' => true]);
        $this->field('survey_form_id', ['visible' => true]);
        $this->field('institution_id', ['visible' => false]);
        $this->field('assignee_id', ['visible' => false]);
        $this->setFieldOrder([
        'institution_code', 'institution_name']);
        $search = $this->getSearchKey(); //POCOR-7271
        if (!empty($search)) {
            $query->find('bySurveyRecipient', ['search' => $search]);
        }

        $moduleId = $this->request->query('survey_module_id');
        $surveyFormId = $this->request->query('survey_form_id');
        $surveyFilterId = $this->request->query('survey_filter_id');
        $where = [];
        //POCOR-7611 start
        $conditions=[];
        //filter for area,institution type and provider
        if($surveyFilterId != -1){
                $SurveyInstitutionProvidersData=$SurveyInstitutionProviders
                            ->find()
                            ->select([$SurveyInstitutionProviders->aliasField('institution_provider_id')])
                            ->where([ $SurveyInstitutionProviders->aliasField('survey_filter_id').'='.$surveyFilterId])
                            ->toArray();
                $provider_ids=[];
                foreach( $SurveyInstitutionProvidersData as $key=>$value){
                $provider_ids[]=$value['institution_provider_id'];
                }

                $SurveyInstitutionTypesData=$SurveyInstitutionTypes
                            ->find()
                            ->select([$SurveyInstitutionTypes->aliasField('institution_type_id')])
                            ->where([ $SurveyInstitutionTypes->aliasField('survey_filter_id').'='.$surveyFilterId])
                            ->toArray();
                $institution_types_ids=[];
                foreach( $SurveyInstitutionTypesData as $key=>$value){
                $institution_types_ids[]=$value['institution_type_id'];
                }

                $SurveyAreaData=$SurveyAreas->find()
                            ->select([$SurveyAreas->aliasField('area_education_id')])
                            ->where([ $SurveyAreas->aliasField('survey_filter_id').'='.$surveyFilterId])
                            ->toArray();
                $area_ids=[];
                foreach(  $SurveyAreaData as $key=>$value){
                $area_ids[]=$value['area_education_id'];
                }

                if(!in_array(-1,$provider_ids)){
                $conditions[]= [$institutions->aliasField('institution_provider_id In')=>$provider_ids];
                }
                if(!in_array(-1,$institution_types_ids)){
                $conditions[]= [$institutions->aliasField('institution_type_id In')=>$institution_types_ids];
                }
                if(!in_array(-1,$area_ids)){
                $conditions[]= [$institutions->aliasField('area_id In')=>$area_ids];
                }
        }
        //POCOR-7611 end
        if($moduleId == null && $surveyFormId == null && $surveyFilterId == null){
             $query
            ->select(['id' => $this->aliasField('id'),'institution_name'=> $institutions->aliasField('name'),
                        'institution_code'=> $institutions->aliasField('code'),'academic_period_id','survey_form_id'])
            ->leftJoin([$institutions->alias() => $institutions->table()],
                [$institutions->aliasField('id').'='.$this->aliasField('institution_id')])
            ->leftJoin([$surveyForm->alias() => $surveyForm->table()],
                [$surveyForm->aliasField('id').'='.$this->aliasField('survey_form_id')])
            //->where([$surveyForm->aliasField('custom_module_id') => $moduleId])
            ->order([$this->aliasField('id') => 'DESC']);
        }elseif($moduleId == 1 && $surveyFormId == -1 && $surveyFilterId == -1){
             $query
            ->select(['id' => $this->aliasField('id'),'institution_name'=> $institutions->aliasField('name'),
                        'institution_code'=> $institutions->aliasField('code'),'academic_period_id','survey_form_id'])
            ->leftJoin([$institutions->alias() => $institutions->table()],
                [$institutions->aliasField('id').'='.$this->aliasField('institution_id')])
            ->leftJoin([$surveyForm->alias() => $surveyForm->table()],
                [$surveyForm->aliasField('id').'='.$this->aliasField('survey_form_id')])
            ->where([$surveyForm->aliasField('custom_module_id') => $moduleId])
            ->order([$this->aliasField('id') => 'DESC']);
        }elseif($moduleId == 1 && $surveyFormId != -1 && $surveyFilterId == -1){

             $query
            ->select(['id' => $this->aliasField('id'),'institution_name'=> $institutions->aliasField('name'),
                        'institution_code'=> $institutions->aliasField('code'),'academic_period_id','survey_form_id'])
            ->leftJoin([$institutions->alias() => $institutions->table()],
                [$institutions->aliasField('id').'='.$this->aliasField('institution_id')])
            ->leftJoin([$surveyForm->alias() => $surveyForm->table()],
                [$surveyForm->aliasField('id').'='.$this->aliasField('survey_form_id')])
            ->where([$this->aliasField('survey_form_id') => $surveyFormId,$surveyForm->aliasField('custom_module_id') => $moduleId])
            ->order([$this->aliasField('id') => 'DESC']);
        }
        elseif($moduleId == 1 && $surveyFormId != -1 && $surveyFilterId != -1){
             $query
            ->select(['id' => $this->aliasField('id'),'institution_name'=> $institutions->aliasField('name'),
                        'institution_code'=> $institutions->aliasField('code'),'academic_period_id','survey_form_id'])
            ->leftJoin([$institutions->alias() => $institutions->table()],
                [$institutions->aliasField('id').'='.$this->aliasField('institution_id')])
            ->leftJoin([$surveyForm->alias() => $surveyForm->table()],
                [$surveyForm->aliasField('id').'='.$this->aliasField('survey_form_id')])
            ->leftJoin([$SurveyFormFilters->alias() => $SurveyFormFilters->table()],
                [$SurveyFormFilters->aliasField('survey_form_id').'='.$this->aliasField('survey_form_id')])
            ->InnerJoin([$SurveyStatus->alias() => $SurveyStatus->table()],//POCOR-7611 
                [$SurveyStatus->aliasField('survey_form_id').'='.$this->aliasField('survey_form_id'),
                 $SurveyStatus->aliasField('survey_filter_id').'='.$SurveyFormFilters->aliasField('id') 
                ])
            ->distinct( $institutions->aliasField('code'))//POCOR-7611 
            ->where([$SurveyFormFilters->aliasField('id') => $surveyFilterId,
                     $SurveyFormFilters->aliasField('custom_module_id') => $moduleId,
                     $this->aliasField('survey_form_id') => $surveyFormId,$conditions])//POCOR-7611 
            ->order([$this->aliasField('id') => 'DESC']);
        
        }
        elseif($moduleId == 1 && $surveyFormId == -1 && $surveyFilterId != -1){
            $query
            ->select(['id' => $this->aliasField('id'),'institution_name'=> $institutions->aliasField('name'),
                        'institution_code'=> $institutions->aliasField('code')])
            ->leftJoin([$institutions->alias() => $institutions->table()],
                [$institutions->aliasField('id').'='.$this->aliasField('institution_id')])
            ->leftJoin([$surveyForm->alias() => $surveyForm->table()],
                [$surveyForm->aliasField('id').'='.$this->aliasField('survey_form_id')])
            ->leftJoin([$SurveyFormFilters->alias() => $SurveyFormFilters->table()],
                [$SurveyFormFilters->aliasField('survey_form_id').'='.$this->aliasField('survey_form_id')])
            ->InnerJoin([$SurveyStatus->alias() => $SurveyStatus->table()],//POCOR-7611 
                [$SurveyStatus->aliasField('survey_filter_id').'='.$SurveyFormFilters->aliasField('id') ])
            ->distinct( $institutions->aliasField('code'))//POCOR-7611 
            ->where([$SurveyFormFilters->aliasField('id') => $surveyFilterId,$conditions])//POCOR-7611 
            ->group([$this->aliasField('institution_id')])
            ->order([$this->aliasField('id') => 'DESC']);
        }  
        else{
            $query
            ->select(['id' => $this->aliasField('id'),'institution_name'=> $institutions->aliasField('name'),
                        'institution_code'=> $institutions->aliasField('code')])
            ->leftJoin([$institutions->alias() => $institutions->table()],
                [$institutions->aliasField('id').'='.$this->aliasField('institution_id')])
            ->leftJoin([$surveyForm->alias() => $surveyForm->table()],
                [$surveyForm->aliasField('id').'='.$this->aliasField('survey_form_id')])
            ->leftJoin([$SurveyFormFilters->alias() => $SurveyFormFilters->table()],
                [$SurveyFormFilters->aliasField('survey_form_id').'='.$this->aliasField('survey_form_id')])
            ->where([$SurveyFormFilters->aliasField('id') => $surveyFilterId,$surveyForm->aliasField('custom_module_id') => $moduleId,$this->aliasField('survey_form_id') => $surveyFormId])
            ->group([$this->aliasField('institution_id')])
            ->order([$this->aliasField('id') => 'DESC']);
        }              
    }

    //POCOR-7271
    public function findBySurveyRecipient(Query $query, array $options)
    {
        if (array_key_exists('search', $options)) {
            $search = $options['search'];
            $query
            ->join([
                [
                    'table' => 'institutions', 'alias' => 'InstitutionsTable', 'type' => 'INNER',
                    'conditions' => ['InstitutionsTable.id = ' . $this->aliasField('institution_id')]
                ],
                [
                    'table' => 'academic_periods', 'alias' => 'AcademicPeriods', 'type' => 'INNER',
                    'conditions' => ['AcademicPeriods.id = ' . $this->aliasField('academic_period_id')]
                ],
            ])
            ->where([
                    'OR' => [
                        ['InstitutionsTable.name LIKE' => '%' . $search . '%'],
                        ['InstitutionsTable.code LIKE' => '%' . $search . '%'],
                        ['AcademicPeriods.name LIKE' => '%' . $search . '%'],
                    ]
                ]
            );
        }

        return $query;
    }

    private function getFilterData(){

    }

}
