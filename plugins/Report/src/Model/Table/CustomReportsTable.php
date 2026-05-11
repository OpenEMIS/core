<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\EventInterface;
use Cake\Network\Request;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use Cake\Http\ServerRequest;

class CustomReportsTable extends AppTable
{
    // format types
    const CSV = 1;
    const XLSX = 2;

    private $formatOptions = [];

	public function initialize(array $config): void
	{
		$this->setTable('reports');
		parent::initialize($config);

        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Report.CustomReport');
        $this->addBehavior('CustomExcel.ExcelReport', [
            'templateTable' => 'Report.CustomReports',
            'templateTableKey' => 'feature',
            'download' => false,
            'purge' => false,
            'variableSource' => 'database'
        ]);
        $this->addBehavior('Report.Csv');

        $this->formatOptions = [
            self::CSV => ['key' => 'csv', 'value' => 'CSV'],
            self::XLSX => ['key'=> 'xlsx', 'value' => 'Excel']
        ];
	}

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['ExcelTemplates.Model.onExcelTemplateBeforeGenerate'] = 'onExcelTemplateBeforeGenerate';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseQueryVariables'] = 'onExcelTemplateInitialiseQueryVariables';
        $events['ExcelTemplates.Model.onCsvBeforeGenerate'] = 'onCsvBeforeGenerate';

