<?php
namespace Report\Model\Table;

use ArrayObject;

use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;

use App\Model\Table\AppTable;

class WashReportsTable extends AppTable
{
    const MALE = 1;
    const FEMALE = 2;
    const MIXED = 3;
    const FUNCTIONAL = 1;
    const NONFUNCTIONAL = 0;

    private $infrastructureTabsData = [0 => "Water", 1 => "Sanitation", 2 => "Hygiene", 3 => "Waste", 4 => "Sewage"];
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

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $sheetData = $settings['sheet']['sheetData'];
        $infrastructureType = $sheetData['infrastructure_tabs_type'];
        $cloneFields = $fields->getArrayCopy();
        $requestData = json_decode($settings['process']['params']);
        $washType = $requestData->wash_type;

        $newFields = [];

        $extraFields[] = [
            'key' => 'code',
            'field' => 'code',
            'type' => 'string',
            'label' => __('Code')
        ];
        
        $extraFields[] = [
            'key' => 'name',
            'field' => 'name',
            'type' => 'string',
            'label' => __('Name')
        ];

        //add columns  POCOR-5865 starts
        $extraFields[] = [
            'key' => 'region_code',
            'field' => 'region_code',
            'type' => 'string',
            'label' => __('Region Code')
        ];

        //start POCOR-6732
        
        $AreaLevelTbl = TableRegistry::get('area_levels');
        $AreaLevelArr = $AreaLevelTbl->find()->select(['id','name'])->order(['id'=>'DESC'])->limit(2)->hydrate(false)->toArray();

        $extraFields[] = [
            'key' => 'region_name',
            'field' => 'region_name',
            'type' => 'string',
            'label' => __($AreaLevelArr[1]['name'])
        ];

        $extraFields[] = [
            'key' => 'area_name',
            'field' => 'area_name',
            'type' => 'string',
            'label' => __($AreaLevelArr[0]['name'])
        ];

        //End POCOR-6732

        //add columns  POCOR-5865 ends
        //update label  POCOR-5865 starts
        $extraFields[] = [
            'key' => 'area_level',
            'field' => 'area_level',
            'type' => 'string',
            'label' => __('District Code')
        ];
        
        //update label POCOR-5865 ends
        $extraFields[] = [
            'key' => 'AcademicPeriods.name',
            'field' => 'academic_period',
            'type' => 'string',
            'label' => __('Academic Period')
        ];

        if(in_array($washType, ['Water', 'Waste', 'Sewage','Sanitation', 'Hygiene'])){

        $extraFields[] = [
                'key' => 'wash'.$washType,
                'field' => 'wash'.$washType,
                'type' => 'string',
                'label' => __('Wash')
            ]; 
        }           

        $extraFields[] = [
            'key' => 'InfrastructureWashWaterTypes.name',
            'field' => 'infrastructure_wash_type',
            'type' => 'string',
            'label' => __('Type')
        ];

