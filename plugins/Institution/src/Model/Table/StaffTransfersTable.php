<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;

class StaffTransfersTable extends ControllerActionTable {

	public function initialize(array $config) {
		$this->table('institution_staff_assignments');
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
		$this->belongsTo('Positions', ['className' => 'Institution.InstitutionPositions', 'foreignKey' => 'institution_position_id']);
		$this->belongsTo('RequestingInstitutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'requesting_institution_id']);
		$this->belongsTo('RequestingPositions', ['className' => 'Institution.InstitutionPositions', 'foreignKey' => 'requesting_position_id']);
	}

	public function beforeAction(Event $event, ArrayObject $extra) {
		$this->field('type', ['visible' => false]);
		$this->field('updated', ['visible' => false]);
		$this->field('status', ['before' => 'staff_id']);
		$this->field('staff_id', ['before' => 'start_date']);
	}

	public function addEditbeforeAction(Event $event, ArrayObject $extra) {
		$this->field('status', ['visible' => false]);
	}

	private function initialiseVariable($entity) {
		$institutionStaff = $this->Session->read('Institution.Staff.new');
		if (is_null($institutionStaffId)) {
			return true;
		}
		$staff = $InstitutionStaff->get($institutionStaffId);
		$approvedStatus = $this->Workflow->getStepsByModelCode($this->registryAlias(), 'APPROVED');
		// $institutionId = $this->Session->read('Institution.Institutions.id');
		$staffTransfer = $this->find()
			->where([
				$this->aliasField('staff_id') => $institutionStaff->staff_id,
				$this->aliasField('requesting_institution_id') => $institutionStaff->institution_id,
				$this->aliasField('requesting_position_id') => $institutionStaff->institution_position_id,
				$this->aliasField('status_id').' NOT IN ' => $approvedStatus
			])
			->first();
		if (empty($staffPositionProfilesRecord)) {
			$entity->institution_staff_id = $staff->id;
			$entity->staff_id = $staff->staff_id;
			$entity->institution_position_id = $staff->institution_position_id;
			$entity->institution_id = $staff->institution_id;
			$entity->start_date = $staff->start_date;
			$entity->end_date = $staff->end_date;
			$entity->staff_type_id = $staff->staff_type_id;
			$entity->FTE = $staff->FTE;
			$this->request->data[$this->alias()]['staff_id'] = $entity->staff_id;
			$this->request->data[$this->alias()]['institution_position_id'] = $entity->institution_position_id;
			$this->request->data[$this->alias()]['institution_id'] = $entity->institution_id;
			return false;
		} else {
			return $staffPositionProfilesRecord;
		}
	}

	public function addOnInitialize(Event $event, Entity $entity) {
		$addOperation = $this->initialiseVariable($entity);
		if ($addOperation) {
			$institutionStaffId = $this->request->query('institution_staff_id');
			if (is_null($institutionStaffId)) {
				$url = $this->url('index');
			} else {
				$staffTableViewUrl = $this->url('view');
				$staffTableViewUrl['action'] = 'Staff';
				$staffTableViewUrl[1] = $institutionStaffId;
				$this->Session->write('Institution.StaffPositionProfiles.viewBackUrl', $staffTableViewUrl);
				$url = $this->url('view');
				$url[1] = $addOperation->id;
			}
			$event->stopPropagation();
			return $this->controller->redirect($url);
		}
	}
}
