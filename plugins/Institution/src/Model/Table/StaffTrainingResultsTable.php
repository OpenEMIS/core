<?php
namespace Institution\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;
use App\Model\Table\ControllerActionTable;

class StaffTrainingResultsTable extends ControllerActionTable
{
	public function initialize(array $config) {
		$this->table('training_session_trainee_results');
		parent::initialize($config);
		$this->belongsTo('Sessions', ['className' => 'Training.TrainingSessions', 'foreignKey' => 'training_session_id']);
		$this->belongsTo('Trainees', ['className' => 'User.Users', 'foreignKey' => 'trainee_id']);
		$this->belongsTo('TrainingResultTypes', ['className' => 'Training.TrainingResultTypes']);
		$this->toggle('edit', false);
		$this->toggle('add', false);
		$this->toggle('search', false);
	}

	public function beforeAction()
	{
		$modelAlias = 'Results';
		$userType = 'StaffUser';
		$this->controller->changeUserHeader($this, $modelAlias, $userType);
	}

	public function onGetStatus(Event $event, Entity $entity)
	{
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

	public function onGetTrainingCourse(Event $event, Entity $entity)
	{
		$trainingSession = $this->Sessions->getTrainingSession($entity->training_session_id);
		return $trainingSession->course->name;
	}

	public function onGetTrainingProvider(Event $event, Entity $entity)
	{
		$trainingSession = $this->Sessions->getTrainingSession($entity->training_session_id);
		return $trainingSession->_matchingData['TrainingProviders']->name;
	}

	public function indexBeforeAction(Event $event, ArrayObject $extra)
	{
		$this->setupFields();
	}

	public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra) {
		$session = $this->request->session();
		$sessionKey = 'Staff.Staff.id';
		if ($session->check($sessionKey)) {
			$userId = $session->read($sessionKey);

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


			//Add controls filter to index page
			$toolbarElements = [
				['name' => 'Staff.Training/controls', 'data' => [], 'options' => []]
			];

			$this->controller->set('toolbarElements', $toolbarElements);
			$this->controller->set('sessionOptions', $sessionOptions);

			// End
		} else {
			// need better solution to return zero results as stopPropagation will cause error
			$query->where([
				$this->aliasField('trainee_id') => -1
			]);
		}
	}

	public function viewBeforeAction(Event $event, ArrayObject $extra) {
		$this->setupFields();
	}

	public function setupFields() {
		$this->field('status');
		$this->field('trainee_id', [
			'visible' => false
		]);
		$this->field('training_course');
		$this->field('training_provider');

		$this->setFieldOrder([
			'status', 'training_course', 'training_provider', 'training_session_id', 'training_result_type_id', 'result'
		]);
	}

	private function setupTabElements()
	{
		$tabElements = $this->controller->getTrainingTabElements();
		$this->controller->set('tabElements', $tabElements);
		$this->controller->set('selectedAction', $this->alias());
	}

	public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
	{
		$this->setupTabElements();
	}
}
