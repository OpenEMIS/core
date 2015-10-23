<?php
namespace Training\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Network\Request;
use Cake\Event\Event;

class TrainingCoursesTable extends AppTable {
	private $_contain = ['TargetPopulations', 'TrainingProviders', 'CoursePrerequisites', 'ResultTypes'];
	private $_fieldOrder = [
		'status_id', 'code', 'name', 'description', 'objective', 'credit_hours', 'duration',
		'training_field_of_study_id', 'training_course_type_id', 'training_mode_of_delivery_id', 'training_requirement_id', 'training_level_id',
		'file_name', 'file_content'
	];

	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
		$this->belongsTo('TrainingFieldStudies', ['className' => 'Training.TrainingFieldStudies', 'foreignKey' => 'training_field_of_study_id']);
		$this->belongsTo('TrainingCourseTypes', ['className' => 'Training.TrainingCourseTypes', 'foreignKey' => 'training_course_type_id']);
		$this->belongsTo('TrainingModeDeliveries', ['className' => 'Training.TrainingModeDeliveries', 'foreignKey' => 'training_mode_of_delivery_id']);
		$this->belongsTo('TrainingRequirements', ['className' => 'Training.TrainingRequirements', 'foreignKey' => 'training_requirement_id']);
		$this->belongsTo('TrainingLevels', ['className' => 'Training.TrainingLevels', 'foreignKey' => 'training_level_id']);
		$this->hasMany('TrainingSessions', ['className' => 'Training.TrainingSessions', 'foreignKey' => 'training_course_id', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->belongsToMany('TargetPopulations', [
			'className' => 'Institution.StaffPositionTitles',
			'joinTable' => 'training_courses_target_populations',
			'foreignKey' => 'training_course_id',
			'targetForeignKey' => 'target_population_id',
			'through' => 'Training.TrainingCoursesTargetPopulations',
			'dependent' => true
		]);
		$this->belongsToMany('TrainingProviders', [
			'className' => 'Training.TrainingProviders',
			'joinTable' => 'training_courses_providers',
			'foreignKey' => 'training_course_id',
			'targetForeignKey' => 'training_provider_id',
			'through' => 'Training.TrainingCoursesProviders',
			'dependent' => true
		]);
		$this->belongsToMany('CoursePrerequisites', [
			'className' => 'Training.PrerequisiteTrainingCourses',
			'joinTable' => 'training_courses_prerequisites',
			'foreignKey' => 'training_course_id',
			'targetForeignKey' => 'prerequisite_training_course_id',
			'through' => 'Training.TrainingCoursesPrerequisites',
			'dependent' => true
		]);
		$this->belongsToMany('ResultTypes', [
			'className' => 'Training.TrainingResultTypes',
			'joinTable' => 'training_courses_result_types',
			'foreignKey' => 'training_course_id',
			'targetForeignKey' => 'training_result_type_id',
			'through' => 'Training.TrainingCoursesResultTypes',
			'dependent' => true
		]);

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
			->allowEmpty('file_content');
	}

	public function implementedEvents() {
    	$events = parent::implementedEvents();
    	$events['Workflow.afterTransition'] = 'workflowAfterTransition';

    	return $events;
    }

	public function afterSave(Event $event, Entity $entity, ArrayObject $options) {
		$this->updateStatusId($entity);
	}

	public function onGetStatusId(Event $event, Entity $entity) {
		return '<span class="status highlight">' . $entity->status->name . '</span>';
	}

	public function beforeAction(Event $event) {
		// Type / Visible
		$this->ControllerAction->field('status_id', [
			'visible' => ['index' => true, 'view' => false, 'edit' => true, 'add' => true]
		]);
		$this->ControllerAction->field('description', [
			'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]
		]);
		$this->ControllerAction->field('objective', [
			'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]
		]);
		$this->ControllerAction->field('duration', [
			'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]
		]);
		$this->ControllerAction->field('training_field_of_study_id', [
			'type' => 'select',
			'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]
		]);
		$this->ControllerAction->field('training_course_type_id', [
			'type' => 'select',
			'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]
		]);
		$this->ControllerAction->field('training_mode_of_delivery_id', [
			'type' => 'select',
			'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]
		]);
		$this->ControllerAction->field('training_requirement_id', [
			'type' => 'select',
			'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]
		]);
		$this->ControllerAction->field('training_level_id', [
			'type' => 'select',
			'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]
		]);
		$this->ControllerAction->field('file_name', [
			'type' => 'hidden',
			'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]
		]);
		$this->ControllerAction->field('file_content', [
			'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]
		]);
	}

	public function indexBeforeAction(Event $event) {
		$this->ControllerAction->setFieldOrder([
			'status_id', 'code', 'name', 'credit_hours'
		]);
	}

	public function viewEditBeforeQuery(Event $event, Query $query) {
		$query->contain($this->_contain);
	}

	// public function viewBeforeAction(Event $event) {
	public function viewAfterAction(Event $event, Entity $entity) {
		$this->setupFields();
	}

	// public function addEditBeforeAction(Event $event) {
	public function addEditAfterAction(Event $event, Entity $entity) {
		$this->setupFields();
	}

	public function addOnInitialize(Event $event, Entity $entity) {
		unset($this->request->query['course']);
	}

	public function editOnInitialize(Event $event, Entity $entity) {
		$this->request->query['course'] = $entity->id;
	}

	public function onUpdateFieldTargetPopulations(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add' || $action == 'edit') {
			$attr['options'] = TableRegistry::get('Institution.StaffPositionTitles')->getList()->toArray();
		}

		return $attr;
	}

	public function onUpdateFieldTrainingProviders(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add' || $action == 'edit') {
			$attr['options'] = TableRegistry::get('Training.TrainingProviders')->getList()->toArray();
		}

		return $attr;
	}

	public function onUpdateFieldCoursePrerequisites(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add' || $action == 'edit') {
			$Courses = TableRegistry::get('Training.PrerequisiteTrainingCourses');
			$courseQuery = $Courses->find('list');

			$id = $request->query('course');
			if (!is_null($id)) {
				$courseQuery->where([
					$Courses->aliasField('id <> ') => $id
				]);
			}

			$courseOptions = $courseQuery->toArray();
			$attr['options'] = $courseOptions;
		}

		return $attr;
	}

	public function onUpdateFieldResultTypes(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add' || $action == 'edit') {
			$attr['options'] = TableRegistry::get('Training.TrainingResultTypes')->getList()->toArray();
		}

		return $attr;
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

	public function setupFields() {
		$this->ControllerAction->field('target_populations', [
			'type' => 'chosenSelect',
			'placeholder' => __('Select Target Populations'),
			'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]
		]);
		$this->ControllerAction->field('training_providers', [
			'type' => 'chosenSelect',
			'placeholder' => __('Select Providers'),
			'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]
		]);
		$this->ControllerAction->field('course_prerequisites', [
			'type' => 'chosenSelect',
			'placeholder' => __('Select Courses'),
			'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]
		]);
		$this->ControllerAction->field('result_types', [
			'type' => 'chosenSelect',
			'placeholder' => __('Select Result Types'),
			'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]
		]);

		// Field order
		$this->_fieldOrder[] = 'target_populations';
		$this->_fieldOrder[] = 'training_providers';
		$this->_fieldOrder[] = 'course_prerequisites';
		$this->_fieldOrder[] = 'result_types';
		$this->ControllerAction->setFieldOrder($this->_fieldOrder);
	}
}
