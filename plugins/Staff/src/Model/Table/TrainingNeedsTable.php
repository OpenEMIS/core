<?php
namespace Staff\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;

class TrainingNeedsTable extends AppTable {
	use OptionsTrait;

	const CATALOGUE = 'CATALOGUE';
	const NEED = 'NEED';

	private $course = null;

	public function initialize(array $config) {
		$this->table('staff_training_needs');
		parent::initialize($config);
		$this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
		$this->belongsTo('Courses', ['className' => 'Training.TrainingCourses', 'foreignKey' => 'course_id']);
		$this->belongsTo('TrainingNeedCategories', ['className' => 'Training.TrainingNeedCategories', 'foreignKey' => 'training_need_category_id']);
		$this->belongsTo('TrainingRequirements', ['className' => 'Training.TrainingRequirements', 'foreignKey' => 'training_requirement_id']);
		$this->belongsTo('TrainingPriorities', ['className' => 'Training.TrainingPriorities', 'foreignKey' => 'training_priority_id']);
		$this->belongsTo('Staff', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);

		return $validator
			->add('course_code', [
				'ruleUnique' => [
					'rule' => ['validateUnique', ['scope' => ['training_need_category_id', 'staff_id']]],
					'provider' => 'table'
				]
			])
			->add('course_id', [
				'ruleUnique' => [
					'rule' => ['validateUnique', ['scope' => ['course_code', 'staff_id']]],
					'provider' => 'table'
				]
			])
			->allowEmpty('course_code', function ($context) {
				if (array_key_exists('type', $context['data'])) {
					$type = $context['data']['type'];
					if ($type == 'CATALOGUE') {
						return true;
					} else {
						return false;
					}
				} else {
					return false;
				}
			})
			->allowEmpty('course_name', function ($context) {
				if (array_key_exists('type', $context['data'])) {
					$type = $context['data']['type'];
					if ($type == 'CATALOGUE') {
						return true;
					} else {
						return false;
					}
				} else {
					return false;
				}
			})
			->add('type', 'notBlank', ['rule' => 'notBlank'])
			;
	}

	public function onGetType(Event $event, Entity $entity) {
		list($typeOptions) = array_values($this->_getSelectOptions());
		$currentAction = $this->ControllerAction->action();
		if ($currentAction == 'index') {
			$entity = $this->setupValues($entity);
		}

		return $typeOptions[$entity->type];
	}

	public function indexBeforeAction(Event $event) {
		$this->ControllerAction->field('type');
		$this->ControllerAction->field('comments', ['visible' => false]);
		$this->ControllerAction->field('course_id', ['visible' => false]);
		$this->ControllerAction->field('course_description', ['visible' => false]);
		$this->ControllerAction->field('training_need_category_id', ['visible' => false]);
		$this->ControllerAction->field('training_requirement_id', ['visible' => false]);
		$this->ControllerAction->field('training_priority_id', ['visible' => false]);
		$this->ControllerAction->field('staff_id', ['visible' => false]);
		$this->ControllerAction->setFieldOrder(['type', 'course_code', 'course_name']);
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$entity = $this->setupValues($entity);
		$this->setupFields($entity);
	}

	public function addOnInitialize(Event $event, Entity $entity) {
		$entity = $this->setupValues($entity);
	}

	public function editOnInitialize(Event $event, Entity $entity) {
		$entity = $this->setupValues($entity);
	}

