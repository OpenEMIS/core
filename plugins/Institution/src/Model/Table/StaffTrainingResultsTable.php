<?php
namespace Institution\Model\Table;

use ArrayObject;
use App\Model\Table\AppTable;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;
use Cake\ORM\TableRegistry;
use App\Model\Table\ControllerActionTable;
use Cake\Database\Schema\Table;

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

        $this->addBehavior('Excel',[
            'excludes' => ['trainee_id','attendance_days','certificate_number','practical'],
            'pages' => ['index'],
        ]);
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

	public function onGetStartDate(Event $event, Entity $entity)
	{
		$training_sessions = TableRegistry::get('training_sessions');
		$attendanceType = $training_sessions
                              ->find()
                              ->where([$training_sessions->aliasField('id') => $entity->training_session_id])
                              ->toArray();

		if ($attendanceType) {
			return $attendanceType[0]['start_date']->format('F d,Y');
        }
        return '';
	}
	

	public function onGetEndDate(Event $event, Entity $entity)
	{
		$training_sessions = TableRegistry::get('training_sessions');
		$attendanceType = $training_sessions
                              ->find()
                              ->where([$training_sessions->aliasField('id') => $entity->training_session_id])
                              ->toArray();
                              
		if ($attendanceType) {
			return $attendanceType[0]['end_date']->format('F d,Y');
        }
        return '';
	}

	public function onGetCreditHours(Event $event, Entity $entity)
	{
		$training_courses = TableRegistry::get('training_courses');
		$attendanceType = $training_courses
                              ->find()
                              ->where([$training_courses->aliasField('id') => $entity['session']['training_course_id']])
                              ->toArray();
		return $attendanceType[0]['credit_hours'];
	}

	public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'start_date':
                return __('Session Start Date');
            case 'end_date':
                return __('Session End Date');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
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
				->select(['id' => 'Sessions.id', 'name' => 'Sessions.name', 'start_date'=> 'Sessions.start_date', 'Sessions.end_date'])
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
		$this->field('start_date');
		$this->field('end_date');
		$this->field('credit_hours');

		$this->setFieldOrder([
			'status', 'training_course', 'training_provider', 'training_session_id', 'training_result_type_id', 'result', 'start_date', 'end_date', 'credit_hours'
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

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
		$extraField[] = [
            'key' => 'WorkflowSteps.name',
			'field' => 'result_status',
			'type' => 'string',
			'label' => 'Status'
        ];

        $extraField[] = [
			'key' => 'TrainingCourses.name',
			'field' => 'course_name',
			'type' => 'string',
			'label' => 'Training Course'
        ];

        $extraField[] = [
			'key' => 'TrainingProviders.name',
			'field' => 'training_provider_name',
			'type' => 'string',
			'label' => 'Training Provider'
        ];

        $extraField[] = [
			'key' => 'StaffTrainingResults.training_session_id',
			'field' => 'training_session_id',
			'type' => 'string',
			'label' => 'Training Session'
        ];

        $extraField[] = [
			'key' => 'StaffTrainingResults.training_result_type_id',
			'field' => 'training_result_type_id',
			'type' => 'string',
			'label' => 'Training Result Type'
        ];

        $extraField[] = [
			'key' => 'StaffTrainingResults.result',
			'field' => 'result',
			'type' => 'string',
			'label' => 'Result'
        ];

        $fields->exchangeArray($extraField);
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $session = $this->request->session();
        $staffUserId = $session->read('Institution.StaffUser.primaryKey.id');
        $trainingSession = TableRegistry::get('TrainingSessions');
        $trainingCourses = TableRegistry::get('TrainingCourses');
        $trainingLevels = TableRegistry::get('TrainingLevels');
        $trainingProviders = TableRegistry::get('TrainingProviders');
        $trainingSessionResults = TableRegistry::get('TrainingSessionResults');
        $workflowSteps = TableRegistry::get('WorkflowSteps');

		$query
        ->select([
            'course_name' => 'TrainingCourses.name',
            'training_provider_name' => 'TrainingProviders.name',
            'training_session' => 'TrainingSessions.name',
            'result_status' => 'WorkflowSteps.name'
        ])
        ->leftJoin([$trainingSession->alias() => $trainingSession->table()],[
            $trainingSession->aliasField('id = ').$this->aliasField('training_session_id')
        ])
        ->leftJoin([$trainingCourses->alias() => $trainingCourses->table()],[
            $trainingCourses->aliasField('id = ').$trainingSession->aliasField('training_course_id')
        ])
        ->leftJoin([$trainingProviders->alias() => $trainingProviders->table()],[
            $trainingProviders->aliasField('id = ').$trainingSession->aliasField('training_provider_id')
        ])
        ->leftJoin([$trainingSessionResults->alias() => $trainingSessionResults->table()],[
            $trainingSessionResults->aliasField('training_session_id = ').$trainingSession->aliasField('id')
        ])
        ->leftJoin([$workflowSteps->alias() => $workflowSteps->table()],[
            $workflowSteps->aliasField('id = ').$trainingSessionResults->aliasField('status_id')
        ])
        ->where([
            'trainee_id =' .$staffUserId,
        ]);
    }
}
