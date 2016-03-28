<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;
use Cake\Validation\Validator;

class StaffTransferApprovalsTable extends ControllerActionTable {
	use OptionsTrait;

	// Type for application
	const NEW_REQUEST = 0;
	const APPROVED = 1;
	const REJECTED = 2;

	// Type status for transfer / assignment
	const TRANSFER = 2;
	const ASSIGNMENT = 1;

	public function initialize(array $config) {
		$this->table('institution_staff_assignments');
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
		$this->belongsTo('Positions', ['className' => 'Institution.InstitutionPositions', 'foreignKey' => 'institution_position_id']);
		$this->belongsTo('PreviousInstitutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'previous_institution_id']);
		$this->belongsTo('StaffTypes', ['className' => 'FieldOption.StaffTypes']);
	}

	public function validationDefault(Validator $validator) {
		return $validator->requirePresence('previous_institution_id');
	}

	public function beforeAction(Event $event, ArrayObject $extra) {
		$this->field('status');
		$this->field('staff_id');
		$this->field('type', ['visible' => false, 'visible' => ['view' => true, 'edit' => true, 'add' => true]]);
		$this->field('end_date', ['visible' => false]);
		$this->field('staff_type_id', ['type' => 'select', 'visible' => ['view' => true, 'edit' => true, 'add' => true]]);
		$this->field('FTE', ['type' => 'select', 'visible' => ['view' => true, 'edit' => true, 'add' => true]]);
		$this->field('updated', ['visible' => false]);
		$this->field('comment', ['visible' => ['view' => true, 'edit' => true, 'add' => true]]);
		$this->field('institution_position_id', ['visible' => false]);
		$extra['config']['selectedLink'] = ['controller' => 'Institutions', 'action' => 'Staff', 'index'];
	}

	public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra) {
		$this->field('id', ['type' => 'hidden', 'value' => $entity->id]);
		$this->field('previous_institution_id', ['type' => 'readonly', 'attr' => ['value' => $this->Institutions->get($entity->previous_institution_id)->name]]);
		$this->field('institution_id', ['type' => 'readonly', 'attr' => ['value' => $this->Institutions->get($entity->institution_id)->name], 'value' => $entity->institution_id]);
		$this->field('staff_id', ['type' => 'readonly', 'attr' => ['value' => $this->Users->get($entity->staff_id)->name_with_id]]);
		$this->field('status', ['type' => 'hidden']);
		$this->field('type', ['type' => 'hidden']);
		$this->field('staff_type_id', ['type' => 'select']);
		$fteOptions = ['0.25' => '25%', '0.5' => '50%', '0.75' => '75%', '1' => '100%'];
		$this->field('FTE', ['type' => 'select', 'options' => $fteOptions, 'value' => $entity->FTE]);
		
	}

	private function getStaffPositionList() {
		$positionTable = TableRegistry::get('Institution.InstitutionPositions');
		$userId = $this->Auth->user('id');
		$institutionId = $this->Session->read('Institution.Institutions.id');
		if ($this->AccessControl->isAdmin()) {
			$userId = null;
			$roles = [];
		} else {
			$roles = $this->Institutions->getInstitutionRoles($userId, $institutionId);
		}
		
		// Filter by active status
		$activeStatusId = $this->Workflow->getStepsByModelCode($positionTable->registryAlias(), 'ACTIVE');
		$staffPositionsOptions = $this->Positions
				->find()
				->innerJoinWith('StaffPositionTitles.SecurityRoles')
				->where([
					$this->Positions->aliasField('institution_id') => $institutionId, 
					$this->Positions->aliasField('status_id').' IN ' => $activeStatusId
				])
				->select(['security_role_id' => 'SecurityRoles.id', 'type' => 'StaffPositionTitles.type'])
				->order(['StaffPositionTitles.type' => 'DESC', 'StaffPositionTitles.order'])
				->autoFields(true)
			    ->toArray();

		// Filter by role previlege
		$SecurityRolesTable = TableRegistry::get('Security.SecurityRoles');
		$roleOptions = $SecurityRolesTable->getRolesOptions($userId, $roles);
		$roleOptions = array_keys($roleOptions);
		$staffPositionRoles = $this->array_column($staffPositionsOptions, 'security_role_id');
		$staffPositionsOptions = array_intersect_key($staffPositionsOptions, array_intersect($staffPositionRoles, $roleOptions));

		// Adding the opt group
		$types = $this->getSelectOptions('Staff.position_types');
		$options = [];
		foreach ($staffPositionsOptions as $position) {
			$type = __($types[$position->type]);
			$options[$type][$position->id] = $position->name;
		}
		return $options;
	}

	private function newStaffProfileRecord(array $data) {
    	$InstitutionStaff = TableRegistry::get('Institution.Staff');
		unset($data['created']);
		unset($data['created_user_id']);
		unset($data['modified']);
		unset($data['modified_user_id']);
		unset($data['id']);
		$newEntity = $InstitutionStaff->newEntity($data);
    	return $newEntity;
    }

	public function indexBeforeAction(Event $event, $extra) {
    	$toolbarButtons = $extra['toolbarButtons'];
    	if (isset($toolbarButtons['add'])) {
    		unset($toolbarButtons['add']);
    	}
    }

	public function onGetStatus(Event $event, Entity $entity) {
		$name = '';
		switch($entity->status) {
			case SELF::APPROVED:
				$name = __('Approved');
				break;
			case SELF::REJECTED:
				$name = __('Rejected');
				break;
			case SELF::NEW_REQUEST:
				$name = __('New');
				break;
		}
		return '<span class="status highlight">' . $name . '</span>';
	}

	public function onGetFormButtons(Event $event, ArrayObject $buttons) {
		if ($this->action == 'edit') {
			// If the status is new application then display the approve and reject button, 
			// if not remove the button just in case the user gets to access the edit page
			if ($this->request->data[$this->alias()]['status'] == self::NEW_REQUEST || !($this->AccessControl->check(['Institutions', 'TransferApprovals', 'edit']))) {
				$buttons[0]['name'] = '<i class="fa fa-check"></i> ' . __('Approve');

				$buttons[1] = [
					'name' => '<i class="fa fa-close"></i> ' . __('Reject'),
					'attr' => ['class' => 'btn btn-outline btn-cancel', 'div' => false, 'name' => 'submit', 'value' => 'reject']
				];
			} else {
				unset($buttons[0]);
				unset($buttons[1]);
			}
		}
	}
}