	public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		if (array_key_exists('course_id', $data[$this->alias()])) {
			$courseId = $data[$this->alias()]['course_id'];
			if (!empty($courseId)) {
				$data[$this->alias()]['training_requirement_id'] = $this->Courses->get($courseId)->training_requirement_id;
			}
		}
	}

	public function addEditAfterAction(Event $event, Entity $entity) {
		$this->setupFields($entity);
	}

	public function onUpdateFieldType(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			list($typeOptions, $selectedType) = array_values($this->_getSelectOptions());

			$attr['type'] = 'select';
			$attr['onChangeReload'] = 'changeType';
			$attr['options'] = $typeOptions;
		} else if ($action == 'edit') {
			list($typeOptions, $selectedType) = array_values($this->_getSelectOptions());
			$attr['type'] = 'readonly';
			$attr['attr']['value'] = $typeOptions[$selectedType];
		}

		return $attr;
	}

	public function onUpdateFieldTrainingNeedCategoryId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'view') {
			$selectedType = $attr['attr']['type_value'];
			if ($selectedType == self::CATALOGUE) {
				$attr['visible'] = false;
			}
		} else if ($action == 'add' || $action == 'edit') {
			list(, $selectedType) = array_values($this->_getSelectOptions());

			if ($selectedType == self::NEED) {
				$attr['type'] = 'select';
			} else {
				$attr['type'] = 'hidden';
				$attr['attr']['value'] = 0;
			}
		}

		return $attr;
	}

	public function onUpdateFieldCourseId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'view') {
			$attr['visible'] = false;
		} else if ($action == 'add' || $action == 'edit') {
			list(, $selectedType) = array_values($this->_getSelectOptions());

			if ($selectedType == self::CATALOGUE) {
				$courseOptions = $this->Training->getCourseList();
				$selectedCourse = (array_key_exists('course', $this->request->query) && array_key_exists($this->request->query['course'], $courseOptions))? $this->request->query['course']: null;
				if (!is_null($selectedCourse)) {
					$this->course = $this->Courses
						->find()
						->matching('TrainingRequirements')
						->where([
							$this->Courses->aliasField('id') => $selectedCourse
						])
						->first();
				}

				$attr['type'] = 'select';
				$attr['onChangeReload'] = 'changeCourse';
				$attr['options'] = $courseOptions;
			} else {
				$attr['type'] = 'hidden';
				$attr['attr']['value'] = 0;
			}
		}

		return $attr;
	}

	public function onUpdateFieldCourseCode(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add' || $action == 'edit') {
			list(, $selectedType) = array_values($this->_getSelectOptions());

			if ($selectedType == self::CATALOGUE) {
				$attr['type'] = 'readonly';
				if (!is_null($this->course)) {
					$attr['value'] = $this->course->code;
					$attr['attr']['value'] = $this->course->code;
				}
			}
		}

		return $attr;
	}

	public function onUpdateFieldCourseName(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add' || $action == 'edit') {
			list(, $selectedType) = array_values($this->_getSelectOptions());

			if ($selectedType == self::CATALOGUE) {
				$attr['type'] = 'readonly';
				if (!is_null($this->course)) {
					$attr['value'] = $this->course->name;
					$attr['attr']['value'] = $this->course->name;
				}
			}
		}

		return $attr;
	}

	public function onUpdateFieldCourseDescription(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add' || $action == 'edit') {
			list(, $selectedType) = array_values($this->_getSelectOptions());

			if ($selectedType == self::CATALOGUE) {
				$attr['attr']['disabled'] = 'disabled';
				if (!is_null($this->course)) {
					$attr['attr']['value'] = $this->course->description;
				}
			}
		}

		return $attr;
	}

	public function onUpdateFieldTrainingRequirementId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add' || $action == 'edit') {
			list(, $selectedType) = array_values($this->_getSelectOptions());

			if ($selectedType == self::CATALOGUE) {
				$attr['type'] = 'readonly';
				if (!is_null($this->course)) {
					$attr['attr']['value'] = $this->course->_matchingData['TrainingRequirements']->name;
				}
			}
		}

		return $attr;
	}

	public function onUpdateFieldStaffId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add' || $action == 'edit') {
			$session = $request->session();
			$sessionKey = 'Staff.Staff.id';

			if ($session->check($sessionKey)) {
				$attr['attr']['value'] = $session->read($sessionKey);
			}
		}

		return $attr;
	}

	public function addEditOnChangeType(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$request = $this->request;
		unset($request->query['type']);
		unset($request->query['course']);

		if ($request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $request->data)) {
				if (array_key_exists('type', $request->data[$this->alias()])) {
					$request->query['type'] = $request->data[$this->alias()]['type'];
				}
			}
			$data[$this->alias()]['course_code'] = '';
			$data[$this->alias()]['course_name'] = '';
			$data[$this->alias()]['status_id'] = $entity->status_id;
		}
	}

	public function addEditOnChangeCourse(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$request = $this->request;
		unset($request->query['type']);
		unset($request->query['course']);

		if ($request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $request->data)) {
				if (array_key_exists('type', $request->data[$this->alias()])) {
					$request->query['type'] = $request->data[$this->alias()]['type'];
				}
				if (array_key_exists('course_id', $request->data[$this->alias()])) {
					$request->query['course'] = $request->data[$this->alias()]['course_id'];
				}
			}
			$data[$this->alias()]['status_id'] = $entity->status_id;
		}
	}

	public function setupValues(Entity $entity) {
		if (!isset($entity->id)) {	// new record
			// list(, $selectedType) = array_values($this->_getSelectOptions());
			$entity->type = '';
		} else {	// existing record
			if ($entity->training_need_category_id == 0) {
				$entity->type = self::CATALOGUE;
				$course = $this->Courses->get($entity->course_id);

				$entity->course_code = $course->code;
				$entity->course_name = $course->name;
				$entity->course_description = $course->description;
			} else {
				$entity->type = self::NEED;
			}
		}
		$this->request->query['type'] = $entity->type;

		return $entity;
	}

	public function setupFields(Entity $entity) {
		$this->ControllerAction->field('type');
		$this->ControllerAction->field('training_need_category_id', [
			'type' => 'select',
			'attr' => ['type_value' => $entity->type]
		]);
		$this->ControllerAction->field('course_id', ['type' => 'select']);
		$this->ControllerAction->field('course_code');
		$this->ControllerAction->field('course_name');
		$this->ControllerAction->field('course_description');
		$this->ControllerAction->field('training_requirement_id', ['type' => 'select']);
		$this->ControllerAction->field('training_priority_id', ['type' => 'select']);
		$this->ControllerAction->field('staff_id', ['type' => 'hidden']);

		$this->ControllerAction->setFieldOrder([
			'type', 'training_need_category_id',
			'course_id', 'course_code', 'course_name', 'course_description',
			'training_requirement_id', 'training_priority_id',
			'comments', 'staff_id'
		]);
	}

	public function _getSelectOptions() {
		//Return all required options and their key
		$typeOptions = $this->getSelectOptions($this->aliasField('types'));
		// $selectedType = $this->queryString('type', $typeOptions);
		$selectedType = array_key_exists('type', $this->request->query)? $this->request->query['type']: '';

		return compact('typeOptions', 'selectedType');
	}
	
	private function setupTabElements() {
		$tabElements = $this->controller->getTrainingTabElements();
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $this->alias());
	}

	public function afterAction(Event $event) {
		$this->setupTabElements();
	}
}
