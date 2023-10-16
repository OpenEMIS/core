<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\Routing\Router;

use App\Model\Table\ControllerActionTable;

class CourseCatalogueTable extends ControllerActionTable
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

        $this->addBehavior('ControllerAction.FileUpload');

        $this->toggle('edit', false);
        $this->toggle('remove', false);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $extra['config']['selectedLink'] = ['controller' => 'Institutions', 'action' => 'StaffTrainingResults'];
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        // remove add button from index
        $this->toggle('add', false);

        $this->fields = [];
        $this->field('code');
        $this->field('name');
        $this->field('training_level_id');
        $this->field('training_field_of_study_id');
        $this->field('credit_hours');
        $this->field('sessions');
        $this->field('description', ['visible' => false]);
        $this->field('objective', ['visible' => false]);
        $this->field('file_name', ['visible' => false]);

        // back button direct to staff application index
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

        $Sessions = TableRegistry::get('Training.TrainingSessions');
        $sessionSteps = $this->Workflow->getStepsByModelCode($Sessions->registryAlias(), 'APPROVED');

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

        if (!empty($staffData) && $staffData->has('position')) {
            $positionTitle = $staffData->position->staff_position_title_id;

            $TargetPopulationTable = TableRegistry::get('Training.TrainingCoursesTargetPopulations');
            $query
                ->leftJoin(
                    [$TargetPopulationTable->alias() => $TargetPopulationTable->table()],
                    [
                        $TargetPopulationTable->aliasField('training_course_id = ') . $this->aliasField('id')
                    ]
                )
                ->where([
                    'OR' => [
                        // showing the course based on position title or target population indicates is for all
                        [$TargetPopulationTable->aliasField('target_population_id') => $positionTitle],
                        [$TargetPopulationTable->aliasField('target_population_id') => -1]
                    ]
                ]);
        } else {
            // To return no results
            $query->where(['1 = 0']);
        }

        $query
            ->contain(['TrainingSessions' => function ($q) use ($sessionSteps) {
                return $q->where([('TrainingSessions.status_id IN ') => $sessionSteps]);
            }])
            ->order($this->aliasField('code'));
    }

    public function viewBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $Sessions = TableRegistry::get('Training.TrainingSessions');
        $sessionSteps = $this->Workflow->getStepsByModelCode($Sessions->registryAlias(), 'APPROVED');
        $query->contain(['TargetPopulations', 'TrainingProviders', 'CoursePrerequisites', 'Specialisations', 'ResultTypes'])
            ->contain(['TrainingSessions' => function ($q) use ($sessionSteps) {
                return $q->where([('TrainingSessions.status_id IN ') => $sessionSteps]);
            }]);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('sessions', ['type' => 'sessions']);
        $this->field('target_populations', ['type' => 'chosenSelect']);
        $this->field('training_providers', ['type' => 'chosenSelect']);
        $this->field('course_prerequisites', ['type' => 'chosenSelect']);
        $this->field('specialisations', ['type' => 'chosenSelect']);
        $this->field('result_types', ['type' => 'chosenSelect']);
        $this->field('file_content');

        $this->field('file_name', ['visible' => false]);
        $this->field('assignee_id', ['visible' => false]);
        $this->field('status_id', ['visible' => false]);
        $this->field('modified_user_id', ['visible' => false]);
        $this->field('modified', ['visible' => false]);
        $this->field('created_user_id', ['visible' => false]);
        $this->field('created', ['visible' => false]);

        $this->setFieldOrder([
            'code', 'name', 'sessions', 'description', 'objective', 'credit_hours', 'duration', 'number_of_months',
            'training_field_of_study_id', 'training_course_type_id', 'training_mode_of_delivery_id', 'training_requirement_id', 'training_level_id',
            'target_populations', 'training_providers', 'course_prerequisites', 'specialisations', 'result_types', 'file_content'
        ]);
    }

    public function onGetSessions(Event $event, Entity $entity)
    {
        $trainingSessions = count($entity->training_sessions);
        if ($trainingSessions) {
            return $trainingSessions;
        } else {
            return __('No Training Sessions');
        }
    }

    public function onGetSessionsElement(Event $event, $action, $entity, $attr, $options=[])
    {
        if ($action == 'view') {
            $trainingSessions = $entity->training_sessions;
            $session = $this->request->session();
            $staffId = $session->read('Staff.Staff.id');

            if (count($trainingSessions) == 0) {
                return __('No Training Sessions');
            } else {
                $StaffTrainingApplications = TableRegistry::get('Institution.StaffTrainingApplications');
                $tableHeaders = [__('Code'), __('Name'), __('Start Date'), __('End Date'), ''];

                $tableCells = [];
                foreach ($trainingSessions as $trainingSession) {
                    $rowData = [];
                    $rowData[] = $trainingSession->code;
                    $rowData[] = $trainingSession->name;
                    $rowData[] = $this->formatDate($trainingSession->start_date);
                    $rowData[] = $this->formatDate($trainingSession->end_date);

                    $existingApplication = $StaffTrainingApplications->find()
                        ->where([
                            $StaffTrainingApplications->aliasField('staff_id') => $staffId,
                            $StaffTrainingApplications->aliasField('training_session_id') => $trainingSession->id
                        ])
                        ->first();

                    if (empty($existingApplication)) {
                        $params = [
                        'plugin' => 'Institution',
                        'controller' => 'Institutions',
                        'action' => 'StaffTrainingApplications',
                        '0' => 'add'
                        ];
                        $url = $this->ControllerAction->setQueryString($params, ['training_session_id' => $trainingSession->id]);
                        $applyUrl = Router::url($url);

                        $rowData[] = "<button aria-expanded='true' onclick='location.href=\"$applyUrl\"' type='button' class='btn btn-dropdown action-toggle btn-single-action'><i class='fa kd-add'></i>&nbsp;<span>".__('Apply')."</span></button>";
                    } else {
                        $rowData[] = $event->subject()->Html->link(__('Already Applied'), [
                            'plugin' => 'Institution',
                            'controller' => 'Institutions',
                            'action' => 'StaffTrainingApplications',
                            '0' => 'view',
                            '1' => $this->paramsEncode(['id' => $existingApplication->id])
                        ]);
                    }


                    $tableCells[] = $rowData;
                }

                $attr['tableHeaders'] = $tableHeaders;
                $attr['tableCells'] = $tableCells;
                return $event->subject()->renderElement('Institution.course_sessions', ['attr' => $attr]);
            }
        }
    }
}
