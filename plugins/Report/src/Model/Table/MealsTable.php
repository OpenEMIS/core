<?php
//POCOR-9267 Starts
namespace Report\Model\Table;

use ArrayObject;
use ZipArchive;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\EventInterface;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use PDOException;
use Cake\Http\ServerRequest;
use Cake\I18n\FrozenTime;//POCOR-9268
use Cake\I18n\Time;//POCOR-9268
use Cake\ORM\Table;//POCOR-9268

class MealsTable extends AppTable
{
    const NO_FILTER = 0;//POCOR-9268

    public function initialize(array $config): void
    {
        $this->setTable('institution_meal_students');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('AreaLevels', ['className' => 'Area.AreaLevels']);
        $this->belongsTo('Areas', ['className' => 'Area.Areas']);
        $this->belongsTo('AreaAdministratives', ['className' => 'Area.AreaAdministratives']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('SecurityUsers', ['className' => 'Security.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses', 'joinType' => 'INNER']);
        
        $this->addBehavior('Excel', ['pages' => false]);
        $this->addBehavior('Report.ReportList');
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.downloadAll'] = 'downloadAll';
        return $events;
    }

    public function beforeAction(EventInterface $event)
    {
        $this->fields = [];
        $this->ControllerAction->field('feature', ['select' => false]);
        $this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);
        $this->ControllerAction->field('area_level_id', ['type' => 'hidden']);
        $this->ControllerAction->field('area_education_id', ['type' => 'hidden', 'attr' => ['required' => true]]);
        $this->ControllerAction->field('institution_id', ['type' => 'hidden']);
        $this->ControllerAction->field('report_start_date', ['type' => 'hidden']);//POCOR-9268
        $this->ControllerAction->field('report_end_date', ['type' => 'hidden']);//POCOR-9268
        $this->ControllerAction->field('format');
    }

