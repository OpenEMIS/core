<?php
namespace App\Shell;

use Cake\Console\Shell;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Exception;
use ControllerAction\Model\Traits\UtilityTrait;
use Cake\I18n\Time;

class UpdateStaffRolesShell extends Shell {
	use UtilityTrait;
	const NEW_PROCESS = 1;
	const COMPLETED = 3;
	const RUNNING = 2;
	const ERROR = -2;
	const ABORT = -1;

	public function initialize() {
		parent::initialize();
	}

 	public function main() {
 		$pid = getmypid();
		$this->out('Initialize Update Staff Roles Shell PID: '.$pid);
		$newRoleId = $this->args[0];
		$titleId = $this->args[1];
		$systemProcessId = isset($this->args[2]) ? $this->args[2] : null;
		$executedCount = isset($this->args[3]) ? $this->args[3] : 0;
		$param = [
			'newRoleId' => $newRoleId,
			'titleId' => $titleId
		];
		$name = 'Update Staff Roles';
		$model = TableRegistry::get('Institution.StaffPositionTitles');
		$eventName = 'shellRestartUpdateRole';
		$processModel = $model->registryAlias();
		$param = json_encode($param);
		$SystemProcesses = TableRegistry::get('SystemProcesses');
		if (!is_null($systemProcessId)) {
			$SystemProcesses->updatePid($systemProcessId, $pid);
		} else {
			$systemProcessId = $SystemProcesses->addProcess($name, $pid, $processModel, $eventName, $param);
		}

		$SystemProcesses->updateProcess($systemProcessId, null, self::RUNNING, ++$executedCount);
		$processInfo = date('d-m-Y H:i:s') . ' : Update Staff Roles';
		$this->out($processInfo . ' - Start Update Records PID:'.$pid);
		
		try {
			$model->securityRolesUpdates($newRoleId, $titleId);
			$processInfo = date('d-m-Y H:i:s') . ' : Update Staff Roles';
			$this->out($processInfo . ' - Update Records PID:'.$pid);

			$processInfo = date('d-m-Y H:i:s') . ' : Update Staff Roles';
			$this->out($processInfo . ' - End Update Records PID:'.$pid);
			$SystemProcesses->updateProcess($systemProcessId, Time::now(), self::COMPLETED);
		
		} catch (\Exception $e) {
			$this->out('Initialize Update Staff Roles Shell PID: '.$pid);
			$this->out($e->getMessage());
			$SystemProcesses->updateProcess($systemProcessId, Time::now(), self::ERROR);
		}
	}
}
