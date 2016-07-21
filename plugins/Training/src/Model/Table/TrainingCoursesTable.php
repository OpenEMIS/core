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
	private $_contain = ['TargetPopulations', 'TrainingProviders', 'CoursePrerequisites', 'Specialisations', 'ResultTypes'];

	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
		$this->belongsTo('TrainingFieldStudies', ['className' => 'Training.TrainingFieldStudies', 'foreignKey' => 'training_field_of_study_id']);
		$this->belongsTo('TrainingCourseTypes', ['className' => 'Training.TrainingCourseTypes', 'foreignKey' => 'training_course_type_id']);
		$this->belongsTo('TrainingModeDeliveries', ['className' => 'Training.TrainingModeDeliveries', 'foreignKey' => 'training_mode_of_delivery_id']);
		$this->belongsTo('TrainingRequirements', ['className' => 'Training.TrainingRequirements', 'foreignKey' => 'training_requirement_id']);
		$this->belongsTo('TrainingLevels', ['className' => 'Training.TrainingLevels', 'foreignKey' => 'training_level_id']);
		$this->hasMany('TrainingSessions', ['className' => 'Training.TrainingSessions', 'foreignKey' => 'training_course_id', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->hasMany('TrainingNeeds', ['className' => 'Staff.TrainingNeeds', 'foreignKey' => 'course_id', 'dependent' => true, 'cascadeCallbacks' => true]);
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
		$this->belongsToMany('Specialisations', [
			'className' => 'Training.TrainingSpecialisations',
			'joinTable' => 'training_courses_specialisations',
			'foreignKey' => 'training_course_id',
			'targetForeignKey' => 'training_specialisation_id',
			'through' => 'Training.TrainingCoursesSpecialisations',
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
			'allowable_file_types' => 'all',
			'useDefaultName' => true
		]);
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);

		return $validator
			->add('code', [
				'ruleUnique' => [
					'rule' => ['validateUnique'],
					'provider' => 'table'
				]
			])
			->allowEmpty('file_content');
	}

	public function beforeAction(Event $event) {
		// Type / Visible
		$visible = ['index' => false, 'view' => true, 'edit' => true, 'add' => true];
		$this->ControllerAction->field('description', ['visible' => $visible]);
		$this->ControllerAction->field('objective', ['visible' => $visible]);
		$this->ControllerAction->field('duration', ['visible' => $visible]);
		$this->ControllerAction->field('number_of_months', ['visible' => $visible]);
		$this->ControllerAction->field('training_field_of_study_id', [
			'type' => 'select',
			'visible' => $visible
		]);
		$this->ControllerAction->field('training_course_type_id', [
			'type' => 'select',
			'visible' => $visible
		]);
		$this->ControllerAction->field('training_mode_of_delivery_id', [
			'type' => 'select',
			'visible' => $visible
		]);
		$this->ControllerAction->field('training_requirement_id', [
			'type' => 'select',
			'visible' => $visible
		]);
		$this->ControllerAction->field('training_level_id', [
			'type' => 'select',
			'visible' => $visible
		]);
		$this->ControllerAction->field('file_name', [
			'type' => 'hidden',
			'visible' => $visible
		]);
		$this->ControllerAction->field('file_content', ['visible' => $visible]);
	}

	public function indexBeforeAction(Event $event) {
		$this->ControllerAction->setFieldOrder([
			'code', 'name', 'credit_hours'
		]);
	}

	public function viewEditBeforeQuery(Event $event, Query $query) {
		$query->contain($this->_contain);
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$this->setupFields();
	}

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
			$Courses = TableRegistry::get('Training.TrainingCourses');

			$id = $request->query('course');
			$excludes = [];
			if (!is_null($id)) {
				$excludes[$id] = $id;
			}

			$courseOptions = $this->Training->getCourseList(['excludes' => $excludes]);
			$attr['options'] = $courseOptions;
		}

		return $attr;
	}

	public function onUpdateFieldSpecialisations(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add' || $action == 'edit') {
			$attr['options'] = TableRegistry::get('Training.TrainingSpecialisations')->getList()->toArray();
		}

		return $attr;
	}

	public function onUpdateFieldResultTypes(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add' || $action == 'edit') {
			$attr['options'] = TableRegistry::get('Training.TrainingResultTypes')->getList()->toArray();
		}

		return $attr;
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
		$this->ControllerAction->field('specialisations', [
			'type' => 'chosenSelect',
			'placeholder' => __('Select Specialisations'),
			'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]
		]);
		$this->ControllerAction->field('result_types', [
			'type' => 'chosenSelect',
			'placeholder' => __('Select Result Types'),
			'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]
		]);

		// Field order
		$this->ControllerAction->setFieldOrder([
			'code', 'name', 'description', 'objective', 'credit_hours', 'duration', 'number_of_months',
			'training_field_of_study_id', 'training_course_type_id', 'training_mode_of_delivery_id', 'training_requirement_id', 'training_level_id',
			'target_populations', 'training_providers', 'course_prerequisites', 'specialisations', 'result_types',
			'file_name', 'file_content'
		]);
	}
}
