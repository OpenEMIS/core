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
use App\Model\Traits\HtmlTrait;

class TrainingSessionsTable extends AppTable {
	use OptionsTrait;
	use HtmlTrait;

	private $_contain = ['Trainers.Users', 'Trainees'];

	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
		$this->belongsTo('Courses', ['className' => 'Training.TrainingCourses', 'foreignKey' => 'training_course_id']);
		$this->belongsTo('TrainingProviders', ['className' => 'Training.TrainingProviders', 'foreignKey' => 'training_provider_id']);
		$this->hasMany('Trainers', ['className' => 'Training.TrainingSessionTrainers', 'foreignKey' => 'training_session_id', 'dependent' => true, 'cascadeCallbacks' => true]);
		$this->belongsToMany('Trainees', [
			'className' => 'User.Users',
			'joinTable' => 'training_sessions_trainees',
			'foreignKey' => 'training_session_id',
			'targetForeignKey' => 'trainee_id',
			'through' => 'Training.TrainingSessionsTrainees',
			'dependent' => true
		]);
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

	public function onGetTraineeTableElement(Event $event, $action, $entity, $attr, $options=[]) {
		$tableHeaders = [__('OpenEMIS No'), __('Name')];
		$tableCells = [];
		$alias = $this->alias();
		$key = 'trainees';

		if ($action == 'index') {
			// not showing
		} else if ($action == 'view') {
			$associated = $entity->extractOriginal([$key]);
			if (!empty($associated[$key])) {
				foreach ($associated[$key] as $i => $obj) {
					$rowData = [];
					$rowData[] = $obj->openemis_no;
					$rowData[] = $obj->name;

					$tableCells[] = $rowData;
				}
			}
		} else if ($action == 'edit') {
			$tableHeaders[] = ''; // for delete column
			$Form = $event->subject()->Form;

			if ($this->request->is(['get'])) {
				if (!array_key_exists($alias, $this->request->data)) {
					$this->request->data[$alias] = [$key => []];
				} else {
					$this->request->data[$alias][$key] = [];
				}

				$associated = $entity->extractOriginal([$key]);
				if (!empty($associated[$key])) {
					foreach ($associated[$key] as $i => $obj) {
						$this->request->data[$alias][$key][] = [
							'id' => $obj->id,
							'_joinData' => ['openemis_no' => $obj->openemis_no, 'trainee_id' => $obj->id, 'name' => $obj->name]
						];
					}
				}
			}
			// refer to addEditOnAddTrainee for http post
			if ($this->request->data("$alias.$key")) {
				$associated = $this->request->data("$alias.$key");

				foreach ($associated as $i => $obj) {
					$joinData = $obj['_joinData'];
					$rowData = [];
					$name = $joinData['name'];
					$name .= $Form->hidden("$alias.$key.$i.id", ['value' => $joinData['trainee_id']]);
					$name .= $Form->hidden("$alias.$key.$i._joinData.openemis_no", ['value' => $joinData['openemis_no']]);
					$name .= $Form->hidden("$alias.$key.$i._joinData.trainee_id", ['value' => $joinData['trainee_id']]);
					$name .= $Form->hidden("$alias.$key.$i._joinData.name", ['value' => $joinData['name']]);
					$rowData[] = [$joinData['openemis_no'], ['autocomplete-exclude' => $joinData['trainee_id']]];
					$rowData[] = $name;
					$rowData[] = $this->getDeleteButton();
					$tableCells[] = $rowData;
				}
			}
		}

		$attr['tableHeaders'] = $tableHeaders;
    	$attr['tableCells'] = $tableCells;

		return $event->subject()->renderElement('Training.Sessions/' . $key, ['attr' => $attr]);
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
		$this->setupFields($entity);
	}

	public function addEditAfterAction(Event $event, Entity $entity) {
		$this->setupFields($entity);
	}

	public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		//Required by patchEntity for associated data
		$newOptions = [];
		// _joinData is required for 'saveStrategy' => 'replace' to work
		$newOptions['associated'] = [
			'Trainers', 'Trainees._joinData'
		];

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
		$dataOptions = [
			'type' => key($this->getSelectOptions($this->aliasField('trainer_types'))),
			'trainer_id' => '',
			'name' => '',
			'visible' => 1
		];
		$data[$this->alias()]['trainers'][] = $dataOptions;