        return $events;
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        return $validator->notEmpty('feature');
    }

	public function beforeAction(EventInterface $event)
	{
		$controllerName = $this->controller->getName();
		$reportName = __('Custom');

		$this->controller->Navigation->substituteCrumb($this->getAlias(), $reportName);
		$this->controller->set('contentHeader', __($controllerName).' - '.$reportName);
	}

	public function addBeforeAction(EventInterface $event)
    {
        $this->fields = [];
        $this->ControllerAction->field('feature', ['type' => 'select', 'select' => false]);
       // $this->ControllerAction->field('format');
        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $id = $this->request->getData($this->getAlias())['feature'];
            $customReportData = $this->find()
                ->where([$this->aliasField('id') => $id])
                ->first();
            // filters
            // if (!empty($customReportData) && !empty($customReportData->filter)) {
            if (!empty($customReportData)) {
                $validator = $this->getValidator();
                $filters = json_decode($customReportData->filter, true);
                // academic period filter
                if (isset($filters['academic_period_id'])) {
                    // add validation
                    $validator->notEmpty('academic_period_id');
                    $this->ControllerAction->field('academic_period_id');
                    unset($filters['academic_period_id']);
                }

                //START: POCOR-7069
                // Institution Type filter
                if (isset($filters['institution_type_id'])) {
                    // add validation
                    $validator->notEmpty('institution_type_id');
                    $this->ControllerAction->field('institution_type_id');
                    unset($filters['institution_type_id']);
                }
                // Institution  filter
                if (isset($filters['institution_id'])) {
                    // add validation
                    $validator->notEmpty('institution_id');
                    $this->ControllerAction->field('institution_id');
                    unset($filters['institution_id']);
                }
                // edication grade filter
                if (isset($filters['education_grade_id'])) {
                    // add validation
                    $validator->notEmpty('education_grade_id');
                    $this->ControllerAction->field('education_grade_id');
                    unset($filters['education_grade_id']);
                }
                // education subject filter
                if (isset($filters['education_subject_id'])) {
                    // add validation
                    $validator->notEmpty('education_subject_id');
                    $this->ControllerAction->field('education_subject_id');
                    unset($filters['education_subject_id']);
                }
                //END: POCOR-7069

                $this->ControllerAction->field('format');
                // if (isset($this->request->data["submit"]) && $this->request->data["submit"] == "academic_period_id") {
                if (isset($this->request->getData()['submit']) && $this->request->getData()['CustomReports']['academic_period_id']) {
                    $toReset = true;
                } else {
                    $toReset = false;
                }
                // other filters
                foreach ($filters as $field => $filterData) {
                    if ($toReset) {
                        unset($this->request->getData($this->getAlias())[$field]);
                    }
                    if (isset($this->request->getData()['submit']) && $field == $this->request->getData()['submit']) {
                        $toReset = true;
                    }

                    $fieldType = isset($filterData['fieldType']) ? $filterData['fieldType'] : 'select';
                    $fieldParams = [];
                    $fieldParams['type'] = $fieldType;

                    if ($fieldType == 'select' || $fieldType == 'chosenSelect') {
                        // get options
                        $queryParams = $this->request->getData($this->getAlias());
                        $queryParams['user_id'] = $this->Auth->user('id');
                        $queryParams['super_admin'] = $this->Auth->user('super_admin');
                        $byaccess = false;
                        $toSql = false;
                        $options = $this->buildQuery($filterData, $queryParams, $byaccess, $toSql);

                        // add additional options
                        if (isset($filterData['options'])) {
                            if (isset($filterData['options_condition'])) {
                                // only allow options if conditions met
                                if ($this->checkOptionCondition($filterData["options_condition"], $queryParams)) {
                                    foreach ($filterData['options'] as $value => $option) {
                                        if ($value == -1) {
                                            $value = "0 OR (0=0)";
                                        }
                                        $options = array($value => $option) + $options;
                                    }
                                }
                            } else { // if no condition, allow options
                                $options = $filterData['options'] + $options;
                            }
                        }

                        // set field parameters
                        $fieldParams['options'] = $options;
                        $fieldParams['select'] = false;
                        $fieldParams['onChangeReload'] = $field;
                        if ($fieldType == 'chosenSelect') {
                            $fieldParams['attr'] = ['multiple' => false];
                        }

                        if (!(isset($this->request->getData($this->getAlias())[$field]))) {
                            $this->request->getData($this->getAlias())[$field] = key($options);
                        }
                    }

                    // add validation for fields
                    $validate = isset($filterData['validate']) ? filter_var($filterData['validate'], FILTER_VALIDATE_BOOLEAN) : true;
                    if ($validate) {
                        $fieldParams['required'] = true;
                        $validator->notEmpty($field);
                    }

                    $this->ControllerAction->field($field, $fieldParams);
                }
            }
        }
    }

	public function onUpdateFieldFeature(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        ini_set('memory_limit', '-1');
        if ($action == 'add') {
            $queryParams = (null !== $this->request->getData($this->getAlias())) ? $this->request->getData($this->getAlias()) : [];
            $queryParams['user_id'] = $this->Auth->user('id');
            $queryParams['super_admin'] = $this->Auth->user('super_admin');

            $customReports = $this
                ->find(
                    'list',
                    ["valueField" => function ($row) {
                            return $row;
                    }]
                )
                ->order('name')
                ->toArray();

            // for translation
            $reportOptions = [];
            foreach ($customReports as $key => $customReport) {
                if (!$queryParams['super_admin'] // if super admin, allow option
                    && $customReport->conditions  // only check condition if field exist
                    && !$this->checkOptionCondition(json_decode($customReport->conditions, true), $queryParams)
                ) {
                    // skip option
                    continue;
                }
                $reportOptions[$key] = __($customReport->name);
            }

            $attr['options'] = $reportOptions;
            $attr['onChangeReload'] = true;
            $option = $this->controller->getFeatureOptions($this->getAlias());
            if (!(isset($this->request->getData($this->getAlias())['feature']))) {
                $option = $attr['options'];
                reset($option);
                $defaultFeatureValue = key($option);
                $this->request = $this->request->withData($this->getAlias() . '.feature', $defaultFeatureValue);
            }
            return $attr;
        }
    }

    public function onUpdateFieldFormat(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            if (isset($this->request->getData($this->getAlias())['feature']) && !empty($this->request->getData($this->getAlias())['feature'])) {
                $reportId = $this->request->getData($this->getAlias())['feature'];
                $format = $this->get($reportId)->format;

                $key = $this->formatOptions[$format]['key'];
                $value = $this->formatOptions[$format]['value'];
            } else {
                $key = '';
                $value = '';
            }

            $attr['value'] = $key;
            $attr['attr']['value'] = $value;
            $attr['type'] = 'readonly';
            return $attr;
        }
    }

    // academic period filter
    public function onUpdateFieldAcademicPeriodId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $AcademicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
            $periodOptions = $AcademicPeriods->getYearList(['isEditable' => true]);
            $selectedPeriod = $AcademicPeriods->getCurrent();

            $attr['onChangeReload'] = true;
            $attr['options'] = $periodOptions;
            // $attr['default'] = $selectedPeriod; //POCOR-7241
            $attr['type'] = 'select';
            $attr['select'] = true; //POCOR-7241
            $attr['required'] = true;
            return $attr;
        }
    }

    public function onExcelTemplateBeforeGenerate(EventInterface $event, array $params, ArrayObject $extra)
    {
        $str = $this->get($params['feature'])->name;
        $reportName = str_replace(' ', '_', $str);
        $this->behaviors()->get('ExcelReport')->setConfig([
            'filename' => $reportName
        ]);
    }

    public function onExcelTemplateInitialiseQueryVariables(EventInterface $event, array $params, ArrayObject $extra)
    {
        // get json query from reports database table
        $customReportData = $this->get($params['feature']);
        $jsonQuery = json_decode($customReportData->query, true);
        $variables = new ArrayObject([]);
        foreach($jsonQuery as $key => $obj) {
            $entity = $this->buildQuery($obj, $params, false);
            $variables[$key] = $entity;
        }

        return $variables;
    }

    public function onCsvBeforeGenerate(EventInterface $event, ArrayObject $settings)
    {
        $params = $settings['requestQuery'];
        $customReportData = $this->get($params['feature']);

		if(!empty($params['start_date'])) {
			$params['start_date'] = date("Y-m-d", strtotime($params['start_date']));
		}
		if(!empty($params['end_date'])) {
			$params['end_date'] = date("Y-m-d", strtotime($params['end_date']));
		}

        //if (isset($settings['requestQuery'])) {
        if (isset($settings['requestQuery'])) {    //POCOR-8126
            $jsonQuery = json_decode($customReportData->query, true);

            // csvBehavior can only can handle one query
            $obj = current($jsonQuery);
            $byaccess = true;
            $toSql = true;
            $settings['sql'] = $this->buildQuery($obj, $params, $byaccess, $toSql);
        }
    }

    /*POCOR-6451 starts- institution filter*/
    public function onUpdateFieldInstitutionId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $InstitutionsTable = TableRegistry::getTableLocator()->get('Institution.Institutions');
            $institutionTypeId = $request->data['CustomReports']['institution_type_id'];
            $institutionQuery = $InstitutionsTable
                                ->find('list', [
                                    'keyField' => 'id',
                                    'valueField' => 'code_name'
                                ])
                                ->where([$InstitutionsTable->aliasField('institution_type_id')=>$institutionTypeId])
                                ->order([$InstitutionsTable->aliasField('name') => 'ASC']);

            $superAdmin = $this->Auth->user('super_admin');
            if (!$superAdmin) { // if user is not super admin, the list will be filtered
                $userId = $this->Auth->user('id');
                $institutionQuery->find('byAccess', ['userId' => $userId]);
            }

            $institutionList = $institutionQuery->toArray();

            $attr['onChangeReload'] = true;
            if (count($institutionList) > 1) {
                //$attr['options'] = [0 => __('All Institutions')] + $institutionList;
                $attr['options'] =  $institutionList;
            } else {
                $attr['options'] = $institutionList;
            }
            $attr['type'] = 'select';
            $attr['select'] = true;
            $attr['required'] = true;
            return $attr;
        }
    }
    /*POCOR-6451 ends*/

    // Institution Type filter POCOR-7069
    public function onUpdateFieldInstitutionTypeId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $TypesTable = TableRegistry::getTableLocator()->get('Institution.Types');
            $typeOptions = $TypesTable
                    ->find('list')
                    ->find('visible')
                    ->find('order')
                    ->toArray();
            $attr['onChangeReload'] = true;
            $attr['options'] = $typeOptions;
            $attr['type'] = 'select';
            $attr['select'] = true;
            $attr['required'] = true;
            return $attr;
        }
    }

    // POCOR-7096 education grade filter
    public function onUpdateFieldEducationGradeId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $AcademicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
            $periodOptions = $AcademicPeriods->getYearList(['isEditable' => true]);
            $selectedPeriod = $AcademicPeriods->getCurrent();

            $selectedPeriod = $request->data['CustomReports']['academic_period_id'];
            $institutionId = $request->data['CustomReports']['institution_id'];
            $institutionTypeId = $request->data['CustomReports']['institution_type_id'];
            $EducationGrades = TableRegistry::getTableLocator()->get('Education.EducationGrades');
            $InstitutionGrades = TableRegistry::getTableLocator()->get('Institution.InstitutionGrades');
            $grades = TableRegistry::getTableLocator()->get('Institution.InstitutionGrades');
            $institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');

            //POCOR-7178 start
            $conditions = [];
            if (!empty($selectedPeriod)) {
            $conditions['EducationSystems.academic_period_id'] = $selectedPeriod;
            }
            if (!empty($institutionId) && $institutionId > 0) {
                $conditions[$grades->aliasField('institution_id')] = $institutionId;
            }
            if (!empty($institutionTypeId)) {
                $conditions[$institutions->aliasField('institution_type_id')] = $institutionTypeId;
            }
            //POCOR-7178 end
            if(!empty($selectedPeriod)){ // POCOR-7241
                $periodGrades = $EducationGrades->find('list', ['keyField' => 'id',
                                'valueField' => 'programme_grade_name'])
                            ->find('visible')
                            ->contain(['EducationProgrammes.EducationCycles.EducationLevels.EducationSystems'])
                            ->LeftJoin([$grades->getAlias() => $grades->getTable()],[
                                $grades->aliasField('education_grade_id').' = ' . $EducationGrades->aliasField('id')
                            ])
                            ->LeftJoin([$institutions->getAlias() => $institutions->getTable()],[
                                $institutions->aliasField('id').' = ' . $grades->aliasField('institution_id')
                            ])
                            ->where($conditions)
                            ->order([$EducationGrades->aliasField('id')])
                            ->toArray();
            }

            $attr['onChangeReload'] = true;
            $attr['options'] = $periodGrades;
            $attr['type'] = 'select';
            $attr['select'] = true;
            $attr['required'] = true;
            return $attr;
        }
    }

    // POCOR-7069 education sujbect filter
    public function onUpdateFieldEducationSubjectId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $selectedPeriod = $request->data['CustomReports']['academic_period_id'];
            $institutionId = $request->data['CustomReports']['institution_id'];
            $institutionTypeId = $request->data['CustomReports']['institution_type_id'];
            $educationGradeId = $request->data['CustomReports']['education_grade_id'];
            $EducationGrades = TableRegistry::getTableLocator()->get('Education.EducationGrades');
            $InstitutionGrades = TableRegistry::getTableLocator()->get('Institution.InstitutionGrades');
            $grades = TableRegistry::getTableLocator()->get('Institution.InstitutionGrades');
            $EducationSubjects = TableRegistry::getTableLocator()->get('Education.EducationSubjects');

            $subjects = $EducationSubjects->find()
                    ->find('list')
                    ->find('visible')
                    ->innerJoinWith('EducationGrades')
                    ->where(['EducationGrades.id' => $educationGradeId])
                    ->order([$EducationSubjects->aliasField('order')])
                    ->toArray();

            $attr['onChangeReload'] = true;
            $attr['options'] = $subjects;
            $attr['type'] = 'select';
            $attr['select'] = true;
            $attr['required'] = true;
            return $attr;
        }
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'feature':
                return __('Feature');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
    //POCOR-9600 Start
    public function onUpdateFieldAreaId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $Area = TableRegistry::getTableLocator()->get('Area.Areas');
            $areaOptions = $Area->find('list', ['keyField' => 'id',
                                'valueField' => 'name'])
                            ->find('visible')
                            
                            ->order([$Area->aliasField('id')])
                            ->toArray();

            $attr['onChangeReload'] = true;
            $attr['options'] = $areaOptions;
            $attr['type'] = 'select';
            $attr['required'] = true;
            return $attr;
        }
    }
    //POCOR-9600 End
}
