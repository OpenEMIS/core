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
use App\Model\Traits\OptionsTrait;

class TrainingSessionsTable extends AppTable {
	use OptionsTrait;

	private $_contain = ['Trainers.Users'];

	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
		$this->belongsTo('Courses', ['className' => 'Training.TrainingCourses', 'foreignKey' => 'training_course_id']);
		$this->belongsTo('TrainingProviders', ['className' => 'Training.TrainingProviders', 'foreignKey' => 'training_provider_id']);
		$this->hasMany('Trainers', ['className' => 'Training.TrainingSessionTrainers', 'foreignKey' => 'training_session_id', 'dependent' => true, 'cascadeCallbacks' => true]);
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);
		
		return $validator
			->add('end_date', 'ruleCompareDateReverse', [
				'rule' => ['compareDateReverse', 'start_date', true]
			]);
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
		$visible = ['index' => false, 'view' => true, 'edit' => true, 'add' => true];
		$this->ControllerAction->field('end_date', ['visible' => $visible]);
		$this->ControllerAction->field('comment', ['visible' => $visible]);

		$trainerTypeOptions = $this->getSelectOptions($this->aliasField('trainer_types'));
		$this->controller->set('trainerTypeOptions', $trainerTypeOptions);
	}

	public function indexBeforeAction(Event $event) {
		$this->ControllerAction->setFieldOrder([
			'status_id', 'training_course_id', 'start_date'
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

	public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		//Required by patchEntity for associated data
		$newOptions = [];
		$newOptions['associated'] = $this->_contain;

		$arrayOptions = $options->getArrayCopy();
		$arrayOptions = array_merge_recursive($arrayOptions, $newOptions);
		$options->exchangeArray($arrayOptions);
	}

	public function addEditOnChangeCourse(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$request = $this->request;
		unset($request->query['course']);

		if ($request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $request->data)) {
				if (array_key_exists('training_course_id', $request->data[$this->alias()])) {
					$request->query['course'] = $request->data[$this->alias()]['training_course_id'];
				}
			}
		}
	}

	public function addEditOnAddTrainer(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$trainerOptions = [
			'type' => key($this->getSelectOptions($this->aliasField('trainer_types'))),
			'trainer_id' => 0,
			'name' => '',
			'visible' => 1
		];
		$data[$this->alias()]['trainers'][] = $trainerOptions;

		//Validation is disabled by default when onReload, however immediate line below will not work and have to disabled validation for associated model like the following lines
		$options['associated'] = [
			'Trainers' => ['validate' => false]
		];
	}

	public function onUpdateFieldTrainingCourseId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add' || $action == 'edit') {
			$courseOptions = $this->Courses
				->find('list')
				->toArray();
			$courseId = $this->queryString('course', $courseOptions);

			$attr['options'] = $courseOptions;
			$attr['onChangeReload'] = 'changeCourse';
		}

		return $attr;
	}

	public function onUpdateFieldTrainingProviderId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add' || $action == 'edit') {
			$courseId = $request->query('course');

			$TrainingCoursesProviders = TableRegistry::get('Training.TrainingCoursesProviders');
			$providers = $TrainingCoursesProviders
				->find()
				->matching('TrainingProviders')
				->where([
					$TrainingCoursesProviders->aliasField('training_course_id') => $courseId
				])
				->all();

			$providerOptions = [];
			foreach ($providers as $provider) {
				$providerOptions[$provider->_matchingData['TrainingProviders']->id] = $provider->_matchingData['TrainingProviders']->name;
			}
			$attr['options'] = $providerOptions;
		}

		return $attr;
	}

	public function onUpdateFieldTrainers(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add' || $action == 'edit') {
			$Users = TableRegistry::get('User.Users');
			$trainerOptions = $Users
				->find('list', ['keyField' => 'id', 'valueField' => 'name_with_id'])
				->where([
					$Users->aliasField('is_student') => 0,
					$Users->aliasField('is_staff') => 0,
					$Users->aliasField('is_guardian') => 0
				])
				->toArray();
			$attr['options'] = $trainerOptions;
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
		$this->ControllerAction->field('training_course_id', [
			'type' => 'select'
		]);
		$this->ControllerAction->field('training_provider_id', [
			'type' => 'select',
			'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]
		]);
		$this->ControllerAction->field('trainers', [
			'type' => 'element',
			'element' => 'Training.TrainingSessions/trainers',
			'valueClass' => 'table-full-width'
		]);
		$this->ControllerAction->field('trainees', [
			'type' => 'element',
			'element' => 'Training.TrainingSessions/trainees',
			'valueClass' => 'table-full-width'
		]);

		$this->ControllerAction->setFieldOrder([
			'status_id', 'training_course_id', 'training_provider_id', 'start_date', 'end_date', 'comment'
		]);
	}
}
