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
use Report\Model\Table\InstitutionPositionsTable as InstitutionPositions;
use Cake\Database\Connection;

class InstitutionInfrastructuresTable extends AppTable
{
    use OptionsTrait;
    private $classificationOptions = [];

    // filter
    const NO_FILTER = 0;
    const NO_STUDENT = 1;
    const NO_STAFF = 2;

    public function initialize(array $config)
    {
        
        $this->table('institutions');

        parent::initialize($config);
        //$this->hasMany('InstitutionShifts', ['className' => 'Institution.InstitutionShifts', 'dependent' => true, 'cascadeCallbacks' => true, 'foreignKey' => 'location_institution_id']);
        $this->addBehavior('Excel', ['excludes' => ['security_group_id', 'logo_name'], 'pages' => false]);
        $this->addBehavior('Report.ReportList');
     

    }

   public function beforeAction(Event $event)
    { 
        $this->fields = [];
        $this->ControllerAction->field('feature');
        $this->ControllerAction->field('format');
    }

    public function onExcelGetAccessibility(Event $event, Entity $entity)
    {
        $accessibility = '';
        if($entity->land_infrastructure_accessibility == 1) { 
            $accessibility ='Accessible';
        } else {
            $accessibility ='Not Accessible';
        }
        return $accessibility;
    }    


   public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $requestData = json_decode($settings['process']['params']);
        $infrastructureLevel  = $requestData->infrastructure_level;
        $newFields = [];
        
        $newFields[] = [
            'key' => 'InstitutionsInfrastructure.code',
            'field' => 'code',
            'type' => 'string',
            'alias' => 'institution_code',
            'label' => __('Institution Code')
        ];
        
        $newFields[] = [
            'key' => 'InstitutionsInfrastructure.name',
            'field' => 'name',
            'type' => 'string',
            'label' => __('Institution Name')
        ];

        //POCOR-5698 two new columns added here
        $newFields[] = [
            'key' => 'ShiftOptions.name',
            'field' => 'shift_name',
            'type' => 'string',
            'label' => __('Institution Shift')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'region_code',
            'type' => 'string',
            'label' => 'Region Code'
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'region_name',
            'type' => 'string',
            'label' => 'Region Name'
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'area_code',
            'type' => 'string',
            'label' => __('Area Code')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'area_name',
            'type' => 'string',
            'label' => __('Area Name')
        ];

        $newFields[] = [
            'key' => 'InstitutionStatuses.name',
            'field' => 'institution_status_name',
            'type' => 'string',
            'label' => __('Institution Status')
        ];

        /**end here */
        $newFields[] = [
            'key' => 'land_infrastructure_code',
            'field' => 'land_infrastructure_code',
            'type' => 'string',
            'label' => __('Infrastructure Code')
        ];

        $newFields[] = [
            'key' => 'land_infrastructure_name',
            'field' => 'land_infrastructure_name',
            'type' => 'string',
            'label' => __('Infrastructure Name')
        ];

        $newFields[] = [
            'key' => 'land_start_date',
            'field' => 'land_start_date',
            'type' => 'string',
            'label' => __('Start Date')
        ];

        $newFields[] = [
            'key' => 'land_infrastructure_type',
            'field' => 'land_infrastructure_type',
            'type' => 'string',
            'label' => __('Infrastructure Type')
        ];

        $newFields[] = [
            'key' => 'land_infrastructure_ownership',
            'field' => 'land_infrastructure_ownership',
            'type' => 'string',
            'label' => __('Infrastructure Ownership')
        ];

        $newFields[] = [
            'key' => 'land_infrastructure_condition',
            'field' => 'land_infrastructure_condition',
            'type' => 'string',
            'label' => __('Infrastructure Condition')
        ];

        $newFields[] = [
            'key' => 'land_infrastructure_status',
            'field' => 'land_infrastructure_status',
            'type' => 'string',
            'label' => __('Infrastructure Status')
        ];

        $newFields[] = [
            'key' => 'accessibility',
            'field' => 'accessibility',
            'type' => 'string',
            'label' => __('Accessibility')
        ];
       
        $fields->exchangeArray($newFields);
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        
        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;
        $institutionId = $requestData->institution_id;
        $infrastructureLevel = $requestData->infrastructure_level;
        $infrastructureType = $requestData->infrastructure_type;
        
        $institutionLands = TableRegistry::get('Institution.InstitutionLands');
        $institutionFloors = TableRegistry::get('Institution.InstitutionFloors');
        $institutionBuildings = TableRegistry::get('Institution.InstitutionBuildings');
        $institutionRooms = TableRegistry::get('Institution.InstitutionRooms');
        $buildingTypes = TableRegistry::get('building_types');
        $infrastructureCondition = TableRegistry::get('infrastructure_conditions');
        $infrastructureStatus = TableRegistry::get('infrastructure_statuses');
        $institutionStatus = TableRegistry::get('institution_statuses');
        $infrastructureOwnerships = TableRegistry::get('infrastructure_ownerships');
        $infrastructureLevels = TableRegistry::get('infrastructure_levels');
        $areas = TableRegistry::get('areas');

        $institutions = TableRegistry::get('institutions');

