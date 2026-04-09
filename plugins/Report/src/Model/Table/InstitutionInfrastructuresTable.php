<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use Cake\I18n\Time;
use Cake\ORM\TableRegistry;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use Institution\Model\Table\InstitutionsTable as Institutions;
use Cake\Database\Connection;

class InstitutionInfrastructuresTable extends AppTable
{
    use OptionsTrait;
    private $classificationOptions = [];

    // filter
    const NO_FILTER = 0;
    const NO_STUDENT = 1;
    const NO_STAFF = 2;

    public function initialize(array $config): void
    {

        $this->setTable('institutions');
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('AreaLevels', ['className' => 'Area.AreaLevels']);
        $this->belongsTo('Areas', ['className' => 'Area.Areas']);
        $this->belongsTo('AreaAdministratives', ['className' => 'Area.AreaAdministratives']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        parent::initialize($config);
        $this->addBehavior('Excel', ['excludes' => ['security_group_id', 'logo_name'], 'pages' => false]);
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Report.AreaList');//POCOR-7794

    }

    public function beforeAction(EventInterface $event)
    {
        $this->fields = [];
        $this->ControllerAction->field('feature', ['select' => false]);
        $this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);
        $this->ControllerAction->field('area_level_id', ['type' => 'hidden']);
        $this->ControllerAction->field('area_education_id', ['type' => 'hidden', 'attr' => ['required' => true]]);
        $this->ControllerAction->field('institution_id', ['type' => 'hidden']);
        $this->ControllerAction->field('infrastructure_level', ['type' => 'hidden']);
        $this->ControllerAction->field('infrastructure_type', ['type' => 'hidden']);
        $this->ControllerAction->field('report_start_date', ['type' => 'hidden']);
        $this->ControllerAction->field('report_end_date', ['type' => 'hidden']);
        $this->ControllerAction->field('wash_type', ['type' => 'hidden']);
        $this->ControllerAction->field('from_date', ['type' => 'hidden']);
        $this->ControllerAction->field('to_date', ['type' => 'hidden']);
        $this->ControllerAction->field('format');
    }

    public function addBeforePatch(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        if ($data[$this->getAlias()]['feature'] == 'Report.InstitutionInfrastructures') {
            $options['validate'] = 'InstitutionInfrastructures';
        }
    }

