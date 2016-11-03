<?php
namespace Training\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Datasource\ResultSetInterface;
use Cake\Event\Event;

use App\Model\Table\ControllerActionTable;

class TrainingApplicationsTable extends ControllerActionTable
{
    // Workflow Steps - category
    const TO_DO = 1;
    const IN_PROGRESS = 2;
    const DONE = 3;

    public function initialize(array $config)
    {
        $this->table('staff_training_applications');
        parent::initialize($config);
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Sessions', ['className' => 'Training.TrainingSessions', 'foreignKey' => 'training_session_id']);
        $this->belongsTo('Staff', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users', 'foreignKey' => 'assignee_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);

        $this->addBehavior('Institution.InstitutionWorkflowAccessControl');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Dashboard' => ['index']
        ]);

        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
    }

    private $workflowEvents = [
        [
            'value' => 'Workflow.onAssignTrainingSession',
            'text' => 'Assign Trainess to Training Sessions',
            'description' => 'Performing this action will assign the trainee to the training sessions.',
            'method' => 'onAssignTrainingSession'
        ],
        [
            'value' => 'Workflow.onWithdrawTrainingSession',
            'text' => 'Withdrawal from Training Sessions',
            'description' => 'Performing this action will withdraw the trainee from assigned training sessions of a particular course.',
            'method' => 'onWithdrawTrainingSession'
        ]
    ];

    public function getWorkflowEvents(Event $event, ArrayObject $eventsObject) {
        foreach ($this->workflowEvents as $key => $attr) {
            $attr['text'] = __($attr['text']);
            $attr['description'] = __($attr['description']);
            $eventsObject[] = $attr;
        }
    }

    public function implementedEvents() {
        $events = parent::implementedEvents();
        $events['Workflow.getEvents'] = 'getWorkflowEvents';
        foreach($this->workflowEvents as $event) {
            $events[$event['value']] = $event['method'];
        }
        return $events;
    }

    public function onWithdrawTrainingSession(Event $event, $id, Entity $workflowTransitionEntity) {
        $entity = $this->get($id);
        $staffId = $entity->staff_id;
        $sessionId = $entity->training_session_id;
        $TrainingSessionsTraineesTable = TableRegistry::get('Training.TrainingSessionsTrainees');
        $trainingSessionsTraineeArr = [
            'training_session_id' => $sessionId,
            'trainee_id' => $staffId,
            'status' => 2
        ];
        $newEntity = $TrainingSessionsTraineesTable->newEntity($trainingSessionsTraineeArr);
        $TrainingSessionsTraineesTable->save($newEntity);
    }

    public function onAssignTrainingSession(Event $event, $id, Entity $workflowTransitionEntity) {
        $entity = $this->get($id);
        $staffId = $entity->staff_id;
        $sessionId = $entity->training_session_id;
        $trainingSessionsTraineeArr = [
            'training_session_id' => $sessionId,
            'trainee_id' => $staffId,
            'status' => 1
        ];
        $TrainingSessionsTraineesTable = TableRegistry::get('Training.TrainingSessionsTrainees');
        $newEntity = $TrainingSessionsTraineesTable->newEntity($trainingSessionsTraineeArr);
        $TrainingSessionsTraineesTable->save($newEntity);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->setupTabElements();
    }

    public function indexbeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('status_id');
        $this->field('assignee_id', ['visible' => false]);
        $this->setFieldOrder([
            'status_id', 'staff_id', 'institution_id', 'training_session_id'
        ]);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        // only show applications which are 'Pending for Approval'
        $steps = $this->Workflow->getStepsByModelCode('Training.TrainingApplications', 'PENDINGAPPROVAL');
        if (!empty($steps)) {
            $query->where([
                $this->aliasField('status_id IN') => $steps
            ]);
        }
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('assignee_id', ['visible' => false]);
        $this->setFieldOrder([
            'status_id', 'staff_id', 'institution_id', 'training_session_id'
        ]);
    }

    private function setupTabElements()
    {
        $tabElements = $this->controller->getSessionTabElements();
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'Applications');
    }

    public function findWorkbench(Query $query, array $options)
    {
        $controller = $options['_controller'];
        $session = $controller->request->session();

        $userId = $session->read('Auth.User.id');
        $Statuses = $this->Statuses;
        $doneStatus = self::DONE;

        $query
            ->select([
                $this->aliasField('id'),
                $this->aliasField('status_id'),
                $this->aliasField('institution_id'),
                $this->aliasField('modified'),
                $this->aliasField('created'),
                $this->Statuses->aliasField('name'),
                $this->Staff->aliasField('openemis_no'),
                $this->Staff->aliasField('first_name'),
                $this->Staff->aliasField('middle_name'),
                $this->Staff->aliasField('third_name'),
                $this->Staff->aliasField('last_name'),
                $this->Staff->aliasField('preferred_name'),
                $this->Sessions->aliasField('code'),
                $this->Sessions->aliasField('name'),
                $this->Sessions->Courses->aliasField('code'),
                $this->Sessions->Courses->aliasField('name'),
                $this->Institutions->aliasField('code'),
                $this->Institutions->aliasField('name'),
                $this->CreatedUser->aliasField('openemis_no'),
                $this->CreatedUser->aliasField('first_name'),
                $this->CreatedUser->aliasField('middle_name'),
                $this->CreatedUser->aliasField('third_name'),
                $this->CreatedUser->aliasField('last_name'),
                $this->CreatedUser->aliasField('preferred_name')
            ])
            ->contain([$this->Staff->alias(), 'Sessions.Courses', $this->Institutions->alias(), $this->CreatedUser->alias()])
            ->matching($this->Statuses->alias(), function ($q) use ($Statuses, $doneStatus) {
                return $q->where([$Statuses->aliasField('category <> ') => $doneStatus]);
            })
            ->where([$this->aliasField('assignee_id') => $userId])
            ->order([$this->aliasField('created') => 'DESC'])
            ->formatResults(function (ResultSetInterface $results) {
                return $results->map(function ($row) {
                        $url = [
                            'plugin' => 'Institution',
                            'controller' => 'Institutions',
                            'action' => 'StaffTrainingApplications',
                            'view',
                            $row->id,
                            'institution_id' => $row->institution_id
                        ];

                    if (is_null($row->modified)) {
                        $receivedDate = $this->formatDate($row->created);
                    } else {
                        $receivedDate = $this->formatDate($row->modified);
                    }

                    $row['url'] = $url;
                    $row['status'] = $row->_matchingData['Statuses']->name;
                    $row['request_title'] = $row->staff->name_with_id . ': ' . $row->session->course->code_name . ' (' . $row->session->code_name . ')';
                    $row['institution'] = $row->institution->code_name;
                    $row['received_date'] = $receivedDate;
                    $row['requester'] = $row->created_user->name_with_id;

                    return $row;
                });
            });

        return $query;
    }
}
