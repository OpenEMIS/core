<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Network\Session;
<<<<<<< HEAD
use Cake\Log\Log;

=======
use ControllerAction\Model\Traits\UtilityTrait;
>>>>>>> origin_ssh/POCOR-2604-dev
use App\Model\Table\ControllerActionTable;

class StaffPositionTitlesTable extends ControllerActionTable {
	use UtilityTrait;

	public function initialize(array $config) {
        $this->addBehavior('ControllerAction.FieldOption');
        $this->table('staff_position_titles');
        parent::initialize($config);
        $this->hasMany('Titles', ['className' => 'Institution.InstitutionPositions', 'foreignKey' => 'staff_position_title_id']);
        $this->hasMany('TrainingCoursesTargetPopulations', ['className' => 'Training.TrainingCoursesTargetPopulations', 'foreignKey' => 'target_population_id']);
        $this->belongsTo('SecurityRoles', ['className' => 'Security.SecurityRoles']);

		$this->behaviors()->get('ControllerAction')->config('actions.remove', 'transfer');
	}

	public function beforeAction(Event $event, ArrayObject $extra) {
		$this->field('type', [
			'visible' => true,
			'options' => $this->getSelectOptions('Staff.position_types'),
			'after' => 'name'
		]);
		$systemRolesList = ['' => '-- '.__('Select Role').' --'] + $this->SecurityRoles->getSystemRolesList();
		$selected = '';
		$this->advancedSelectOptions($systemRolesList, $selected);
		$extra['roleList'] = $systemRolesList;
		$this->field('security_role_id', ['after' => 'type', 'options' => $extra['roleList']]);
	}

	public function indexBeforeAction(Event $event, ArrayObject $extra) {
		if ($this->Session->check('StaffPositionTitles.error')) {
			$this->Alert->error($this->Session->read('StaffPositionTitles.error'), ['reset' => true]);
			$this->Session->delete('StaffPositionTitles.inProgress');
		}
		$this->field('type', ['after' => 'name']);
		$this->field('security_role_id', ['after' => 'type']);
	}

	public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra) {
		$titleId = $entity->id;
		$errorProcess = $this->checkIfError($titleId);
		if ($this->checkIfRunning($titleId)) {
			$urlParams = $this->url('index');
			$this->Session->write('StaffPositionTitles.error', 'StaffPositionTitles.inProgress');
			$event->stopPropagation();
			return $this->controller->redirect($urlParams);
		} else if ($errorProcess) {
			$urlParams = $this->url('index');
			$event = $this->dispatchEvent('Shell.shellRestartUpdateRole', [$errorProcess['id'], $errorProcess['executed_count'], $errorProcess['params']]);
			$this->Session->write('StaffPositionTitles.error', 'StaffPositionTitles.error');
			$event->stopPropagation();
			return $this->controller->redirect($urlParams);
		}
	}

	public function onGetType(Event $event, Entity $entity) {
		$types = $this->getSelectOptions('Staff.position_types');
		return array_key_exists($entity->type, $types) ? $types[$entity->type] : $entity->type;
	}

	public function afterSave(Event $event, Entity $entity, ArrayObject $options) {
		if (!$entity->isNew() && $entity->dirty('security_role_id')) {
			$oldRoleId = $entity->getOriginal('security_role_id');
			$newRoleId = $entity->security_role_id;
			$titleId = $entity->id;

			$this->startUpdateRoles($newRoleId, $titleId);
		}
	}

	private function startUpdateRoles($newRoleId, $titleId, $systemProcessId = null, $executedCount = null) {
		$cmd = ROOT . DS . 'bin' . DS . 'cake UpdateStaffRoles '.$newRoleId.' '.$titleId;

		if (!is_null($systemProcessId)) {
			$cmd .= ' '.$systemProcessId;
			$cmd .= ' '.$executedCount;
		}

		$logs = ROOT . DS . 'logs' . DS . 'UpdateStaffRoles.log & echo $!';
		$shellCmd = $cmd . ' >> ' . $logs;

		try {
			$pid = exec($shellCmd);
			Log::write('debug', $shellCmd);
		} catch(\Exception $ex) {
			Log::write('error', __METHOD__ . ' exception when removing inactive roles : '. $ex);
		}
	}

	public function checkIfRunning($titleId) {
		$SystemProcesses = TableRegistry::get('SystemProcesses');
		$runningProcess = $SystemProcesses->getRunningProcesses('Institution.StaffPositionTitles');
		foreach ($runningProcess as $process) {
			$param = json_decode($process['params']);
			if ($param->titleId == $titleId) {
				return true;
			}
		}
		return false;
	}

	public function checkIfError($titleId) {
		$SystemProcesses = TableRegistry::get('SystemProcesses');
		$runningProcess = $SystemProcesses->getErrorProcesses('Institution.StaffPositionTitles');
		foreach ($runningProcess as $process) {
			$param = json_decode($process['params']);
			if ($param->titleId == $titleId) {
				return $process;
			}
		}
		return false;
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$events['Shell.shellRestartUpdateRole'] = 'shellRestartUpdateRole';
		return $events;
	}

	public function shellRestartUpdateRole(Event $event, $systemProcessId, $executedCount, $params) {
		$decodedParam = json_decode($params);
		$newRoleId = $decodedParam->newRoleId;
		$titleId = $decodedParam->titleId;
		if (!$this->checkIfRunning($titleId)) {
			$entity = $this->find()->where([$this->aliasField('id') => $titleId])->first();
			if (!empty($entity) && $entity->security_role_id == $newRoleId) {
				$this->startUpdateRoles($newRoleId, $titleId, $systemProcessId, $executedCount);
			}
		}
	}

	public function securityRolesUpdates($newRoleId, $titleId) {
		$SecurityGroupUsersTable = TableRegistry::get('Security.SecurityGroupUsers');
		$InstitutionStaffTable = TableRegistry::get('Institution.Staff');

		while (true) {
			$subQuery = $InstitutionStaffTable->find()
				->innerJoinWith('Positions.StaffPositionTitles', function($q) use ($titleId) {
					return $q->where(['StaffPositionTitles.id' => $titleId]);
				})
				->innerJoinWith('SecurityGroupUsers')
				->where([
					$InstitutionStaffTable->aliasField('security_group_user_id').' IS NOT NULL', 
					'SecurityGroupUsers.security_role_id <> ' => $newRoleId
				])
				->where([
					'OR' => [
						[function ($exp) use ($InstitutionStaffTable) {
							return $exp->gte($InstitutionStaffTable->aliasField('end_date'), $InstitutionStaffTable->find()->func()->now('date'));
						}],
						[$InstitutionStaffTable->aliasField('end_date').' IS NULL']
					]
				])
				->select([
					'security_group_user_id' => $InstitutionStaffTable->aliasField('security_group_user_id'),
					'staff_id' => $InstitutionStaffTable->aliasField('staff_id')
				])
				->limit(10)
				->page(1);
			
			$updateSubQuery = $this->query()
				->select(['security_group_user_id' => 'GroupUsers.security_group_user_id', 'staff_id' => 'GroupUsers.staff_id'])
				->from(['GroupUsers' => $subQuery]);

			$resultSet = $updateSubQuery->all();

			if ($resultSet->count() == 0) {
				break;
			} else {
				foreach ($resultSet as $entity) {
					Log::write('debug', __FUNCTION__ . ' - Updating roles for user_id (' . $entity->staff_id . ')');
					$SecurityGroupUsersTable->updateAll(
						['security_role_id' => $newRoleId], 
						['id' => $entity->security_group_user_id]);
				}
			}
		}
	}
}
