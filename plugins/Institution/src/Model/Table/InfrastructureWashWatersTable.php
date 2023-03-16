<?php
namespace Institution\Model\Table;
use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Query;
use App\Model\Table\AppTable;
use App\Model\Table\ControllerActionTable;
use Cake\ORM\TableRegistry;

class InfrastructureWashWatersTable extends ControllerActionTable
{
    private $infrastructureTabsData = [0 => "Water", 1 => "Sanitation", 2 => "Hygiene", 3 => "Waste", 4 => "Sewage"];
    public function initialize(array $config)
    {
        $this->table('infrastructure_wash_waters');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreign_key' => 'academic_period_id']);
        //$this->belongsTo('Institutions',   ['className' => 'Institution.Institutions', 'foreign_key' => 'id']);
        $this->belongsTo('InfrastructureWashWaterTypes', ['className' => 'Institution.InfrastructureWashWaterTypes', 'foreign_key' => 'infrastructure_wash_water_type_id']);
        $this->belongsTo('InfrastructureWashWaterFunctionalities', ['className' => 'Institution.InfrastructureWashWaterFunctionalities', 'foreign_key' => 'infrastructure_wash_water_functionality_id']);
        $this->belongsTo('InfrastructureWashWaterProximities', ['className' => 'Institution.InfrastructureWashWaterProximities', 'foreign_key' => 'infrastructure_wash_water_proximity_id']);
        $this->belongsTo('InfrastructureWashWaterQuantities', ['className' => 'Institution.InfrastructureWashWaterQuantities', 'foreign_key' => 'infrastructure_wash_water_quantity_id']);
        $this->belongsTo('InfrastructureWashWaterQualities', ['className' => 'Institution.InfrastructureWashWaterQualities', 'foreign_key' => 'infrastructure_wash_water_quality_id']);
        $this->belongsTo('InfrastructureWashWaterAccessibilities', ['className' => 'Institution.InfrastructureWashWaterAccessibilities', 'foreign_key' => 'infrastructure_wash_water_accessibility_id']);

        //$this->belongsTo('InfrastructureWashSanitations', ['className' => 'Institution.InfrastructureWashSanitations', 'foreign_key' => 'institution_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->toggle('search', false);

        $this->addBehavior('Excel', ['excludes' => ['academic_period_id', 'institution_id'], 'pages' => ['index'], ]);

    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $modelAlias = 'InfrastructureWashWaters';
        $userType = '';
        $this
            ->controller
            ->changeUtilitiesHeader($this, $modelAlias, $userType);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('infrastructure_wash_water_type_id');
        $this->field('infrastructure_wash_water_functionality_id');
        $this->field('infrastructure_wash_water_proximity_id');
        $this->field('infrastructure_wash_water_quantity_id');
        $this->field('infrastructure_wash_water_quality_id');
        $this->field('infrastructure_wash_water_accessibility_id');
        $this->field('academic_period_id', ['visible' => false]);

        // element control
        $academicPeriodOptions = $this
            ->AcademicPeriods
            ->getYearList();
        $requestQuery = $this
            ->request->query;

        $selectedAcademicPeriodId = !empty($requestQuery['academic_period_id']) ? $requestQuery['academic_period_id'] : $this
            ->AcademicPeriods
            ->getCurrent();

        $extra['selectedAcademicPeriodId'] = $selectedAcademicPeriodId;

        $extra['elements']['control'] = ['name' => 'Risks/controls', 'data' => ['academicPeriodOptions' => $academicPeriodOptions, 'selectedAcademicPeriod' => $selectedAcademicPeriodId], 'options' => [], 'order' => 3];
        // end element control

        // Start POCOR-5188
        $is_manual_exist = $this->getManualUrl('Institutions','Infrastructure WASH Water','Details');       
        if(!empty($is_manual_exist)){
            $btnAttr = [
                'class' => 'btn btn-xs btn-default icon-big',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false,
                'target'=>'_blank'
            ];
    
            $helpBtn['url'] = $is_manual_exist['url'];
            $helpBtn['type'] = 'button';
            $helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
            $helpBtn['attr'] = $btnAttr;
            $helpBtn['attr']['title'] = __('Help');
            $extra['toolbarButtons']['help'] = $helpBtn;
        }
        // End POCOR-5188
        
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field)
        {
            case 'infrastructure_wash_water_type_id':
                return __('Type');
            case 'infrastructure_wash_water_functionality_id':
                return __('Functionality');
            case 'infrastructure_wash_water_proximity_id':
                return __('Proximity');
            case 'infrastructure_wash_water_quantity_id':
                return __('Quantity');
            case 'infrastructure_wash_water_quality_id':
                return __('Quality');
            case 'infrastructure_wash_water_accessibility_id':
                return __('Accessibility');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->where([$this->aliasField('academic_period_id') => $extra['selectedAcademicPeriodId']])->orderDesc($this->aliasField('created'));
    }

