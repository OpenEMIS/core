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
		$this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
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

    public function beforeSave(Event $event, Entity $entity, ArrayObject $options) {
		parent::beforeSave($event, $entity, $options);
		$dateFrom = date_create($entity->date_from);
		$dateTo = date_create($entity->date_to);
		$diff = date_diff($dateFrom, $dateTo, true);
		$numberOfDays = $diff->format("%a");
		$entity->number_of_days = ++$numberOfDays;
	}

	public function beforeAction(Event $event) {
		$this->ControllerAction->field('staff_leave_type_id', [
			'type' => 'select'
		]);
		$this->ControllerAction->field('number_of_days', [
			'visible' => ['index' => true, 'view' => true, 'edit' => false, 'add' => false]
		]);
		$this->ControllerAction->field('file_name', [
			'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]
		]);
		$this->ControllerAction->field('file_content', [
			'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]
		]);
		$this->ControllerAction->setFieldOrder(['staff_leave_type_id', 'date_from', 'date_to', 'number_of_days', 'comments', 'file_name', 'file_content']);
	}

	public function onUpdateFieldFileName(Event $event, array $attr, $action, Request $request) {
		if ($action == 'view') {
			$attr['type'] = 'hidden';
		} else if ($action == 'add' || $action == 'edit') {
			$attr['type'] = 'hidden';
		}

		return $attr;
	}

	private function setupTabElements() {
		$tabElements = $this->controller->getCareerTabElements();
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $this->alias());
	}

	public function indexAfterAction(Event $event, $data) {
		$this->setupTabElements();
	}
}
