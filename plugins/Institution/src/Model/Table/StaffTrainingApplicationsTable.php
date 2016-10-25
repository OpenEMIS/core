<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Validation\Validator;
use Cake\Event\Event;

use App\Model\Table\ControllerActionTable;

class StaffTrainingApplicationsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('staff_training_applications');
        parent::initialize($config);
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Courses', ['className' => 'Training.TrainingCourses', 'foreignKey' => 'course_id']);
        // $this->belongsTo('TrainingNeedCategories', ['className' => 'Training.TrainingNeedCategories', 'foreignKey' => 'training_need_category_id']);
        // $this->belongsTo('TrainingRequirements', ['className' => 'Training.TrainingRequirements', 'foreignKey' => 'training_requirement_id']);
        // $this->belongsTo('TrainingPriorities', ['className' => 'Training.TrainingPriorities', 'foreignKey' => 'training_priority_id']);
        $this->belongsTo('Staff', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users']);

        $this->addBehavior('Institution.InstitutionWorkflowAccessControl');
        // $this->addBehavior('Restful.RestfulAccessControl', [
        //     'Dashboard' => ['index']
        // ]);
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        $this->setupTabElements();
    }

    private function setupTabElements()
    {
        $tabElements = $this->controller->getTrainingTabElements();
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());
    }
}
