<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use App\Model\Table\AppTable;

class WorkflowTrainingCourseTable extends AppTable  
{
    public function initialize(array $config) 
    {
        $this->table("training_courses");
        parent::initialize($config);

        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('TrainingFieldStudies', ['className' => 'Training.TrainingFieldStudies', 'foreignKey' => 'training_field_of_study_id']);
        $this->belongsTo('TrainingCourseTypes', ['className' => 'Training.TrainingCourseTypes', 'foreignKey' => 'training_course_type_id']);
        $this->belongsTo('TrainingModeDeliveries', ['className' => 'Training.TrainingModeDeliveries', 'foreignKey' => 'training_mode_of_delivery_id']);
        $this->belongsTo('TrainingRequirements', ['className' => 'Training.TrainingRequirements', 'foreignKey' => 'training_requirement_id']);
        $this->belongsTo('TrainingLevels', ['className' => 'Training.TrainingLevels', 'foreignKey' => 'training_level_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users']);
        $this->hasMany('TrainingSessions', ['className' => 'Training.TrainingSessions', 'foreignKey' => 'training_course_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('TrainingNeeds', ['className' => 'Staff.TrainingNeeds', 'foreignKey' => 'training_course_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->belongsToMany('TargetPopulations', [
            'className' => 'Institution.StaffPositionTitles',
            'joinTable' => 'training_courses_target_populations',
            'foreignKey' => 'training_course_id',
            'targetForeignKey' => 'target_population_id',
            'through' => 'Training.TrainingCoursesTargetPopulations',
            'dependent' => true
        ]);
        $this->belongsToMany('TrainingProviders', [
            'className' => 'Training.TrainingProviders',
            'joinTable' => 'training_courses_providers',
            'foreignKey' => 'training_course_id',
            'targetForeignKey' => 'training_provider_id',
            'through' => 'Training.TrainingCoursesProviders',
            'dependent' => true
        ]);
        $this->belongsToMany('CoursePrerequisites', [
            'className' => 'Training.PrerequisiteTrainingCourses',
            'joinTable' => 'training_courses_prerequisites',
            'foreignKey' => 'training_course_id',
            'targetForeignKey' => 'prerequisite_training_course_id',
            'through' => 'Training.TrainingCoursesPrerequisites',
            'dependent' => true
        ]);
        $this->belongsToMany('Specialisations', [
            'className' => 'Training.TrainingSpecialisations',
            'joinTable' => 'training_courses_specialisations',
            'foreignKey' => 'training_course_id',
            'targetForeignKey' => 'training_specialisation_id',
            'through' => 'Training.TrainingCoursesSpecialisations',
            'dependent' => true
        ]);
        $this->belongsToMany('ResultTypes', [
            'className' => 'Training.TrainingResultTypes',
            'joinTable' => 'training_courses_result_types',
            'foreignKey' => 'training_course_id',
            'targetForeignKey' => 'training_result_type_id',
            'through' => 'Training.TrainingCoursesResultTypes',
            'dependent' => true
        ]);

        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Report.WorkflowReport');
        $this->addBehavior('Excel', [
            'pages' => false,
            'autoFields' => false
        ]);
    }
}