    public function addEditBeforeAction(Event $event, ArrayObject $extra)
    {
        $academicPeriodOptions = $this
            ->AcademicPeriods
            ->getYearList();

        $this->fields['academic_period_id']['type'] = 'select';
        $this->fields['academic_period_id']['options'] = $academicPeriodOptions;
        $this->field('academic_period_id', ['attr' => ['label' => __('Academic Period') ]]);

        $this->fields['infrastructure_wash_water_type_id']['type'] = 'select';
        $this->field('infrastructure_wash_water_type_id', ['attr' => ['label' => __('Type') ]]);

        $this->fields['infrastructure_wash_water_functionality_id']['type'] = 'select';
        $this->field('infrastructure_wash_water_functionality_id', ['attr' => ['label' => __('Functionality') ]]);

        $this->fields['infrastructure_wash_water_proximity_id']['type'] = 'select';
        $this->field('infrastructure_wash_water_proximity_id', ['attr' => ['label' => __('Proximity') ]]);

        $this->fields['infrastructure_wash_water_quantity_id']['type'] = 'select';
        $this->field('infrastructure_wash_water_quantity_id', ['attr' => ['label' => __('Quantity') ]]);

        $this->fields['infrastructure_wash_water_quality_id']['type'] = 'select';
        $this->field('infrastructure_wash_water_quality_id', ['attr' => ['label' => __('Quality') ]]);

        $this->fields['infrastructure_wash_water_accessibility_id']['type'] = 'select';
        $this->field('infrastructure_wash_water_accessibility_id', ['attr' => ['label' => __('Accessibility') ]]);
    }

