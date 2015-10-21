<?php
namespace Staff\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\Entity;
use Cake\Validation\Validator;
use Cake\Network\Request;
use Cake\Event\Event;

class LeavesTable extends AppTable {
	public function initialize(array $config) {
		$this->table('staff_leaves');
		parent::initialize($config);

		$this->belongsTo('StaffLeaveTypes', ['className' => 'FieldOption.StaffLeaveTypes']);
		$this->belongsTo('LeaveStatuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
		$this->addBehavior('ControllerAction.FileUpload', [
			// 'name' => 'file_name',
			// 'content' => 'file_content',
			'size' => '10MB',
			'contentEditable' => true,
			'allowable_file_types' => 'all'
		]);
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);
		
		return $validator
			->add('date_to', 'ruleCompareDateReverse', [
				'rule' => ['compareDateReverse', 'date_from', true]
			])
			->allowEmpty('file_content')
		;
	}

	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$events['Workflow.afterTransition'] = 'workflowAfterTransition';

    	return $events;
    }

	public function afterSave(Event $event, Entity $entity, ArrayObject $options) {
		$this->updateStatusId($entity);
	}

	public function beforeAction(Event $event) {
		$this->ControllerAction->field('staff_leave_type_id', [
			'type' => 'select'
		]);
		$this->ControllerAction->field('status_id', [
			'visible' => ['index' => true, 'view' => false, 'edit' => true, 'add' => true]
		]);
		$this->ControllerAction->field('file_name', [
			'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]
		]);
		$this->ControllerAction->field('file_content', [
			'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]
		]);
		$this->ControllerAction->setFieldOrder(['status_id', 'staff_leave_type_id', 'date_from', 'date_to', 'number_of_days', 'comments', 'file_name', 'file_content']);
	}

	public function onGetStatusId(Event $event, Entity $entity) {
		return '<span class="status highlight">' . $entity->leave_status->name . '</span>';
	}

	public function onUpdateFieldStatusId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'index') {
			$attr['type'] = 'select';
		} else {
			$attr['type'] = 'hidden';
			$attr['value'] = 0;
		}

		return $attr;
	}

	public function onUpdateFieldFileName(Event $event, array $attr, $action, Request $request) {
		if ($action == 'view') {
			$attr['type'] = 'hidden';
		} else if ($action == 'add' || $action == 'edit') {
			$attr['type'] = 'hidden';
		}

		return $attr;
	}

	public function workflowAfterTransition(Event $event, $id=null) {
		$entity = $this->get($id);
		$this->updateStatusId($entity);
	}

	public function updateStatusId(Entity $entity) {
		$workflowRecord = $this->getRecord($this->registryAlias(), $entity);
		if (!empty($workflowRecord)) {
			$this->updateAll(
				['status_id' => $workflowRecord->workflow_step_id],
				['id' => $entity->id]
			);
		}
	}
}
