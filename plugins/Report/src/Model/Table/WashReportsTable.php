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
        
        if($washType == 'Waste' || $washType == 'Sewage'){
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
        
        if($washType == 'Sanitation'){
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
                    'infrastructure_wash_id' => 'InfrastructureWashSanitations.id'
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

        //Start POCOR-6732
        if($washType == 'All'){
            $query
                ->select([
                    $this->aliasField('id'),
                    $this->aliasField('code'),
                    $this->aliasField('name'),
                    'area_code' => 'Areas.code',
                    'area_name' => 'Areas.name',
                    'academic_period' => 'AcademicPeriods.name',
                    //Water
                    'infrastructure_wash_type_water' => 'InfrastructureWashWaterTypes.name',
                    'infrastructure_wash_functionality_water' => 'InfrastructureWashWaterFunctionalities.name',
                    'infrastructure_wash_accessibility_water' => 'InfrastructureWashWaterAccessibilities.name',
                    'infrastructure_wash_quantity' => 'InfrastructureWashWaterQuantities.name',
                    'infrastructure_wash_quality_water' => 'InfrastructureWashWaterQualities.name',
                    'infrastructure_wash_proximity' => 'InfrastructureWashWaterProximities.name',
                    //Waste
                    'infrastructure_wash_type_waste' => 'InfrastructureWashWasteTypes.name',
                    'infrastructure_wash_functionality_waste' => 'InfrastructureWashWasteFunctionalities.name',
                    //Sewage
                    'infrastructure_wash_type_sewage' => 'InfrastructureWashSewageTypes.name',
                    'infrastructure_wash_functionality_sewage' => 'InfrastructureWashSewageFunctionalities.name',
                    //Sanitation
                    'infrastructure_wash_type_sanitation' => 'InfrastructureWashSanitationTypes.name',
                    'infrastructure_wash_uses' => 'InfrastructureWashSanitationUses.name',
                    'infrastructure_wash_accessibility_sanitation' => 'InfrastructureWashSanitationAccessibilities.name',                    
                    'infrastructure_wash_quality_sanitation' => 'InfrastructureWashSanitationQualities.name',
                    'infrastructure_wash_id_sanitation' => 'InfrastructureWashSanitations.id',
                    //Hygiene
                    'infrastructure_wash_type_hygiene' => 'InfrastructureWashHygieneTypes.name',
                    'infrastructure_wash_hygiene_education' => 'InfrastructureWashHygieneEducations.name',
                    'infrastructure_wash_accessibility_hygiene' => 'InfrastructureWashHygieneSoapashAccessibilities.name',                    
                    'infrastructure_wash_id' => 'InfrastructureWashHygienes.id'
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
                )
                ->leftJoin(
                    ['InfrastructureWashWastes' => 'infrastructure_wash_wastes'],
                    [
                        'InfrastructureWashWastes.institution_id = ' . $this->aliasField('id'),
                        'InfrastructureWashWastes.academic_period_id = ' . $academicPeriodId
                    ]
                )->leftJoin(
                    ['InfrastructureWashWasteTypes' => 'infrastructure_wash_waste_types'],
                    [
                        'InfrastructureWashWasteTypes.id = InfrastructureWashWastes.infrastructure_wash_waste_type_id'
                    ]
                )->leftJoin(
                    ['InfrastructureWashWasteFunctionalities' => 'infrastructure_wash_waste_functionalities'],
                    [
                        'InfrastructureWashWasteFunctionalities.id = InfrastructureWashWastes.infrastructure_wash_waste_functionality_id'
                    ]
                )
                ->leftJoin(
                    ['InfrastructureWashSewages' => 'infrastructure_wash_sewages'],
                    [
                        'InfrastructureWashSewages.institution_id = ' . $this->aliasField('id'),
                        'InfrastructureWashSewages.academic_period_id = ' . $academicPeriodId
                    ]
                )->leftJoin(
                    ['InfrastructureWashSewageTypes' => 'infrastructure_wash_sewage_types'],
                    [
                        'InfrastructureWashSewageTypes.id = InfrastructureWashSewages.infrastructure_wash_sewage_type_id'
                    ]
                )->leftJoin(
                    ['InfrastructureWashSewageFunctionalities' => 'infrastructure_wash_sewage_functionalities'],
                    [
                        'InfrastructureWashSewageFunctionalities.id = InfrastructureWashSewages.infrastructure_wash_sewage_functionality_id'
                    ]
                )->leftJoin(
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
        //End POCOR-6732
        
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
        
        
        if(!array($washType, ['All'])){    //start POCOR-6732
        
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
         }
         //End POCOR-6732
        
        if(!array($washType, ['Water', 'Waste', 'Sewage'])){
            $extraFields[] = [
                'key' => 'InfrastructureWash'.$washType.'Functionalities.name',
                'field' => 'infrastructure_wash_functionality',
                'type' => 'string',
                'label' => __('Functionallity')
            ];
        }
        
        if($washType == 'Sanitation'){
           $extraFields[] = [
                'key' => 'InfrastructureWashSanitationUses.name',
                'field' => 'infrastructure_wash_uses',
                'type' => 'string',
                'label' => __('Use')
            ]; 
        }
        
        if($washType == 'Hygiene'){
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
        }
        
        if($washType == 'Water'){
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
        }
        
        if(in_array($washType, ['Sanitation', 'Hygiene'])){
            $extraFields[] = [
                'key' => 'male'.$washType.'Functional',
                'field' => 'male'.$washType.'Functional',
                'type' => 'string',
                'label' => __('Male (Functional)')
            ];
            
            $extraFields[] = [
                'key' => 'male'.$washType.'NonFunctional',
                'field' => 'male'.$washType.'NonFunctional',
                'type' => 'string',
                'label' => __('Male (Non-Functional)')
            ];
            
            $extraFields[] = [
                'key' => 'female'.$washType.'Functional',
                'field' => 'female'.$washType.'Functional',
                'type' => 'string',
                'label' => __('Female (Functional)')
            ];
            
            $extraFields[] = [
                'key' => 'female'.$washType.'NonFunctional',
                'field' => 'female'.$washType.'NonFunctional',
                'type' => 'string',
                'label' => __('Female (Non-Functional)')
            ];
            
            $extraFields[] = [
                'key' => 'mixed'.$washType.'Functional',
                'field' => 'mixed'.$washType.'Functional',
                'type' => 'string',
                'label' => __('Mixed (Functional)')
            ];
            
            $extraFields[] = [
                'key' => 'mixed'.$washType.'NonFunctional',
                'field' => 'mixed'.$washType.'NonFunctional',
                'type' => 'string',
                'label' => __('Mixed (Non-Functional)')
            ];
        }
        
        if(in_array($washType, ['Water', 'Sanitation'])){
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

        //start POCOR-6732

        if(in_array($washType, ['All'])){
            //Water
            $extraFields[] = [
                'key' => 'InfrastructureWashWaterTypes.name',
                'field' => 'infrastructure_wash_type_water',
                'type' => 'string',
                'label' => __('Water Type')
            ];

            $extraFields[] = [
                'key' => 'InfrastructureWashWaterFunctionalities.name',
                'field' => 'infrastructure_wash_functionality_water',
                'type' => 'string',
                'label' => __('Water Functionallity')
            ];

            $extraFields[] = [
                'key' => 'InfrastructureWashWaterAccessibilities.name',
                'field' => 'infrastructure_wash_accessibility_water',
                'type' => 'string',
                'label' => __('Water Accessibility')
            ];

            $extraFields[] = [
                'key' => 'InfrastructureWashWaterQuantities.name',
                'field' => 'infrastructure_wash_quantity',
                'type' => 'string',
                'label' => __('Water Quantity')
            ];

            $extraFields[] = [
                'key' => 'InfrastructureWashWaterQualities.name',
                'field' => 'infrastructure_wash_quality_water',
                'type' => 'string',
                'label' => __('Water Quality')
            ];

            $extraFields[] = [
                'key' => 'InfrastructureWashWaterProximities.name',
                'field' => 'infrastructure_wash_proximity',
                'type' => 'string',
                'label' => __('Proximity')
            ];

            //Waste

            $extraFields[] = [
            'key' => 'InfrastructureWashWasteTypes.name',
            'field' => 'infrastructure_wash_type_waste',
            'type' => 'string',
            'label' => __('Waste Type')
            ];

            $extraFields[] = [
                'key' => 'InfrastructureWashWasteFunctionalities.name',
                'field' => 'infrastructure_wash_functionality_waste',
                'type' => 'string',
                'label' => __('Waste Functionallity')
            ];

            //Sewage

            $extraFields[] = [
            'key' => 'InfrastructureWashSewageTypes.name',
            'field' => 'infrastructure_wash_type_sewage',
            'type' => 'string',
            'label' => __('Sewage Type')
            ];

            $extraFields[] = [
                'key' => 'InfrastructureWashSewageFunctionalities.name',
                'field' => 'infrastructure_wash_functionality_sewage',
                'type' => 'string',
                'label' => __('Sewage Functionallity')
            ];

            //Sanitation

            $extraFields[] = [
                'key' => 'InfrastructureWashSanitationTypes.name',
                'field' => 'infrastructure_wash_type_sanitation',
                'type' => 'string',
                'label' => __('Sanitation Type')
            ];

            $extraFields[] = [
                'key' => 'InfrastructureWashSanitationUses.name',
                'field' => 'infrastructure_wash_uses',
                'type' => 'string',
                'label' => __('Use')
            ];

            $extraFields[] = [
                'key' => 'InfrastructureWashSanitationAccessibilities.name',
                'field' => 'infrastructure_wash_accessibility_sanitation',
                'type' => 'string',
                'label' => __('Sanitation Accessibility')
            ];

            $extraFields[] = [
                'key' => 'InfrastructureWashSanitationQualities.name',
                'field' => 'infrastructure_wash_quality_sanitation',
                'type' => 'string',
                'label' => __('Sanitation Quality')
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

            //Hygiene

            $extraFields[] = [
                'key' => 'InfrastructureWashHygieneTypes.name',
                'field' => 'infrastructure_wash_type_hygiene',
                'type' => 'string',
                'label' => __('Hygiene Type')
            ];

            $extraFields[] = [
                'key' => 'InfrastructureWashHygieneSoapashAccessibilities.name',
                'field' => 'infrastructure_wash_accessibility_hygiene',
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
        //End POCOR-6732
        
        $newFields = array_merge($extraFields);
        $fields->exchangeArray($newFields);
    }
}
