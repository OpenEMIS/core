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
		$className = $params['className'];
		$model = TableRegistry::get($className);

		$extra = new ArrayObject([]);
		$event = $model->dispatchEvent('ExcelTemplates.Model.onRenderExcelTemplate', [$extra], $this->controller);
		if ($event->isStopped()) { return $event->result; }
	}

	public function viewVars($params=[])
	{
		$className = $params['className'];
		$model = TableRegistry::get($className);

		$extra = new ArrayObject([]);
		$event = $model->dispatchEvent('ExcelTemplates.Model.onGetExcelTemplateVars', [$extra], $this->controller);
		if ($event->isStopped()) { return $event->result; }
	}	
}
