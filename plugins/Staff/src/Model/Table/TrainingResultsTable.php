<?php
namespace Staff\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;

class TrainingResultsTable extends AppTable {
	public function initialize(array $config) {
		$this->table('training_session_trainee_results');
		parent::initialize($config);
		$this->belongsTo('Sessions', ['className' => 'Training.TrainingSessions', 'foreignKey' => 'training_session_id']);
		$this->belongsTo('Trainees', ['className' => 'User.Users', 'foreignKey' => 'trainee_id']);
		$this->belongsTo('TrainingResultTypes', ['className' => 'Training.TrainingResultTypes']);
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

	public function viewBeforeAction(Event $event) {
		$this->setupFields();
	}

	public function indexBeforePaginate(Event $event, Request $request, Query $query, ArrayObject $options) {
		$session = $this->request->session();
		$sessionKey = 'Staff.Staff.id';
		if ($session->check($sessionKey)) {
			$userId = $session->read($sessionKey);
			$query->where([
				$this->aliasField('trainee_id') => $userId
			]);
		} else {
			$this->Alert->warning('general.noData');
			$event->stopPropagation();
			return $this->redirect(['action' => 'index']);
		}
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
}
