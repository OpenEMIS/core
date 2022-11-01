<?php
namespace CustomExcel\Controller\Component;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\Controller\Component;
use Cake\Event\Event;

class ExcelReportComponent extends Component
{
	private $controller;

	public function initialize(array $config)
	{
		parent::initialize($config);
		$this->controller = $this->_registry->getController();
	}

	public function renderExcel($params=[])
	{
		$this->controller->autoRender = false;

		$className = $params['className'];
		$model = TableRegistry::get($className);

		if ($model->behaviors()->has('ExcelReport') && array_key_exists('format', $params)) {
			$model->behaviors()->get('ExcelReport')->config([
				'format' => $params['format']
			]);
		}

		$extra = new ArrayObject($params);
		$event = $model->dispatchEvent('ExcelTemplates.Model.onRenderExcelTemplate', [$extra], $this->controller);
		if ($event->isStopped()) { return $event->result; }

		$event = $model->dispatchEvent('ExcelTemplates.Model.afterRenderExcelTemplate', [$extra, $this->controller], $this->controller);
		if ($event->isStopped()) { return $event->result; }
	}

	public function viewVars($params=[])
	{
		$this->controller->autoRender = false;

		$className = $params['className'];
		$model = TableRegistry::get($className);

		$extra = new ArrayObject([]);
		$event = $model->dispatchEvent('ExcelTemplates.Model.onGetExcelTemplateVars', [$extra], $this->controller);
		if ($event->isStopped()) { return $event->result; }
	}
}
