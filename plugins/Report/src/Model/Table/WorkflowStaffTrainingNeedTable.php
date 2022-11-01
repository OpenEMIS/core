<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use App\Model\Table\AppTable;

class WorkflowStaffTrainingNeedTable extends AppTable  
{
    public function initialize(array $config) 
    {
        $this->table("staff_training_needs");
        parent::initialize($config);

        $this->belongsTo('TrainingCourses', ['className' => 'Training.TrainingCourses', 'foreignKey' => 'training_course_id']);
        $this->belongsTo('TrainingNeedCategories', ['className' => 'Training.TrainingNeedCategories', 'foreignKey' => 'training_need_category_id']);
        $this->belongsTo('TrainingNeedCompetencies', ['className' => 'Training.TrainingNeedCompetencies', 'foreignKey' => 'training_need_competency_id']);
        $this->belongsTo('TrainingNeedSubStandards', ['className' => 'Training.TrainingNeedSubStandards', 'foreignKey' => 'training_need_sub_standard_id']);
        $this->belongsTo('TrainingPriorities', ['className' => 'Training.TrainingPriorities', 'foreignKey' => 'training_priority_id']);
        $this->belongsTo('Staff', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users']);
        
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Report.WorkflowReport');
        $this->addBehavior('Excel', [
            'pages' => false,
            'autoFields' => false
        ]);
    }

    public function implementedEvents() {
        $events = parent::implementedEvents();
        $events['Model.excel.onExcelBeforeQuery'] = 'onExcelBeforeQuery';
        return $events;
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, $query) {
        $query
            ->contain([
                'TrainingCourses' => [
                    'fields' => [
                        'TrainingCourses.name'
                    ]
                ]   
            ])
            ->contain([
                'TrainingNeedCategories' => [
                    'fields' => [
                        'TrainingNeedCategories.name'
                    ]
                ]   
            ])
            ->contain([
                'TrainingNeedCompetencies' => [
                    'fields' => [
                        'TrainingNeedCategories.name'
                    ]
                ]   
            ])
            ->contain([
                'TrainingNeedSubStandards' => [
                    'fields' => [
                        'TrainingNeedSubStandards.name'
                    ]
                ]   
            ])
            ->contain([
                'TrainingPriorities' => [
                    'fields' => [
                        'TrainingPriorities.name'
                    ]
                ]   
            ])
            ->contain([
                'Staff' => [
                    'fields' => [
                        'Staff.first_name',
                        'Staff.middle_name',
                        'Staff.third_name',
                        'Staff.last_name',
                        'Staff.preferred_name'
                    ]
                ]   
            ])
            ->contain([
                'Statuses' => [
                    'fields' => [
                        'Statuses.name'
                    ]
                ]   
            ])
            ->contain([
                'Assignees' => [
                    'fields' => [
                        'Assignees.first_name',
                        'Assignees.middle_name',
                        'Assignees.third_name',
                        'Assignees.last_name',
                        'Assignees.preferred_name'
                    ]
                ]   
            ]);
    }
}