    public function addBeforePatch(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        if ($data[$this->getAlias()]['feature'] == 'Report.MealSummary') {
            $options['validate'] = 'MealSummary';
        }else if($data[$this->getAlias()]['feature'] == 'Report.MealDetails'){//POCOR-9268
            $options['validate'] = 'MealDetails';
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
    //POCOR-9268 Starts
    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        // validation for meals details feature
        $validator
            ->add('report_start_date', [
                'ruleCompareDate' => [
                    'rule' => ['compareDate', 'report_end_date', true],
                    'on' => function ($context) {
                        $feature = $context['data']['feature'];
                        return in_array($feature, ['Report.MealDetails']);
                    }
                ],
                'ruleInAcademicPeriod' => [
                    'rule' => ['inAcademicPeriod', 'academic_period_id', []],
                    'on' => function ($context) {
                        $feature = $context['data']['feature'];
                        return in_array($feature, ['Report.MealDetails']);
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
                        return in_array($feature, ['Report.MealDetails']);
                    },
                    'message' => __('Report End Date should be earlier than Academic Period End Date')
                ],
                // 'ruleForOneMonthDate' => [
                //     'rule' => ['forOneMonthDate'],
                //     'on' => function ($context) {
                //         $feature = $context['data']['feature'];
                //         return in_array($feature, ['Report.MealDetails']);
                //     },
                //     'message' => __('Date range should be one month only')
                // ]
            ]);

        return $validator;
    }//POCOR-9268 Ends

    public function validationMealSummary(Validator $validator)
    {
        $validator = $this->validationDefault($validator);
        $validator = $validator
            ->notEmpty('institution_id')
            ->notEmpty('area_education_id');
        return $validator;
    }
    //POCOR-9268 Starts
    public function validationMealDetails(Validator $validator)
    {
        $validator = $this->validationDefault($validator);
        $validator = $validator
            ->notEmpty('institution_id')
            ->notEmpty('area_education_id');
        return $validator;
    }//POCOR-9268 Ends

    function array_flatten($array) {
        if (!is_array($array)) {
            return false;
        }
        $result = array();
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $result = array_merge($result, $this->array_flatten($value));
            } else {
                $result = array_merge($result, array($key => $value));
            }
        }
        return $result;
    }

    public function onUpdateFieldInstitutionId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $areaId = $request->getData($this->getAlias())['area_education_id'];
        $InstitutionsTable = TableRegistry::getTableLocator()->get('Institution.Institutions');
        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];

            if (in_array($feature, ['Report.MealSummary','Report.MealDetails'])) {
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

                    if (in_array($feature, ['Report.MealSummary']) && count($institutionList) > 1) {
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

            if ((in_array($feature, ['Report.MealSummary','Report.MealDetails'])
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

            if ((in_array($feature, ['Report.MealSummary','Report.MealDetails']))) {
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
            if (in_array($feature, ['Report.MealSummary','Report.MealDetails'])) {
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

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'feature':
                return __('Feature');
            case 'format':
                return __('Format');
            case 'academic_period_id':
                return __('Academic Period');
            case 'area_level_id':
                return __('Area Level');
            case 'institution_id':
                return __('Institution');
            case 'education_grade_id':
                return __('Education Grade');
            case 'education_subject_id':
                return __('Education Subject');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function getChildren($id, $idArray) {
        $Areas = TableRegistry::getTableLocator()->get('Area.Areas');
        $result = $Areas->find()
                           ->where([
                               $Areas->aliasField('parent_id') => $id
                            ]) 
                             ->toArray();
       foreach ($result as $key => $value) {
            $idArray[] = $value['id'];
           $idArray = $this->getChildren($value['id'], $idArray);
        }
        return $idArray;
    }
    //POCOR-9268 Starts
    public function onUpdateFieldReportStartDate(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $requestData = $this->request->getData($this->getAlias());
        $feature = isset($requestData['feature']) ? $requestData['feature'] : null;
        $selectedAcademicPeriodId = isset($requestData['academic_period_id']) ? $requestData['academic_period_id'] : null;
        if ($feature) {
            $attr['value'] = self::NO_FILTER;
            if ($selectedAcademicPeriodId) {
                $AcademicPeriods = self::getDynamicTableInstance('AcademicPeriod.AcademicPeriods');
                $selectedPeriod = $AcademicPeriods->get($selectedAcademicPeriodId);
                if (in_array($feature, [
                    'Report.MealDetails'
                ])
                ) {
                    $attr['type'] = 'date';
                    $attr['date_options']['startDate'] = ($selectedPeriod->start_date)->format('d-m-Y');
                    $attr['date_options']['endDate'] = ($selectedPeriod->end_date)->format('d-m-Y');
                    $attr['value'] = $selectedPeriod->start_date;
                }
            }
            return $attr;
        }
    }

    public function onUpdateFieldReportEndDate(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $requestData = $this->request->getData($this->getAlias());
        $feature = isset($requestData['feature']) ? $requestData['feature'] : null;
        $selectedAcademicPeriodId = isset($requestData['academic_period_id']) ? $requestData['academic_period_id'] : null;
        if ($feature) {
            $attr['value'] = self::NO_FILTER;
            if ($selectedAcademicPeriodId) {
                $AcademicPeriods = self::getDynamicTableInstance('AcademicPeriod.AcademicPeriods');
                $selectedPeriod = $AcademicPeriods->get($selectedAcademicPeriodId);
                if (in_array($feature, [
                    'Report.MealDetails'
                ])
                ) {
                    $attr['type'] = 'date';
                    $attr['date_options']['startDate'] = ($selectedPeriod->start_date)->format('d-m-Y');
                    $attr['date_options']['endDate'] = ($selectedPeriod->end_date)->format('d-m-Y');
                    $attr['value'] = $selectedPeriod->end_date;
                }
            }
            return $attr;
        }
    }

    /**
     * Get a dynamic table instance with all associations.
     *
     * @param string $tableName
     * @return \Cake\ORM\Table
     */
    private static function getDynamicTableInstance(string $tableName): Table
    {
        // Parse plugin and table names if dot notation is used
        $locator = TableRegistry::getTableLocator();
        try {
            return $locator->get($tableName);
        } catch (\Exception $exception) {

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
    }//POCOR-9268 Ends
}
