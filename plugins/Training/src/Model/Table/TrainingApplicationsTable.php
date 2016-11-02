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
        $this->belongsTo('Courses', ['className' => 'Training.TrainingCourses', 'foreignKey' => 'training_course_id']);
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
        $events['Workflow.addCustomModalFields'] = 'addCustomModalFields';
        $events['Workflow.setVisibleCustomModalField'] = 'setVisibleCustomModalField';
        $events['Workflow.getEvents'] = 'getWorkflowEvents';
        foreach($this->workflowEvents as $event) {
            $events[$event['value']] = $event['method'];
        }
        return $events;
    }

    public function onWithdrawTrainingSession(Event $event, $id, Entity $workflowTransitionEntity) {
        $entity = $this->get($id);
        $staffId = $entity->staff_id;
        $courseId = $entity->training_course_id;
        $TrainingSessionsTable = TableRegistry::get('Training.TrainingSessions');
        $trainingSessionsId = $TrainingSessionsTable->find()->where([$TrainingSessionsTable->aliasField('training_course_id') => $courseId])->select($TrainingSessionsTable->aliasField('id'));
        $TrainingSessionsTraineesTable = TableRegistry::get('Training.TrainingSessionsTrainees');
        $TrainingSessionsTraineesTable->updateAll([
            'status' => 2
        ], [
            'training_session_id IN ' => $trainingSessionsId,
            'staff_id' => $staffId
        ]);
    }

    public function onAssignTrainingSession(Event $event, $id, Entity $workflowTransitionEntity) {
        $entity = $this->get($id);
        $staffId = $entity->staff_id;
        $TrainingSessionsId = $workflowTransitionEntity['training_session_id']['_ids'];
        $trainingSessionsTraineeArr = [];
        foreach ($TrainingSessionsId as $sessionId) {
            $trainingSessionsTraineeArr[] = [
                'training_session_id' => $sessionId,
                'trainee_id' => $staffId,
                'status' => 1
            ];
        }
        $TrainingSessionsTraineesTable = TableRegistry::get('Training.TrainingSessionsTrainees');
        $newEntities = $TrainingSessionsTraineesTable->newEntities($trainingSessionsTraineeArr);
        $TrainingSessionsTraineesTable->saveMany($newEntities);
    }

    public function setVisibleCustomModalField(Event $event, $eventKey)
    {
        $arr = ['fields' => ['workflowtransition-training-session'], 'visible' => false];
        if ($eventKey == 'Workflow.onAssignTrainingSession') {
            $arr['visible'] = true;
        }
        return $arr;
    }

    public function addCustomModalFields(Event $event, Entity $entity, $fields, $alias)
    {
        $TrainingSessions = TableRegistry::get('Training.TrainingSessions');
        $statuses = $this->Workflow->getStepsByModelCode('Training.TrainingSessions', 'APPROVED');
        if (empty($statuses)) {
            $statuses[] = 0;
        }
        $sessionOptions = $TrainingSessions->find('list', [
                'keyField' => 'id',
                'valueField' => 'code_name'
            ])
            ->where([
                $TrainingSessions->aliasField('training_course_id') => $entity->training_course_id,
                $TrainingSessions->aliasField('status_id').' IN ' => $statuses
            ])
            ->toArray();

        if (!empty($sessionOptions)) {
            $sessionOptions = ['' => __('-- Select --')] + $sessionOptions;
        } else {
            $sessionOptions = ['' => __('No Options')];
        }

        $fields[$alias.'.training_session_id'] = [
             'label' => __('Training Session'),
             'model' => $alias,
             'id' => 'workflowtransition-training-session',
             'field' => 'training_session_id',
             'type' => 'chosenSelect',
             'options' => $sessionOptions
        ];

        return $fields;
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
            'status_id', 'staff_id', 'institution_id', 'training_course_id'
        ]);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        // only show applications which are "Pending for Approval"
        // $steps = $this->Workflow->getStepsByModelCode('Institution.StaffTrainingApplications', 'PENDINGAPPROVAL');
        // if (!empty($steps)) {
        //     $query->where([
        //         $this->aliasField('status_id IN') => $steps
        //     ]);
        // }

        // pr($query->sql());die;
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('assignee_id', ['visible' => false]);
        $this->setFieldOrder([
            'status_id', 'staff_id', 'institution_id', 'training_course_id'
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
                $this->Courses->aliasField('code'),
                $this->Courses->aliasField('name'),
                $this->Institutions->aliasField('code'),
                $this->Institutions->aliasField('name'),
                $this->CreatedUser->aliasField('openemis_no'),
                $this->CreatedUser->aliasField('first_name'),
                $this->CreatedUser->aliasField('middle_name'),
                $this->CreatedUser->aliasField('third_name'),
                $this->CreatedUser->aliasField('last_name'),
                $this->CreatedUser->aliasField('preferred_name')
            ])
            ->contain([$this->Staff->alias(), $this->Courses->alias(), $this->Institutions->alias(), $this->CreatedUser->alias()])
            ->matching($this->Statuses->alias(), function ($q) use ($Statuses, $doneStatus) {
                return $q->where([$Statuses->aliasField('category <> ') => $doneStatus]);
            })
            ->where([$this->aliasField('assignee_id') => $userId])
            ->order([$this->aliasField('created') => 'DESC'])
            ->formatResults(function (ResultSetInterface $results) {
                return $results->map(function ($row) {
                        $url = [
                            'plugin' => 'Training',
                            'controller' => 'Trainings',
                            'action' => 'Applications',
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
                    $row['request_title'] = $row->staff->name_with_id . ' - ' . $row->course->code_name;
                    $row['institution'] = $row->institution->code_name;
                    $row['received_date'] = $receivedDate;
                    $row['requester'] = $row->created_user->name_with_id;

                    return $row;
                });
            });

        return $query;
    }
}
