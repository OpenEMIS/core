<?php
namespace Training\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;

class TrainingSessionResultsTable extends AppTable {
	public $openStatusIds = [];
	public $approvedStatusIds = [];
	public $resultTypeOptions = [];

	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
		$this->belongsTo('Sessions', ['className' => 'Training.TrainingSessions', 'foreignKey' => 'training_session_id']);
	}

	public function onGetTrainingCourse(Event $event, Entity $entity) {
		$trainingSession = $this->Sessions->getTrainingSession($entity->training_session_id);
		return $trainingSession->course->name;
	}

	public function onGetTrainingProvider(Event $event, Entity $entity) {
		$trainingSession = $this->Sessions->getTrainingSession($entity->training_session_id);
		return $trainingSession->_matchingData['TrainingProviders']->name;
	}

	public function onGetResultType(Event $event, Entity $entity) {
		$html = '';

		$Form = $event->subject()->Form;
		$url = [
			'plugin' => $this->request->params['plugin'],
		    'controller' => $this->request->params['controller'],
		    'action' => $this->request->params['action']
		];
		if (!empty($this->request->pass)) {
			$url = array_merge($url, $this->request->pass);
		}
		$dataNamedGroup = [];
		if (!empty($this->request->query)) {
			foreach ($this->request->query as $key => $value) {
				if (in_array($key, ['result_type'])) continue;
				echo $Form->hidden($key, [
					'value' => $value,
					'data-named-key' => $key
				]);
				$dataNamedGroup[] = $key;
			}
		}
		$baseUrl = $event->subject()->Url->build($url);

		$inputOptions = [
			'class' => 'form-control',
			'label' => false,
			'options' => $this->resultTypeOptions,
			'url' => $baseUrl,
			'data-named-key' => 'result_type',
			'escape' => false
		];
		if (!empty($dataNamedGroup)) {
			$inputOptions['data-named-group'] = implode(',', $dataNamedGroup);
			$dataNamedGroup[] = 'result_type';
		}

		$fieldPrefix = $this->alias();
        $html = $Form->input($fieldPrefix.".result_type", $inputOptions);

		return $html;
	}

	public function onGetTraineeTableElement(Event $event, $action, $entity, $attr, $options=[]) {
		$sessionId = $entity->training_session_id;
		$selectedResultType = $this->request->query('result_type');

		$tableHeaders = [__('OpenEMIS No'), __('Name'), __('Result')];
		$tableCells = [];
		$alias = $this->alias();
		$key = 'trainees';

		$trainees = [];
		if (!is_null($selectedResultType)) {
			$SessionsTrainees = TableRegistry::get('Training.TrainingSessionsTrainees');
			$TraineeResults = TableRegistry::get('Training.TrainingSessionTraineeResults');

			$query = $SessionsTrainees
				->find()
				->matching('Trainees')
				->select([
					$TraineeResults->aliasField('id'),
					$TraineeResults->aliasField('result')
				])
				->leftJoin(
					[$TraineeResults->alias() => $TraineeResults->table()],
					[
						$TraineeResults->aliasField('trainee_id = ') . $SessionsTrainees->aliasField('trainee_id'),
						$TraineeResults->aliasField('training_session_id') => $sessionId,
						$TraineeResults->aliasField('training_result_type_id') => $selectedResultType
					]
				)
				->where([
					$SessionsTrainees->aliasField('training_session_id') => $sessionId
				])
				->group([$SessionsTrainees->aliasField('trainee_id')])
				->autoFields(true);

			$trainees = $query->toArray();

			if (empty($trainees)) {
		  		$this->Alert->warning($this->aliasField('noTrainees'));
		  	}
		}

		if ($action == 'view') {
			foreach ($trainees as $i => $obj) {
				$traineeObj = $obj->_matchingData['Trainees'];
				$traineeResult = $obj->{$TraineeResults->alias()};

				$rowData = [];
				$rowData[] = $traineeObj->openemis_no;
				$rowData[] = $traineeObj->name;
				$rowData[] = strlen($traineeResult['result']) ? $traineeResult['result'] : '';
				$tableCells[] = $rowData;
			}
		} else {
			$Form = $event->subject()->Form;
			foreach ($trainees as $i => $obj) {
				$fieldPrefix = $alias . '.' . $key . '.' . $i;
				$traineeObj = $obj->_matchingData['Trainees'];
				$traineeResult = $obj->{$TraineeResults->alias()};

				$rowData = [];
				$name = $traineeObj->name;
				$name .= $Form->hidden("$fieldPrefix.trainee_id", ['value' => $traineeObj->id]);
				$result = $Form->input("$fieldPrefix.result", ['label' => false, 'value' => $traineeResult['result']]);
				if (isset($traineeResult['id'])) {
					$result .= $Form->hidden("$fieldPrefix.id", ['value' => $traineeResult['id']]);
				}

				$rowData[] = $traineeObj->openemis_no;
				$rowData[] = $name;
				$rowData[] = $result;
				$tableCells[] = $rowData;
			}
		}

	  	$attr['tableHeaders'] = $tableHeaders;
    	$attr['tableCells'] = $tableCells;

		return $event->subject()->renderElement('Training.Results/' . $key, ['attr' => $attr]);
	}

	public function beforeAction(Event $event) {
		$this->openStatusIds = $this->Workflow->getStepsByModelCode($this->registryAlias(), 'OPEN');
		$this->approvedStatusIds = $this->Workflow->getStepsByModelCode($this->registryAlias(), 'APPROVED');
	}

	public function indexBeforeAction(Event $event) {
		$selectedStatus = $this->ControllerAction->getVar('selectedStatus');

		$this->ControllerAction->field('training_course');
		$this->ControllerAction->field('training_provider');

		if (is_null($selectedStatus) || $selectedStatus == -1) {
			$this->buildRecords();
		} else {
			if (in_array($selectedStatus, $this->openStatusIds)) {	// Open
				$this->buildRecords();
			}
		}

		$this->ControllerAction->setFieldOrder([
			'training_course', 'training_provider', 'training_session_id'
		]);
	}

	public function viewAfterAction(Event $event, Entity $entity) {
		$this->setupFields($entity);
	}

	public function editBeforeSave(Event $event, Entity $entity, ArrayObject $data) {
		$SessionResults = $this;
		$TraineeResults = TableRegistry::get('Training.TrainingSessionTraineeResults');

		$process = function($model, $entity) use ($data, $SessionResults, $TraineeResults) {
			$errors = $entity->errors();

			if (empty($errors)) {
				$sessionId = $data[$SessionResults->alias()]['training_session_id'];
				$resultTypeId = $data[$SessionResults->alias()]['result_type'];
				$trainees = $data[$SessionResults->alias()]['trainees'];

				foreach ($trainees as $key => $obj) {
					if (strlen($obj['result']) > 0) {
						$resultData = [
							'result' => $obj['result'],
							'training_result_type_id' => $resultTypeId,
							'trainee_id' => $obj['trainee_id'],
							'training_session_id' => $sessionId
						];

						if (isset($obj['id'])) {
							$resultData['id'] = $obj['id'];
						}

						$resultEntity = $TraineeResults->newEntity($resultData, ['validate' => false]);
						if( $TraineeResults->save($resultEntity) ){
						} else {
							$TraineeResults->log($resultEntity->errors(), 'debug');
						}
					} else {
						if (isset($obj['id'])) {
							$TraineeResults->deleteAll([
								$TraineeResults->aliasField('id') => $obj['id']
							]);
						}
					}
				}

				return true;
			} else {
				return false;
			}
		};

		return $process;
	}

	public function editAfterAction(Event $event, Entity $entity) {
		$this->setupFields($entity);
	}

	public function onUpdateFieldStatus(Event $event, array $attr, $action, Request $request) {
		if ($action == 'edit') {
			$statusOptions = $this->getWorkflowStepList();
			if (isset($attr['attr']['value'])) {
				$statusId = $attr['attr']['value'];

				$attr['type'] = 'readonly';
				$attr['attr']['value'] = $statusOptions[$statusId];
			}
		}

    	return $attr;
    }

    public function onUpdateFieldTrainingCourse(Event $event, array $attr, $action, $request) {
    	if ($action == 'view') {
    		// refer onGetTrainingCourse
    	} else if ($action == 'edit') {
    		$attr['type'] = 'readonly';
			if (isset($attr['attr']['value'])) {
				$sessionId = $attr['attr']['value'];
				$trainingSession = $this->Sessions->getTrainingSession($sessionId);

				$attr['type'] = 'readonly';
				$attr['attr']['value'] = $trainingSession->course->name;
			}
    	}

    	return $attr;
    }

    public function onUpdateFieldTrainingProvider(Event $event, array $attr, $action, $request) {
    	if ($action == 'view') {
    		// refer onGetTrainingProvider
    	} else if ($action == 'edit') {
    		$attr['type'] = 'readonly';
			if (isset($attr['attr']['value'])) {
				$sessionId = $attr['attr']['value'];
				$trainingSession = $this->Sessions->getTrainingSession($sessionId);

				$attr['type'] = 'readonly';
				$attr['attr']['value'] = $trainingSession->_matchingData['TrainingProviders']->name;
			}
    	}

    	return $attr;
    }

	public function onUpdateFieldTrainingSessionId(Event $event, array $attr, $action, $request) {
		if ($action == 'view') {
			$attr['type'] = 'select';
		} else if ($action == 'edit') {
			$sessionOptions = $this->controller->getSessionList();
			if (isset($attr['attr']['value'])) {
				$sessionId = $attr['attr']['value'];

				$attr['type'] = 'readonly';
				$attr['attr']['value'] = $sessionOptions[$sessionId];
			}
		}

		return $attr;
	}

    public function onUpdateFieldResultType(Event $event, array $attr, $action, $request) {
    	$resultTypeOptions = [];

    	if (isset($attr['attr']['value'])) {
			$sessionId = $attr['attr']['value'];
			$resultTypeOptions = $this->getResultTypeOptions($sessionId);
		}

		if (empty($resultTypeOptions )) {
	  		$this->Alert->warning($this->aliasField('noResultTypes'));
	  	} else {
	  		$selectedResultType = $this->queryString('result_type', $resultTypeOptions);
			$this->advancedSelectOptions($resultTypeOptions, $selectedResultType);
	  	}

		if ($action == 'view') {
			$this->resultTypeOptions = $resultTypeOptions;
    		$attr['valueClass'] = 'table-full-width';
    	} else if ($action == 'edit') {
    		$attr['type'] = 'select';
    		$attr['attr']['options'] = $resultTypeOptions;
    		$attr['onChangeReload'] = 'changeResultType';
    	}

    	return $attr;
    }

    public function editOnChangeResultType(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options) {
    	$request = $this->request;
		unset($request->query['result_type']);

		if ($request->is(['post', 'put'])) {
			if (array_key_exists($this->alias(), $request->data)) {
				if (array_key_exists('result_type', $request->data[$this->alias()])) {
					$request->query['result_type'] = $request->data[$this->alias()]['result_type'];
				}
			}
			$data[$this->alias()]['status_id'] = $entity->status_id;
		}
    }

	public function buildRecords($sessionId=null) {
		$sessions = $this->controller->getSessionList();
		
		$openStatusId = null;
		$workflow = $this->getWorkflow($this->registryAlias());
		if (!empty($workflow)) {
			foreach ($workflow->workflow_steps as $workflowStep) {
				if ($workflowStep->stage == 0) {
					$openStatusId = $workflowStep->id;
					break;
				}
			}

			foreach ($sessions as $sessionId => $session) {
				$where = [
					$this->aliasField('training_session_id') => $sessionId
				];

				$results = $this
					->find('all')
					->where($where)
					->all();

				if ($results->isEmpty()) {
					// Insert New Records if not found
					$data = [
						'status_id' => $openStatusId,
						'training_session_id' => $sessionId
					];

					$entity = $this->newEntity($data, ['validate' => false]);
					if ($this->save($entity)) {
					} else {
						$this->log($entity->errors(), 'debug');
					}
				}
			}
		}
	}

	public function getResultTypeOptions($id=null) {
		$list = [];

		if (!is_null($id)) {
			$trainingSession = $this->Sessions->getTrainingSession($id);
			foreach ($trainingSession->course->result_types as $key => $obj) {
				$list[$obj->id] = $obj->name;
			}
		}

		return $list;
	}

	public function setupFields(Entity $entity) {
		$this->ControllerAction->field('status', [
			'visible' => ['index' => false, 'view' => false, 'edit' => true],
			'attr' => ['value' => $entity->status_id]
		]);
		$this->ControllerAction->field('training_course', [
			'attr' => ['value' => $entity->training_session_id]
		]);
		$this->ControllerAction->field('training_provider', [
			'attr' => ['value' => $entity->training_session_id]
		]);
		$this->ControllerAction->field('result_type', [
			'attr' => ['value' => $entity->training_session_id]
		]);
		$this->ControllerAction->field('training_session_id', [
			'attr' => ['value' => $entity->training_session_id]
		]);
		$this->ControllerAction->field('trainees', [
			'type' => 'trainee_table',
			'valueClass' => 'table-full-width'
		]);

		$this->ControllerAction->setFieldOrder([
			'status', 'training_course', 'training_provider', 'training_session_id', 'result_type', 'trainees'
		]);
	}
}
