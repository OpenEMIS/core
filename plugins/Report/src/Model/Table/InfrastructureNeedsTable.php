<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use Cake\I18n\Time;
use Cake\ORM\TableRegistry;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use Institution\Model\Table\InstitutionsTable as Institutions;
use Cake\Database\Connection;

class InfrastructureNeedsTable extends AppTable  {
    public function initialize(array $config) {
        $this->table('infrastructure_needs');
        parent::initialize($config);

        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('InfrastructureNeedTypes', ['className' => 'InfrastructureNeedTypes.infrastructure_need_types']);
        
        $this->addBehavior('Excel', [
            'autoFields' => false
        ]);
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Report.InstitutionSecurity');
    }

    public function beforeAction(Event $event) {
        $this->fields = [];
        $this->ControllerAction->field('feature');
        $this->ControllerAction->field('format');
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) 
    {
        $requestData = json_decode($settings['process']['params']);
        
        $academicPeriodId = $requestData->academic_period_id;
        $institutionId = $requestData->institution_id;
        $areaId = $requestData->area_education_id;
        $conditions = [];
        if (!empty($institutionId) && $institutionId > 0) {
            $conditions['InfrastructureNeeds.institution_id'] = $institutionId;
        }
        if (!empty($areaId) && $areaId != -1) {
            $conditions['Institutions.area_id'] = $areaId;
        }
        
        $infrastructureNeeds = TableRegistry::get('Institutions.InfrastructureNeeds');
        $infrastructureNeedTypes = TableRegistry::get('infrastructure_need_types');
        $institutionStatus = TableRegistry::get('institution_statuses');
        $institutions = TableRegistry::get('institutions');
        $areas = TableRegistry::get('Area.Areas');
        $areaAdministratives = TableRegistry::get('Area.AreaAdministratives');
        $query
                ->select([
                'area_name' => 'Areas.name',
                'area_administrative_name' => 'AreaAdministratives.name',
                'institution_name' => 'Institutions.name',
                'institution_code' => 'Institutions.code',
                'institution_status_name'=> 'InstitutionStatuses.name',
                'need_id'=>'InfrastructureNeeds.id',
                'need_code'=>'InfrastructureNeeds.code',
                'need_name'=>'InfrastructureNeeds.name',
                'need_type'=>'InfrastructureNeedTypes.name',
                'description'=>'InfrastructureNeeds.description',
                'need_priority'=>'(case when InfrastructureNeeds.priority = 1 then "High"
                        when InfrastructureNeeds.priority = 2 then "Medium"
                        when InfrastructureNeeds.priority = 3 then "Low"
                        else "" end)',
                'need_date_determined'=>'InfrastructureNeeds.date_determined',
                'need_date_started'=>'InfrastructureNeeds.date_started',
                'need_date_completed'=>'InfrastructureNeeds.date_completed',
                'need_comment'=>'InfrastructureNeeds.comment'
                ])
                //status
                ->LeftJoin(['Institutions' => $institutions->table()], [
                    'InfrastructureNeeds.institution_id = Institutions.id',
                ])
                ->LeftJoin(['Areas' => $areas->table()], [
                    'Areas.id = Institutions.area_id',
                ])
                ->LeftJoin(['AreaAdministratives' => $areaAdministratives->table()], [
                    'AreaAdministratives.id = Institutions.area_administrative_id',
                ])
                ->LeftJoin(['InstitutionStatuses' => $institutionStatus->table()], [
                    'InstitutionStatuses.id = Institutions.institution_status_id',
                ])
                ->LeftJoin(['InfrastructureNeedTypes' => $infrastructureNeedTypes->table()], [
                    'InfrastructureNeeds.infrastructure_need_type_id = InfrastructureNeedTypes.id',
                ])
                ->where($conditions);   
    }

    public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request) {
            $attr['options'] = $this->controller->getFeatureOptions('Institutions');
            return $attr;
    }
     
    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $requestData = json_decode($settings['process']['params']);
        $newFields = [];
        /*POCOR-6019 starts*/
        $newFields[] = [
            'key' => 'Areas.name',
            'field' => 'area_name',
            'type' => 'string',
            'label' => __('Area Education')
        ];

        $newFields[] = [
            'key' => 'AreaAdministratives.name',
            'field' => 'area_administrative_name',
            'type' => 'string',
            'label' => __('Administrative Area')
        ];
        /*POCOR-6019 ends*/
        $newFields[] = [
            'key' => 'Institutions.name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Institution Name')
        ];
        
        $newFields[] = [
            'key' => 'Institutions.code',
            'field' => 'institution_code',
            'type' => 'string',
            'label' => __('Institution Code')
        ];

        $newFields[] = [
            'key' => 'InstitutionStatuses.name',
            'field' => 'institution_status_name',
            'type' => 'string',
            'label' => __('Institution Status')
        ];

        $newFields[] = [
            'key' => 'InfrastructureNeeds.code',
            'field' => 'need_code',
            'type' => 'string',
            'label' => __('Needs Code') 
        ];

        $newFields[] = [
            'key' => 'InfrastructureNeeds.name',
            'field' => 'need_name',
            'type' => 'string',
            'label' => __('Needs Name')
        ];

        $newFields[] = [
            'key' => 'InfrastructureNeedTypes.name',
            'field' => 'need_type',
            'type' => 'string',
            'label' => __('Needs Type')
        ];

        $newFields[] = [
            'key' => 'InfrastructureNeeds.description',
            'field' => 'description',
            'type' => 'string',
            'label' => __('Descriptions')
        ];

        $newFields[] = [
            'key' => 'InfrastructureNeeds.priority',
            'field' => 'need_priority',
            'type' => 'string',
            'label' => __('Priority')
        ];

        $newFields[] = [
            'key' => 'InfrastructureNeeds.date_determined',
            'field' => 'need_date_determined',
            'type' => 'string',
            'label' => __('Date Determined')
        ];

        $newFields[] = [
            'key' => 'InfrastructureNeeds.date_started',
            'field' => 'need_date_started',
            'type' => 'string',
            'label' => __('Date Started')
        ];

        $newFields[] = [
            'key' => 'InfrastructureNeeds.date_completed',
            'field' => 'need_date_completed',
            'type' => 'string',
            'label' => __('Date Completed')
        ];
        /*POCOR-6019 starts*/
        $newFields[] = [
            'key' => 'associated_project',
            'field' => 'associated_project',
            'type' => 'string',
            'label' => __('Associated Project')
        ];
        /*POCOR-6019 ends*/
        $newFields[] = [
            'key' => 'InfrastructureNeeds.comment',
            'field' => 'need_comment',
            'type' => 'string',
            'label' => __('Comment')
        ];
      
        $fields->exchangeArray($newFields);
    }

    public function onExcelGetAssociatedProject(Event $event, Entity $entity)
    { 
        $InfrastructureProjectsNeeds = TableRegistry::get('Institution.InfrastructureProjectsNeeds');
        $InfrastructureProjects = TableRegistry::get('Institution.InfrastructureProjects');
        $needId = $entity->need_id;
        $data = $InfrastructureProjectsNeeds->find()
                ->select([$InfrastructureProjects->aliasField('name')])
                ->leftJoin([$InfrastructureProjects->alias() => $InfrastructureProjects->table()], [
                    $InfrastructureProjects->aliasField('id = ') . $InfrastructureProjectsNeeds->aliasField('infrastructure_project_id'),
                ])
                ->where([$InfrastructureProjectsNeeds->aliasField('infrastructure_need_id') => $needId])
                ->toArray();
        $projects = [];
        if (!empty($data)) {
            foreach ($data as $value) {
                $projects[] = $value->InfrastructureProjects['name'];
            }
        }
        
        return implode($projects, ",");
    }
}
