<?php
namespace Survey\Controller\Component;

use Cake\ORM\TableRegistry;
use Cake\Controller\Component;
use Cake\Log\LogTrait;

class SurveyComponent extends Component {
	use LogTrait;

	// Survey Status
	const EXPIRED = -1;
	const NEW_SURVEY = 0;
	const DRAFT = 1;
	const COMPLETED = 2;

	private $controller;

	public function initialize(array $config) {
		$this->controller = $this->_registry->getController();
	}

	public function getAttachWorkflow() {
		$paramsPass = $this->controller->ControllerAction->paramsPass();
		$currentAction = current($paramsPass);

		if ($currentAction == 'view') {
			$recordId = next($paramsPass);
			$Surveys = TableRegistry::get($this->config('model'));
			$entity = $Surveys->get($recordId);

			if ($entity->status != self::COMPLETED) {
				return false;
			}
		}

		return true;
	}
}
