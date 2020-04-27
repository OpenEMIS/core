<?php
namespace Report\Model\Table;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;

use App\Model\Table\AppTable;

class WashReportsTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('institutions');
        
        parent::initialize($config);
        
        $this->belongsTo('Types', ['className' => 'Institution.Types', 'foreignKey' => 'institution_type_id']);
        $this->belongsTo('Areas', ['className' => 'Area.Areas']);
        $this->belongsTo('AreaAdministratives', ['className' => 'Area.AreaAdministratives']);

        // Behaviors
        $this->addBehavior('Excel', [
            'excludes' => [
                'student_status_id', 'academic_period_id', 'start_date', 'start_year', 'end_date', 'end_year', 'previous_institution_student_id'
            ],
            'pages' => false,
            'autoFields' => false
        ]);
        
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Report.InstitutionSecurity');
    }

    public function beforeAction(Event $event) 
    {
        $this->fields = [];
        $this->ControllerAction->field('feature');
        $this->ControllerAction->field('format');
    }

    public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request) 
    {
        $attr['options'] = $this->controller->getFeatureOptions($this->alias());
        return $attr;
    }
    
    public function onExcelGetWashWater(Event $event, Entity $entity)
    {
        return 'Water';
    }
    
    public function onExcelGetWashSanitation(Event $event, Entity $entity)
    {
        return 'Sanitation';
    }
    
    public function onExcelGetWashHygiene(Event $event, Entity $entity)
    {
        return 'Hygiene';
    }
    
    public function onExcelGetWashWaste(Event $event, Entity $entity)
    {
        return 'Waste';
    }
    
    public function onExcelGetWashSewage(Event $event, Entity $entity)
    {
        return 'Sewage';
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        
        $academicPeriodId = $requestData->academic_period_id;
        $washType = $requestData->wash_type;
        $conditions = [];
        
        if (!empty($academicPeriodId)) {
            //$conditions[$this->aliasField('academic_period_id')] = $academicPeriodId;
        }
        
        //if (!empty($washType)) {
            //$conditions['Institutions.id'] = $institutionId;
        //}
        
        $query
            ->select([
                $this->aliasField('id'),
                $this->aliasField('code'),
                $this->aliasField('name'),
                'area_code' => 'Areas.code',
                'area_name' => 'Areas.name',
                'academic_period' => 'AcademicPeriods.name',
                'infrastructure_wash_type' => 'InfrastructureWashWaterTypes.name',
                'infrastructure_wash_functionality' => 'InfrastructureWashWaterFunctionalities.name',
                'infrastructure_wash_accessibility' => 'InfrastructureWashWaterAccessibilities.name',
                'infrastructure_wash_quantity' => 'InfrastructureWashWaterQuantities.name',
                'infrastructure_wash_quality' => 'InfrastructureWashWaterQualities.name',
                'infrastructure_wash_proximity' => 'InfrastructureWashWaterProximities.name'
            ])
            ->contain(['Areas', 'AreaAdministratives'])
            ->leftJoin(
                ['InfrastructureWashWaters' => 'infrastructure_wash_waters'],
                [
                    'InfrastructureWashWaters.institution_id = ' . $this->aliasField('id'),
                    'InfrastructureWashWaters.academic_period_id = ' . $academicPeriodId
                ]
            )->leftJoin(
                ['InfrastructureWashWaterTypes' => 'infrastructure_wash_water_types'],
                [
                    'InfrastructureWashWaterTypes.id = InfrastructureWashWaters.infrastructure_wash_water_type_id'
                ]
            )->leftJoin(
                ['InfrastructureWashWaterFunctionalities' => 'infrastructure_wash_water_functionalities'],
                [
                    'InfrastructureWashWaterFunctionalities.id = InfrastructureWashWaters.infrastructure_wash_water_functionality_id'
                ]
            )->leftJoin(
                ['InfrastructureWashWaterAccessibilities' => 'infrastructure_wash_water_accessibilities'],
                [
                    'InfrastructureWashWaterAccessibilities.id = InfrastructureWashWaters.infrastructure_wash_water_accessibility_id'
                ]
            )->leftJoin(
                ['InfrastructureWashWaterQuantities' => 'infrastructure_wash_water_quantities'],
                [
                    'InfrastructureWashWaterQuantities.id = InfrastructureWashWaters.infrastructure_wash_water_quantity_id'
                ]
            )->leftJoin(
                ['InfrastructureWashWaterQualities' => 'infrastructure_wash_water_qualities'],
                [
                    'InfrastructureWashWaterQualities.id = InfrastructureWashWaters.infrastructure_wash_water_quality_id'
                ]
            )->leftJoin(
                ['InfrastructureWashWaterProximities' => 'infrastructure_wash_water_proximities'],
                [
                    'InfrastructureWashWaterProximities.id = InfrastructureWashWaters.infrastructure_wash_water_proximity_id'
                ]
            )->leftJoin(
                ['AcademicPeriods' => 'academic_periods'],
                [
                    'AcademicPeriods.id = ' . $academicPeriodId
                ]
            );
        
            // ->where($conditions);
        
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $cloneFields = $fields->getArrayCopy();
        $requestData = json_decode($settings['process']['params']);
        $washType = $requestData->wash_type;

        $extraFields = [];
        
        $extraFields[] = [
            'key' => 'code',
            'field' => 'code',
            'type' => 'string',
            'label' => __('Institution Code')
        ];
        
        $extraFields[] = [
            'key' => 'name',
            'field' => 'name',
            'type' => 'string',
            'label' => __('Institution Name')
        ];
        
        $extraFields[] = [
            'key' => 'area_name',
            'field' => 'area_name',
            'type' => 'string',
            'label' => __('Area Education')
        ];
        
        $extraFields[] = [
            'key' => 'AcademicPeriods.name',
            'field' => 'academic_period',
            'type' => 'string',
            'label' => __('Academic Period')
        ];
        
        
        $extraFields[] = [
            'key' => 'wash'.$washType,
            'field' => 'wash'.$washType,
            'type' => 'string',
            'label' => __('Wash')
        ];
        
        $extraFields[] = [
            'key' => 'InfrastructureWashWaterTypes.name',
            'field' => 'infrastructure_wash_type',
            'type' => 'string',
            'label' => __('Type')
        ];
        
        $extraFields[] = [
            'key' => 'InfrastructureWashWaterFunctionalities.name',
            'field' => 'infrastructure_wash_functionality',
            'type' => 'string',
            'label' => __('Functionallity')
        ];
        
        $extraFields[] = [
            'key' => 'InfrastructureWashWaterProximities.name',
            'field' => 'infrastructure_wash_proximity',
            'type' => 'string',
            'label' => __('Proximity')
        ];
        
        $extraFields[] = [
            'key' => 'InfrastructureWashWaterQuantities.name',
            'field' => 'infrastructure_wash_quantity',
            'type' => 'string',
            'label' => __('Quantity')
        ];
        
        $extraFields[] = [
            'key' => 'InfrastructureWashWaterQualities.name',
            'field' => 'infrastructure_wash_quality',
            'type' => 'string',
            'label' => __('Quailty')
        ];
        
        $extraFields[] = [
            'key' => 'InfrastructureWashWaterAccessibilities.name',
            'field' => 'infrastructure_wash_accessibility',
            'type' => 'string',
            'label' => __('Accessibity')
        ];
        
        $newFields = array_merge($extraFields);
        $fields->exchangeArray($newFields);
    }
}