		//Validation is disabled by default when onReload, however immediate line below will not work and have to disabled validation for associated model like the following lines
		$options['associated'] = [
			'Trainers' => ['validate' => false]
		];
	}

	public function addEditOnAddTrainee(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
		$alias = $this->alias();
		$key = 'trainees';

		if ($data->offsetExists('trainee_id')) {
			$id = $data['trainee_id'];
			try {
				$obj = $this->Trainees->get($id);

				if (!array_key_exists($key, $data[$alias])) {
					$data[$alias][$key] = [];
				}
				$data[$alias][$key][] = [
					'id' => $obj->id,
					'_joinData' => ['openemis_no' => $obj->openemis_no, 'trainee_id' => $obj->id, 'name' => $obj->name]
				];
			} catch (RecordNotFoundException $ex) {
				$this->log(__METHOD__ . ': Record not found for id: ' . $id, 'debug');
			}
		}

		//Validation is disabled by default when onReload, however immediate line below will not work and have to disabled validation for associated model like the following lines
		$options['associated'] = [
			'Trainees' => ['validate' => false]
		];
	}

	public function editOnInitialize(Event $event, Entity $entity) {
		$this->request->query['course'] = $entity->training_course_id;
	}

	public function onUpdateIncludes(Event $event, ArrayObject $includes, $action) {
		if ($action == 'edit') {
			$includes['autocomplete'] = [
				'include' => true, 
				'css' => ['OpenEmis.jquery-ui.min', 'OpenEmis.../plugins/autocomplete/css/autocomplete'],
				'js' => ['OpenEmis.jquery-ui.min', 'OpenEmis.../plugins/autocomplete/js/autocomplete']
			];
		}
	}

	public function ajaxTraineeAutocomplete() {
		$this->controller->autoRender = false;
		$this->ControllerAction->autoRender = false;

		if ($this->request->is(['ajax'])) {
			$term = $this->request->query['term'];
			// $data = $this->Trainees->autocomplete($term);

			// autocomplete
			$session = $this->request->session();
			$sessionKey = $this->registryAlias() . '.id';

			$data = [];
			if ($session->check($sessionKey)) {
				$id = $session->read($sessionKey);
				$entity = $this->get($id);

				$TargetPopulations = TableRegistry::get('Training.TrainingCoursesTargetPopulations');
				$Staff = TableRegistry::get('Institution.InstitutionSiteStaff');
				$Users = TableRegistry::get('User.Users');
				$Positions = TableRegistry::get('Institution.InstitutionSitePositions');
				$search = sprintf('%%%s%%', $term);

				$targetPopulationIds = $TargetPopulations
					->find('list', ['keyField' => 'target_population_id', 'valueField' => 'target_population_id'])
					->where([$TargetPopulations->aliasField('training_course_id') => $entity->training_course_id])
					->toArray();

				$list = $Staff
					->find()
					->matching('Users', function($q) use ($Users, $search) {
						return $q
							->find('all')
							->where([
								'OR' => [
									$Users->aliasField('openemis_no') . ' LIKE' => $search,
									$Users->aliasField('first_name') . ' LIKE' => $search,
									$Users->aliasField('middle_name') . ' LIKE' => $search,
									$Users->aliasField('third_name') . ' LIKE' => $search,
									$Users->aliasField('last_name') . ' LIKE' => $search
								]
							]);
					})
					->matching('Positions', function($q) use ($Positions, $targetPopulationIds) {
						return $q
							->find('all')
							->where([
								'Positions.staff_position_title_id IN' => $targetPopulationIds
							]);
					})
					->group([
						$Staff->aliasField('security_user_id')
					])
					->order([$Users->aliasField('first_name')])
					->all();

				foreach($list as $obj) {
					$_matchingData = $obj->_matchingData['Users'];
					$data[] = [
						'label' => sprintf('%s - %s', $_matchingData->openemis_no, $_matchingData->name),
						'value' => $_matchingData->id
					];
				}
			}
			// End

			echo json_encode($data);
			die;
		}
	}

	public function onUpdateFieldTrainingCourseId(Event $event, array $attr, $action, Request $request) {
		if ($action == 'add') {
			$courseOptions = $this->Courses
				->find('list', ['keyField' => 'id', 'valueField' => 'code_name'])
				->toArray();
			$courseId = $this->queryString('course', $courseOptions);

			$attr['options'] = $courseOptions;
			$attr['onChangeReload'] = 'changeCourse';
		} else if ($action == 'edit') {
			$courseId = $request->query('course');
			$course = $this->Courses->get($courseId);

			$attr['type'] = 'readonly';
			$attr['attr']['value'] = $course->code_name;
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
			$Users = $this->Trainers->Users;
			$trainerOptions = $Users
				->find('list', ['keyField' => 'id', 'valueField' => 'name_with_id'])
				->where([
					$Users->aliasField('is_student') => 0,
					$Users->aliasField('is_staff') => 0,
					$Users->aliasField('is_guardian') => 0
				])
				->toArray();
			$trainerOptions = ['' => '-- ' . __('Select Trainer') . ' --'] + $trainerOptions;

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

	public function setupFields(Entity $entity) {
		$fieldOrder = [
			'status_id', 'training_course_id', 'training_provider_id', 'start_date', 'end_date', 'comment',
			'trainers'
		];

		$this->ControllerAction->field('training_course_id', [
			'type' => 'select'
		]);
		$this->ControllerAction->field('training_provider_id', [
			'type' => 'select',
			'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]
		]);
		$this->ControllerAction->field('trainers', [
			'type' => 'element',
			'element' => 'Training.Sessions/trainers',
			'valueClass' => 'table-full-width'
		]);

		if (isset($entity->id)) {
			$this->ControllerAction->field('trainees', [
				'type' => 'trainee_table',
				'valueClass' => 'table-full-width'
			]);
			$fieldOrder[] = 'trainees';
		}

		$this->ControllerAction->setFieldOrder($fieldOrder);
	}
}
