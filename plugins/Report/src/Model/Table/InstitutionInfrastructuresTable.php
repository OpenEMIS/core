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
        // $newFields[] = [
        //     'key' => 'ShiftOptions.name',
        //     'field' => 'shift_name',
        //     'type' => 'string',
        //     'label' => __('Institution Shift')
        // ];

        //POCOR-6650 Starts
        $AreaLevelTbl = TableRegistry::get('area_levels');
        $AreaLevelArr = $AreaLevelTbl->find()->select(['id','name'])->order(['id'=>'DESC'])->limit(2)->hydrate(false)->toArray();
         
        $newFields[] = [
            'key' => '',
            'field' => 'region_name',
            'type' => 'string',
            'label' => __($AreaLevelArr[1]['name'])
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'area_name',
            'type' => 'string',
            'label' => __($AreaLevelArr[0]['name'])
        ]; //POCOR-6650 Ends

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
            'key' => 'ShiftOptions.name',
            'field' => 'shift_name',
            'type' => 'string',
            'label' => __('Institution Shift')
            ];

			$newFields[] = [
				'key' => 'area',
				'field' => 'area',
				'type' => 'string',
				'label' => __($level.' Area')
			];
		}

        //Start POCOR-6731
        if($infrastructureLevel == 3 || $infrastructureLevel == 4) { 
            $newFields[] = [
                'key' => 'institution_buildings_name',
                'field' => 'institution_buildings_name',
                'type' => 'string',
                'label' => __('Buildings Name')
            ];
        }
        if($infrastructureLevel == 4) {
            
            $newFields[] = [
            'key' => 'shift_info.shift_options_name',
            'field' => 'shift_name',
            'type' => 'string',
            'label' => __('Institution Shift')
            ];

            $newFields[] = [
                'key' => 'institution_floor_name',
                'field' => 'institution_floor_name',
                'type' => 'string',
                'label' => __('Floors Name')
            ];
        }

        //End POCOR-6731
		
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
		
		/*POCOR-6264 starts*/
		$InfrastructureCustomFields = TableRegistry::get('infrastructure_custom_fields');
		$customModules = TableRegistry::get('custom_modules');
		$infrastructureCustomForms = TableRegistry::get('infrastructure_custom_forms');
		$infrastructureCustomFormsFields = TableRegistry::get('infrastructure_custom_forms_fields');
		$customModuleId = $customModules->find()
						->where([
						    $customModules->aliasField('name') => 'Institution >'. ' ' . ucwords($type)
						])->first()->id;
		$redcordIds = [];
        if(!empty($customModuleId)){
            $getRecords = $infrastructureCustomForms->find()
                        ->where([ $infrastructureCustomForms->aliasField('custom_module_id') => $customModuleId ])->toArray();
            if (!empty($getRecords)) {
                foreach ($getRecords as $record) {
                    $redcordIds[] = $record->id;
                }
            }
        }
        $ids = [];
        if (!empty($redcordIds)) {
            $customdata = $infrastructureCustomFormsFields->find()
                        ->where([
                            $infrastructureCustomFormsFields->aliasfield('infrastructure_custom_form_id IN') => $redcordIds
                        ])->toArray();
            if (!empty($customdata)) {
                foreach ($customdata as $val) {
                    $ids[] = $val->infrastructure_custom_field_id;
                }
            }
        }
		if(!empty($ids)){
            $customFieldData = $InfrastructureCustomFields->find()
                ->select([
                    'custom_field_id' => $InfrastructureCustomFields->aliasfield('id'),
                    'custom_field' => $InfrastructureCustomFields->aliasfield('name')
                ])
                ->leftJoin(['CustomFieldValues' => lcfirst($type).'_custom_field_values' ], [
                    'CustomFieldValues.infrastructure_custom_field_id = ' . $InfrastructureCustomFields->aliasField('id'),
                ])
                ->where([$InfrastructureCustomFields->aliasfield('id IN') => $ids])
                ->group($InfrastructureCustomFields->aliasfield('id'))
                ->toArray();

            /*POCOR-6264 ends*/
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
		/*POCOR-6335 starts - applying academic period condition*/
		if (!empty($academicPeriodId)) {
             $conditions['Institution'.$level.'.'.'academic_period_id'] = $academicPeriodId;
         
        }
		/*POCOR-633 ends*/	       
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
            $InstitutionBuildings = 'buildings';
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
                        'institution_buildings_name' => 'Institution'.$InstitutionBuildings.'.'.'name', //POCOR-6731
						])
						->LeftJoin([ 'Institution'.$level => 'institution_'.lcfirst($level) ], [
							'Institution'.$level.'.'.'institution_id = ' . $this->aliasField('id'),
						])
						->LeftJoin(['InfrastructureTypes' => $type.'_types'], [
							'InfrastructureTypes.id = ' . $type.'_type_id',
						])
                        ->LeftJoin([ 'Institution'.$InstitutionBuildings => 'institution_'.lcfirst($InstitutionBuildings) ], [
                            'Institution'.$InstitutionBuildings.'.'.'institution_id = ' . $this->aliasField('id'),
                        ])//POCOR-6731
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
            $InstitutionBuildings = 'buildings';
            $InstitutionFloors = 'floors';
            $query
                ->select(['land_infrastructure_code'=>'Institution'.$level.'.'.'code',
                        'land_infrastructure_name'=>'Institution'.$level.'.'.'name',
                        'area_id' => $this->aliasField('area_id'),
                        'area_code' => $areas->aliasField('code'),
                        'area_name' => $areas->aliasField('name'),
                        'level_id'=>'Institution'.$level.'.'.'id',
                        'land_start_date'=>'Institution'.$level.'.'.'start_date',
                        'land_infrastructure_type'=> 'InfrastructureTypes.name',
                        'land_infrastructure_condition'=>$infrastructureCondition->aliasField('name'),
                        'land_infrastructure_status'=>$infrastructureStatus->aliasField('name'),
                        //POCOR-5698 two new columns added here
                        'shift_name' => 'shift_info.shift_options_name',
                        'institution_status_name'=> 'InstitutionStatuses.name',
                        //POCOR-5698 ends here
                        'land_infrastructure_ownership'=>$infrastructureOwnerships->aliasField('name'),
                        'land_infrastructure_accessibility' => 'Institution'.$level.'.'.'accessibility',
                        //Start POCOR-6731
                        'institution_buildings_name' => 'Institution'.$InstitutionBuildings.'.'.'name',
                        'institution_floor_name' => 'Institution'.$InstitutionFloors.'.'.'name',
                        //End POCOR-6731
                        ])
                ->innerJoin([$areas->alias() => $areas->table()],[
                   $this->aliasField('area_id = ') . $areas->aliasField('id'),
                ])

                
                ->LeftJoin(['InstitutionStatuses' => $institutionStatus->table()], [
                    'InstitutionStatuses.id = '. $this->aliasField('institution_status_id'),
                ])
                ->innerJoin(['AcademicPeriods' => 'academic_periods'], [
                    'OR' => 
                        [
                            [
                                'OR' => [
                                    [
                                       $this->aliasField('date_closed') . ' IS NOT NULL',
                                       $this->aliasField('date_opened') . ' <= AcademicPeriods.start_date',
                                       $this->aliasField('date_closed') . ' >= AcademicPeriods.start_date',
                                   ],
                                   [
                                       $this->aliasField('date_closed') . ' IS NOT NULL',
                                       $this->aliasField('date_opened') . ' <= AcademicPeriods.end_date',
                                       $this->aliasField('date_closed') . ' >= AcademicPeriods.end_date',
                                   ],
                                   [
                                       $this->aliasField('date_closed') . ' IS NOT NULL',
                                       $this->aliasField('date_opened') . ' >= AcademicPeriods.start_date',
                                       $this->aliasField('date_closed') . ' <= AcademicPeriods.end_date',
                                   ],
                               ]
                           ],
                           [
                            'OR' => [
                                [
                                    $this->aliasField('date_closed') . ' IS NULL',
                                    $this->aliasField('date_opened') . ' <= AcademicPeriods.end_date',              
                                ]
                            ]
                        ]
                    ]
                ])
                ->LeftJoin([ 'Institution'.$level => 'institution_'.lcfirst($level) ], [
                    'Institution'.$level.'.'.'institution_id = ' . $this->aliasField('id'),
                    'Institution'.$level.'.'.'academic_period_id = AcademicPeriods.id'
                ])
                ->LeftJoin(['InfrastructureTypes' => $type.'_types'], [
                    'InfrastructureTypes.id = ' . 'Institution'.$level.'.'.'room_type_id',
                ])
                ->LeftJoin([ 'Institution'.$InstitutionFloors => 'institution_'.lcfirst($InstitutionFloors) ], [
                    'Institution'.$InstitutionFloors.'.'.'id = ' . 'Institution'.$level.'.'.'institution_floor_id',
                    'Institution'.$InstitutionFloors.'.'.'institution_id = ' . $this->aliasField('id'),
                    'Institution'.$InstitutionFloors.'.'.'academic_period_id = ' .  'Institution'.$level.'.'.'academic_period_id',
                ])
                ->LeftJoin([ 'Institution'.$InstitutionBuildings => 'institution_'.lcfirst($InstitutionBuildings) ], [
                    'Institution'.$InstitutionBuildings.'.'.'id = ' . 'Institution'.$InstitutionFloors.'.'.'institution_building_id',
                    'Institution'.$InstitutionBuildings.'.'.'institution_id = ' . $this->aliasField('id'),
                    'Institution'.$InstitutionBuildings.'.'.'academic_period_id = ' . 'Institution'.$InstitutionFloors.'.'.'academic_period_id',
                ])
                ->LeftJoin([$infrastructureCondition->alias() => $infrastructureCondition->table()], ['Institution'.$level.'.'.'infrastructure_condition_id = ' . $infrastructureCondition->aliasField('id'),
                ])
                ->LeftJoin([$infrastructureStatus->alias() => $infrastructureStatus->table()], [
                    'Institution'.$level.'.'.$type.'_status_id = ' . $infrastructureStatus->aliasField('id'),
                ])
                            //POCOR-5698 two new columns added here
                            //status
                ->LeftJoin([$infrastructureOwnerships->alias() => $infrastructureOwnerships->table()], [
                            'Institution'.$level.'.'.$type.'_status_id = ' . $infrastructureOwnerships->aliasField('id'),
                ])
                ->join([
                    'shift_info' => [
                        'type' => 'left',
                        'table' => '( SELECT institution_shifts.institution_id, institution_shifts.academic_period_id,GROUP_CONCAT(shift_options.name) shift_options_name FROM institution_shifts INNER JOIN shift_options ON shift_options.id = institution_shifts.shift_option_id GROUP BY institution_shifts.academic_period_id,institution_shifts.institution_id)',
                        'conditions' => [
                           'shift_info.academic_period_id = AcademicPeriods.id',
                           'shift_info.institution_id = ' . $this->aliasField('id'),
                        ]
                    ],
                ])
                ->where([
                    $this->aliasField('id') => $institutionId,
                    'AcademicPeriods.id' => $academicPeriodId,

                ]);
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
							'field_type' => $InfrastructureCustomFields->aliasfield('field_type'),
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
				$optVal = [];
				if(!empty($customFieldData)) {
					foreach($customFieldData as $data) {
						if(!empty($data->text_value)) {
							$row[$data->custom_field_id] = $data->text_value;
						} 
						if(!empty($data->number_value) && $data->field_type == 'CHECKBOX') {
							/*POCOR-6376 starts*/
							$infrastructureCustomFieldOptions = TableRegistry::get('infrastructure_custom_field_options');
							$infrastructureCustomFields = TableRegistry::get('infrastructure_custom_fields');
							$fieldValue = $infrastructureCustomFieldOptions->find()
											->select([$infrastructureCustomFieldOptions->aliasField('name')])
											->innerJoin([$infrastructureCustomFields->alias() => $infrastructureCustomFields->table()],[
									            $infrastructureCustomFields->aliasField('id').' = ' . $infrastructureCustomFieldOptions->aliasField('infrastructure_custom_field_id')
									        ])
									        ->innerJoin(['CustomFieldValues' => lcfirst($type).'_custom_field_values' ], [
												'CustomFieldValues.infrastructure_custom_field_id = ' . $infrastructureCustomFieldOptions->aliasField('infrastructure_custom_field_id'),
												'CustomFieldValues.institution_'.lcfirst($type).'_id  = ' . $row['level_id'],
												'CustomFieldValues.number_value  = ' . $infrastructureCustomFieldOptions->aliasField('id')
											])
											->where([
												$infrastructureCustomFields->alias('field_type') => 'CHECKBOX',
												'CustomFieldValues.institution_'.lcfirst($type).'_id  = ' . $row['level_id']])
											->group([$infrastructureCustomFieldOptions->aliasField('name')])
											->toArray();
							
							if (!empty($fieldValue)) {
								foreach ($fieldValue as $numValue) {
									$optVal[] = $numValue->name;
								}
							}
							$str = implode(',', $optVal);
							$row[$data->custom_field_id] = $str;
							unset($optVal);
						} 
						if (!empty($data->number_value) && ($data->field_type != 'CHECKBOX')) {
                            //START :comment this code becuase its affect POCOR-6650
							/*$optvalue = TableRegistry::get('infrastructure_custom_field_options');
							$fieldVal = $optvalue->get($data->number_value);
							if (!empty($fieldVal)) {
								$opt = $fieldVal->name;
							} else {
								$opt = '';
							}
							$row[$data->custom_field_id] = $opt;*///END :
                            $row[$data->custom_field_id] = $data->number_value;
						}
						/*POCOR-6376 ends*/
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