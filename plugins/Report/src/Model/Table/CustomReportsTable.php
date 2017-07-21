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
        $this->ControllerAction->field('format');

        if (isset($this->request->data[$this->alias()]['feature'])) {
            $id = $this->request->data[$this->alias()]['feature'];
            $customReportData = $this->find()
                ->where([$this->aliasField('id') => $id])
                ->first();

            // filters
            if (!empty($customReportData) && !empty($customReportData->filter)) {
                $jsonFilters = $customReportData->filter;
                $filters = json_decode($jsonFilters, true);

                // academic period filter
                if (array_key_exists('academic_period_id', $filters)) {
                     $this->ControllerAction->field('academic_period_id');
                     unset($filters['academic_period_id']);
                }

                foreach ($filters as $field => $data) {
                    $fieldType = array_key_exists('fieldType', $data) ? $data['fieldType'] : 'select';
                    $parameters = ['type' => $fieldType];

                    if ($fieldType == 'select' || $fieldType == 'chosenSelect') {
                        $params = $this->request->data[$this->alias()];

                        $options = $this->buildQuery($data, $params, false);
                        if (array_key_exists('options', $data)) {
                            $options = $data['options'] + $options;
                        }

                        $parameters = $parameters + ['options' => $options, 'select' => false, 'onChangeReload' => true];

                        if ($fieldType == 'chosenSelect') {
                            $parameters['attr'] = ['multiple' => false];
                        }

                        if (!(isset($this->request->data[$this->alias()][$field]))) {
                            $this->request->data[$this->alias()][$field] = key($options);
                        }
                    }

                    $this->ControllerAction->field($field, $parameters);
                }
            }
        }
    }

	public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
        	$customReports = $this->find('list')->order('name')->toArray();

            // for translation
            $reportOptions = [];
            foreach ($customReports as $key => $name) {
                $reportOptions[$key] = __($name);
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
            if (isset($this->request->data[$this->alias()]['feature'])) {
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

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
            $periodOptions = $AcademicPeriods->getYearList(['isEditable' => true]);
            $selectedPeriod = $AcademicPeriods->getCurrent();

            $attr['onChangeReload'] = true;
            $attr['options'] = $periodOptions;
            $attr['default'] = $selectedPeriod;
            $attr['type'] = 'select';
            $attr['select'] = false;
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

        // set name of report
        $reportName = str_replace(' ', '_', $customReportData->name). '_' . date('Ymd') . 'T' . date('His') . '.csv';
        $settings['file'] = $reportName;

        if (array_key_exists('requestQuery', $settings)) {
            $jsonQuery = json_decode($customReportData->query, true);

            // csvBehavior can only can handle one query
            $obj = current($jsonQuery);
            $settings['sql'] = $this->buildQuery($obj, $params, true);
        }
    }
}