        if($infrastructureLevel == 1) { $level = "Lands"; $type ='land';}
        if($infrastructureLevel == 2) { $level = "Buildings"; $type ='building';}
        if($infrastructureLevel == 3) { $level = "Floors"; $type ='floor';}    
        if($infrastructureLevel == 4) { $level = "Rooms"; $type ='room'; }

        $conditions = [];
        if (!empty($infrastructureType)) {
            $conditions['Institution'.$level.'.'.$type.'_type_id'] = $infrastructureType;
        }
        if (!empty($institutionId)) {
            $conditions[$this->aliasField('id')] = $institutionId;
        }
       
        $query
                    ->select(['land_infrastructure_code'=>'Institution'.$level.'.'.'code',
                    'land_infrastructure_name'=>'Institution'.$level.'.'.'name',
                    'area_id' => 'Institutions.area_id',
                    'area_code' => $areas->aliasField('code'),
                    'area_name' => $areas->aliasField('name'),
                    'land_start_date'=>'Institution'.$level.'.'.'start_date',
                    'land_infrastructure_type'=>$buildingTypes->aliasField('name'),
                    'land_infrastructure_condition'=>$infrastructureCondition->aliasField('name'),
                    'land_infrastructure_status'=>$infrastructureStatus->aliasField('name'),
                    //POCOR-5698 two new columns added here
                    'shift_name' => 'ShiftOptions.name',
                    'institution_status_name'=> 'InstitutionStatuses.name',
                    //POCOR-5698 ends here
                    'land_infrastructure_ownership'=>$infrastructureOwnerships->aliasField('name'),
                    'land_infrastructure_accessibility' => 'Institution'.$level.'.'.'accessibility',
                    ])
                    ->LeftJoin([ 'Institution'.$level => 'institution_'.lcfirst($level) ], [
                        'Institution'.$level.'.'.'institution_id = ' . $this->aliasField('id'),
                    ])
                    ->LeftJoin([$buildingTypes->alias() => $buildingTypes->table()], [
                        'Institution'.$level.'.'.$type.'_type_id = ' . $buildingTypes->aliasField('id'),
                    ])
                    ->LeftJoin([$infrastructureCondition->alias() => $infrastructureCondition->table()], ['Institution'.$level.'.'.'infrastructure_condition_id = ' . $infrastructureCondition->aliasField('id'),
                    ])
                    ->LeftJoin([$infrastructureStatus->alias() => $infrastructureStatus->table()], [
                        'Institution'.$level.'.'.$type.'_status_id = ' . $infrastructureStatus->aliasField('id'),
                    ])
                    //POCOR-5698 two new columns added here
                    //status
                    ->LeftJoin(['Institutions' => $institutions->table()], [
                        'Institution'.$level.'.'.'institution_id = Institutions.id',
                    ])
                    ->LeftJoin([$areas->alias() => $areas->table()], [
                        'Institutions.area_id = ' . $areas->aliasField('id'),
                    ])
                    ->LeftJoin(['InstitutionStatuses' => $institutionStatus->table()], [
                        'InstitutionStatuses.id = Institutions.institution_status_id',
                    ])
                    //shift
                    ->LeftJoin(['InstitutionShifts' => 'institution_shifts'],[
                        'Institution'.$level.'.'.'institution_id = InstitutionShifts.institution_id',
                        'Institution'.$level.'.'.'academic_period_id = InstitutionShifts.academic_period_id'
                    ])
                    ->LeftJoin(['ShiftOptions' => 'shift_options'],[
                        'ShiftOptions.id = InstitutionShifts.shift_option_id'
                    ])
                    //POCOR-5698 two new columns ends here
                    ->LeftJoin([$infrastructureOwnerships->alias() => $infrastructureOwnerships->table()], [
                        'Institution'.$level.'.'.$type.'_status_id = ' . $infrastructureOwnerships->aliasField('id'),
                    ])
                    ->where($conditions);
        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
            return $results->map(function ($row) {
                
                $areas1 = TableRegistry::get('areas');
                $areasData = $areas1
                            ->find()
                            ->where([$areas1->alias('code')=>$row->area_code])
                            ->first();
                $row['region_code'] = '';            
                $row['region_name'] = '';
                if(!empty($areasData)){
                    $areas = TableRegistry::get('areas');
                    $areaLevels = TableRegistry::get('area_levels');
                    $institutions = TableRegistry::get('institutions');
                    $val = $areas
                                ->find()
                                ->select([
                                    $areas1->aliasField('code'),
                                    $areas1->aliasField('name'),
                                    ])
                                ->leftJoin(
                                    [$areaLevels->alias() => $areaLevels->table()],
                                    [
                                        $areas->aliasField('area_level_id  = ') . $areaLevels->aliasField('id')
                                    ]
                                )
                                ->leftJoin(
                                    [$institutions->alias() => $institutions->table()],
                                    [
                                        $areas->aliasField('id  = ') . $institutions->aliasField('area_id')
                                    ]
                                )    
                                ->where([
                                    $areaLevels->aliasField('level !=') => 1,
                                    $areas->aliasField('id') => $areasData->parent_id
                                ])->first();
                    
                    if (!empty($val->name) && !empty($val->code)) {
                        $row['region_code'] = $val->code;
                        $row['region_name'] = $val->name;
                    }
                }            
                
                return $row;
            });
        });
    }
}
