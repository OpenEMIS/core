<?php
namespace App\Controller\Component;

use Exception;
use ArrayObject;
use Cake\I18n\Time;
use Cake\Controller\Component;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;
use Cake\Log\Log;

class WorkBenchComponent extends Component {
	private $controller;
	private $action;
	private $Session;

	public $components = ['Auth', 'AccessControl', 'Workflow'];

	public function initialize(array $config) {
	}

	public function getList() {
		$models = $this->config('models');

		$data = new ArrayObject([]);
		foreach ($models as $model => $attr) {
			// trigger event for getList to each model
			$subject = TableRegistry::get($model);
			$eventMap = $subject->implementedEvents();

			if ($attr['version'] == 1) {
				$params = [$this->AccessControl, $data];
			} else if ($attr['version'] == 2) {
				$isAdmin = $this->AccessControl->isAdmin();

				$institutionRoles = [];

				if (!$isAdmin) {
					$institutionIds = $this->AccessControl->getInstitutionsByUser();
					$userId = $this->Auth->user('id');
					$Institutions = TableRegistry::get('Institution.Institutions');
					foreach ($institutionIds as $institutionId) {
						$roles = $Institutions->getInstitutionRoles($userId, $institutionId);
						$institutionRoles[$institutionId] = $roles;
					}
				}

				$params = [$isAdmin, $institutionRoles, $data];
			}

			$event = new Event('Workbench.Model.onGetList', $this, $params);
			try {
				$subject->eventManager()->dispatch($event);
				if ($event->isStopped()) { return $event->result; }
			} catch (Exception $ex) {
				Log::write('error', 'WorkBenchComponent: ' . $ex->getMessage());
			}
		}
		return $data;
	}
}
