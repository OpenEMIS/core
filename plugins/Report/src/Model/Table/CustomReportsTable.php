<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;

class CustomReportsTable extends AppTable
{
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
	}

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['ExcelTemplates.Model.onExcelTemplateBeforeGenerate'] = 'onExcelTemplateBeforeGenerate';
        $events['ExcelTemplates.Model.onExcelTemplateInitialiseQueryVariables'] = 'onExcelTemplateInitialiseQueryVariables';

        return $events;
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

                    if ($fieldType == 'select') {
                        $params = $this->request->data[$this->alias()];

                        $options = $this->parseJson($data, $params);
                        if (array_key_exists('options', $data)) {
                            $options = $data['options'] + $options;
                        }

                        $parameters = $parameters + ['options' => $options, 'select' => false, 'onChangeReload' => true];

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
        	$reportOptions = $this->find('list')->order('name')->toArray();

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
            $entity = $this->parseJson($obj, $params);
            $variables[$key] = $entity;
        }

        return $variables;
    }
}
