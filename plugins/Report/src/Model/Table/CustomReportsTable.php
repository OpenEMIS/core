<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;

class CustomReportsTable extends AppTable
{
    // format types
    const CSV = 1;
    const XLSX = 2;

    private $formatOptions = [];

	public function initialize(array $config)
	{
		$this->table('reports');
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

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ExcelTemplates.Model.onExcelTemplateBeforeGenerate'] = 'onExcelTemplateBeforeGenerate';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseQueryVariables'] = 'onExcelTemplateInitialiseQueryVariables';
        $events['ExcelTemplates.Model.onCsvBeforeGenerate'] = 'onCsvBeforeGenerate';

        return $events;
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        return $validator->notEmpty('feature');
    }

	public function beforeAction(Event $event)
	{
		$controllerName = $this->controller->name;
		$reportName = __('Custom');

		$this->controller->Navigation->substituteCrumb($this->alias(), $reportName);
		$this->controller->set('contentHeader', __($controllerName).' - '.$reportName);
	}

	public function addBeforeAction(Event $event)
    {
        $this->fields = [];
        $this->ControllerAction->field('feature', ['type' => 'select', 'select' => false]);
       // $this->ControllerAction->field('format');

        if (isset($this->request->data[$this->alias()]['feature'])) {
            $id = $this->request->data[$this->alias()]['feature'];
            $customReportData = $this->find()
                ->where([$this->aliasField('id') => $id])
                ->first();

            // filters
            if (!empty($customReportData) && !empty($customReportData->filter)) {
                $validator = $this->validator();
                $filters = json_decode($customReportData->filter, true);

                // academic period filter
                if (array_key_exists('academic_period_id', $filters)) {
                    // add validation
                    $validator->notEmpty('academic_period_id');
                    $this->ControllerAction->field('academic_period_id');
                    unset($filters['academic_period_id']);
                }

                //START: POCOR-7069
                // Institution Type filter
                if (array_key_exists('institution_type_id', $filters)) {
                    // add validation
                    $validator->notEmpty('institution_type_id');
                    $this->ControllerAction->field('institution_type_id');
                    unset($filters['institution_type_id']);
                }
                // Institution  filter
                if (array_key_exists('institution_id', $filters)) {
                    // add validation
                    $validator->notEmpty('institution_id');
                    $this->ControllerAction->field('institution_id');
                    unset($filters['institution_id']);
                }
                // edication grade filter
                if (array_key_exists('education_grade_id', $filters)) {
                    // add validation
                    $validator->notEmpty('education_grade_id');
                    $this->ControllerAction->field('education_grade_id');
                    unset($filters['education_grade_id']);
                }
                
                // education subject filter
                if (array_key_exists('education_subject_id', $filters)) {
                    // add validation
                    $validator->notEmpty('education_subject_id');
                    $this->ControllerAction->field('education_subject_id');
                    unset($filters['education_subject_id']);
                }
                //END: POCOR-7069

                $this->ControllerAction->field('format');

                if (isset($this->request->data["submit"]) && $this->request->data["submit"] == "academic_period_id") {
                    $toReset = true;
                } else {
                    $toReset = false;
                }

                // other filters
                foreach ($filters as $field => $filterData) {
                    if ($toReset) {
                        unset($this->request->data[$this->alias()][$field]);
                    }
                    if (isset($this->request->data["submit"]) && $field == $this->request->data["submit"]) {
                        $toReset = true;
                    }

                    $fieldType = array_key_exists('fieldType', $filterData) ? $filterData['fieldType'] : 'select';
                    $fieldParams = [];
                    $fieldParams['type'] = $fieldType;

                    if ($fieldType == 'select' || $fieldType == 'chosenSelect') {
                        // get options
                        $queryParams = $this->request->data[$this->alias()];
                        $queryParams['user_id'] = $this->Auth->user('id');
                        $queryParams['super_admin'] = $this->Auth->user('super_admin');
                        $byaccess = false;
                        $toSql = false;
                        $options = $this->buildQuery($filterData, $queryParams, $byaccess, $toSql);

                        // add additional options
                        if (array_key_exists('options', $filterData)) {
                            if (array_key_exists('options_condition', $filterData)) {
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

                        if (!(isset($this->request->data[$this->alias()][$field]))) {
                            $this->request->data[$this->alias()][$field] = key($options);
                        }
                    }

                    // add validation for fields
                    $validate = array_key_exists('validate', $filterData) ? filter_var($filterData['validate'], FILTER_VALIDATE_BOOLEAN) : true;
                    if ($validate) {
                        $fieldParams['required'] = true;
                        $validator->notEmpty($field);
                    }

                    $this->ControllerAction->field($field, $fieldParams);
                }
            }
        }
    }

	public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request)
    {
        ini_set('memory_limit', '-1');
        if ($action == 'add') {
            $queryParams = isset($this->request->data[$this->alias()]) ? $this->request->data[$this->alias()] : [];
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
            if (!(isset($this->request->data[$this->alias()]['feature']))) {
                $option = $attr['options'];
                reset($option);
                $this->request->data[$this->alias()]['feature'] = key($option);
            }
            return $attr;
        }
    }

    public function onUpdateFieldFormat(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            if (isset($this->request->data[$this->alias()]['feature']) && !empty($this->request->data[$this->alias()]['feature'])) {
                $reportId = $this->request->data[$this->alias()]['feature'];
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
    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
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

    public function onExcelTemplateBeforeGenerate(Event $event, array $params, ArrayObject $extra)
    {
        $str = $this->get($params['feature'])->name;
        $reportName = str_replace(' ', '_', $str);
        $this->behaviors()->get('ExcelReport')->config([
            'filename' => $reportName
        ]);
    }

    public function onExcelTemplateInitialiseQueryVariables(Event $event, array $params, ArrayObject $extra)
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

    public function onCsvBeforeGenerate(Event $event, ArrayObject $settings)
    {
        $params = $settings['requestQuery'];
        $customReportData = $this->get($params['feature']);
		
		if(!empty($params['start_date'])) {
			$params['start_date'] = date("Y-m-d", strtotime($params['start_date']));	
		}
		if(!empty($params['end_date'])) {
			$params['end_date'] = date("Y-m-d", strtotime($params['end_date']));	
		}
		
        if (array_key_exists('requestQuery', $settings)) {
            $jsonQuery = json_decode($customReportData->query, true);

            // csvBehavior can only can handle one query
            $obj = current($jsonQuery);
            $byaccess = true;
            $toSql = true;
            $settings['sql'] = $this->buildQuery($obj, $params, $byaccess, $toSql);
        }
    }

    /*POCOR-6451 starts- institution filter*/
    public function onUpdateFieldInstitutionId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $InstitutionsTable = TableRegistry::get('Institution.Institutions');
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
    public function onUpdateFieldInstitutionTypeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $TypesTable = TableRegistry::get('Institution.Types');
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
    public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
            $periodOptions = $AcademicPeriods->getYearList(['isEditable' => true]);
            $selectedPeriod = $AcademicPeriods->getCurrent();

            $selectedPeriod = $request->data['CustomReports']['academic_period_id'];
            $institutionId = $request->data['CustomReports']['institution_id'];
            $institutionTypeId = $request->data['CustomReports']['institution_type_id'];
            $EducationGrades = TableRegistry::get('Education.EducationGrades');
            $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
            $grades = TableRegistry::get('Institution.InstitutionGrades');
            $institutions = TableRegistry::get('Institution.Institutions');
            
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
                            ->LeftJoin([$grades->alias() => $grades->table()],[
                                $grades->aliasField('education_grade_id').' = ' . $EducationGrades->aliasField('id')
                            ])
                            ->LeftJoin([$institutions->alias() => $institutions->table()],[
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
    public function onUpdateFieldEducationSubjectId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $selectedPeriod = $request->data['CustomReports']['academic_period_id'];
            $institutionId = $request->data['CustomReports']['institution_id'];
            $institutionTypeId = $request->data['CustomReports']['institution_type_id'];
            $educationGradeId = $request->data['CustomReports']['education_grade_id'];
            $EducationGrades = TableRegistry::get('Education.EducationGrades');
            $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
            $grades = TableRegistry::get('Institution.InstitutionGrades');
            $EducationSubjects = TableRegistry::get('Education.EducationSubjects');

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
}
