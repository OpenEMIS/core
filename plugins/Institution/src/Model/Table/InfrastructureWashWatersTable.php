<?php
namespace Institution\Model\Table;
use ArrayObject;

use Cake\Event\EventInterface;
use Cake\ORM\Query;
use App\Model\Table\AppTable;
use App\Model\Table\ControllerActionTable;
use Cake\ORM\TableRegistry;

class InfrastructureWashWatersTable extends ControllerActionTable
{
    private $infrastructureTabsData = [0 => "Water", 1 => "Sanitation", 2 => "Hygiene", 3 => "Waste", 4 => "Sewage"];
    public function initialize(array $config): void
    {
        $this->setTable('infrastructure_wash_waters');
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

        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['InfrastructureWashWaters'=>['id']]
        ]);

    }

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $modelAlias = 'InfrastructureWashWaters';
        $userType = '';
        $this
            ->controller
            ->changeUtilitiesHeader($this, $modelAlias, $userType);
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
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
            ->request->getQuery();

        $selectedAcademicPeriodId = !empty($requestQuery['academic_period_id']) ? $requestQuery['academic_period_id'] : $this
            ->AcademicPeriods
            ->getCurrent();

        $extra['selectedAcademicPeriodId'] = $selectedAcademicPeriodId;

        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);

        $extra['elements']['control'] = ['name' => 'Risks/controls', 'data' => ['encodedQueryString' => $encodedQueryString, 'academicPeriodOptions' => $academicPeriodOptions, 'selectedAcademicPeriod' => $selectedAcademicPeriodId], 'options' => [], 'order' => 3];
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

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
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

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $query->where([$this->aliasField('academic_period_id') => $extra['selectedAcademicPeriodId']])->orderDesc($this->aliasField('created'));
    }

    public function addEditBeforeAction(EventInterface $event, ArrayObject $extra)
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

    public function onExcelBeforeStart(EventInterface $event, ArrayObject $settings, ArrayObject $sheets)
    {
        unset($sheets[0]);
        $infrastructureTabsData = $this->infrastructureTabsData;
        $InstitutionStudents = TableRegistry::getTableLocator()->get('Institution.InstitutionStudents');
        $institutionStudentId = $settings['id'];

        foreach ($infrastructureTabsData as $key => $val)
        {
            $tabsName = $val;
            $sheets[] = ['sheetData' => ['infrastructure_tabs_type' => $val], 'name' => $tabsName, 'table' => $this, 'query' => $this->find()
            /* ->leftJoin([$InstitutionStudents->getAlias() => $InstitutionStudents->getTable()],[
                        $this->aliasField('id = ').$InstitutionStudents->aliasField('student_id')
                    ])
                    ->where([
                        $InstitutionStudents->aliasField('student_id = ').$institutionStudentId,
                    ]) */
            , 'orientation' => 'landscape'];
        }
    }
    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, ArrayObject $fields)
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

    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query)
    {
        $session = $this
            ->request
            ->getSession();
        //$institutionId = $session->read('Institution.Institutions.id');
        $institutionId  = $this->getInstitutionID();
        $requestQuery = $this
            ->request->getQuery();
        $selectedAcademicPeriodId = !empty($requestQuery['academic_period_id']) ? $requestQuery['academic_period_id'] : $this
            ->AcademicPeriods
            ->getCurrent();
        $sheetData = $settings['sheet']['sheetData'];
        $infrastructureType = $sheetData['infrastructure_tabs_type'];
        $areaAdministratives = TableRegistry::getTableLocator()->get('Area.AreaAdministratives');
        $institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
        $area = TableRegistry::getTableLocator()->get('Area.Areas');
        /* $query->select(['institutions_name'=>'institutions.name','institutions_code'=>'institutions.code','area_administratives_name'=>'area_administratives.name','area_name'=>'areas.name'])
             ->LeftJoin([$institutions->getAlias() => $institutions->getTable()],[
                $institutions->aliasField('id').' = ' . $this->aliasField('institution_id')
                ])
              ->LeftJoin([$areaAdministratives->getAlias() => $areaAdministratives->getTable()],[
                $areaAdministratives->aliasField('id').' = ' . $institutions->aliasField('area_administrative_id')
                ])
              ->LeftJoin([$area->getAlias() => $area->getTable()],[
                $area->aliasField('id').' = ' . $institutions->aliasField('area_id')
                ])
              ->where([
                $this->aliasField('academic_period_id') => $selectedAcademicPeriodId,
                $this->aliasField('institution_id') => $institutionId
            ]);*/

        if ($infrastructureType == 'Water')
        {
            $infrastructureWashWaterTypes = TableRegistry::getTableLocator()->get('Institution.InfrastructureWashWaterTypes');
            $infrastructureWashWaterProximities = TableRegistry::getTableLocator()->get('Institution.InfrastructureWashWaterProximities');
            $infrastructureWashWaterFunctionalities = TableRegistry::getTableLocator()->get('Institution.InfrastructureWashWaterFunctionalities');
            $infrastructureWashWaterQualities = TableRegistry::getTableLocator()->get('Institution.InfrastructureWashWaterQualities');
            $infrastructureWashWaterQuantities = TableRegistry::getTableLocator()->get('Institution.InfrastructureWashWaterQuantities');
            $infrastructureWashWaterAccessibilities = TableRegistry::getTableLocator()->get('Institution.InfrastructureWashWaterAccessibilities');
            $res = $query->select(['water_type' => $infrastructureWashWaterTypes->aliasField('name'), 'water_functionality' => $infrastructureWashWaterFunctionalities->aliasField('name'), 'water_proximity' => $infrastructureWashWaterProximities->aliasField('name'), 'water_quality' => $infrastructureWashWaterQualities->aliasField('name'), 'water_quantity' => $infrastructureWashWaterQuantities->aliasField('name'), 'water_accessbility' => $infrastructureWashWaterAccessibilities->aliasField('name'), 'institutions_name' => $institutions->aliasField('name'), 'institutions_code' => $institutions->aliasField('code'), 'area_administratives_name' => $areaAdministratives->aliasField('name'), 'area_name' => $area->aliasField('name')])
                ->LeftJoin([$infrastructureWashWaterTypes->getAlias() => $infrastructureWashWaterTypes->getTable() ], [$this->aliasField('infrastructure_wash_water_type_id') . ' = ' . $infrastructureWashWaterTypes->aliasField('id') ])
                ->LeftJoin([$infrastructureWashWaterProximities->getAlias() => $infrastructureWashWaterProximities->getTable() ], [$this->aliasField('infrastructure_wash_water_proximity_id') . ' = ' . $infrastructureWashWaterProximities->aliasField('id') ])
                ->LeftJoin([$infrastructureWashWaterFunctionalities->getAlias() => $infrastructureWashWaterFunctionalities->getTable() ], [$this->aliasField('infrastructure_wash_water_functionality_id') . ' = ' . $infrastructureWashWaterFunctionalities->aliasField('id') ])
                ->LeftJoin([$infrastructureWashWaterQualities->getAlias() => $infrastructureWashWaterQualities->getTable() ], [$this->aliasField('infrastructure_wash_water_quality_id') . ' = ' . $infrastructureWashWaterQualities->aliasField('id') ])
                ->LeftJoin([$infrastructureWashWaterQuantities->getAlias() => $infrastructureWashWaterQuantities->getTable() ], [$this->aliasField('infrastructure_wash_water_quantity_id') . ' = ' . $infrastructureWashWaterQuantities->aliasField('id') ])
                ->LeftJoin([$infrastructureWashWaterAccessibilities->getAlias() => $infrastructureWashWaterAccessibilities->getTable() ], [$this->aliasField('infrastructure_wash_water_accessibility_id') . ' = ' . $infrastructureWashWaterAccessibilities->aliasField('id') ])
                ->LeftJoin([$institutions->getAlias() => $institutions->getTable() ], [$institutions->aliasField('id') . ' = ' . $this->aliasField('institution_id') ])
                ->LeftJoin([$areaAdministratives->getAlias() => $areaAdministratives->getTable() ], [$areaAdministratives->aliasField('id') . ' = ' . $institutions->aliasField('area_administrative_id') ])
                ->LeftJoin([$area->getAlias() => $area->getTable() ], [$area->aliasField('id') . ' = ' . $institutions->aliasField('area_id') ])
                ->where([$this->aliasField('academic_period_id') => $selectedAcademicPeriodId, $this->aliasField('institution_id') => $institutionId])->orderDesc($this->aliasField('created'));

        }
        if ($infrastructureType == 'Sanitation')
        {
            $infrastructureWashSanitations = TableRegistry::getTableLocator()->get('Institution.InfrastructureWashSanitations');
            $infrastructureWashSanitationTypes = TableRegistry::getTableLocator()->get('Institution.InfrastructureWashSanitationTypes');
            $infrastructureWashSanitationUses = TableRegistry::getTableLocator()->get('Institution.InfrastructureWashSanitationUses');
            $infrastructureWashSanitationQualities = TableRegistry::getTableLocator()->get('Institution.InfrastructureWashSanitationQualities');
            $infrastructureWashSanitationAccessibilities = TableRegistry::getTableLocator()->get('Institution.InfrastructureWashSanitationAccessibilities');

            $res = $query->select(['sanitation_name' => $infrastructureWashSanitationTypes->aliasField('name'), 'sanitation_use_name' => $infrastructureWashSanitationUses->aliasField('name'), 'total_male' => $infrastructureWashSanitations->aliasField('infrastructure_wash_sanitation_total_male'), 'total_female' => $infrastructureWashSanitations->aliasField('infrastructure_wash_sanitation_total_female'), 'total_mixed' => $infrastructureWashSanitations->aliasField('infrastructure_wash_sanitation_total_mixed'), 'quality' => $infrastructureWashSanitationQualities->aliasField('name'), 'accessibility' => $infrastructureWashSanitationAccessibilities->aliasField('name'), 'institutions_name' => $institutions->aliasField('name'), 'institutions_code' => $institutions->aliasField('code'), 'area_administratives_name' => $areaAdministratives->aliasField('name'), 'area_name' => $area->aliasField('name')])
            // Sanitation Name
            
                ->LeftJoin([$infrastructureWashSanitations->getAlias() => $infrastructureWashSanitations->getTable() ], [$infrastructureWashSanitations->aliasField('institution_id') . ' = ' . $this->aliasField('institution_id') , $infrastructureWashSanitations->aliasField('academic_period_id') . ' = ' . $this->aliasField('academic_period_id') ])
                ->LeftJoin([$infrastructureWashSanitationTypes->getAlias() => $infrastructureWashSanitationTypes->getTable() ], [$infrastructureWashSanitations->aliasField('infrastructure_wash_sanitation_type_id') . ' = ' . $infrastructureWashSanitationTypes->aliasField('id') ])
            // Sanitation Name End
            // Sanitation  Use
            
                ->LeftJoin([$infrastructureWashSanitationUses->getAlias() => $infrastructureWashSanitationUses->getTable() ], [$infrastructureWashSanitations->aliasField('infrastructure_wash_sanitation_use_id') . ' = ' . $infrastructureWashSanitationUses->aliasField('id') ])
            // Sanitation  Use End
            // Sanitation  Quality
            
                ->LeftJoin([$infrastructureWashSanitationQualities->getAlias() => $infrastructureWashSanitationQualities->getTable() ], [$infrastructureWashSanitations->aliasField('infrastructure_wash_sanitation_quality_id') . ' = ' . $infrastructureWashSanitationQualities->aliasField('id') ])
            // Sanitation  Quality End
            // Sanitation  Accessbility
            
                ->LeftJoin([$infrastructureWashSanitationAccessibilities->getAlias() => $infrastructureWashSanitationAccessibilities->getTable() ], [$infrastructureWashSanitations->aliasField('infrastructure_wash_sanitation_accessibility_id') . ' = ' . $infrastructureWashSanitationAccessibilities->aliasField('id') ])
                ->LeftJoin([$institutions->getAlias() => $institutions->getTable() ], [$institutions->aliasField('id') . ' = ' . $this->aliasField('institution_id') ])
                ->LeftJoin([$areaAdministratives->getAlias() => $areaAdministratives->getTable() ], [$areaAdministratives->aliasField('id') . ' = ' . $institutions->aliasField('area_administrative_id') ])
                ->LeftJoin([$area->getAlias() => $area->getTable() ], [$area->aliasField('id') . ' = ' . $institutions->aliasField('area_id') ])
                ->where([$infrastructureWashSanitations->aliasField('academic_period_id') => $selectedAcademicPeriodId, $infrastructureWashSanitations->aliasField('institution_id') => $institutionId])->group($infrastructureWashSanitations->aliasField('id'));

            //->orderDesc($this->infrastructureWashSanitations->aliasField('created'))
            
        }
        if ($infrastructureType == 'Hygiene')
        {
            $infrastructureWashHygienes = TableRegistry::getTableLocator()->get('Institution.InfrastructureWashHygienes');
            $infrastructureWashHygieneTypes = TableRegistry::getTableLocator()->get('Institution.InfrastructureWashHygieneTypes');
            $infrastructureWashHygieneSoapashAvailabilities = TableRegistry::getTableLocator()->get('Institution.InfrastructureWashHygieneSoapashAvailabilities');
            $infrastructureWashHygieneEducations = TableRegistry::getTableLocator()->get('Institution.InfrastructureWashHygieneEducations');
            $res = $query->select(['hygiene_type_name' => $infrastructureWashHygieneTypes->aliasField('name'), 'soap_ash_availability' => $infrastructureWashHygieneSoapashAvailabilities->aliasField('name'), 'hygiene_education' => $infrastructureWashHygieneEducations->aliasField('name'), 'hygeine_total_male' => $infrastructureWashHygienes->aliasField('infrastructure_wash_hygiene_total_male'), 'hygeine_total_female' => $infrastructureWashHygienes->aliasField('infrastructure_wash_hygiene_total_female'), 'hygeine_total_mixed' => $infrastructureWashHygienes->aliasField('infrastructure_wash_hygiene_total_mixed'), 'institutions_name' => $institutions->aliasField('name'), 'institutions_code' => $institutions->aliasField('code'), 'area_administratives_name' => $areaAdministratives->aliasField('name'), 'area_name' => $area->aliasField('name')])
                ->LeftJoin([$infrastructureWashHygienes->getAlias() => $infrastructureWashHygienes->getTable() ], [$infrastructureWashHygienes->aliasField('institution_id') . ' = ' . $this->aliasField('institution_id') , $infrastructureWashHygienes->aliasField('academic_period_id') . ' = ' . $this->aliasField('academic_period_id') ])
                ->LeftJoin([$infrastructureWashHygieneTypes->getAlias() => $infrastructureWashHygieneTypes->getTable() ], [$infrastructureWashHygieneTypes->aliasField('id') . ' = ' . $infrastructureWashHygienes->aliasField('infrastructure_wash_hygiene_type_id') ])
                ->LeftJoin([$infrastructureWashHygieneSoapashAvailabilities->getAlias() => $infrastructureWashHygieneSoapashAvailabilities->getTable() ], [$infrastructureWashHygieneSoapashAvailabilities->aliasField('id') . ' = ' . $infrastructureWashHygienes->aliasField('infrastructure_wash_hygiene_soapash_availability_id ') ])
                ->LeftJoin([$infrastructureWashHygieneEducations->getAlias() => $infrastructureWashHygieneEducations->getTable() ], [$infrastructureWashHygieneEducations->aliasField('id') . ' = ' . $infrastructureWashHygienes->aliasField('infrastructure_wash_hygiene_education_id ') ])
                ->LeftJoin([$institutions->getAlias() => $institutions->getTable() ], [$institutions->aliasField('id') . ' = ' . $this->aliasField('institution_id') ])
                ->LeftJoin([$areaAdministratives->getAlias() => $areaAdministratives->getTable() ], [$areaAdministratives->aliasField('id') . ' = ' . $institutions->aliasField('area_administrative_id') ])
                ->LeftJoin([$area->getAlias() => $area->getTable() ], [$area->aliasField('id') . ' = ' . $institutions->aliasField('area_id') ])
                ->where([$infrastructureWashHygienes->aliasField('academic_period_id') => $selectedAcademicPeriodId, $infrastructureWashHygienes->aliasField('institution_id') => $institutionId])->group($infrastructureWashHygienes->aliasField('id'));
            // /print_r($res); exit;
            
        }
        if ($infrastructureType == 'Waste')
        {
            $infrastructureWashWastes = TableRegistry::getTableLocator()->get('Institution.InfrastructureWashWastes');
            $infrastructureWashWasteTypes = TableRegistry::getTableLocator()->get('Institution.InfrastructureWashWasteTypes');
            $infrastructureWashWasteFunctionalities = TableRegistry::getTableLocator()->get('Institution.InfrastructureWashWasteFunctionalities');

            $res = $query->select(['waste_type' => $infrastructureWashWasteTypes->aliasField('name'), 'waste_functionality' => $infrastructureWashWasteFunctionalities->aliasField('name'), 'institutions_name' => $institutions->aliasField('name'), 'institutions_code' => $institutions->aliasField('code'), 'area_administratives_name' => $areaAdministratives->aliasField('name'), 'area_name' => $area->aliasField('name')])
                ->LeftJoin([$infrastructureWashWastes->getAlias() => $infrastructureWashWastes->getTable() ], [$infrastructureWashWastes->aliasField('institution_id') . ' = ' . $this->aliasField('institution_id') , $infrastructureWashWastes->aliasField('academic_period_id') . ' = ' . $this->aliasField('academic_period_id') ])
                ->LeftJoin([$infrastructureWashWasteTypes->getAlias() => $infrastructureWashWasteTypes->getTable() ], [$infrastructureWashWasteTypes->aliasField('id') . ' = ' . $infrastructureWashWastes->aliasField('infrastructure_wash_waste_type_id') ])
                ->LeftJoin([$infrastructureWashWasteFunctionalities->getAlias() => $infrastructureWashWasteFunctionalities->getTable() ], [$infrastructureWashWasteFunctionalities->aliasField('id') . ' = ' . $infrastructureWashWastes->aliasField('infrastructure_wash_waste_functionality_id') ])
                ->LeftJoin([$institutions->getAlias() => $institutions->getTable() ], [$institutions->aliasField('id') . ' = ' . $this->aliasField('institution_id') ])
                ->LeftJoin([$areaAdministratives->getAlias() => $areaAdministratives->getTable() ], [$areaAdministratives->aliasField('id') . ' = ' . $institutions->aliasField('area_administrative_id') ])
                ->LeftJoin([$area->getAlias() => $area->getTable() ], [$area->aliasField('id') . ' = ' . $institutions->aliasField('area_id') ])
                ->where([$infrastructureWashWastes->aliasField('academic_period_id') => $selectedAcademicPeriodId, $infrastructureWashWastes->aliasField('institution_id') => $institutionId])->group($infrastructureWashWastes->aliasField('id'));
        }
        if ($infrastructureType == 'Sewage')
        {
            $infrastructureWashSewages = TableRegistry::getTableLocator()->get('Institution.InfrastructureWashSewages');
            $infrastructureWashSewageTypes = TableRegistry::getTableLocator()->get('Institution.InfrastructureWashSewageTypes');
            $infrastructureWashSewageFunctionalities = TableRegistry::getTableLocator()->get('Institution.InfrastructureWashSewageFunctionalities');
            $res = $query->select(['sewage_type' => $infrastructureWashSewageTypes->aliasField('name'), 'sewage_functionality' => $infrastructureWashSewageFunctionalities->aliasField('name'), 'institutions_name' => $institutions->aliasField('name'), 'institutions_code' => $institutions->aliasField('code'), 'area_administratives_name' => $areaAdministratives->aliasField('name'), 'area_name' => $area->aliasField('name')])
                ->LeftJoin([$infrastructureWashSewages->getAlias() => $infrastructureWashSewages->getTable() ], [$infrastructureWashSewages->aliasField('institution_id') . ' = ' . $this->aliasField('institution_id') , $infrastructureWashSewages->aliasField('academic_period_id') . ' = ' . $this->aliasField('academic_period_id') ])
                ->LeftJoin([$infrastructureWashSewageTypes->getAlias() => $infrastructureWashSewageTypes->getTable() ], [$infrastructureWashSewageTypes->aliasField('id') . ' = ' . $infrastructureWashSewages->aliasField('infrastructure_wash_sewage_type_id') ])
                ->LeftJoin([$infrastructureWashSewageFunctionalities->getAlias() => $infrastructureWashSewageFunctionalities->getTable() ], [$infrastructureWashSewageFunctionalities->aliasField('id') . ' = ' . $infrastructureWashSewages->aliasField('infrastructure_wash_sewage_functionality_id') ])
                ->LeftJoin([$institutions->getAlias() => $institutions->getTable() ], [$institutions->aliasField('id') . ' = ' . $this->aliasField('institution_id') ])
                ->LeftJoin([$areaAdministratives->getAlias() => $areaAdministratives->getTable() ], [$areaAdministratives->aliasField('id') . ' = ' . $institutions->aliasField('area_administrative_id') ])
                ->LeftJoin([$area->getAlias() => $area->getTable() ], [$area->aliasField('id') . ' = ' . $institutions->aliasField('area_id') ])
                ->where([$infrastructureWashSewages->aliasField('academic_period_id') => $selectedAcademicPeriodId, $infrastructureWashSewages->aliasField('institution_id') => $institutionId])->group($infrastructureWashSewages->aliasField('id'));

        }
    }

}