    public function addBeforeAction(EventInterface $event)
    {
        $this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);
        $this->ControllerAction->field('area_education_id', ['type' => 'hidden', 'attr' => ['label'=>'Area Education','required' => true]]);
        $this->ControllerAction->field('institution_id', ['type' => 'hidden', 'attr' => ['required' => true]]);
    }

    public function onUpdateFieldFeature(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $options = $this->controller->getFeatureOptions($this->getAlias());
        $attr['options'] = $this->controller->getFeatureOptions($this->getAlias());
        $attr['onChangeReload'] = true;
        if (!(isset($this->request->getData($this->getAlias())['feature']))) {
                $option = $attr['options'];
                reset($option);
                $defaultFeatureValue = key($options);
                $this->request = $this->request->withData($this->getAlias() . '.feature', $defaultFeatureValue);
            }
        return $attr;
    }

    public function onUpdateFieldInstitutionId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $alias = $this->getAlias();
        $data = $this->request->getData($alias);
        $areaId = $data['area_education_id'];
        $institutionTypeId = $data['institution_type_id'] ?? -1;
        $InstitutionsTable = self::getDynamicTableInstance('Institution.Institutions');
        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];

            if (in_array($feature, ['Report.InstitutionInfrastructures','Report.InfrastructureNeeds', 'Report.InstitutionAssets', 'Report.Income', 'Report.Expenditure', 'Report.WashReports', 'Report.InfrastructureElectricities', 'Report.InfrastructureInternets', 'Report.InfrastructureTelephones'])) {
                $institutionList = [];
                if (array_key_exists('institution_type_id', (array)$request->getData($this->getAlias())) && !empty($request->getData($this->getAlias())['institution_type_id'])) {
                    $institutionTypeId = $request->getData($this->getAlias())['institution_type_id'];
                    $institutionQuery = $InstitutionsTable
                        ->find('list', [
                            'keyField' => 'id',
                            'valueField' => 'code_name'
                        ])
                        ->where([
                            $InstitutionsTable->aliasField('institution_type_id') => $institutionTypeId
                        ])
                        ->order([
                            $InstitutionsTable->aliasField('code') => 'ASC',
                            $InstitutionsTable->aliasField('name') => 'ASC'
                        ]);


                    $superAdmin = $this->Auth->user('super_admin');
                    if (!$superAdmin) { // if user is not super admin, the list will be filtered
                        $userId = $this->Auth->user('id');
                        $institutionQuery->find('byAccess', ['userId' => $userId]);
                    }

                    $institutionList = $institutionQuery->toArray();
                } elseif (!$institutionTypeId && array_key_exists('area_education_id', (array)$request->getData($this->getAlias())) && !empty($request->getData($this->getAlias())['area_education_id']) && $areaId != -1) {
                    //Start:POCOR-6818 Modified this for POCOR-6859
                    $AreaT = TableRegistry::getTableLocator()->get('Area.Areas');                    
                    //Level-1
                    $AreaData = $AreaT->find('all',['fields'=>'id'])->where(['parent_id' => $areaId])->toArray();
                    $childArea =[];
                    $childAreaMain = [];
                    $childArea3 = [];
                    $childArea4 = [];
                    foreach($AreaData as $kkk =>$AreaData11 ){
                        $childArea[$kkk] = $AreaData11->id;
                    }
                    //level-2
                    foreach($childArea as $kyy =>$AreaDatal2 ){
                        $AreaDatas = $AreaT->find('all',['fields'=>'id'])->where(['parent_id' => $AreaDatal2])->toArray();
                        foreach($AreaDatas as $ky =>$AreaDatal22 ){
                            $childAreaMain[$ky] = $AreaDatal22->id;
                        }
                    }
                    //level-3
                    if(!empty($childAreaMain)){
                        foreach($childAreaMain as $kyy =>$AreaDatal3 ){
                            $AreaDatass = $AreaT->find('all',['fields'=>'id'])->where(['parent_id' => $AreaDatal3])->toArray();
                            foreach($AreaDatass as $ky =>$AreaDatal222 ){
                                $childArea3[$ky] = $AreaDatal222->id;
                            }
                        }
                    }
                    
                    //level-4
                    if(!empty($childAreaMain)){
                        foreach($childArea3 as $kyy =>$AreaDatal4 ){
                            $AreaDatasss = $AreaT->find('all',['fields'=>'id'])->where(['parent_id' => $AreaDatal4])->toArray();
                            foreach($AreaDatasss as $ky =>$AreaDatal44 ){
                                $childArea4[$ky] = $AreaDatal44->id;
                            }
                        }
                    }
                    $mergeArr = array_merge($childAreaMain,$childArea,$childArea3,$childArea4);
                    array_push($mergeArr,$areaId);
                    $mergeArr = array_unique($mergeArr);
                    $finalIds = implode(',',$mergeArr);
                    $finalIds = explode(',',$finalIds);
                    //End:POCOR-6818 Modified this for POCOR-6859
                    $institutionQuery = $InstitutionsTable
                        ->find('list', [
                            'keyField' => 'id',
                            'valueField' => 'code_name'
                        ])
                        ->where([
                            $InstitutionsTable->aliasField('area_id').' IN' => $finalIds //POCOR-6818
                        ])
                        ->order([
                            $InstitutionsTable->aliasField('code') => 'ASC',
                            $InstitutionsTable->aliasField('name') => 'ASC'
                        ]);

                    $superAdmin = $this->Auth->user('super_admin');
                    if (!$superAdmin) { // if user is not super admin, the list will be filtered
                        $userId = $this->Auth->user('id');
                        $institutionQuery->find('byAccess', ['userId' => $userId]);
                    }

                    $institutionList = $institutionQuery->toArray();
                } else {
                   $institutionQuery = $InstitutionsTable
                                       ->find('list', [
                                                'keyField' => 'id',
                                                'valueField' => 'code_name'
                                            ])
                                       ->order([
                                           $InstitutionsTable->aliasField('code') => 'ASC',
                                           $InstitutionsTable->aliasField('name') => 'ASC'
                                       ]);

                   $superAdmin = $this->Auth->user('super_admin');
                   if (!$superAdmin) { // if user is not super admin, the list will be filtered
                       $userId = $this->Auth->user('id');
                       $institutionQuery->find('byAccess', ['userId' => $userId]);
                   }

                   $institutionList = $institutionQuery->toArray();
                }

                if (empty($institutionList)) {
                    $institutionOptions = ['' => $this->getMessage('general.select.noOptions')];
                    $attr['type'] = 'select';
                    $attr['options'] = $institutionOptions;
                    $attr['attr']['required'] = true;
                } else {

                    if (count($institutionList) > 1) {
                        $institutionOptions = ['' => '-- ' . __('Select') . ' --', '0' => __('All Institutions')] + $institutionList;
                    } else {
                        $institutionOptions = ['' => '-- ' . __('Select') . ' --'] + $institutionList;
                    }
                   
                    $attr['type'] = 'chosenSelect';
                    $attr['onChangeReload'] = true;
                    $attr['attr']['multiple'] = false;
                    $attr['options'] = $institutionOptions;
                    $attr['attr']['required'] = true;
                }
            }
            return $attr;
        }
    }

    public function onUpdateFieldAcademicPeriodId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];

            if ((in_array($feature, ['Report.InstitutionInfrastructures','Report.InfrastructureNeeds', 'Report.InstitutionAssets', 'Report.Income', 'Report.Expenditure', 'Report.WashReports','Report.InfrastructureElectricities', 'Report.InfrastructureInternets', 'Report.InfrastructureTelephones'])
            )) {
                $AcademicPeriodTable = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
                $academicPeriodOptions = $AcademicPeriodTable->getYearList();
                $currentPeriod = $AcademicPeriodTable->getCurrent();

                $attr['options'] = $academicPeriodOptions;
                $attr['type'] = 'select';
                $attr['select'] = false;

                if (in_array($feature, ['Report.Meals','Report.MealDetails'])
                ) {
                    $attr['onChangeReload'] = true;
                }

                if (empty($request->getData($this->getAlias())['academic_period_id'])) {
                    $request->getData($this->getAlias())['academic_period_id'] = $currentPeriod;
                }
                return $attr;
            }
        }
    }

    public function onUpdateFieldAreaLevelId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];

            if ((in_array($feature, ['Report.InstitutionInfrastructures','Report.InfrastructureNeeds', 'Report.InstitutionAssets', 'Report.Income', 'Report.Expenditure', 'Report.WashReports', 'Report.InfrastructureElectricities', 'Report.InfrastructureInternets', 'Report.InfrastructureTelephones']))) {
                $Areas = TableRegistry::getTableLocator()->get('Area.AreaLevels');
                $entity = $attr['entity'];

                if ($action == 'add') {
                    $areaOptions = $Areas
                        ->find('list', ['keyField' => 'id', 'valueField' => 'name'])
                        ->order(['level'])
                        ->enableHydration(false);
                    $attr['type'] = 'chosenSelect';
                    $attr['attr']['multiple'] = false;
                    $attr['select'] = true;
                    $attr['options'] = ['' => '-- ' . __('Select') . ' --', '-1' => __('All Areas Level')] + $areaOptions->toArray();
                    $attr['onChangeReload'] = true;
                } else {
                    $attr['type'] = 'hidden';
                }
            }
        }
        
        return $attr;
    }

    public function onUpdateFieldAreaEducationId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {

        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];
            $areaLevelId = $this->request->getData($this->getAlias())['area_level_id'];//POCOR-6333
            if (in_array($feature, ['Report.InstitutionInfrastructures','Report.InfrastructureNeeds', 'Report.InstitutionAssets', 'Report.Income', 'Report.Expenditure', 'Report.WashReports', 'Report.InfrastructureElectricities', 'Report.InfrastructureInternets', 'Report.InfrastructureTelephones'])) {
                    $Areas = TableRegistry::getTableLocator()->get('Area.Areas');
                    $entity = $attr['entity'];

                    if ($action == 'add') {
                        $where = [];
                        
                        if ($areaLevelId != -1 && !empty($areaLevelId)) {
                            $where[$Areas->aliasField('area_level_id')] = $areaLevelId;
                        }
                        $areas = $Areas
                            ->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
                            ->where([$where])
                            ->order([$Areas->aliasField('order')]);
                        $areaOptions = $areas->toArray();
                        $attr['type'] = 'chosenSelect';
                        $attr['attr']['multiple'] = false;
                        $attr['select'] = true;
                        /*POCOR-6333 starts*/
                        if (count($areaOptions) > 1) {
                            $attr['options'] = ['' => '-- ' . __('Select') . ' --', '-1' => __('All Areas')] + $areaOptions;
                        } else {
                            $attr['options'] = ['' => '-- ' . __('Select') . ' --'] + $areaOptions;
                        }
                        /*POCOR-6333 ends*/
                        $attr['onChangeReload'] = true;
                    } else {
                        $attr['type'] = 'hidden';
                    }
            }
        }
        return $attr;
    }


    public function onExcelGetAccessibility(EventInterface $event, Entity $entity)
    {
        $accessibility = '';
        if($entity->land_infrastructure_accessibility == 1) {
            $accessibility ='Accessible';
        } else {
            $accessibility ='Not Accessible';
        }
        return $accessibility;
    }


   public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, $fields)
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
        $AreaLevelTbl = TableRegistry::getTableLocator()->get('Area.AreaLevels');
        $AreaLevelArr = $AreaLevelTbl->find()->select(['id','name'])->order(['id'=>'DESC'])->limit(2)->enableHydration(false)->toArray();

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
        $InfrastructureCustomFields = TableRegistry::getTableLocator()->get('Infrastructure.InfrastructureCustomFields');
        $customModules = TableRegistry::getTableLocator()->get('CustomField.CustomModules');
        $infrastructureCustomForms = TableRegistry::getTableLocator()->get('Infrastructure.InfrastructureCustomForms');
        $infrastructureCustomFormsFields = TableRegistry::getTableLocator()->get('Infrastructure.InfrastructureCustomFormsFields');
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

    /**
     * POCOR-9400
     * 
     * Made changes in the existing query as the academic_period_id column no longer exists. 
     * institution_land, institution_building , institution_floor, institution_room
     * Changes in conditions
     * 
     * */

    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query)
    {

        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;
        $institutionId = $requestData->institution_id;
        $infrastructureLevel = $requestData->infrastructure_level;
        $infrastructureType = $requestData->infrastructure_type;
        $institutionTypeId = $requestData->institution_type_id;
        $areaId = $requestData->area_education_id;
        $areaLevelId = $requestData->area_level_id; //POCOR-7794
        //$institutionLands = TableRegistry::getTableLocator()->get('Institution.InstitutionLands');
        $institutionFloors = TableRegistry::getTableLocator()->get('Institution.InstitutionFloors');
        $institutionBuildings = TableRegistry::getTableLocator()->get('Institution.InstitutionBuildings');
        $institutionRooms = TableRegistry::getTableLocator()->get('Institution.InstitutionRooms');
        $buildingTypes = TableRegistry::getTableLocator()->get('Infrastructure.BuildingTypes');
        $infrastructureCondition = TableRegistry::getTableLocator()->get('Infrastructure.InfrastructureConditions');
        $infrastructureStatus = TableRegistry::getTableLocator()->get('Infrastructure.InfrastructureStatuses');
        $institutionStatus = TableRegistry::getTableLocator()->get('Institution.InstitutionStatuses');
        $infrastructureOwnerships = TableRegistry::getTableLocator()->get('Institution.InfrastructureOwnerships');
        $infrastructureLevels = TableRegistry::getTableLocator()->get('Institution.InfrastructureLevels');
        $areas = TableRegistry::getTableLocator()->get('Area.Areas');
        //POCOR-9400 start
        if ($areaId != -1 && $areaId != '') {
            $areaIds = [];
            $allgetArea = $this->getChildren($selectedArea, $areaIds);
            $selectedArea1[]= $selectedArea;
            if(!empty($allgetArea)){
                $allselectedAreas = array_merge($selectedArea1, $allgetArea);
            }else{
                $allselectedAreas = $selectedArea1;
            }
                $conditions['Institutions.area_id IN'] = $allselectedAreas;
        } //POCOR-9400 end

        $institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');

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
        //POCOR-7794 start
        $areaList = [];
        if (
            $areaLevelId > 1 && $areaId > 1
        ) {
            $areaList = $this->getAreaList($areaLevelId, $areaId);
        } elseif ($areaLevelId > 1) {

            $areaList = $this->getAreaList($areaLevelId, 0);
        } elseif ($areaId > 1) {
            $areaList = $this->getAreaList(0, $areaId);
        }
        if (!empty($areaList)) {
            $conditions[$this->aliasField('area_id IN')] = $areaList;
        }
        //POCOR-7794 end

        $institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
        $institutionIds = $institutions->find('list', [
                                                    'keyField' => 'id',
                                                    'valueField' => 'id'
                                             ])
                            ->where(['institution_type_id IS' => $institutionTypeId])
                            ->toArray();

        if (!empty($institutionTypeId)) {
             $conditions['Institution'.$level.'.'.'institution_id IN'] = $institutionIds;

        }
        /*POCOR-6335 starts - applying academic period condition*/
        /*if (!empty($academicPeriodId)) {
             $conditions['Institution'.$level.'.'.'academic_period_id'] = $academicPeriodId;

        }*/
        if (!empty($academicPeriodId)) {
            $academicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriods');
            $academicPeriod  = $academicPeriods->get($academicPeriodId);
            $conditions['Institution'.$level.'.'.'start_year'] = $academicPeriod->start_year;
        }
        /*POCOR-633 ends*/
        if ($infrastructureLevel == 1 || $infrastructureLevel == 2) {
            $query
                    ->select(['land_infrastructure_code'=>'Institution'.$level.'.'.'code',
                        'land_infrastructure_name'=>'Institution'.$level.'.'.'name',
                        'institution_id'=>'Institution'.$level.'.'.'institution_id',
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
                        ->LeftJoin([$infrastructureCondition->getAlias() => $infrastructureCondition->getTable()], ['Institution'.$level.'.'.'infrastructure_condition_id = ' . $infrastructureCondition->aliasField('id'),
                        ])
                        ->LeftJoin([$infrastructureStatus->getAlias() => $infrastructureStatus->getTable()], [
                            'Institution'.$level.'.'.$type.'_status_id = ' . $infrastructureStatus->aliasField('id'),
                        ])
                        //POCOR-5698 two new columns added here
                        //status
                        ->LeftJoin(['Institutions' => $institutions->getTable()], [
                            'Institution'.$level.'.'.'institution_id = Institutions.id',
                        ])
                        ->LeftJoin([$areas->getAlias() => $areas->getTable()], [
                            'Institutions.area_id = ' . $areas->aliasField('id'),
                        ])
                        ->LeftJoin(['InstitutionStatuses' => $institutionStatus->getTable()], [
                            'InstitutionStatuses.id = Institutions.institution_status_id',
                        ])
                        //shift
                        ->LeftJoin(['InstitutionShifts' => 'institution_shifts'],[
                            'Institution'.$level.'.'.'institution_id = InstitutionShifts.institution_id',
                        ])
                        ->LeftJoin(['ShiftOptions' => 'shift_options'],[
                            'ShiftOptions.id = InstitutionShifts.shift_option_id'
                        ])
                        //POCOR-5698 two new columns ends here
                        ->LeftJoin([$infrastructureOwnerships->getAlias() => $infrastructureOwnerships->getTable()], [
                            'Institution'.$level.'.'.$type.'_status_id = ' . $infrastructureOwnerships->aliasField('id'),
                        ])
                    ->where($conditions)
                    ->distinct(['Institution' . $level . '.id']);
        }
        if ($infrastructureLevel == 3)
        {
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
                        ->LeftJoin([$infrastructureCondition->getAlias() => $infrastructureCondition->getTable()], ['Institution'.$level.'.'.'infrastructure_condition_id = ' . $infrastructureCondition->aliasField('id'),
                        ])
                        ->LeftJoin([$infrastructureStatus->getAlias() => $infrastructureStatus->getTable()], [
                            'Institution'.$level.'.'.$type.'_status_id = ' . $infrastructureStatus->aliasField('id'),
                        ])
                        //POCOR-5698 two new columns added here
                        //status
                        ->LeftJoin(['Institutions' => $institutions->getTable()], [
                            'Institution'.$level.'.'.'institution_id = Institutions.id',
                        ])
                        ->LeftJoin([$areas->getAlias() => $areas->getTable()], [
                            'Institutions.area_id = ' . $areas->aliasField('id'),
                        ])
                        ->LeftJoin(['InstitutionStatuses' => $institutionStatus->getTable()], [
                            'InstitutionStatuses.id = Institutions.institution_status_id',
                        ])
                        //shift
                        ->LeftJoin(['InstitutionShifts' => 'institution_shifts'],[
                            'Institution'.$level.'.'.'institution_id = InstitutionShifts.institution_id',
                        ])
                        ->LeftJoin(['ShiftOptions' => 'shift_options'],[
                            'ShiftOptions.id = InstitutionShifts.shift_option_id'
                        ])
                        //POCOR-5698 two new columns ends here
                        ->LeftJoin([$infrastructureOwnerships->getAlias() => $infrastructureOwnerships->getTable()], [
                            'Institution'.$level.'.'.$type.'_status_id = ' . $infrastructureOwnerships->aliasField('id'),
                        ])
                    ->where($conditions)
                    ->group(['Institution' . $level . '.id']) // Ensures one row per institution
                    ->distinct(['Institution' . $level . '.id']);
        }
        if ($infrastructureLevel == 4)
        {
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
                        'institution_buildings_name' => 'InstitutionBuildings.name',
                        'institution_floor_name' => 'InstitutionFloors.name',
                        //End POCOR-6731
                        ])
                ->innerJoin([$areas->getAlias() => $areas->getTable()],[
                   $this->aliasField('area_id = ') . $areas->aliasField('id'),
                ])


                ->LeftJoin(['InstitutionStatuses' => $institutionStatus->getTable()], [
                    'InstitutionStatuses.id = '. $this->aliasField('institution_status_id'),
                ])
                ->innerJoin(['AcademicPeriods' => 'academic_periods'], [
                    'AcademicPeriods.id' => $academicPeriodId
                ])
                ->LeftJoin(['Institution'.$level => 'institution_'.lcfirst($level)], [
                    'Institution'.$level.'.institution_id = ' . $this->aliasField('id')
                    // academic_period_id join removed
                ])
                ->LeftJoin(['InfrastructureTypes' => $type.'_types'], [
                        'InfrastructureTypes.id = ' . 'Institution'.$level.'.'.'room_type_id',
                    ])
                ->LeftJoin(['InstitutionFloors' => 'institution_floors'], [
                    'InstitutionFloors.id = Institution'.$level.'.institution_floor_id',
                    'InstitutionFloors.institution_id = ' . $this->aliasField('id')
                    // academic_period_id join removed
                ])
                ->LeftJoin(['InstitutionBuildings' => 'institution_buildings'], [
                    'InstitutionBuildings.id = InstitutionFloors.institution_building_id',
                    'InstitutionBuildings.institution_id = ' . $this->aliasField('id')
                    // academic_period_id join removed
                ])
                ->LeftJoin([$infrastructureCondition->getAlias() => $infrastructureCondition->getTable()], ['Institution'.$level.'.'.'infrastructure_condition_id = ' . $infrastructureCondition->aliasField('id'),
                    ])
                ->LeftJoin([$infrastructureStatus->getAlias() => $infrastructureStatus->getTable()], [
                        'Institution'.$level.'.'.$type.'_status_id = ' . $infrastructureStatus->aliasField('id'),
                    ])
                            //POCOR-5698 two new columns added here
                            //status
                ->LeftJoin([$infrastructureOwnerships->getAlias() => $infrastructureOwnerships->getTable()], [
                            'Institution'.$level.'.'.$type.'_status_id = ' . $infrastructureOwnerships->aliasField('id'),
                ])
                ->join([
                'shift_info' => [
                    'type' => 'left',
                    'table' => '( SELECT institution_shifts.institution_id, institution_shifts.academic_period_id, GROUP_CONCAT(shift_options.name) shift_options_name FROM institution_shifts INNER JOIN shift_options ON shift_options.id = institution_shifts.shift_option_id GROUP BY institution_shifts.academic_period_id, institution_shifts.institution_id )',
                    'conditions' => [
                        'shift_info.academic_period_id = AcademicPeriods.id',
                        'shift_info.institution_id = ' . $this->aliasField('id'),
                    ]
                ],
            ])
            ->where($conditions)
            ->group(['Institution' . $level . '.id']) 
            ->distinct(['Institution' . $level . '.id']);

        }
        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) use($type) {
            return $results->map(function ($row) use($type) {
                $areas1 = TableRegistry::getTableLocator()->get('Area.Areas');
                $areasData = $areas1
                            ->find()
                            ->where([$areas1->aliasField('code IS') => $row->area_code])
                            ->first();
                $row['region_code'] = '';
                $row['region_name'] = '';
                if($areasData->parent_id){ // POCOR-9070
                    $areas = TableRegistry::getTableLocator()->get('Area.Areas');
                    $areaLevels = TableRegistry::getTableLocator()->get('Area.AreaLevels');
                    $institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
                    $val = $areas
                                ->find()
                                ->select([
                                    $areas1->aliasField('code'),
                                    $areas1->aliasField('name'),
                                    ])
                                ->leftJoin(
                                    [$areaLevels->getAlias() => $areaLevels->getTable()],
                                    [
                                        $areas->aliasField('area_level_id  = ') . $areaLevels->aliasField('id')
                                    ]
                                )
                                ->leftJoin(
                                    [$institutions->getAlias() => $institutions->getTable()],
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

                $InfrastructureCustomFields = TableRegistry::getTableLocator()->get('Infrastructure.InfrastructureCustomFields');
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
                            $infrastructureCustomFieldOptions = TableRegistry::getTableLocator()->get('Infrastructure.InfrastructureCustomFieldOptions');
                            $infrastructureCustomFields = TableRegistry::getTableLocator()->get('Infrastructure.InfrastructureCustomFields');

                            $fieldValue = $infrastructureCustomFieldOptions->find()
                                ->select([
                                    $infrastructureCustomFieldOptions->aliasField('name')
                                ])
                                ->innerJoin(
                                    [$infrastructureCustomFields->getAlias() => $infrastructureCustomFields->getTable()],
                                    [
                                        $infrastructureCustomFields->aliasField('id') . ' = ' .
                                        $infrastructureCustomFieldOptions->aliasField('infrastructure_custom_field_id')
                                    ]
                                )
                                ->innerJoin(
                                    ['CustomFieldValues' => lcfirst($type) . '_custom_field_values'],
                                    [
                                        'CustomFieldValues.infrastructure_custom_field_id = ' .
                                            $infrastructureCustomFieldOptions->aliasField('infrastructure_custom_field_id'),
                                        'CustomFieldValues.number_value = ' .
                                            $infrastructureCustomFieldOptions->aliasField('id')
                                    ]
                                )
                                ->where([
                                    $infrastructureCustomFields->aliasField('field_type') => 'CHECKBOX',
                                    'CustomFieldValues.institution_' . lcfirst($type) . '_id' => $row['level_id']
                                ])
                                ->group([
                                    $infrastructureCustomFieldOptions->aliasField('name')
                                ])
                                ->toArray();


                            if (!empty($fieldValue)) {
                                $optVal = [];
                                foreach ($fieldValue as $numValue) {
                                    $optVal[] = $numValue->name;
                                }
                                $str = implode(',', $optVal);

                                if (!empty($data->custom_field_id)) {
                                    // safe to assign
                                    $row['custom_' . $data->custom_field_id] = $str;
                                }
                            }

                        }
                        if (!empty($data->number_value) && ($data->field_type != 'CHECKBOX')) {
                            //START :comment this code becuase its affect POCOR-6650
                            /*$optvalue = TableRegistry::getTableLocator()->get('infrastructure_custom_field_options');
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

    public function onUpdateFieldInfrastructureLevel(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];
            if (in_array(
                $feature,
                [
                    'Report.InstitutionInfrastructures'
                ]
            )) {

                $TypesTable = self::getDynamicTableInstance('Infrastructure.InfrastructureLevels');
                $typeOptions = $TypesTable
                    ->find('list')
                    ->toArray();

                $attr['type'] = 'select';
                $attr['onChangeReload'] = true;
                $attr['options'] = $typeOptions;
                $attr['attr']['required'] = true;
            }
            return $attr;
        }
    }

    public function onUpdateFieldInfrastructureType(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];
            if (in_array(
                $feature,
                [
                    //'Report.InstitutionInfrastructures'
                ]
            )) {

                $TypesTable = self::getDynamicTableInstance('building_types');
                $typeOptions = $TypesTable
                    ->find('list')
                    ->toArray();

                $attr['type'] = 'select';
                $attr['onChangeReload'] = true;
                $attr['options'] = ['0' => __('All Infrastructure Type')] + $typeOptions;
                //$attr['attr']['required'] = true;
            }
            return $attr;
        }
    }

    //POCOR-9400
    public function getChildren($id, $idArray) {
        $Areas = TableRegistry::getTableLocator()->get('Area.Areas');
        $result = $Areas->find()
                           ->where([
                               $Areas->aliasField('parent_id IS') => $id
                            ])
                             ->toArray();
       foreach ($result as $key => $value) {
            $idArray[] = $value['id'];
           $idArray = $this->getChildren($value['id'], $idArray);
        }
        return $idArray;
    }

    private static function getDynamicTableInstance(string $tableName): Table
    {
        // Parse plugin and table names if dot notation is used
        $locator = TableRegistry::getTableLocator();
        try {
            return $locator->get($tableName);
        } catch (\Exception $exception) {
        }
        if ($tableName == 'Institution.InstitutionStatuses') {
            $tableName = 'Institution.Statuses';
        }
        $parts = explode('.', $tableName);
        $plugin = count($parts) > 1 ? $parts[0] : null;
        $table = count($parts) > 1 ? $parts[1] : $parts[0];

        // Convert the table name to camel case as expected by CakePHP conventions
        $tableFullAlias = Inflector::camelize($tableName);
        $tableAlias = Inflector::camelize($table);

        // Create the fully qualified class name if a plugin is specified
        if ($plugin) {
            $className = $plugin . '\\Model\\Table\\' . $tableAlias . 'Table';
        } else {
            $className = 'App\\Model\\Table\\' . $tableAlias . 'Table';
        }
        // Check if the table instance already exists
        if (!$locator->exists($tableFullAlias)) {
            // Check if the specific table class exists
            if (!class_exists($className)) {
                $className = Table::class; // Fallback to generic Table class
            }

            // Configure a new table instance
            $locator->setConfig($tableAlias, [
                'className' => $className,
                'table' => $table,
                'alias' => $tableAlias,
            ]);
        }

        // Return the table instance
        return $locator->get($tableFullAlias);
    }

    public function onUpdateFieldWashType(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];
            if (in_array($feature, ['Report.WashReports'])) {
                $options = [
                    'All' => __('All'),   //POCOR-6732
                    'Hygiene' => __('Hygiene'),
                    'Sanitation' => __('Sanitation'),
                    'Sewage' => __('Sewage'),
                    'Waste' => __('Waste'),
                    'Water' => __('Water'),
                ];
                $attr['type'] = 'select';
                $attr['select'] = false;
                $attr['options'] = $options;
            } else {
                $attr['value'] = self::NO_FILTER;
            }
            return $attr;
        }
    }

    public
    function onUpdateFieldFromDate(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];

            if ((in_array($feature, ['Report.Income']))) {
                $attr['type'] = 'date';
                return $attr;
            }
            if ((in_array($feature, ['Report.Expenditure']))) {
                $attr['type'] = 'date';
                return $attr;
            }
        }
    }

    public
    function onUpdateFieldToDate(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];

            if ((in_array($feature, ['Report.Income']))) {
                $attr['type'] = 'date';
                return $attr;
            }
            if ((in_array($feature, ['Report.Expenditure']))) {
                $attr['type'] = 'date';
                return $attr;
            }
        }
    }

     public function onUpdateFieldReportStartDate(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        //POCOR-7665 refactured code to minimize errors
        $requestData = $this->request->getData($this->getAlias());
        $feature = isset($requestData['feature']) ? $requestData['feature'] : null;
        $selectedAcademicPeriodId = isset($requestData['academic_period_id']) ? $requestData['academic_period_id'] : null;
        if ($feature) {
            $attr['value'] = self::NO_FILTER;
            if ($selectedAcademicPeriodId) {
                $AcademicPeriods = self::getDynamicTableInstance('AcademicPeriod.AcademicPeriods');
                $selectedPeriod = $AcademicPeriods->get($selectedAcademicPeriodId);
            }
                
            if (in_array($feature, [
                'Report.InstitutionAssets'
            ])) {
                $attr['type'] = 'date';
                if ($requestData['report_start_date']) {
                    $attr['value'] = $requestData['report_start_date'];
                } else {
                    $currentDate = new \DateTime();
                    // Set the date to the first day of the year
                    $firstDayOfTheYear = $currentDate->setDate($currentDate->format('Y'), 1, 1);
                    // Format the result if needed
                    $attr['value'] = $firstDayOfTheYear;
                }
                $attr['onChangeReload'] = false;
            }
            return $attr;
        }
    }

    public
    function onUpdateFieldReportEndDate(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        //POCOR-7665 refactured code to minimize errors
        $requestData = $this->request->getData($this->getAlias());
        $feature = isset($requestData['feature']) ? $requestData['feature'] : null;
        $selectedAcademicPeriodId = isset($requestData['academic_period_id']) ? $requestData['academic_period_id'] : null;
        if ($feature) {
            $attr['value'] = self::NO_FILTER;
            if ($selectedAcademicPeriodId) {
                $AcademicPeriods = self::getDynamicTableInstance('AcademicPeriod.AcademicPeriods');
                $selectedPeriod = $AcademicPeriods->get($selectedAcademicPeriodId);
            }
            if (in_array($feature, [
                'Report.InstitutionAssets'
            ])) {
                $attr['type'] = 'date';
                if ($requestData['report_end_date']) {
                    $attr['value'] = $requestData['report_end_date'];
                } else {
                    $currentDate = new \DateTime();
                    // Set the date to the first day of the year
                    $lastDayOfTheYear = $currentDate->setDate($currentDate->format('Y'), 12, 31);
                    // Format the result if needed
                    $attr['value'] = $lastDayOfTheYear;
                }
                $attr['onChangeReload'] = false;
            }
            return $attr;
        }
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        $validator
            ->add('report_start_date', [
                'ruleCompareDate' => [
                    'rule' => ['compareDate', 'report_end_date', true],
                    'on' => function ($context) {
                        $feature = $context['data']['feature'];
                        return in_array($feature, [
                            'Report.InstitutionAssets',
                        ]);
                    }
                ],
                'ruleInAcademicPeriod' => [
                    'rule' => ['inAcademicPeriod', 'academic_period_id', []],
                    'on' => function ($context) {
                        $feature = $context['data']['feature'];
                        return in_array($feature, ['Report.InstitutionAssets']);
                    },
                    'message' => __('Report Start Date should be later than Academic Period Start Date')
                ],
            ]);

        $validator
            ->add('report_end_date', [
                'ruleInAcademicPeriod' => [
                    'rule' => ['inAcademicPeriod', 'academic_period_id', []],
                    'on' => function ($context) {
                        $feature = $context['data']['feature'];
                        return in_array($feature, ['Report.InstitutionAssets']);
                    },
                    'message' => __('Report End Date should be earlier than Academic Period End Date')
                ]
            ]);
        $validator = $validator
            ->notEmpty('area_level_id')
            ->notEmpty('area_education_id')
            ->notEmpty('institution_id');
        return $validator;
    }

    public function validationInstitutionInfrastructures(Validator $validator)
    {
        $validator = $this->validationDefault($validator);
        $validator = $validator
            //->notEmpty('institution_type_id')
            ->notEmpty('infrastructure_level');
        return $validator;
    }



    
}