        if($washType == 'All'){

            if ($infrastructureType == 'Water')
            {
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
                'label' => __('Quality')
                ];

                $extraFields[] = [
                    'key' => 'InfrastructureWashWaterAccessibilities.name',
                    'field' => 'infrastructure_wash_accessibility',
                    'type' => 'string',
                    'label' => __('Accessibility')
                ];
            }
            if ($infrastructureType == 'Sanitation')
            {
                $extraFields[] = [
                    'key' => 'InfrastructureWashSanitationUses.name',
                    'field' => 'infrastructure_wash_uses',
                    'type' => 'string',
                    'label' => __('Use')
                ];

                $extraFields[] = [
                'key' => 'maleSanitationFunctional',
                'field' => 'maleSanitationFunctional',
                'type' => 'string',
                'label' => __('Male (Functional)')
                ];
                
                $extraFields[] = [
                    'key' => 'maleSanitationNonFunctional',
                    'field' => 'maleSanitationNonFunctional',
                    'type' => 'string',
                    'label' => __('Male (Non-Functional)')
                ];
                
                $extraFields[] = [
                    'key' => 'femaleSanitationFunctional',
                    'field' => 'femaleSanitationFunctional',
                    'type' => 'string',
                    'label' => __('Female (Functional)')
                ];
                
                $extraFields[] = [
                    'key' => 'femaleSanitationNonFunctional',
                    'field' => 'femaleSanitationNonFunctional',
                    'type' => 'string',
                    'label' => __('Female (Non-Functional)')
                ];
                
                $extraFields[] = [
                    'key' => 'mixedSanitationFunctional',
                    'field' => 'mixedSanitationFunctional',
                    'type' => 'string',
                    'label' => __('Mixed (Functional)')
                ];
                
                $extraFields[] = [
                    'key' => 'mixedSanitationNonFunctional',
                    'field' => 'mixedSanitationNonFunctional',
                    'type' => 'string',
                    'label' => __('Mixed (Non-Functional)')
                ];

                $extraFields[] = [
                'key' => 'InfrastructureWashWaterQualities.name',
                'field' => 'infrastructure_wash_quality',
                'type' => 'string',
                'label' => __('Quality')
                ];

                $extraFields[] = [
                    'key' => 'InfrastructureWashWaterAccessibilities.name',
                    'field' => 'infrastructure_wash_accessibility',
                    'type' => 'string',
                    'label' => __('Accessibility')
                ]; 
            }
            if ($infrastructureType == 'Hygiene')
            {
                $extraFields[] = [
                    'key' => 'InfrastructureWashHygieneSoapashAccessibilities.name',
                    'field' => 'infrastructure_wash_accessibility',
                    'type' => 'string',
                    'label' => __('Soap/Ash Availability')
                ]; 
               
                $extraFields[] = [
                    'key' => 'InfrastructureWashHygieneEducations.name',
                    'field' => 'infrastructure_wash_hygiene_education',
                    'type' => 'string',
                    'label' => __('Hygiene Education')
                ];

                $extraFields[] = [
                'key' => 'maleHygieneFunctional',
                'field' => 'maleHygieneFunctional',
                'type' => 'string',
                'label' => __('Male (Functional)')
                ];
                
                $extraFields[] = [
                    'key' => 'maleHygieneNonFunctional',
                    'field' => 'maleHygieneNonFunctional',
                    'type' => 'string',
                    'label' => __('Male (Non-Functional)')
                ];
                
                $extraFields[] = [
                    'key' => 'femaleHygieneFunctional',
                    'field' => 'femaleHygieneFunctional',
                    'type' => 'string',
                    'label' => __('Female (Functional)')
                ];
                
                $extraFields[] = [
                    'key' => 'femaleHygieneNonFunctional',
                    'field' => 'femaleHygieneNonFunctional',
                    'type' => 'string',
                    'label' => __('Female (Non-Functional)')
                ];
                
                $extraFields[] = [
                    'key' => 'mixedHygieneFunctional',
                    'field' => 'mixedHygieneFunctional',
                    'type' => 'string',
                    'label' => __('Mixed (Functional)')
                ];
                
                $extraFields[] = [
                    'key' => 'mixedHygieneNonFunctional',
                    'field' => 'mixedHygieneNonFunctional',
                    'type' => 'string',
                    'label' => __('Mixed (Non-Functional)')
                ]; 

            }
            if ($infrastructureType == 'Waste')
            {
                $extraFields[] = [
                    'key' => 'InfrastructureWashWasteFunctionalities.name',
                    'field' => 'infrastructure_wash_functionality',
                    'type' => 'string',
                    'label' => __('Functionallity')
                ];
            }
            if ($infrastructureType == 'Sewage')
            {
                $extraFields[] = [
                    'key' => 'InfrastructureWashSewageFunctionalities.name',
                    'field' => 'infrastructure_wash_functionality',
                    'type' => 'string',
                    'label' => __('Functionallity')
                ];
            }
        }

        else if ($washType == 'Water'){
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
                'label' => __('Quality')
                ];

                $extraFields[] = [
                    'key' => 'InfrastructureWashWaterAccessibilities.name',
                    'field' => 'infrastructure_wash_accessibility',
                    'type' => 'string',
                    'label' => __('Accessibility')
                ];
            }
            else if ($washType == 'Hygiene'){
                
                $extraFields[] = [
                    'key' => 'InfrastructureWashHygieneSoapashAccessibilities.name',
                    'field' => 'infrastructure_wash_accessibility',
                    'type' => 'string',
                    'label' => __('Soap/Ash Availability')
                ]; 
               
                $extraFields[] = [
                    'key' => 'InfrastructureWashHygieneEducations.name',
                    'field' => 'infrastructure_wash_hygiene_education',
                    'type' => 'string',
                    'label' => __('Hygiene Education')
                ];

                $extraFields[] = [
                'key' => 'maleHygieneFunctional',
                'field' => 'maleHygieneFunctional',
                'type' => 'string',
                'label' => __('Male (Functional)')
                ];
                
                $extraFields[] = [
                    'key' => 'maleHygieneNonFunctional',
                    'field' => 'maleHygieneNonFunctional',
                    'type' => 'string',
                    'label' => __('Male (Non-Functional)')
                ];
                
                $extraFields[] = [
                    'key' => 'femaleHygieneFunctional',
                    'field' => 'femaleHygieneFunctional',
                    'type' => 'string',
                    'label' => __('Female (Functional)')
                ];
                
                $extraFields[] = [
                    'key' => 'femaleHygieneNonFunctional',
                    'field' => 'femaleHygieneNonFunctional',
                    'type' => 'string',
                    'label' => __('Female (Non-Functional)')
                ];
                
                $extraFields[] = [
                    'key' => 'mixedHygieneFunctional',
                    'field' => 'mixedHygieneFunctional',
                    'type' => 'string',
                    'label' => __('Mixed (Functional)')
                ];
                
                $extraFields[] = [
                    'key' => 'mixedHygieneNonFunctional',
                    'field' => 'mixedHygieneNonFunctional',
                    'type' => 'string',
                    'label' => __('Mixed (Non-Functional)')
                ]; 
            }

            else if ($washType == 'Sanitation'){
                $extraFields[] = [
                    'key' => 'InfrastructureWashSanitationUses.name',
                    'field' => 'infrastructure_wash_uses',
                    'type' => 'string',
                    'label' => __('Use')
                ];

                $extraFields[] = [
                'key' => 'maleSanitationFunctional',
                'field' => 'maleSanitationFunctional',
                'type' => 'string',
                'label' => __('Male (Functional)')
                ];
                
                $extraFields[] = [
                    'key' => 'maleSanitationNonFunctional',
                    'field' => 'maleSanitationNonFunctional',
                    'type' => 'string',
                    'label' => __('Male (Non-Functional)')
                ];
                
                $extraFields[] = [
                    'key' => 'femaleSanitationFunctional',
                    'field' => 'femaleSanitationFunctional',
                    'type' => 'string',
                    'label' => __('Female (Functional)')
                ];
                
                $extraFields[] = [
                    'key' => 'femaleSanitationNonFunctional',
                    'field' => 'femaleSanitationNonFunctional',
                    'type' => 'string',
                    'label' => __('Female (Non-Functional)')
                ];
                
                $extraFields[] = [
                    'key' => 'mixedSanitationFunctional',
                    'field' => 'mixedSanitationFunctional',
                    'type' => 'string',
                    'label' => __('Mixed (Functional)')
                ];
                
                $extraFields[] = [
                    'key' => 'mixedSanitationNonFunctional',
                    'field' => 'mixedSanitationNonFunctional',
                    'type' => 'string',
                    'label' => __('Mixed (Non-Functional)')
                ];

                $extraFields[] = [
                'key' => 'InfrastructureWashWaterQualities.name',
                'field' => 'infrastructure_wash_quality',
                'type' => 'string',
                'label' => __('Quality')
                ];

                $extraFields[] = [
                    'key' => 'InfrastructureWashWaterAccessibilities.name',
                    'field' => 'infrastructure_wash_accessibility',
                    'type' => 'string',
                    'label' => __('Accessibility')
                ];
            }

        
        $fields->exchangeArray($extraFields);
    }

    public function onExcelBeforeStart(Event $event, ArrayObject $settings, ArrayObject $sheets)
    {
        unset($sheets[0]);
        $infrastructureTabsData = $this->infrastructureTabsData;
        $InstitutionStudents = TableRegistry::get('User.InstitutionStudents');
        $institutionStudentId = $settings['id'];
        $requestData = json_decode($settings['process']['params']);
        $washType = $requestData->wash_type;

         if($washType == 'All'){

            foreach ($infrastructureTabsData as $key => $val)
            {
                $tabsName = $val;
                $sheets[] = ['sheetData' => ['infrastructure_tabs_type' => $val], 'name' => $tabsName, 'table' => $this, 'query' => $this->find()
                /* ->leftJoin([$InstitutionStudents->alias() => $InstitutionStudents->table()],[
                            $this->aliasField('id = ').$InstitutionStudents->aliasField('student_id')
                        ])
                        ->where([
                            $InstitutionStudents->aliasField('student_id = ').$institutionStudentId,
                        ]) */
                , 'orientation' => 'landscape'];
            }
        }

    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {

        $requestData = json_decode($settings['process']['params']);
        
        $academicPeriodId = $requestData->academic_period_id;
        $institutionId = $requestData->institution_id;
        $washType = $requestData->wash_type;
        $areaId = $requestData->area_education_id;
        $conditions = [];
        $SanitationQuantitiesTable = TableRegistry::get('Institution.InfrastructureWashSanitationQuantities');
        if (!empty($academicPeriodId)) {
            $conditions['AcademicPeriods.id'] = $academicPeriodId;
        }
        if (!empty($institutionId) && $institutionId > 0) {
            $conditions[$this->aliasField('id')] = $institutionId;
        }
        if (!empty($areaId) && $areaId > 0) {
            $conditions[$this->aliasField('area_id')] = $areaId;
        }

        $sheetData = $settings['sheet']['sheetData'];
        $infrastructureType = $sheetData['infrastructure_tabs_type'];

        
        if ($infrastructureType == 'Water')
        {
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
                )
                ->where($conditions);

        }
        if ($infrastructureType == 'Sanitation')
        {
            $query
                ->select([
                    $this->aliasField('id'),
                    $this->aliasField('code'),
                    $this->aliasField('name'),
                    'area_code' => 'Areas.code',
                    'area_name' => 'Areas.name',
                    'academic_period' => 'AcademicPeriods.name',
                    'infrastructure_wash_type' => 'InfrastructureWashSanitationTypes.name',
                    'infrastructure_wash_uses' => 'InfrastructureWashSanitationUses.name',
                    'infrastructure_wash_accessibility' => 'InfrastructureWashSanitationAccessibilities.name',                    
                    'infrastructure_wash_quality' => 'InfrastructureWashSanitationQualities.name',
                    'infrastructure_wash_id_sanitation' => 'InfrastructureWashSanitations.id'
                ])
                ->contain(['Areas', 'AreaAdministratives'])
                ->leftJoin(
                    ['InfrastructureWashSanitations' => 'infrastructure_wash_sanitations'],
                    [
                        'InfrastructureWashSanitations.institution_id = ' . $this->aliasField('id'),
                        'InfrastructureWashSanitations.academic_period_id = ' . $academicPeriodId
                    ]
                )->leftJoin(
                    ['InfrastructureWashSanitationTypes' => 'infrastructure_wash_sanitation_types'],
                    [
                        'InfrastructureWashSanitationTypes.id = InfrastructureWashSanitations.infrastructure_wash_sanitation_type_id'
                    ]
                )->leftJoin(
                    ['InfrastructureWashSanitationUses' => 'infrastructure_wash_sanitation_uses'],
                    [
                        'InfrastructureWashSanitationUses.id = InfrastructureWashSanitations.infrastructure_wash_sanitation_use_id'
                    ]
                )->leftJoin(
                    ['InfrastructureWashSanitationAccessibilities' => 'infrastructure_wash_sanitation_accessibilities'],
                    [
                        'InfrastructureWashSanitationAccessibilities.id = InfrastructureWashSanitations.infrastructure_wash_sanitation_accessibility_id'
                    ]
                )->leftJoin(
                    ['InfrastructureWashSanitationQualities' => 'infrastructure_wash_sanitation_qualities'],
                    [
                        'InfrastructureWashSanitationQualities.id = InfrastructureWashSanitations.infrastructure_wash_sanitation_quality_id'
                    ]
                )->leftJoin(
                    ['AcademicPeriods' => 'academic_periods'],
                    [
                        'AcademicPeriods.id = ' . $academicPeriodId
                    ]
                )
                ->where($conditions);
        }
        if ($infrastructureType == 'Hygiene')
        {
            $query
                ->select([
                    $this->aliasField('id'),
                    $this->aliasField('code'),
                    $this->aliasField('name'),
                    'area_code' => 'Areas.code',
                    'area_name' => 'Areas.name',
                    'academic_period' => 'AcademicPeriods.name',
                    'infrastructure_wash_type' => 'InfrastructureWashHygieneTypes.name',
                    'infrastructure_wash_hygiene_education' => 'InfrastructureWashHygieneEducations.name',
                    'infrastructure_wash_accessibility' => 'InfrastructureWashHygieneSoapashAccessibilities.name',                    
                    'infrastructure_wash_id' => 'InfrastructureWashHygienes.id'
                ])
                ->contain(['Areas', 'AreaAdministratives'])
                ->leftJoin(
                    ['InfrastructureWashHygienes' => 'infrastructure_wash_hygienes'],
                    [
                        'InfrastructureWashHygienes.institution_id = ' . $this->aliasField('id'),
                        'InfrastructureWashHygienes.academic_period_id = ' . $academicPeriodId
                    ]
                )->leftJoin(
                    ['InfrastructureWashHygieneTypes' => 'infrastructure_wash_hygiene_types'],
                    [
                        'InfrastructureWashHygieneTypes.id = InfrastructureWashHygienes.infrastructure_wash_hygiene_type_id'
                    ]
                )->leftJoin(
                    ['InfrastructureWashHygieneEducations' => 'infrastructure_wash_hygiene_educations'],
                    [
                        'InfrastructureWashHygieneEducations.id = InfrastructureWashHygienes.infrastructure_wash_hygiene_education_id'
                    ]
                )->leftJoin(
                    ['InfrastructureWashHygieneSoapashAccessibilities' => 'infrastructure_wash_hygiene_soapash_availabilities'],
                    [
                        'InfrastructureWashHygieneSoapashAccessibilities.id = InfrastructureWashHygienes.infrastructure_wash_hygiene_soapash_availability_id'
                    ]
                )->leftJoin(
                    ['AcademicPeriods' => 'academic_periods'],
                    [
                        'AcademicPeriods.id = ' . $academicPeriodId
                    ]
                )
                ->where($conditions);
        }
        if ($infrastructureType == 'Waste')
        {
            $washType = 'Waste';
            $query
                ->select([
                    $this->aliasField('id'),
                    $this->aliasField('code'),
                    $this->aliasField('name'),
                    'area_code' => 'Areas.code',
                    'area_name' => 'Areas.name',
                    'academic_period' => 'AcademicPeriods.name',
                    'infrastructure_wash_type' => 'InfrastructureWash'.$washType.'Types.name',
                    'infrastructure_wash_functionality' => 'InfrastructureWash'.$washType.'Functionalities.name'                    
                ])
                ->contain(['Areas', 'AreaAdministratives'])
                ->leftJoin(
                    ['InfrastructureWash'.$washType.'s' => 'infrastructure_wash_'.strtolower($washType).'s'],
                    [
                        'InfrastructureWash'.$washType.'s.institution_id = ' . $this->aliasField('id'),
                        'InfrastructureWash'.$washType.'s.academic_period_id = ' . $academicPeriodId
                    ]
                )->leftJoin(
                    ['InfrastructureWash'.$washType.'Types' => 'infrastructure_wash_'.strtolower($washType).'_types'],
                    [
                        'InfrastructureWash'.$washType.'Types.id = InfrastructureWash'.$washType.'s.infrastructure_wash_'.strtolower($washType).'_type_id'
                    ]
                )->leftJoin(
                    ['InfrastructureWash'.$washType.'Functionalities' => 'infrastructure_wash_'.strtolower($washType).'_functionalities'],
                    [
                        'InfrastructureWash'.$washType.'Functionalities.id = InfrastructureWash'.$washType.'s.infrastructure_wash_'.strtolower($washType).'_functionality_id'
                    ]
                )->leftJoin(
                    ['AcademicPeriods' => 'academic_periods'],
                    [
                        'AcademicPeriods.id = ' . $academicPeriodId
                    ]
                )
                ->where($conditions);
        }
        if ($infrastructureType == 'Sewage')
        {
            $washType = 'Sewage';
            $query
                ->select([
                    $this->aliasField('id'),
                    $this->aliasField('code'),
                    $this->aliasField('name'),
                    'area_code' => 'Areas.code',
                    'area_name' => 'Areas.name',
                    'academic_period' => 'AcademicPeriods.name',
                    'infrastructure_wash_type' => 'InfrastructureWash'.$washType.'Types.name',
                    'infrastructure_wash_functionality' => 'InfrastructureWash'.$washType.'Functionalities.name'                    
                ])
                ->contain(['Areas', 'AreaAdministratives'])
                ->leftJoin(
                    ['InfrastructureWash'.$washType.'s' => 'infrastructure_wash_'.strtolower($washType).'s'],
                    [
                        'InfrastructureWash'.$washType.'s.institution_id = ' . $this->aliasField('id'),
                        'InfrastructureWash'.$washType.'s.academic_period_id = ' . $academicPeriodId
                    ]
                )->leftJoin(
                    ['InfrastructureWash'.$washType.'Types' => 'infrastructure_wash_'.strtolower($washType).'_types'],
                    [
                        'InfrastructureWash'.$washType.'Types.id = InfrastructureWash'.$washType.'s.infrastructure_wash_'.strtolower($washType).'_type_id'
                    ]
                )->leftJoin(
                    ['InfrastructureWash'.$washType.'Functionalities' => 'infrastructure_wash_'.strtolower($washType).'_functionalities'],
                    [
                        'InfrastructureWash'.$washType.'Functionalities.id = InfrastructureWash'.$washType.'s.infrastructure_wash_'.strtolower($washType).'_functionality_id'
                    ]
                )->leftJoin(
                    ['AcademicPeriods' => 'academic_periods'],
                    [
                        'AcademicPeriods.id = ' . $academicPeriodId
                    ]
                )
                ->where($conditions);
        }

        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {
            return $results->map(function ($row) {
                //POCOR-5865 starts
                /*$areaLevel = '';
                $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
                $areaLevelId = $ConfigItems->value('institution_area_level_id');
                
                $AreaTable = TableRegistry::get('Area.AreaLevels');
                $value = $AreaTable->find()
                            ->where([$AreaTable->aliasField('level') => $areaLevelId])
                            ->first();
            
                if (!empty($value->name)) {
                    $areaLevel = $value->name;
                }*/

                $row['area_level'] = $row->area_code;
                //POCOR-5865 ends
                return $row;
            });
        });
        //POCOR-5865 starts
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
                                    $areas->aliasField('code'),
                                    $areas->aliasField('name'),
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
        //POCOR-5865 ends

        if($washType == 'Water'){
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
                )
                ->where($conditions);
           
        }

        if($washType == 'Hygiene'){
            $query
                ->select([
                    $this->aliasField('id'),
                    $this->aliasField('code'),
                    $this->aliasField('name'),
                    'area_code' => 'Areas.code',
                    'area_name' => 'Areas.name',
                    'academic_period' => 'AcademicPeriods.name',
                    'infrastructure_wash_type' => 'InfrastructureWashHygieneTypes.name',
                    'infrastructure_wash_hygiene_education' => 'InfrastructureWashHygieneEducations.name',
                    'infrastructure_wash_accessibility' => 'InfrastructureWashHygieneSoapashAccessibilities.name',                    
                    'infrastructure_wash_id' => 'InfrastructureWashHygienes.id'
                ])
                ->contain(['Areas', 'AreaAdministratives'])
                ->leftJoin(
                    ['InfrastructureWashHygienes' => 'infrastructure_wash_hygienes'],
                    [
                        'InfrastructureWashHygienes.institution_id = ' . $this->aliasField('id'),
                        'InfrastructureWashHygienes.academic_period_id = ' . $academicPeriodId
                    ]
                )->leftJoin(
                    ['InfrastructureWashHygieneTypes' => 'infrastructure_wash_hygiene_types'],
                    [
                        'InfrastructureWashHygieneTypes.id = InfrastructureWashHygienes.infrastructure_wash_hygiene_type_id'
                    ]
                )->leftJoin(
                    ['InfrastructureWashHygieneEducations' => 'infrastructure_wash_hygiene_educations'],
                    [
                        'InfrastructureWashHygieneEducations.id = InfrastructureWashHygienes.infrastructure_wash_hygiene_education_id'
                    ]
                )->leftJoin(
                    ['InfrastructureWashHygieneSoapashAccessibilities' => 'infrastructure_wash_hygiene_soapash_availabilities'],
                    [
                        'InfrastructureWashHygieneSoapashAccessibilities.id = InfrastructureWashHygienes.infrastructure_wash_hygiene_soapash_availability_id'
                    ]
                )->leftJoin(
                    ['AcademicPeriods' => 'academic_periods'],
                    [
                        'AcademicPeriods.id = ' . $academicPeriodId
                    ]
                )
                ->where($conditions);
        }

        if($washType == 'Hygiene'){
            $query
                ->select([
                    $this->aliasField('id'),
                    $this->aliasField('code'),
                    $this->aliasField('name'),
                    'area_code' => 'Areas.code',
                    'area_name' => 'Areas.name',
                    'academic_period' => 'AcademicPeriods.name',
                    'infrastructure_wash_type' => 'InfrastructureWashSanitationTypes.name',
                    'infrastructure_wash_uses' => 'InfrastructureWashSanitationUses.name',
                    'infrastructure_wash_accessibility' => 'InfrastructureWashSanitationAccessibilities.name',                    
                    'infrastructure_wash_quality' => 'InfrastructureWashSanitationQualities.name',
                    'infrastructure_wash_id_sanitation' => 'InfrastructureWashSanitations.id'
                ])
                ->contain(['Areas', 'AreaAdministratives'])
                ->leftJoin(
                    ['InfrastructureWashSanitations' => 'infrastructure_wash_sanitations'],
                    [
                        'InfrastructureWashSanitations.institution_id = ' . $this->aliasField('id'),
                        'InfrastructureWashSanitations.academic_period_id = ' . $academicPeriodId
                    ]
                )->leftJoin(
                    ['InfrastructureWashSanitationTypes' => 'infrastructure_wash_sanitation_types'],
                    [
                        'InfrastructureWashSanitationTypes.id = InfrastructureWashSanitations.infrastructure_wash_sanitation_type_id'
                    ]
                )->leftJoin(
                    ['InfrastructureWashSanitationUses' => 'infrastructure_wash_sanitation_uses'],
                    [
                        'InfrastructureWashSanitationUses.id = InfrastructureWashSanitations.infrastructure_wash_sanitation_use_id'
                    ]
                )->leftJoin(
                    ['InfrastructureWashSanitationAccessibilities' => 'infrastructure_wash_sanitation_accessibilities'],
                    [
                        'InfrastructureWashSanitationAccessibilities.id = InfrastructureWashSanitations.infrastructure_wash_sanitation_accessibility_id'
                    ]
                )->leftJoin(
                    ['InfrastructureWashSanitationQualities' => 'infrastructure_wash_sanitation_qualities'],
                    [
                        'InfrastructureWashSanitationQualities.id = InfrastructureWashSanitations.infrastructure_wash_sanitation_quality_id'
                    ]
                )->leftJoin(
                    ['AcademicPeriods' => 'academic_periods'],
                    [
                        'AcademicPeriods.id = ' . $academicPeriodId
                    ]
                )
                ->where($conditions);
        }

        if ($washType == 'Waste')
        {
            $washType = 'Waste';
            $query
                ->select([
                    $this->aliasField('id'),
                    $this->aliasField('code'),
                    $this->aliasField('name'),
                    'area_code' => 'Areas.code',
                    'area_name' => 'Areas.name',
                    'academic_period' => 'AcademicPeriods.name',
                    'infrastructure_wash_type' => 'InfrastructureWash'.$washType.'Types.name',
                    'infrastructure_wash_functionality' => 'InfrastructureWash'.$washType.'Functionalities.name'                    
                ])
                ->contain(['Areas', 'AreaAdministratives'])
                ->leftJoin(
                    ['InfrastructureWash'.$washType.'s' => 'infrastructure_wash_'.strtolower($washType).'s'],
                    [
                        'InfrastructureWash'.$washType.'s.institution_id = ' . $this->aliasField('id'),
                        'InfrastructureWash'.$washType.'s.academic_period_id = ' . $academicPeriodId
                    ]
                )->leftJoin(
                    ['InfrastructureWash'.$washType.'Types' => 'infrastructure_wash_'.strtolower($washType).'_types'],
                    [
                        'InfrastructureWash'.$washType.'Types.id = InfrastructureWash'.$washType.'s.infrastructure_wash_'.strtolower($washType).'_type_id'
                    ]
                )->leftJoin(
                    ['InfrastructureWash'.$washType.'Functionalities' => 'infrastructure_wash_'.strtolower($washType).'_functionalities'],
                    [
                        'InfrastructureWash'.$washType.'Functionalities.id = InfrastructureWash'.$washType.'s.infrastructure_wash_'.strtolower($washType).'_functionality_id'
                    ]
                )->leftJoin(
                    ['AcademicPeriods' => 'academic_periods'],
                    [
                        'AcademicPeriods.id = ' . $academicPeriodId
                    ]
                )
                ->where($conditions);
        }

        if ($washType == 'Sewage')
        {
            $washType = 'Sewage';
            $query
                ->select([
                    $this->aliasField('id'),
                    $this->aliasField('code'),
                    $this->aliasField('name'),
                    'area_code' => 'Areas.code',
                    'area_name' => 'Areas.name',
                    'academic_period' => 'AcademicPeriods.name',
                    'infrastructure_wash_type' => 'InfrastructureWash'.$washType.'Types.name',
                    'infrastructure_wash_functionality' => 'InfrastructureWash'.$washType.'Functionalities.name'                    
                ])
                ->contain(['Areas', 'AreaAdministratives'])
                ->leftJoin(
                    ['InfrastructureWash'.$washType.'s' => 'infrastructure_wash_'.strtolower($washType).'s'],
                    [
                        'InfrastructureWash'.$washType.'s.institution_id = ' . $this->aliasField('id'),
                        'InfrastructureWash'.$washType.'s.academic_period_id = ' . $academicPeriodId
                    ]
                )->leftJoin(
                    ['InfrastructureWash'.$washType.'Types' => 'infrastructure_wash_'.strtolower($washType).'_types'],
                    [
                        'InfrastructureWash'.$washType.'Types.id = InfrastructureWash'.$washType.'s.infrastructure_wash_'.strtolower($washType).'_type_id'
                    ]
                )->leftJoin(
                    ['InfrastructureWash'.$washType.'Functionalities' => 'infrastructure_wash_'.strtolower($washType).'_functionalities'],
                    [
                        'InfrastructureWash'.$washType.'Functionalities.id = InfrastructureWash'.$washType.'s.infrastructure_wash_'.strtolower($washType).'_functionality_id'
                    ]
                )->leftJoin(
                    ['AcademicPeriods' => 'academic_periods'],
                    [
                        'AcademicPeriods.id = ' . $academicPeriodId
                    ]
                )
                ->where($conditions);
        }
    }

    public function onExcelGetMaleSanitationFunctional(Event $event, Entity $entity)
    {
        $maleSanitationFunctional = '';
        
        if(!empty($entity->infrastructure_wash_id_sanitation)){  //POCOR-6732
            $sanitationQuantitiesTable = TableRegistry::get('Institution.InfrastructureWashSanitationQuantities');
            $sanitationQuantitiesResult = $sanitationQuantitiesTable->find()
                    ->where(['gender_id' => self::MALE, 'functional' => self::FUNCTIONAL, 
                        'infrastructure_wash_sanitation_id' => $entity->infrastructure_wash_id_sanitation])
                    ->first();     //POCOR-6732
            $maleSanitationFunctional = $sanitationQuantitiesResult->value;
        }
            
        return $maleSanitationFunctional;
    }
    
    public function onExcelGetMaleSanitationNonFunctional(Event $event, Entity $entity)
    {
        $maleSanitationNonFunctional = '';
        
        if(!empty($entity->infrastructure_wash_id_sanitation)){     //POCOR-6732
            $sanitationQuantitiesTable = TableRegistry::get('Institution.InfrastructureWashSanitationQuantities');
            $sanitationQuantitiesResult = $sanitationQuantitiesTable->find()
                    ->where(['gender_id' => self::MALE, 'functional' => self::NONFUNCTIONAL, 
                        'infrastructure_wash_sanitation_id' => $entity->infrastructure_wash_id_sanitation])
                    ->first();     //POCOR-6732
            $maleSanitationNonFunctional = $sanitationQuantitiesResult->value;
        }
            
        return $maleSanitationNonFunctional;
    }
    
    public function onExcelGetFemaleSanitationFunctional(Event $event, Entity $entity)
    {
        $femaleSanitationFunctional = '';
        
        if(!empty($entity->infrastructure_wash_id_sanitation)){      //POCOR-6732
            $sanitationQuantitiesTable = TableRegistry::get('Institution.InfrastructureWashSanitationQuantities');
            $sanitationQuantitiesResult = $sanitationQuantitiesTable->find()
                    ->where(['gender_id' => self::FEMALE, 'functional' => self::FUNCTIONAL, 
                        'infrastructure_wash_sanitation_id' => $entity->infrastructure_wash_id_sanitation])
                    ->first();      //POCOR-6732
            $femaleSanitationFunctional = $sanitationQuantitiesResult->value;
        }
            
        return $femaleSanitationFunctional;
    }
    
    public function onExcelGetFemaleSanitationNonFunctional(Event $event, Entity $entity)
    {
        $femaleSanitationNonFunctional = '';
        
        if(!empty($entity->infrastructure_wash_id_sanitation)){      //POCOR-6732
            $sanitationQuantitiesTable = TableRegistry::get('Institution.InfrastructureWashSanitationQuantities');
            $sanitationQuantitiesResult = $sanitationQuantitiesTable->find()
                    ->where(['gender_id' => self::FEMALE, 'functional' => self::NONFUNCTIONAL, 
                        'infrastructure_wash_sanitation_id' => $entity->infrastructure_wash_id_sanitation])
                    ->first();    //POCOR-6732
            $femaleSanitationNonFunctional = $sanitationQuantitiesResult->value;
        }
            
        return $femaleSanitationNonFunctional;
    }
    
    public function onExcelGetMixedSanitationFunctional(Event $event, Entity $entity)
    {
        $mixedSanitationFunctional = '';
        
        if(!empty($entity->infrastructure_wash_id_sanitation)){       //POCOR-6732
            $sanitationQuantitiesTable = TableRegistry::get('Institution.InfrastructureWashSanitationQuantities');
            $sanitationQuantitiesResult = $sanitationQuantitiesTable->find()
                    ->where(['gender_id' => self::MIXED, 'functional' => self::FUNCTIONAL, 
                        'infrastructure_wash_sanitation_id' => $entity->infrastructure_wash_id_sanitation])
                    ->first();     //POCOR-6732
            $mixedSanitationFunctional = $sanitationQuantitiesResult->value;
        }
            
        return $mixedSanitationFunctional;
    }
    
    public function onExcelGetMixedSanitationNonFunctional(Event $event, Entity $entity)
    {
        $mixedSanitationNonFunctional = '';
        
        if(!empty($entity->infrastructure_wash_id_sanitation)){      //POCOR-6732
            $sanitationQuantitiesTable = TableRegistry::get('Institution.InfrastructureWashSanitationQuantities');
            $sanitationQuantitiesResult = $sanitationQuantitiesTable->find()
                    ->where(['gender_id' => self::MIXED, 'functional' => self::NONFUNCTIONAL, 
                        'infrastructure_wash_sanitation_id' => $entity->infrastructure_wash_id_sanitation])
                    ->first();        //POCOR-6732
            $mixedSanitationNonFunctional = $sanitationQuantitiesResult->value;
        }
            
        return $mixedSanitationNonFunctional;
    }
    
    public function onExcelGetMaleHygieneFunctional(Event $event, Entity $entity)
    {
        $maleHygieneFunctional = '';
        
        if(!empty($entity->infrastructure_wash_id)){
            $hygieneQuantitiesTable = TableRegistry::get('Institution.InfrastructureWashHygieneQuantities');
            $hygieneQuantitiesResult = $hygieneQuantitiesTable->find()
                    ->where(['gender_id' => self::MALE, 'functional' => self::FUNCTIONAL, 
                        'infrastructure_wash_hygiene_id' => $entity->infrastructure_wash_id])
                    ->first();
            $maleHygieneFunctional = $hygieneQuantitiesResult->value;
        }
            
        return $maleHygieneFunctional;
    }
    
    public function onExcelGetMaleHygieneNonFunctional(Event $event, Entity $entity)
    {
        $maleHygieneNonFunctional = '';
        
        if(!empty($entity->infrastructure_wash_id)){
            $sanitationQuantitiesTable = TableRegistry::get('Institution.InfrastructureWashHygieneQuantities');
            $sanitationQuantitiesResult = $sanitationQuantitiesTable->find()
                    ->where(['gender_id' => self::MALE, 'functional' => self::NONFUNCTIONAL, 
                        'infrastructure_wash_hygiene_id' => $entity->infrastructure_wash_id])
                    ->first();
            $maleHygieneNonFunctional = $sanitationQuantitiesResult->value;
        }
            
        return $maleHygieneNonFunctional;
    }
    
    public function onExcelGetFemaleHygieneFunctional(Event $event, Entity $entity)
    {
        $femaleHygieneFunctional = '';
        
        if(!empty($entity->infrastructure_wash_id)){
            $sanitationQuantitiesTable = TableRegistry::get('Institution.InfrastructureWashHygieneQuantities');
            $sanitationQuantitiesResult = $sanitationQuantitiesTable->find()
                    ->where(['gender_id' => self::FEMALE, 'functional' => self::FUNCTIONAL, 
                        'infrastructure_wash_hygiene_id' => $entity->infrastructure_wash_id])
                    ->first();
            $femaleHygieneFunctional = $sanitationQuantitiesResult->value;
        }
            
        return $femaleHygieneFunctional;
    }
    
    public function onExcelGetFemaleHygieneNonFunctional(Event $event, Entity $entity)
    {
        $femaleHygieneNonFunctional = '';
        
        if(!empty($entity->infrastructure_wash_id)){
            $hygieneQuantitiesTable = TableRegistry::get('Institution.InfrastructureWashHygieneQuantities');
            $hygieneQuantitiesResult = $hygieneQuantitiesTable->find()
                    ->where(['gender_id' => self::FEMALE, 'functional' => self::NONFUNCTIONAL, 
                        'infrastructure_wash_hygiene_id' => $entity->infrastructure_wash_id])
                    ->first();
            $femaleHygieneNonFunctional = $hygieneQuantitiesResult->value;
        }
            
        return $femaleHygieneNonFunctional;
    }
    
    public function onExcelGetMixedHygieneFunctional(Event $event, Entity $entity)
    {
        $mixedHygieneFunctional = '';
        
        if(!empty($entity->infrastructure_wash_id)){
            $hygieneQuantitiesTable = TableRegistry::get('Institution.InfrastructureWashHygieneQuantities');
            $hygieneQuantitiesResult = $hygieneQuantitiesTable->find()
                    ->where(['gender_id' => self::MIXED, 'functional' => self::FUNCTIONAL, 
                        'infrastructure_wash_hygiene_id' => $entity->infrastructure_wash_id])
                    ->first();
            $mixedHygieneFunctional = $hygieneQuantitiesResult->value;
        }
            
        return $mixedHygieneFunctional;
    }
    
    public function onExcelGetMixedHygieneNonFunctional(Event $event, Entity $entity)
    {
        $mixedHygieneNonFunctional = '';
        
        if(!empty($entity->infrastructure_wash_id)){
            $hygieneQuantitiesTable = TableRegistry::get('Institution.InfrastructureWashHygieneQuantities');
            $hygieneQuantitiesResult = $hygieneQuantitiesTable->find()
                    ->where(['gender_id' => self::MIXED, 'functional' => self::NONFUNCTIONAL, 
                        'infrastructure_wash_hygiene_id' => $entity->infrastructure_wash_id])
                    ->first();
            $mixedHygieneNonFunctional = $hygieneQuantitiesResult->value;
        }
            
        return $mixedHygieneNonFunctional;
    }
    
    
}
