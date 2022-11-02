<?php
namespace App\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Log\Log;
use Cake\I18n\Time;

use App\Model\Table\ControllerActionTable;

class SystemProcessesTable extends ControllerActionTable {
	const NEW_PROCESS = 1;
	const COMPLETED = 3;
	const RUNNING = 2;
	const ERROR = -2;
	const ABORT = -1;
	public function initialize(array $config) {
        $this->table('system_processes');
        parent::initialize($config);
	}

	public function addProcess($name, $pid, $model, $callableEvent=null, $params=null) {
		$newArr = [
			'name' => $name,
			'process_id' => $pid,
			'callable_event' => $callableEvent,
			'status' => self::NEW_PROCESS,
			'start_date' => Time::now(),
			'model' => $model,
			'params' => $params
		];
		$entity = $this->save($this->newEntity($newArr));
		return $entity->id;
	}

	public function updatePid($systemProcessId, $pid) {
		$this->updateAll(['process_id' => getmypid()], ['id' => $systemProcessId]);
	}

	public function getProcessByPid($name, $pid, $model) {
		return $this->find()
			->where([
				$this->aliasField('name') => $name,
				$this->aliasField('model') => $model,
				$this->aliasField('process_id') => $pid
			])
			->hydrate(false)
			->toArray();
	}

	public function updateProcess($systemProcessId, $endTime = null, $status=SELF::COMPLETED, $executedCount=null) {
		$variableToUpdate = ['status' => $status, 'end_date' => $endTime];
		if (!is_null($executedCount)) {
			$variableToUpdate['executed_count'] = $executedCount;
		}
		$this->updateAll(
			$variableToUpdate, 
			['id' => $systemProcessId]
		);
	}

	public function killProcess($pid = 0) {
		if ($pid > 0) {
			$pCmd = "pkill -TERM -P $pid";
			exec($pCmd);

			$cmd = "kill $pid";
			exec($cmd);
		}
	}

	public function getRunningProcesses($model) {
		return $this->find()
			->where([
				$this->aliasField('model') => $model,
				$this->aliasField('status') => self::RUNNING
			])
			->hydrate(false)
			->toArray();
	}

	public function getErrorProcesses($model=null) {
		$query = $this->find()
			->where([
				$this->aliasField('status') => self::ERROR,
				$this->aliasField('executed_count').' <= ' => 10
			]);

		if (!is_null($model)) {
			$query = $query->where([
				$this->aliasField('model') => $model
			]);
		}

		return $query->hydrate(false)->toArray();
	}
}
