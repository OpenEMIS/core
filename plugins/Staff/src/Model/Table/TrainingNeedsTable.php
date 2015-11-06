<?php
namespace Staff\Model\Table;

use ArrayObject;

use App\Model\Table\AppTable;

use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\Network\Request;

class TrainingNeedsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		
		$this->belongsTo('Staff', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
		$this->belongsTo('Courses', ['className' => 'Training.TrainingCourses', 'foreignKey' => 'course_id']);
		$this->belongsTo('TrainingNeedCategories', ['className' => 'Training.TrainingNeedCategories', 'foreignKey' => 'training_need_category_id']);
		$this->belongsTo('TrainingRequirements', ['className' => 'Training.TrainingRequirements', 'foreignKey' => 'training_requirement_id']);
		$this->belongsTo('TrainingPriorities', ['className' => 'Training.TrainingPriorities', 'foreignKey' => 'training_priority_id']);
	}

	public function beforeAction() {
		$this->ControllerAction->field('type');
		$this->ControllerAction->field('staff_id', ['type' => 'hidden']);
		$this->ControllerAction->field('course_id', ['type' => 'select']);
		$this->ControllerAction->field('training_need_category_id', ['type' => 'hidden', 'value' => 0]);
		$this->ControllerAction->field('training_requirement_id', ['type' => 'select']);
		$this->ControllerAction->field('training_priority_id', ['type' => 'select']);
	}

	public function onUpdateFieldType(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			$attr['onChangeReload'] = 'changeType';
			$attr['options'] = ['CATALOGUE' => __('Course Catelogue'), 'NEED' => __('Need Category')];
		} else if ($action == 'edit') {
			
		}

		return $attr;
	}

	public function onUpdateFieldCourseId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			$attr['options'] = [];
		} else if ($action == 'edit') {
			
		}

		return $attr;
	}

	public function addOnChangeType(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$type = $data[$this->alias()]['type'];
		
		if ($type=='NEED') {
			$this->fields['training_need_category_id']['type'] = 'select';
			$this->fields['course_id']['type'] = 'hidden';
			$this->fields['course_id']['value'] = 0;
		}
	}
	
	// public function indexBeforeAction(Event $event) {
	// 	$order = 0;
	// 	$this->ControllerAction->setFieldOrder('employment_type_id', $order++);
	// 	$this->ControllerAction->setFieldOrder('employment_date', $order++);
	// 	$this->ControllerAction->setFieldOrder('comment', $order++);
	// }

	// public function addEditBeforeAction(Event $event) {
	// 	$order = 0;
	// 	$this->ControllerAction->setFieldOrder('employment_type_id', $order++);
	// 	$this->ControllerAction->setFieldOrder('employment_date', $order++);
	// 	$this->ControllerAction->setFieldOrder('comment', $order++);
	// }

	// public function validationDefault(Validator $validator) {
	// 	return $validator;
	// }
}
