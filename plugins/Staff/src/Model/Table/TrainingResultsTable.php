<?php
namespace Staff\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Http\ServerRequest;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;

class TrainingResultsTable extends AppTable {
	public function initialize(array $config): void {
		$this->setTable('training_session_trainee_results');
		parent::initialize($config);
		$this->belongsTo('Sessions', ['className' => 'Training.TrainingSessions', 'foreignKey' => 'training_session_id']);
		$this->belongsTo('Trainees', ['className' => 'User.Users', 'foreignKey' => 'trainee_id']);
		$this->belongsTo('TrainingResultTypes', ['className' => 'Training.TrainingResultTypes']);
		$this->addBehavior('User.UserTab', [
            'appliedAction' => ['TrainingResults' =>
                ['id'],
            ]
        ]);
	}

	public function onGetStatus(Event $event, Entity $entity) {
		$SessionResults = $this->Sessions->SessionResults;
		$sessionResult = $SessionResults
			->find()
			->matching('Statuses')
			->where([
				$SessionResults->aliasField('training_session_id') => $entity->training_session_id
			])
			->first();

		return '<span class="status highlight">' . $sessionResult->_matchingData['Statuses']->name . '</span>';
	}

	public function onGetTrainingCourse(Event $event, Entity $entity) {
		$trainingSession = $this->Sessions->getTrainingSession($entity->training_session_id);
		return $trainingSession->course->name;
	}

	public function onGetTrainingProvider(Event $event, Entity $entity) {
		$trainingSession = $this->Sessions->getTrainingSession($entity->training_session_id);
		return $trainingSession->_matchingData['TrainingProviders']->name;
	}

	public function indexBeforeAction(Event $event) {
		$this->setupFields();
	}

	public function indexBeforePaginate(Event $event, $request, Query $query, ArrayObject $options) {
		$session = $this->request->getSession();
		$sessionKey = 'Staff.Staff.id';

		if (!$session->check($sessionKey)) {
			$sessionKey = 'Auth.User.id';
		}
		
		$userId = $session->read($sessionKey);
		if (!empty($userId) && $this->controller->getName() == 'Directories' && isset($this->request->getParam('pass')[1])) {
			$param = $this->paramsDecode($this->request->getParam('pass')[1]);
			$userId = isset($param['staff_id']) ? $param['staff_id'] : '';
			
		}
		if($userId == NULL){
			$userId = '';
		}

		if ($userId) {
			
			// Filter by trainee
			$query->where([
				$this->aliasField('trainee_id') => $userId
			]);
			// End

			// Filter by training session
			$sessionOptions = $this
				->find('list', ['keyField' => 'id', 'valueField' => 'name'])
				->matching('Sessions')
				->select(['id' => 'Sessions.id', 'name' => 'Sessions.name'])
				->where([
					$this->aliasField('trainee_id') => $userId
				])
				->group([
					$this->aliasField('training_session_id')
				])
				->toArray();

			if (!empty($sessionOptions)) {
				$selectedSession = $this->queryString('training_session', $sessionOptions);
				$this->advancedSelectOptions($sessionOptions, $selectedSession);
				$encodedQueryString = $this->request->getParam('pass')[1];
				//Add controls filter to index page
				$toolbarElements = [
					['name' => 'Staff.Training/controls', 'data' => ['encodedQueryString' => $encodedQueryString], 'options' => []]
				];

				$this->controller->set('toolbarElements', $toolbarElements);
				$this->controller->set('sessionOptions', $sessionOptions);

				$query->where([
					$this->aliasField('training_session_id') => $selectedSession
				]);
			}
			// End
		} else {
			// need better solution to return zero results as stopPropagation will cause error
			$query->where([
				$this->aliasField('trainee_id') => -1
			]);
			// $this->Alert->warning('general.noData');
			// $event->stopPropagation();
			// return $this->controller->redirect(['action' => 'index']);
		}
	}

	public function viewBeforeAction(Event $event) {
		$this->setupFields();
	}

	public function setupFields() {
		$this->ControllerAction->field('status');
		$this->ControllerAction->field('trainee_id', [
			'visible' => false
		]);
		$this->ControllerAction->field('training_course');
		$this->ControllerAction->field('training_provider');

		$this->ControllerAction->setFieldOrder([
			'status', 'training_course', 'training_provider', 'training_session_id', 'training_result_type_id', 'result'
		]);
	}

	private function setupTabElements() {
		$tabElements = $this->controller->getTrainingTabElements();
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $this->getAlias());
	}

	public function indexAfterAction(Event $event, $data) {
		$this->setupTabElements();
	}

	public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'result':
                return __('Result');
            case 'training_result_type_id':
                return __('Training Result Type');
            case 'training_course_id':
                return __('Training Course');
            case 'training_provider':
                return __('Training Provider');
            case 'modified':
                return __('Modified');
            case 'modified_user_id':
                return __('Modified By');
            case 'created':
                return __('Created');
            case 'created_user_id':
                return __('Created By');
              case 'status':
                return __('Status');
            case 'training_course':
                return __('Training Course');
            case 'training_session_id':
                return __('Training Sessions');
            case 'attendance_days':
                return __('Attendance Day');
            case 'certificate_number':
                return __('Certificat Number');
            case 'practical':
                return __('Practical');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
}
