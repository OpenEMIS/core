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
        
        $conditions = [];
        if (!empty($institutionId)) {
            $conditions['InfrastructureNeeds.institution_id'] = $institutionId;
        }
        
        $infrastructureNeeds = TableRegistry::get('Institutions.InfrastructureNeeds');
        $infrastructureNeedTypes = TableRegistry::get('infrastructure_need_types');
        $institutionStatus = TableRegistry::get('institution_statuses');
        $institutions = TableRegistry::get('institutions');
        $query
                ->select([
                'institution_name' => 'Institutions.name',
                'institution_code' => 'Institutions.code',
                'institution_status_name'=> 'InstitutionStatuses.name',
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

        $newFields[] = [
            'key' => 'InfrastructureNeeds.comment',
            'field' => 'need_comment',
            'type' => 'string',
            'label' => __('Comment')
        ];
       
        $fields->exchangeArray($newFields);
    }
}
