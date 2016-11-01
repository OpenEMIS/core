<?php
namespace Training\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Event\Event;

use App\Model\Table\ControllerActionTable;

class TrainingApplicationsTable extends ControllerActionTable
{
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
        // $this->addBehavior('Restful.RestfulAccessControl', [
        //     'Dashboard' => ['index']
        // ]);

        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
    }

    public function implementedEvents() {
        $events = parent::implementedEvents();
        $events['Workflow.addCustomModalFields'] = 'addCustomModalFields';
        $events['Workflow.setVisibleCustomModalField'] = 'setVisibleCustomModalField';
        return $events;
    }

    public function setVisibleCustomModalField(Event $event, $eventKey)
    {
        $arr = ['fields' => ['workflowtransition-training-session'], 'visible' => false];
        if ($eventKey == 'Training.onAssignTrainingSession') {
            $arr['visible'] = true;
        }
        return $arr;
    }

    public function addCustomModalFields(Event $event, Entity $entity, $fields, $alias)
    {
        $TrainingSessions = TableRegistry::get('Training.TrainingSessions');
        $sessionOptions = $TrainingSessions->find('list', [
                'keyField' => 'id',
                'valueField' => 'code_name'
            ])
            ->where([$TrainingSessions->aliasField('training_course_id') => $entity->training_course_id])
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

}
