<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use App\Model\Table\AppTable;

class WorkflowTrainingSessionTable extends AppTable  {

    public function initialize(array $config) {
        //This controller base table is "workflow_models" so '$this' will represent the "workflow_models" table
        $this->table("training_sessions");
        parent::initialize($config);

        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Courses', ['className' => 'Training.TrainingCourses', 'foreignKey' => 'training_course_id']);
        $this->belongsTo('TrainingProviders', ['className' => 'Training.TrainingProviders', 'foreignKey' => 'training_provider_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users']);
        $this->belongsTo('Areas', ['className' => 'Area.Areas']);
        // revert back the association for Trainers to hasMany to handle saving of External Trainers
        $this->hasMany('Trainers', ['className' => 'Training.TrainingSessionTrainers', 'foreignKey' => 'training_session_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('TrainingApplications', ['className' => 'Training.TrainingApplications', 'foreignKey' => 'training_session_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('SessionResults', ['className' => 'Training.TrainingSessionResults', 'foreignKey' => 'training_session_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('TraineeResults', ['className' => 'Training.TrainingSessionTraineeResults', 'foreignKey' => 'training_session_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->belongsToMany('Trainees', [
            'className' => 'User.Users',
            'joinTable' => 'training_sessions_trainees',
            'foreignKey' => 'training_session_id',
            'targetForeignKey' => 'trainee_id',
            'through' => 'Training.TrainingSessionsTrainees',
            'dependent' => false
        ]);

        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Report.WorkflowReport');
        $this->addBehavior('Excel', [
            'excludes' => ['staff_id', 'date_from'],
            'pages' => false,
            'autoFields' => false
        ]);
    }

    public function implementedEvents() {
        $events = parent::implementedEvents();
        // $events['WorkflowReport.onExcelBeforeQuery'] = 'workflowBeforeQuery';
        // $events['WorkflowReport.onExcelUpdateFields'] = 'workflowUpdateFields';
        return $events;
    }
}
