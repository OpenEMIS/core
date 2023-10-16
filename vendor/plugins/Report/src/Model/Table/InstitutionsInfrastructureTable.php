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
        $infrastructureLevel = $requestData->infrastructure_level;
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
            'field' => 'region_name',
            'type' => 'string',
            'label' => 'Region Name'
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
        
        if($infrastructureLevel == 1) { $level = "Lands"; $type ='land';}
        if($infrastructureLevel == 2) { $level = "Buildings"; $type ='building';}
        if($infrastructureLevel == 3) { $level = "Floors"; $type ='floor';}    
        if($infrastructureLevel == 4) { $level = "Rooms"; $type ='room'; }
            
        if($infrastructureLevel == 1 || $infrastructureLevel == 2 || $infrastructureLevel == 3) { 
            
            $newFields[] = [
                'key' => 'area',
                'field' => 'area',
                'type' => 'string',
                'label' => __($level.' Area')
            ];
        }
        
        if($infrastructureLevel == 1 || $infrastructureLevel == 2) {
        
            $newFields[] = [
                'key' => 'year_acquired',
                'field' => 'year_acquired',
                'type' => 'string',
                'label' => __('Year Acquired')
            ];
            
            $newFields[] = [
                'key' => 'year_disposed',
                'field' => 'year_disposed',
                'type' => 'string',
                'label' => __('Year Disposed')
            ];
        }

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
        
        $InfrastructureCustomFields = TableRegistry::get('infrastructure_custom_fields');
                    
        $customFieldData = $InfrastructureCustomFields->find()
            ->select([
                'custom_field_id' => $InfrastructureCustomFields->aliasfield('id'),
                'custom_field' => $InfrastructureCustomFields->aliasfield('name')
            ])
            ->innerJoin(['CustomFieldValues' => lcfirst($type).'_custom_field_values' ], [
                'CustomFieldValues.infrastructure_custom_field_id = ' . $InfrastructureCustomFields->aliasField('id'),
            ])
            ->group($InfrastructureCustomFields->aliasfield('id'))
            ->toArray();
       
        if(!empty($customFieldData)) {
            foreach($customFieldData as $data) {
                $custom_field_id = $data->custom_field_id;
                $custom_field = $data->custom_field;
                $newFields[] = [
                    'key' => '',
                    'field' => $custom_field_id,
                    'type' => 'string',
                    'label' => __($custom_field)
                ];
            }
        }
       
        $fields->exchangeArray($newFields);
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        
        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;
        $institutionId = $requestData->institution_id;
        $infrastructureLevel = $requestData->infrastructure_level;
        $infrastructureType = $requestData->infrastructure_type;
        $institutionTypeId = $requestData->institution_type_id;
        $areaId = $requestData->area_education_id;
        
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
        if (!empty($areaId) && $areaId != -1) {
            $conditions[$this->aliasField('area_id')] = $areaId;
        }
        $institutions = TableRegistry::get('Institution.Institutions');
        $institutionIds = $institutions->find('list', [
                                                    'keyField' => 'id',
                                                    'valueField' => 'id'
                                             ])
                            ->where(['institution_type_id' => $institutionTypeId])
                            ->toArray();

        if (!empty($institutionTypeId)) {
             $conditions['Institution'.$level.'.'.'institution_id IN'] = $institutionIds;
         
        }
       
        if ($infrastructureLevel == 1 || $infrastructureLevel == 2) {
            $query
                    ->select(['land_infrastructure_code'=>'Institution'.$level.'.'.'code',
                        'land_infrastructure_name'=>'Institution'.$level.'.'.'name',
                        'area_id' => 'Institutions.area_id',
                        'area_code' => $areas->aliasField('code'),
                        'area_name' => $areas->aliasField('name'),
                        'level_id'=>'Institution'.$level.'.'.'id',
                        'land_start_date'=>'Institution'.$level.'.'.'start_date',
                        'area'=>'Institution'.$level.'.'.'area',
                        'year_acquired'=>'Institution'.$level.'.'.'year_acquired',
                        'year_disposed'=>'Institution'.$level.'.'.'year_disposed',
                        'land_infrastructure_type'=> 'InfrastructureTypes.name',
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
                        ->LeftJoin(['InfrastructureTypes' => $type.'_types'], [
                            'InfrastructureTypes.id = ' . $type.'_type_id',
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
        } else if ($infrastructureLevel == 3) {
            $query
                    ->select(['land_infrastructure_code'=>'Institution'.$level.'.'.'code',
                        'land_infrastructure_name'=>'Institution'.$level.'.'.'name',
                        'area_id' => 'Institutions.area_id',
                        'area_code' => $areas->aliasField('code'),
                        'area_name' => $areas->aliasField('name'),
                        'level_id'=>'Institution'.$level.'.'.'id',
                        'land_start_date'=>'Institution'.$level.'.'.'start_date',
                        'area'=>'Institution'.$level.'.'.'area',
                        'land_infrastructure_type'=> 'InfrastructureTypes.name',
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
                        ->LeftJoin(['InfrastructureTypes' => $type.'_types'], [
                            'InfrastructureTypes.id = ' . $type.'_type_id',
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
        } else {
            $query
                    ->select(['land_infrastructure_code'=>'Institution'.$level.'.'.'code',
                        'land_infrastructure_name'=>'Institution'.$level.'.'.'name',
                        'area_id' => 'Institutions.area_id',
                        'area_code' => $areas->aliasField('code'),
                        'area_name' => $areas->aliasField('name'),
                        'level_id'=>'Institution'.$level.'.'.'id',
                        'land_start_date'=>'Institution'.$level.'.'.'start_date',
                        'land_infrastructure_type'=> 'InfrastructureTypes.name',
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
                        ->LeftJoin(['InfrastructureTypes' => $type.'_types'], [
                            'InfrastructureTypes.id = ' . $type.'_type_id',
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
        }
        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) use($type) {
            return $results->map(function ($row) use($type) {
            
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
                
                $InfrastructureCustomFields = TableRegistry::get('infrastructure_custom_fields');
                if(!empty($row['level_id'])) { 
                    $customFieldData = $InfrastructureCustomFields->find()
                        ->select([
                            'custom_field_id' => $InfrastructureCustomFields->aliasfield('id'),
                            'custom_field' => $InfrastructureCustomFields->aliasfield('name'),
                            'text_value' => 'CustomFieldValues.text_value',
                            'number_value' => 'CustomFieldValues.number_value',
                            'decimal_value' => 'CustomFieldValues.decimal_value',
                            'textarea_value' => 'CustomFieldValues.textarea_value',
                            'date_value' => 'CustomFieldValues.date_value',
                            'time_value' => 'CustomFieldValues.time_value'
                        ])
                        ->innerJoin(['CustomFieldValues' => lcfirst($type).'_custom_field_values' ], [
                            'CustomFieldValues.infrastructure_custom_field_id = ' . $InfrastructureCustomFields->aliasField('id'),
                            'CustomFieldValues.institution_'.lcfirst($type).'_id  = ' . $row['level_id']
                        ])
                        ->toArray();
                }
                if(!empty($customFieldData)) {
                    foreach($customFieldData as $data) {
                        if(!empty($data->text_value)) {
                            $row[$data->custom_field_id] = $data->text_value;
                        } 
                        if(!empty($data->number_value)) {
                            $row[$data->custom_field_id] = $data->number_value;
                        }
                        if(!empty($data->decimal_value)) {
                            $row[$data->custom_field_id] = $data->decimal_value;
                        }
                        if(!empty($data->textarea_value)) {
                            $row[$data->custom_field_id] = $data->textarea_value;
                        }   
                        if(!empty($data->date_value)) {
                            $row[$data->custom_field_id] = $data->date_value;
                            
                        }
                        if(!empty($data->time_value)) {
                            $row[$data->custom_field_id] = $data->time_value;
                            
                        }
                    }
                }
                return $row;
            });
        });
    }
}