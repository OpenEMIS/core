<?php
namespace Student\Model\Table;

use App\Model\Table\ControllerActionTable;

class StudentAttachmentTypesTable extends ControllerActionTable
{
    // public function initialize(array $config)
    // {
    //     $this->table('student_visit_purpose_types');
    //     parent::initialize($config);

    //     $this->hasMany('StudentVisitRequests', ['className' => 'Student.StudentVisitRequests', 'dependent' => true, 'cascadeCallbacks' => true]);
    //     $this->hasMany('StudentVisits', ['className' => 'Student.StudentVisits', 'dependent' => true, 'cascadeCallbacks' => true]);
        
    //     $this->addBehavior('FieldOption.FieldOption');
    // }

    public function initialize(array $config)
    {
        $this->table('student_attachment_types');
        parent::initialize($config);

        //$this->hasMany('StudentBehaviours', ['className' => 'Student.StudentBehaviours', 'foreignKey' => 'student_behaviour_category_id']);

        //$this->belongsTo('BehaviourClassifications', ['className' => 'Student.BehaviourClassifications', 'foreignKey' => 'behaviour_classification_id']);

        $this->addBehavior('FieldOption.FieldOption');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'OpenEMIS_Classroom' => ['index']
        ]);
    }
}
