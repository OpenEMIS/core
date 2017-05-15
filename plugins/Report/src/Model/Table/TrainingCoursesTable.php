<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;

class TrainingCoursesTable extends AppTable  {
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('WorkflowSteps', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('TrainingFieldStudies', ['className' => 'Training.TrainingFieldStudies', 'foreignKey' => 'training_field_of_study_id']);
        $this->belongsTo('TrainingCourseTypes', ['className' => 'Training.TrainingCourseTypes', 'foreignKey' => 'training_course_type_id']);
        $this->belongsTo('TrainingModeDeliveries', ['className' => 'Training.TrainingModeDeliveries', 'foreignKey' => 'training_mode_of_delivery_id']);
        $this->belongsTo('TrainingRequirements', ['className' => 'Training.TrainingRequirements', 'foreignKey' => 'training_requirement_id']);
        $this->belongsTo('TrainingLevels', ['className' => 'Training.TrainingLevels', 'foreignKey' => 'training_level_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users']);
        $this->belongsToMany('CoursePrerequisites', [
            'className' => 'Training.PrerequisiteTrainingCourses',
            'joinTable' => 'training_courses_prerequisites',
            'foreignKey' => 'training_course_id',
            'targetForeignKey' => 'prerequisite_training_course_id',
            'through' => 'Training.TrainingCoursesPrerequisites',
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
        $this->belongsToMany('ResultTypes', [
            'className' => 'Training.TrainingResultTypes',
            'joinTable' => 'training_courses_result_types',
            'foreignKey' => 'training_course_id',
            'targetForeignKey' => 'training_result_type_id',
            'through' => 'Training.TrainingCoursesResultTypes',
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
        $this->belongsToMany('TargetPopulations', [
            'className' => 'Institution.StaffPositionTitles',
            'joinTable' => 'training_courses_target_populations',
            'foreignKey' => 'training_course_id',
            'targetForeignKey' => 'target_population_id',
            'through' => 'Training.TrainingCoursesTargetPopulations',
            'dependent' => true
        ]);

        $this->addBehavior('Excel', [
            'excludes' => ['file_name', 'assignee_id', 'status_id']
        ]);
        $this->addBehavior('Report.ReportList');
    }

    public function onExcelBeforeStart (Event $event, ArrayObject $settings, ArrayObject $sheets)
    {
        $sheets[] = [
            'name' => $this->alias(),
            'table' => $this,
            'query' => $this->find(),
            'orientation' => 'landscape'
        ];
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $selectedStatus = $requestData->status;

        $query
            ->contain(['CoursePrerequisites', 'TrainingProviders', 'ResultTypes', 'Specialisations', 'TargetPopulations'])
            ->order([$this->aliasField('code')]);

        if ($selectedStatus != '-1') {
            $query->matching('WorkflowSteps.WorkflowStatuses', function ($q) use ($selectedStatus) {
                return $q->where(['WorkflowStatuses.id' => $selectedStatus]);
            });
        }
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $extraFields = [];
        $extraFields[] = [
            'key' => 'TrainingCourses.status_id',
            'field' => 'status_id',
            'type' => 'integer',
            'label' => '',
        ];

        $newFields = array_merge($extraFields, $fields->getArrayCopy());

        $extraFields = [];
        $extraFields[] = [
            'key' => 'CoursePrerequisites.course_prerequisites',
            'field' => 'course_prerequisites',
            'type' => 'string',
            'label' => '',
        ];

        $extraFields[] = [
            'key' => 'TrainingProviders.training_providers',
            'field' => 'training_providers',
            'type' => 'string',
            'label' => '',
        ];

        $extraFields[] = [
            'key' => 'ResultTypes.result_types',
            'field' => 'result_types',
            'type' => 'string',
            'label' => '',
        ];

        $extraFields[] = [
            'key' => 'Specialisations.specialisations',
            'field' => 'specialisations',
            'type' => 'string',
            'label' => '',
        ];

        $extraFields[] = [
            'key' => 'TargetPopulations.target_populations',
            'field' => 'target_populations',
            'type' => 'string',
            'label' => '',
        ];

        $newFields = array_merge($newFields, $extraFields);
        $fields->exchangeArray($newFields);
    }

    public function onExcelGetCoursePrerequisites(Event $event, Entity $entity)
    {
        if ($entity->has('course_prerequisites') && !empty($entity->course_prerequisites)) {
            $prerequisites = [];
            foreach ($entity->course_prerequisites as $obj) {
                $prerequisites[] = $obj->name;
            }
            return implode(', ', $prerequisites);
        } else {
            return '';
        }
    }

    public function onExcelGetTrainingProviders(Event $event, Entity $entity)
    {
        if ($entity->has('training_providers') && !empty($entity->training_providers)) {
            $providers = [];
            foreach ($entity->training_providers as $obj) {
                $providers[] = $obj->name;
            }
            return implode(', ', $providers);
        } else {
            return '';
        }
    }

    public function onExcelGetResultTypes(Event $event, Entity $entity)
    {
        if ($entity->has('result_types') && !empty($entity->result_types)) {
            $types = [];
            foreach ($entity->result_types as $obj) {
                $types[] = $obj->name;
            }
            return implode(', ', $types);
        } else {
            return '';
        }
    }

    public function onExcelGetSpecialisations(Event $event, Entity $entity)
    {
        if ($entity->has('specialisations') && !empty($entity->specialisations)) {
            $specialisations = [];
            foreach ($entity->specialisations as $obj) {
                $specialisations[] = $obj->name;
            }
            return implode(', ', $specialisations);
        } else {
            return '';
        }
    }

    public function onExcelGetTargetPopulations(Event $event, Entity $entity)
    {
        if ($entity->has('target_populations') && !empty($entity->target_populations)) {
            $targetPopulations = [];
            foreach ($entity->target_populations as $obj) {
                $targetPopulations[] = $obj->name;
            }
            return implode(', ', $targetPopulations);
        } else {
            return '';
        }
    }
}
