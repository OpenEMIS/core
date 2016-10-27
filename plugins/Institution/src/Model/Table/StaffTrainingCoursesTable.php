<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\ORM\TableRegistry;

use App\Model\Table\ControllerActionTable;

class StaffTrainingCoursesTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('training_courses');
        parent::initialize($config);
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('TrainingFieldStudies', ['className' => 'Training.TrainingFieldStudies', 'foreignKey' => 'training_field_of_study_id']);
        $this->belongsTo('TrainingCourseTypes', ['className' => 'Training.TrainingCourseTypes', 'foreignKey' => 'training_course_type_id']);
        $this->belongsTo('TrainingModeDeliveries', ['className' => 'Training.TrainingModeDeliveries', 'foreignKey' => 'training_mode_of_delivery_id']);
        $this->belongsTo('TrainingRequirements', ['className' => 'Training.TrainingRequirements', 'foreignKey' => 'training_requirement_id']);
        $this->belongsTo('TrainingLevels', ['className' => 'Training.TrainingLevels', 'foreignKey' => 'training_level_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users']);
        $this->hasMany('TrainingSessions', ['className' => 'Training.TrainingSessions', 'foreignKey' => 'training_course_id', 'dependent' => true, 'cascadeCallbacks' => true]);
        $this->hasMany('TrainingNeeds', ['className' => 'Staff.TrainingNeeds', 'foreignKey' => 'course_id', 'dependent' => true, 'cascadeCallbacks' => true]);
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

        $this->setDeleteStrategy('restrict');

        $this->toggle('add', false);
        $this->toggle('edit', false);
        $this->toggle('remove', false);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $extra['config']['selectedLink'] = ['controller' => 'Institutions', 'action' => 'StaffTrainingResults'];
        $this->controller->set('contentHeader', __('Staff Training Courses'));
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->fields=[];
        $this->field('code');
        $this->field('name');
        $this->field('training_course_type_id');
        $this->field('credit_hours');
        $this->field('description');
        $this->field('objective', ['visible' => false]);
        $this->field('file_name', ['visible' => false]);

        // back button direct to staff course application index
        $backBtn['type'] = 'button';
        $backBtn['label'] = '<i class="fa kd-back"></i>';
        $backBtn['attr'] = [
            'class' => 'btn btn-xs btn-default',
            'data-toggle' => 'tooltip',
            'data-placement' => 'bottom',
            'escape' => false,
            'title' => 'Back'
        ];
        $backBtn['url']= [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'StaffTrainingApplications',
            '0' => 'index'
        ];
        $extra['toolbarButtons']['back'] = $backBtn;
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        // courses must be approved
        $Courses = TableRegistry::get('Training.TrainingCourses');
        $steps = $this->Workflow->getStepsByModelCode($Courses->registryAlias(), 'APPROVED');
        if (!empty($steps)) {
            $query->where([
                $this->aliasField('status_id IN') => $steps
            ]);
        }

        $session = $this->request->session();
        $staffId = $session->read('Staff.Staff.id');
        $InstitutionId = $session->read('Institution.Institutions.id');

        $StaffStatuses = TableRegistry::get('Staff.StaffStatuses');
        $assignedStatus = $StaffStatuses->getIdByCode('ASSIGNED');

        $Staff = TableRegistry::get('Institution.Staff');
        $staffData = $Staff->find()
            ->contain('Positions')
            ->where([
                $Staff->aliasField('staff_id') => $staffId,
                $Staff->aliasField('institution_id') => $InstitutionId,
                $Staff->aliasField('staff_status_id') => $assignedStatus
            ])
            ->first();

        $positionTitle = '';
        if ($staffData->has('position')) {
            $positionTitle = $staffData->position->staff_position_title_id;

            // only show courses where user is in target population
            $query
                ->matching('TargetPopulations')
                ->where(['TargetPopulations.id' => $positionTitle]);
        }
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('file_name', ['visible' => false]);
        $this->field('file_content', ['visible' => false]);
        $this->field('assignee_id', ['visible' => false]);
        $this->field('status_id', ['visible' => false]);
        $this->field('modified_user_id', ['visible' => false]);
        $this->field('modified', ['visible' => false]);
        $this->field('created_user_id', ['visible' => false]);
        $this->field('created', ['visible' => false]);

        // back button direct to staff course application index
        $addBtn['type'] = 'button';
        $addBtn['label'] = '<i class="fa kd-add"></i>';
        $addBtn['attr'] = [
            'class' => 'btn btn-xs btn-default',
            'data-toggle' => 'tooltip',
            'data-placement' => 'bottom',
            'escape' => false,
            'title' => 'Add'
        ];

        $url = [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'StaffTrainingApplications',
            '0' => 'add'
        ];
        $addBtn['url']= $this->setQueryString($url, ['course_id' => $entity->id]);
        $extra['toolbarButtons']['add'] = $addBtn;
    }

    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons) {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        $buttons['add'] = [
            'label' => '<i class="fa kd-add"></i>'.__('Add'),
            'attr' => $buttons['view']['attr']
        ];

        $url = [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'StaffTrainingApplications',
            '0' => 'add'
        ];

        $buttons['add']['url'] = $this->setQueryString($url, ['course_id' => $entity->id]);

        return $buttons;
    }
}