    public function onExcelBeforeStart(Event $event, ArrayObject $settings, ArrayObject $sheets)
    {
        unset($sheets[0]);
        $infrastructureTabsData = $this->infrastructureTabsData;
        $InstitutionStudents = TableRegistry::get('User.InstitutionStudents');
        $institutionStudentId = $settings['id'];

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
    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $sheetData = $settings['sheet']['sheetData'];
        $infrastructureType = $sheetData['infrastructure_tabs_type'];

        $newFields = [];
        if ($infrastructureType == 'Water')
        {
            $extraField[] = ["key" => "", "field" => "water_type", "type" => "integer", "label" => "Type"];
            $extraField[] = ["key" => "", "field" => "water_functionality", "type" => "integer", "label" => "Functionality"];
            $extraField[] = ["key" => "", "field" => "water_proximity", "type" => "integer", "label" => "Proximity"];
            $extraField[] = ["key" => "", "field" => "water_quantity", "type" => "integer", "label" => "Quantity"];
            $extraField[] = ["key" => "", "field" => "water_quality", "type" => "integer", "label" => "Quality"];
            $extraField[] = ["key" => "", "field" => "water_accessbility", "type" => "integer", "label" => "Accessibility"];
            $extraField[] = ["key" => "", "field" => "institutions_name", "type" => "integer", "label" => "Institution Name"];
            $extraField[] = ["key" => "", "field" => "institutions_code", "type" => "integer", "label" => "Institution Code"];
            $extraField[] = ["key" => "", "field" => "area_administratives_name", "type" => "integer", "label" => "Area Administration"];
            $extraField[] = ["key" => "", "field" => "area_name", "type" => "integer", "label" => "Area Education"];
        }
        if ($infrastructureType == 'Sanitation')
        {
            $extraField[] = ["key" => "", "field" => "sanitation_name", "type" => "string", "label" => "Type"];

            $extraField[] = ["key" => "", "field" => "sanitation_use_name", "type" => "string", "label" => "Use"];

            $extraField[] = ["key" => "", "field" => "total_male", "type" => "integer", "label" => "Total Male"];

            $extraField[] = ["key" => "", "field" => "total_female", "type" => "integer", "label" => "Total Female"];

            $extraField[] = ["key" => "", "field" => "total_mixed", "type" => "integer", "label" => "Total Mixed"];

            $extraField[] = ["key" => "", "field" => "quality", "type" => "string", "label" => "Quality"];

            $extraField[] = ["key" => "", "field" => "accessibility", "type" => "integer", "label" => "Accessibility"];

            $extraField[] = ["key" => "", "field" => "institutions_name", "type" => "integer", "label" => "Institution Name"];
            $extraField[] = ["key" => "", "field" => "institutions_code", "type" => "integer", "label" => "Institution Code"];
            $extraField[] = ["key" => "", "field" => "area_administratives_name", "type" => "integer", "label" => "Area Administration"];
            $extraField[] = ["key" => "", "field" => "area_name", "type" => "integer", "label" => "Area Education"];

        }
        if ($infrastructureType == 'Hygiene')
        {
            $extraField[] = ["key" => "", "field" => "hygiene_type_name", "type" => "string", "label" => "Type"];
            $extraField[] = ["key" => "", "field" => "soap_ash_availability", "type" => "string", "label" => "Soap/Ash Availability"];
            $extraField[] = ["key" => "", "field" => "hygiene_education", "type" => "string", "label" => "Hygiene Education"];
            $extraField[] = ["key" => "", "field" => "hygeine_total_male", "type" => "string", "label" => "Total Male"];
            $extraField[] = ["key" => "", "field" => "hygeine_total_female", "type" => "string", "label" => "Total Female"];
            $extraField[] = ["key" => "", "field" => "hygeine_total_mixed", "type" => "string", "label" => "Total Mixed"];
            $extraField[] = ["key" => "", "field" => "institutions_name", "type" => "integer", "label" => "Institution Name"];
            $extraField[] = ["key" => "", "field" => "institutions_code", "type" => "integer", "label" => "Institution Code"];
            $extraField[] = ["key" => "", "field" => "area_administratives_name", "type" => "integer", "label" => "Area Administration"];
            $extraField[] = ["key" => "", "field" => "area_name", "type" => "integer", "label" => "Area Education"];

        }
        if ($infrastructureType == 'Waste')
        {
            $extraField[] = ["key" => "", "field" => "waste_type", "type" => "string", "label" => "Type"];
            $extraField[] = ["key" => "", "field" => "waste_functionality", "type" => "string", "label" => "Functionality"];
            $extraField[] = ["key" => "", "field" => "institutions_name", "type" => "integer", "label" => "Institution Name"];
            $extraField[] = ["key" => "", "field" => "institutions_code", "type" => "integer", "label" => "Institution Code"];
            $extraField[] = ["key" => "", "field" => "area_administratives_name", "type" => "integer", "label" => "Area Administration"];
            $extraField[] = ["key" => "", "field" => "area_name", "type" => "integer", "label" => "Area Education"];
        }
        if ($infrastructureType == 'Sewage')
        {
            $extraField[] = ["key" => "", "field" => "sewage_type", "type" => "string", "label" => "Type"];
            $extraField[] = ["key" => "", "field" => "sewage_functionality", "type" => "string", "label" => "Functionality"];
            $extraField[] = ["key" => "", "field" => "institutions_name", "type" => "integer", "label" => "Institution Name"];
            $extraField[] = ["key" => "", "field" => "institutions_code", "type" => "integer", "label" => "Institution Code"];
            $extraField[] = ["key" => "", "field" => "area_administratives_name", "type" => "integer", "label" => "Area Administration"];
            $extraField[] = ["key" => "", "field" => "area_name", "type" => "integer", "label" => "Area Education"];
            
        }
        $fields->exchangeArray($extraField);
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $session = $this
            ->request
            ->session();
        $institutionId = $session->read('Institution.Institutions.id');
        $requestQuery = $this
            ->request->query;
        $selectedAcademicPeriodId = !empty($requestQuery['academic_period_id']) ? $requestQuery['academic_period_id'] : $this
            ->AcademicPeriods
            ->getCurrent();
        $sheetData = $settings['sheet']['sheetData'];
        $infrastructureType = $sheetData['infrastructure_tabs_type'];
        $areaAdministratives = TableRegistry::get('area_administratives');
        $institutions = TableRegistry::get('institutions');
        $area = TableRegistry::get('areas');
        /* $query->select(['institutions_name'=>'institutions.name','institutions_code'=>'institutions.code','area_administratives_name'=>'area_administratives.name','area_name'=>'areas.name'])
             ->LeftJoin([$institutions->alias() => $institutions->table()],[
                $institutions->aliasField('id').' = ' . $this->aliasField('institution_id')
                ])
              ->LeftJoin([$areaAdministratives->alias() => $areaAdministratives->table()],[
                $areaAdministratives->aliasField('id').' = ' . $institutions->aliasField('area_administrative_id')
                ])
              ->LeftJoin([$area->alias() => $area->table()],[
                $area->aliasField('id').' = ' . $institutions->aliasField('area_id')
                ])
              ->where([
                $this->aliasField('academic_period_id') => $selectedAcademicPeriodId,
                $this->aliasField('institution_id') => $institutionId
            ]);*/

        if ($infrastructureType == 'Water')
        {
            $infrastructureWashWaterTypes = TableRegistry::get('infrastructure_wash_water_types');
            $infrastructureWashWaterProximities = TableRegistry::get('infrastructure_wash_water_proximities');
            $infrastructureWashWaterFunctionalities = TableRegistry::get('infrastructure_wash_water_functionalities');
            $infrastructureWashWaterQualities = TableRegistry::get('infrastructure_wash_water_qualities');
            $infrastructureWashWaterQuantities = TableRegistry::get('infrastructure_wash_water_quantities');
            $infrastructureWashWaterAccessibilities = TableRegistry::get('infrastructure_wash_water_accessibilities');
            $res = $query->select(['water_type' => 'infrastructure_wash_water_types.name', 'water_functionality' => 'infrastructure_wash_water_functionalities.name', 'water_proximity' => 'infrastructure_wash_water_proximities.name', 'water_quality' => 'infrastructure_wash_water_qualities.name', 'water_quantity' => 'infrastructure_wash_water_quantities.name', 'water_accessbility' => 'infrastructure_wash_water_accessibilities.name', 'institutions_name' => 'institutions.name', 'institutions_code' => 'institutions.code', 'area_administratives_name' => 'area_administratives.name', 'area_name' => 'areas.name'])
                ->LeftJoin([$infrastructureWashWaterTypes->alias() => $infrastructureWashWaterTypes->table() ], [$this->aliasField('infrastructure_wash_water_type_id') . ' = ' . $infrastructureWashWaterTypes->aliasField('id') ])
                ->LeftJoin([$infrastructureWashWaterProximities->alias() => $infrastructureWashWaterProximities->table() ], [$this->aliasField('infrastructure_wash_water_proximity_id') . ' = ' . $infrastructureWashWaterProximities->aliasField('id') ])
                ->LeftJoin([$infrastructureWashWaterFunctionalities->alias() => $infrastructureWashWaterFunctionalities->table() ], [$this->aliasField('infrastructure_wash_water_functionality_id') . ' = ' . $infrastructureWashWaterFunctionalities->aliasField('id') ])
                ->LeftJoin([$infrastructureWashWaterQualities->alias() => $infrastructureWashWaterQualities->table() ], [$this->aliasField('infrastructure_wash_water_quality_id') . ' = ' . $infrastructureWashWaterQualities->aliasField('id') ])
                ->LeftJoin([$infrastructureWashWaterQuantities->alias() => $infrastructureWashWaterQuantities->table() ], [$this->aliasField('infrastructure_wash_water_quantity_id') . ' = ' . $infrastructureWashWaterQuantities->aliasField('id') ])
                ->LeftJoin([$infrastructureWashWaterAccessibilities->alias() => $infrastructureWashWaterAccessibilities->table() ], [$this->aliasField('infrastructure_wash_water_accessibility_id') . ' = ' . $infrastructureWashWaterAccessibilities->aliasField('id') ])
                ->LeftJoin([$institutions->alias() => $institutions->table() ], [$institutions->aliasField('id') . ' = ' . $this->aliasField('institution_id') ])
                ->LeftJoin([$areaAdministratives->alias() => $areaAdministratives->table() ], [$areaAdministratives->aliasField('id') . ' = ' . $institutions->aliasField('area_administrative_id') ])
                ->LeftJoin([$area->alias() => $area->table() ], [$area->aliasField('id') . ' = ' . $institutions->aliasField('area_id') ])
                ->where([$this->aliasField('academic_period_id') => $selectedAcademicPeriodId, $this->aliasField('institution_id') => $institutionId])->orderDesc($this->aliasField('created'));

        }
        if ($infrastructureType == 'Sanitation')
        {
            $infrastructureWashSanitations = TableRegistry::get('infrastructure_wash_sanitations');
            $infrastructureWashSanitationTypes = TableRegistry::get('infrastructure_wash_sanitation_types');
            $infrastructureWashSanitationUses = TableRegistry::get('infrastructure_wash_sanitation_uses');
            $infrastructureWashSanitationQualities = TableRegistry::get('infrastructure_wash_sanitation_qualities');
            $infrastructureWashSanitationAccessibilities = TableRegistry::get('infrastructure_wash_sanitation_accessibilities');

            $res = $query->select(['sanitation_name' => 'infrastructure_wash_sanitation_types.name', 'sanitation_use_name' => 'infrastructure_wash_sanitation_uses.name', 'total_male' => 'infrastructure_wash_sanitations.infrastructure_wash_sanitation_total_male', 'total_female' => 'infrastructure_wash_sanitations.infrastructure_wash_sanitation_total_female', 'total_mixed' => 'infrastructure_wash_sanitations.infrastructure_wash_sanitation_total_mixed', 'quality' => 'infrastructure_wash_sanitation_qualities.name', 'accessibility' => 'infrastructure_wash_sanitation_accessibilities.name', 'institutions_name' => 'institutions.name', 'institutions_code' => 'institutions.code', 'area_administratives_name' => 'area_administratives.name', 'area_name' => 'areas.name'])
            // Sanitation Name
            
                ->LeftJoin([$infrastructureWashSanitations->alias() => $infrastructureWashSanitations->table() ], [$infrastructureWashSanitations->aliasField('institution_id') . ' = ' . $this->aliasField('institution_id') , $infrastructureWashSanitations->aliasField('academic_period_id') . ' = ' . $this->aliasField('academic_period_id') ])
                ->LeftJoin([$infrastructureWashSanitationTypes->alias() => $infrastructureWashSanitationTypes->table() ], [$infrastructureWashSanitations->aliasField('infrastructure_wash_sanitation_type_id') . ' = ' . $infrastructureWashSanitationTypes->aliasField('id') ])
            // Sanitation Name End
            // Sanitation  Use
            
                ->LeftJoin([$infrastructureWashSanitationUses->alias() => $infrastructureWashSanitationUses->table() ], [$infrastructureWashSanitations->aliasField('infrastructure_wash_sanitation_use_id') . ' = ' . $infrastructureWashSanitationUses->aliasField('id') ])
            // Sanitation  Use End
            // Sanitation  Quality
            
                ->LeftJoin([$infrastructureWashSanitationQualities->alias() => $infrastructureWashSanitationQualities->table() ], [$infrastructureWashSanitations->aliasField('infrastructure_wash_sanitation_quality_id') . ' = ' . $infrastructureWashSanitationQualities->aliasField('id') ])
            // Sanitation  Quality End
            // Sanitation  Accessbility
            
                ->LeftJoin([$infrastructureWashSanitationAccessibilities->alias() => $infrastructureWashSanitationAccessibilities->table() ], [$infrastructureWashSanitations->aliasField('infrastructure_wash_sanitation_accessibility_id') . ' = ' . $infrastructureWashSanitationAccessibilities->aliasField('id') ])
                ->LeftJoin([$institutions->alias() => $institutions->table() ], [$institutions->aliasField('id') . ' = ' . $this->aliasField('institution_id') ])
                ->LeftJoin([$areaAdministratives->alias() => $areaAdministratives->table() ], [$areaAdministratives->aliasField('id') . ' = ' . $institutions->aliasField('area_administrative_id') ])
                ->LeftJoin([$area->alias() => $area->table() ], [$area->aliasField('id') . ' = ' . $institutions->aliasField('area_id') ])
                ->where(['infrastructure_wash_sanitations.academic_period_id' => $selectedAcademicPeriodId, 'infrastructure_wash_sanitations.institution_id' => $institutionId])->group('infrastructure_wash_sanitations.id');

            //->orderDesc($this->infrastructureWashSanitations->aliasField('created'))
            
        }
        if ($infrastructureType == 'Hygiene')
        {
            $infrastructureWashHygienes = TableRegistry::get('infrastructure_wash_hygienes');
            $infrastructureWashHygieneTypes = TableRegistry::get('infrastructure_wash_hygiene_types');
            $infrastructureWashHygieneSoapashAvailabilities = TableRegistry::get('infrastructure_wash_hygiene_soapash_availabilities');
            $infrastructureWashHygieneEducations = TableRegistry::get('infrastructure_wash_hygiene_educations');
            $res = $query->select(['hygiene_type_name' => 'infrastructure_wash_hygiene_types.name', 'soap_ash_availability' => 'infrastructure_wash_hygiene_soapash_availabilities.name', 'hygiene_education' => 'infrastructure_wash_hygiene_educations.name', 'hygeine_total_male' => 'infrastructure_wash_hygienes.infrastructure_wash_hygiene_total_male', 'hygeine_total_female' => 'infrastructure_wash_hygienes.infrastructure_wash_hygiene_total_female', 'hygeine_total_mixed' => 'infrastructure_wash_hygienes.infrastructure_wash_hygiene_total_mixed', 'institutions_name' => 'institutions.name', 'institutions_code' => 'institutions.code', 'area_administratives_name' => 'area_administratives.name', 'area_name' => 'areas.name'])
                ->LeftJoin([$infrastructureWashHygienes->alias() => $infrastructureWashHygienes->table() ], [$infrastructureWashHygienes->aliasField('institution_id') . ' = ' . $this->aliasField('institution_id') , $infrastructureWashHygienes->aliasField('academic_period_id') . ' = ' . $this->aliasField('academic_period_id') ])
                ->LeftJoin([$infrastructureWashHygieneTypes->alias() => $infrastructureWashHygieneTypes->table() ], [$infrastructureWashHygieneTypes->aliasField('id') . ' = ' . $infrastructureWashHygienes->aliasField('infrastructure_wash_hygiene_type_id') ])
                ->LeftJoin([$infrastructureWashHygieneSoapashAvailabilities->alias() => $infrastructureWashHygieneSoapashAvailabilities->table() ], [$infrastructureWashHygieneSoapashAvailabilities->aliasField('id') . ' = ' . $infrastructureWashHygienes->aliasField('infrastructure_wash_hygiene_soapash_availability_id ') ])
                ->LeftJoin([$infrastructureWashHygieneEducations->alias() => $infrastructureWashHygieneEducations->table() ], [$infrastructureWashHygieneEducations->aliasField('id') . ' = ' . $infrastructureWashHygienes->aliasField('infrastructure_wash_hygiene_education_id ') ])
                ->LeftJoin([$institutions->alias() => $institutions->table() ], [$institutions->aliasField('id') . ' = ' . $this->aliasField('institution_id') ])
                ->LeftJoin([$areaAdministratives->alias() => $areaAdministratives->table() ], [$areaAdministratives->aliasField('id') . ' = ' . $institutions->aliasField('area_administrative_id') ])
                ->LeftJoin([$area->alias() => $area->table() ], [$area->aliasField('id') . ' = ' . $institutions->aliasField('area_id') ])
                ->where(['infrastructure_wash_hygienes.academic_period_id' => $selectedAcademicPeriodId, 'infrastructure_wash_hygienes.institution_id' => $institutionId])->group('infrastructure_wash_hygienes.id');
            // /print_r($res); exit;
            
        }
        if ($infrastructureType == 'Waste')
        {
            $infrastructureWashWastes = TableRegistry::get('infrastructure_wash_wastes');
            $infrastructureWashWasteTypes = TableRegistry::get('infrastructure_wash_waste_types');
            $infrastructureWashWasteFunctionalities = TableRegistry::get('infrastructure_wash_waste_functionalities');

            $res = $query->select(['waste_type' => 'infrastructure_wash_waste_types.name', 'waste_functionality' => 'infrastructure_wash_waste_functionalities.name', 'institutions_name' => 'institutions.name', 'institutions_code' => 'institutions.code', 'area_administratives_name' => 'area_administratives.name', 'area_name' => 'areas.name'])
                ->LeftJoin([$infrastructureWashWastes->alias() => $infrastructureWashWastes->table() ], [$infrastructureWashWastes->aliasField('institution_id') . ' = ' . $this->aliasField('institution_id') , $infrastructureWashWastes->aliasField('academic_period_id') . ' = ' . $this->aliasField('academic_period_id') ])
                ->LeftJoin([$infrastructureWashWasteTypes->alias() => $infrastructureWashWasteTypes->table() ], [$infrastructureWashWasteTypes->aliasField('id') . ' = ' . $infrastructureWashWastes->aliasField('infrastructure_wash_waste_type_id') ])
                ->LeftJoin([$infrastructureWashWasteFunctionalities->alias() => $infrastructureWashWasteFunctionalities->table() ], [$infrastructureWashWasteFunctionalities->aliasField('id') . ' = ' . $infrastructureWashWastes->aliasField('infrastructure_wash_waste_functionality_id') ])
                ->LeftJoin([$institutions->alias() => $institutions->table() ], [$institutions->aliasField('id') . ' = ' . $this->aliasField('institution_id') ])
                ->LeftJoin([$areaAdministratives->alias() => $areaAdministratives->table() ], [$areaAdministratives->aliasField('id') . ' = ' . $institutions->aliasField('area_administrative_id') ])
                ->LeftJoin([$area->alias() => $area->table() ], [$area->aliasField('id') . ' = ' . $institutions->aliasField('area_id') ])
                ->where(['infrastructure_wash_wastes.academic_period_id' => $selectedAcademicPeriodId, 'infrastructure_wash_wastes.institution_id' => $institutionId])->group('infrastructure_wash_wastes.id');
        }
        if ($infrastructureType == 'Sewage')
        {
            $infrastructureWashSewages = TableRegistry::get('infrastructure_wash_sewages');
            $infrastructureWashSewageTypes = TableRegistry::get('infrastructure_wash_sewage_types');
            $infrastructureWashSewageFunctionalities = TableRegistry::get('infrastructure_wash_sewage_functionalities');
            $res = $query->select(['sewage_type' => 'infrastructure_wash_sewage_types.name', 'sewage_functionality' => 'infrastructure_wash_sewage_functionalities.name', 'institutions_name' => 'institutions.name', 'institutions_code' => 'institutions.code', 'area_administratives_name' => 'area_administratives.name', 'area_name' => 'areas.name'])
                ->LeftJoin([$infrastructureWashSewages->alias() => $infrastructureWashSewages->table() ], [$infrastructureWashSewages->aliasField('institution_id') . ' = ' . $this->aliasField('institution_id') , $infrastructureWashSewages->aliasField('academic_period_id') . ' = ' . $this->aliasField('academic_period_id') ])
                ->LeftJoin([$infrastructureWashSewageTypes->alias() => $infrastructureWashSewageTypes->table() ], [$infrastructureWashSewageTypes->aliasField('id') . ' = ' . $infrastructureWashSewages->aliasField('infrastructure_wash_sewage_type_id') ])
                ->LeftJoin([$infrastructureWashSewageFunctionalities->alias() => $infrastructureWashSewageFunctionalities->table() ], [$infrastructureWashSewageFunctionalities->aliasField('id') . ' = ' . $infrastructureWashSewages->aliasField('infrastructure_wash_sewage_functionality_id') ])
                ->LeftJoin([$institutions->alias() => $institutions->table() ], [$institutions->aliasField('id') . ' = ' . $this->aliasField('institution_id') ])
                ->LeftJoin([$areaAdministratives->alias() => $areaAdministratives->table() ], [$areaAdministratives->aliasField('id') . ' = ' . $institutions->aliasField('area_administrative_id') ])
                ->LeftJoin([$area->alias() => $area->table() ], [$area->aliasField('id') . ' = ' . $institutions->aliasField('area_id') ])
                ->where(['infrastructure_wash_sewages.academic_period_id' => $selectedAcademicPeriodId, 'infrastructure_wash_sewages.institution_id' => $institutionId])->group('infrastructure_wash_sewages.id');

        }
    }

}

