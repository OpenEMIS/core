<?php
namespace Staff\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;

use App\Model\Table\ControllerActionTable;

class StaffAttachmentTypesTable extends ControllerActionTable
{
    // public function initialize(array $config)
    // {
    //     $this->table('staff_training_categories');
    //     parent::initialize($config);

    //     $this->hasMany('StaffTrainings', ['className' => 'Staff.StaffTrainings', 'foreignKey' => 'staff_training_category_id']);

    //     $this->addBehavior('FieldOption.FieldOption');
    // }

    public function initialize(array $config)
    {
        $this->table('staff_attachment_types');
        parent::initialize($config);

        // $this->hasMany('StudentBehaviours', ['className' => 'Student.StudentBehaviours', 'foreignKey' => 'student_behaviour_category_id']);

        // $this->belongsTo('BehaviourClassifications', ['className' => 'Student.BehaviourClassifications', 'foreignKey' => 'behaviour_classification_id']);

        $this->addBehavior('FieldOption.FieldOption');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'OpenEMIS_Classroom' => ['index']
        ]);
    }

    
}
