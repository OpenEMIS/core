<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\Http\ServerRequest;
use App\Model\Table\AppTable;

class InstitutionStatisticsTable extends AppTable
{
	// format types
	const CSV = 1;
	const XLSX = 2;

	private $formatOptions = [];

	public function initialize(array $config): void
    {
        $this->setTable('institution_statistics');
        parent::initialize($config);
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Report.InstitutionStatistics');
        $this->addBehavior('CustomExcel.ExcelReport', [
            'templateTable' => 'Institution.InstitutionStatistics',
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
        $this->addBehavior('ControllerAction.QueryString');
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['ExcelTemplates.Model.onExcelTemplateBeforeGenerate'] = 'onExcelTemplateBeforeGenerate';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseQueryVariables'] = 'onExcelTemplateInitialiseQueryVariables';
        $events['ExcelTemplates.Model.onCsvBeforeGenerate'] = 'onCsvBeforeGenerate';
        $events['Model.custom.onUpdateToolbarButtons'] = 'onUpdateToolbarButtons';

        return $events;
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        return $validator->notEmpty('feature');
    }

	public function beforeAction(Event $event)
	{
		$controllerName = $this->controller->getName();
		$reportName = __('Statistics');
        /*POCOR-6403 starts*/
        if (array_key_exists('institutionId',$this->request->getAttribute('params'))) {
            $institutionId = $this->request->getAttribute('params')['institutionId'];
            $jsonData = base64_decode($institutionId);
            preg_match_all('/{(.*?)}/', $jsonData, $matches);
            $requestData = json_decode($matches[0][0]);
            $id = $requestData->id;
            $this->Session->write('inst_id', $id);
        }
        /*POCOR-6403 ends*/
		$this->controller->Navigation->substituteCrumb($this->getAlias(), $reportName);
		$this->controller->set('contentHeader', __((string) $controllerName).' - '.$reportName);
	}

	public function addBeforeAction(Event $event)
	{
        $this->fields = [];
        $institutionId =  $this->getInstitutionID();
        $this->ControllerAction->field('institution_id', ['type' => 'hidden', 'value' => $institutionId]);
        $this->ControllerAction->field('feature', ['type' => 'select', 'select' => false]);
        $this->ControllerAction->field('format');

        if (isset($this->request->getData()[$this->getAlias()]['feature'])) {
            $id = $this->request->getData()[$this->getAlias()]['feature'];
            $customReportData = $this->find()
                ->where([$this->aliasField('id') => $id])
                ->first();
            // filters
            if (!empty($customReportData) && !empty($customReportData->filter)) {
                $validator = $this->getValidator();
                $filters = json_decode($customReportData->filter, true);

                // academic period filter
                if (isset($filters['academic_period_id'])) {
                    // add validation
                    $validator->notEmpty('academic_period_id');
                    $this->ControllerAction->field('academic_period_id');
                    unset($filters['academic_period_id']);
                }
                //START: POCOR-6629
                // edication grade filter
                if (isset($filters['education_grade_id'])) {
                    // add validation
                    $validator->notEmpty('education_grade_id');
                    $this->ControllerAction->field('education_grade_id');
                    unset($filters['education_grade_id']);
                }
                //END: POCOR-6629

                $submitValue = $this->request->getData("submit");

                if (isset($submitValue) && $submitValue == "academic_period_id") {
                    $toReset = true;
                } else {
                    $toReset = false;
                }

                // other filters
                foreach ($filters as $field => $filterData) {
                    if ($toReset) {
                        unset($this->request->getData($this->getAlias())[$field]);
                    }
                   if (null !== $this->request->getData("submit") && $field == $this->request->getData("submit")) {
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

                        if (!isset($this->request->getData($this->getAlias())[$field])) {
                           // $this->request->getData($this->getAlias())[$field] = key($options); //POCOR-8485
                            $requestData = $this->request->getData($this->getAlias());
                            $requestData[$field] = $options;
                            $this->request = $this->request->withData($this->getAlias(), $requestData);
                            
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

	public function onUpdateFieldFeature(Event $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $requestData = $this->request->getData();
            $queryParams = (isset($requestData[$this->getAlias()]) ? $requestData[$this->getAlias()] : []);
            //$queryParams = isset($this->request->getData($this->getAlias())) ? $this->request->getData()[$this->getAlias()] : [];
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

            $attr['options'] =  $reportOptions;
            $attr['onChangeReload'] = true;
            $attr['type']           = 'select';
            if (!(isset($this->request->getData($this->getAlias())['feature']))) {
                $option = $attr['options'];
                reset($option);
                $firstOptionKey = key($option);
                //$this->request->getData()[$this->getAlias()]['feature'] = $firstOptionKey;
                $requestData = $this->request->getData($this->getAlias());//POCOR-8485
                $requestData = ['feature' => $firstOptionKey]; 
                $this->request = $this->request->withData($this->getAlias(), $requestData);

            }
            return $attr;
        }
    }

    public function onUpdateFieldFormat(Event $event, array $attr, $action, ServerRequest $request)
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
    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
            $periodOptions = $AcademicPeriods->getYearList(['isEditable' => true]);
            $selectedPeriod = $AcademicPeriods->getCurrent();
            $attr['onChangeReload'] = "academic_period_id";
            $attr['options'] = $periodOptions;
            $attr['default'] = $selectedPeriod;
            $attr['type'] = 'select';
            $attr['select'] = false;
            $attr['required'] = true;
            return $attr;
        }
    }

    //START: POCOR-6629
    // education grade filter
    public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
            $periodOptions = $AcademicPeriods->getYearList(['isEditable' => true]);
            $selectedPeriod = $this->request->getData('InstitutionStatistics')['academic_period_id'] ?? $AcademicPeriods->getCurrent();//POCOR-8857
            $institutionId = $this->request->getData('InstitutionStatistics')['institution_id'] ?? $this->getInstitutionID();//POCOR-8857
            $EducationGrades = TableRegistry::get('Education.EducationGrades');
            $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
            $grades = TableRegistry::get('Institution.InstitutionGrades');
            $periodGrades = $EducationGrades->find('list', ['keyField' => 'id',
                                'valueField' => 'programme_grade_name'])
                            ->find('visible')
                            ->contain(['EducationProgrammes.EducationCycles.EducationLevels.EducationSystems'])
                            ->LeftJoin([$grades->getAlias() => $grades->getTable()],[
                                $EducationGrades->aliasField('id').' = ' . $grades->aliasField('education_grade_id')
                            ])
                            ->where([
                                'EducationSystems.academic_period_id' => $selectedPeriod,
                                $grades->aliasField('institution_id') => $institutionId
                            ])
                            ->order([$EducationGrades->aliasField('id')])
                            ->toArray();

            $attr['onChangeReload'] = "academic_period_id";
            $attr['options'] = $periodGrades;
            $attr['default'] = $selectedPeriod;
            $attr['type'] = 'select';
            $attr['select'] = false;
            $attr['required'] = true;
            return $attr;
        }
    }
    //START: POCOR-6629

    public function onExcelTemplateBeforeGenerate(Event $event, array $params, ArrayObject $extra)
    {
        $str = $this->get($params['feature'])->name;
        $reportName = str_replace(' ', '_', $str);
        $this->behaviors()->get('ExcelReport')->getConfig([
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

        if (isset($settings['requestQuery'])) {
            $jsonQuery = json_decode($customReportData->query, true);

            // csvBehavior can only can handle one query
            $obj = current($jsonQuery);

            $byaccess = true;
            $toSql = true;
            $settings['sql'] = $this->buildQuery($obj, $params, $byaccess, $toSql);
        }
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize=true)
    {
        if ($field == 'feature') {
            return __('Feature');
        } elseif ($field == 'format') {
            return __('Format');
        } elseif ($field == 'modified_user_id') {
            return __('Modified By');
        } elseif ($field == 'modified') {
            return __('Modified On');
        } elseif ($field == 'created_user_id') {
            return __('Created By');
        } elseif ($field == 'created') {
            return __('Created On');
        }elseif ($field == 'academic_period_id') {
            return __('Academic Period');
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function onUpdateToolbarButtons(Event $event, ArrayObject $buttons, ArrayObject $toolbarButtons, array $attr, $action, $isFromModel)
    {
        $params = $this->getQueryString();
        $encodedQueryParams = $this->ControllerAction->paramsEncode($params);
        switch ($action) {
            case 'add':
                $toolbarButtons['back'] = $buttons['back'];
                $toolbarButtons['back']['type'] = 'button';
                $toolbarButtons['back']['label'] = '<i class="fa kd-back"></i>';
                $toolbarButtons['back']['attr'] = $attr;
                $toolbarButtons['back']['attr']['title'] = __('Back');
                $toolbarButtons['back']['url']['0'] = 'index';
                $toolbarButtons['back']['url']['1'] = $encodedQueryParams;
            break;
        }

    }

    /**
     * redirect to index page after save
     * @param int $requestData
     * @return object
     * @author Ehteram Ahmad
     */

    public function addAfterSave(Event $event, Entity $entity, ArrayObject $requestData)
    {
        $param = $this->request->getParam('pass')[1];
        $url = ['plugin' => $this->request->getParam('plugin'), 'controller' => $this->request->getParam('controller'), 'action' =>  'InstitutionStatistics', '0' => 'index','1' => $param ];
        return $this->controller->redirect($url);
    }
}
