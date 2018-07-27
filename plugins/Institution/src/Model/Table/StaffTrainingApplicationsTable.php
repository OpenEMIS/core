<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;
use Cake\Validation\Validator;
use Cake\Event\Event;

use App\Model\Table\ControllerActionTable;
use Workflow\Model\Behavior\WorkflowBehavior;

class StaffTrainingApplicationsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        $this->table('staff_training_applications');
        parent::initialize($config);

        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Sessions', ['className' => 'Training.TrainingSessions', 'foreignKey' => 'training_session_id']);
        $this->belongsTo('Staff', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users', 'foreignKey' => 'assignee_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);

        $this->addBehavior('Institution.InstitutionWorkflowAccessControl');
        $this->addBehavior('ControllerAction.FileUpload', [
            'name' => 'file_name',
            'content' => 'file_content',
            'useDefaultName' => true
        ]);

        $this->addBehavior('Workflow.Workflow', ['model' => 'Training.TrainingApplications']);

        $this->toggle('edit', false);
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $modelAlias = 'Applications';
        $userType = 'StaffUser';
        $this->controller->changeUserHeader($this, $modelAlias, $userType);
        $this->setupTabElements();

        $session = $this->request->session();
        $extra['staffId'] = $session->read('Staff.Staff.id');
        $extra['institutionId'] = $session->read('Institution.Institutions.id');
    }

    public function addBeforeAction(Event $event, ArrayObject $extra)
    {
        $query = $this->ControllerAction->getQueryString();

        if (isset($extra['redirect']['query'])) {
            unset($extra['redirect']['query']);
        }

        if ($query) {
            $sessionId = $query['training_session_id'];

            // check if user has already added this session before
            $existingApplication = $this->find()
                ->where([
                    $this->aliasField('staff_id') => $extra['staffId'],
                    $this->aliasField('training_session_id') => $sessionId
                ])
                ->first();

            if (empty($existingApplication)) {
                // save session
                if ($this->saveSession($sessionId, $extra)) {
                    $this->Alert->success($this->aliasField('success'), ['reset' => true]);
                    $event->stopPropagation();
                    return $this->controller->redirect($extra['redirect']);

                } else {
                    $this->Alert->error($this->aliasField('fail'), ['reset' => true]);
                }
            } else {
                $this->Alert->warning($this->aliasField('exists'), ['reset' => true]);
            }
        }

        $event->stopPropagation();
        return $this->controller->redirect($extra['redirect']);
    }

    private function saveSession($sessionId, ArrayObject $extra)
    {
        $staffId = $extra['staffId'];
        $institutionId = $extra['institutionId'];

        $application = [];
        $application['staff_id'] = $staffId;
        $application['training_session_id'] = $sessionId;
        $application['status_id'] = WorkflowBehavior::STATUS_OPEN;
        $application['assignee_id'] = WorkflowBehavior::AUTO_ASSIGN;
        $application['institution_id'] = $institutionId;
        $entity = $this->newEntity($application);

        if ($this->save($entity)) {
            return true;
        }

        return false;
    }

    public function indexbeforeAction(Event $event, ArrayObject $extra)
    {
        if (isset($extra['redirect']['query'])) {
            unset($extra['redirect']['query']);
        }

        // add button to course catalogue
        if (isset($extra['toolbarButtons']['add']['url'])) {
            $extra['toolbarButtons']['add']['url']['action'] = 'CourseCatalogue';
            $extra['toolbarButtons']['add']['url'][0] = 'index';
            $extra['toolbarButtons']['add']['attr']['title'] = 'Apply';
        }
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query
            ->contain(['Sessions.Courses.TrainingFieldStudies', 'Sessions.Courses.TrainingLevels'])
            ->where([$this->aliasField('staff_id') => $extra['staffId']]);

        $extra['auto_contain_fields'] = ['Sessions.Courses' => ['credit_hours']];

        // for searching course name
        $search = $this->getSearchKey();
        if (!empty($search)) {
            $extra['OR'] = [$this->Sessions->Courses->aliasField('name').' LIKE' => '%' . $search . '%'];
        }
    }

    public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        $this->field('course');
        $this->field('training_level');
        $this->field('field_of_study');
        $this->field('credit_hours');
        $this->field('training_session_id');
        $this->field('assignee_id', ['type' => 'hidden']);
        $this->field('staff_id', ['type' => 'hidden']);

        $this->setFieldOrder([
            'course', 'training_level', 'field_of_study', 'credit_hours', 'training_session_id'
        ]);
    }

    public function viewBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['Sessions.Courses.TrainingFieldStudies', 'Sessions.Courses.TrainingCourseTypes', 'Sessions.Courses.TrainingModeDeliveries', 'Sessions.Courses.TrainingRequirements', 'Sessions.Courses.TrainingLevels', 'Sessions.Courses.TargetPopulations', 'Sessions.Courses.TrainingProviders', 'Sessions.Courses.CoursePrerequisites', 'Sessions.Courses.Specialisations', 'Sessions.Courses.ResultTypes']);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->fields = [];
        $this->field('code');
        $this->field('course');
        $this->field('applied_session', ['type' => 'sessions']);
        $this->field('description');
        $this->field('objective');
        $this->field('credit_hours');
        $this->field('duration');
        $this->field('experiences');
        $this->field('field_of_study');
        $this->field('course_type');
        $this->field('mode_of_delivery');
        $this->field('training_requirement');
        $this->field('training_level');
        $this->field('target_populations');
        $this->field('training_providers');
        $this->field('course_prerequisites');
        $this->field('specialisations');
        $this->field('result_types');
        $this->field('attachment');

        $this->setFieldOrder([
            'code', 'course', 'applied_session', 'description', 'objective', 'credit_hours', 'duration', 'experiences',
            'field_of_study', 'course_type', 'mode_of_delivery', 'training_requirement', 'training_level',
            'target_populations', 'training_providers', 'course_prerequisites', 'specialisations', 'result_types', 'attachment'
        ]);
    }

    public function onGetCode(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->session->has('course')) {
            $value = $entity->session->course->code;
        }

        return $value;
    }

    public function onGetCourse(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('session') && $entity->session->has('course')) {
            $value = $entity->session->course->name;
        }

        return $value;
    }

    public function onGetSessionsElement(Event $event, $action, $entity, $attr, $options=[])
    {
        if ($action == 'view') {
            $trainingSession = $entity->session;
            $tableHeaders = [__('Code'), __('Name'), __('Start Date'), __('End Date')];

            $tableCells = [];
            $tableCells[] = $trainingSession->code;
            $tableCells[] = $trainingSession->name;
            $tableCells[] = $this->formatDate($trainingSession->start_date);
            $tableCells[] = $this->formatDate($trainingSession->end_date);

            $attr['tableHeaders'] = $tableHeaders;
            $attr['tableCells'] = $tableCells;
            return $event->subject()->renderElement('Institution.course_sessions', ['attr' => $attr]);
        }
    }

    public function onGetDescription(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('session') && $entity->session->has('course')) {
            $value = $entity->session->course->description;
        }

        return $value;
    }

    public function onGetObjective(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('session') && $entity->session->has('course')) {
            $value = $entity->session->course->objective;
        }

        return $value;
    }

    public function onGetCreditHours(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('session') && $entity->session->has('course')) {
            $value = $entity->session->course->credit_hours;
        }

        return $value;
    }

    public function onGetDuration(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('session') && $entity->session->has('course')) {
            $value = $entity->session->course->duration;
        }

        return $value;
    }

    public function onGetExperiences(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('session') && $entity->session->has('course')) {
            $value = $entity->session->course->number_of_months;
        }

        return $value;
    }

    public function onGetFieldOfStudy(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('session') && $entity->session->has('course')) {
            $value = $entity->session->course->training_field_study->name;
        }

        return $value;
    }

    public function onGetCourseType(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('session') && $entity->session->has('course')) {
            $value = $entity->session->course->training_course_type->name;
        }

        return $value;
    }

    public function onGetModeOfDelivery(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('session') && $entity->session->has('course')) {
            $value = $entity->session->course->training_mode_delivery->name;
        }

        return $value;
    }

    public function onGetTrainingRequirement(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('session') && $entity->session->has('course')) {
            $value = $entity->session->course->training_requirement->name;
        }

        return $value;
    }

    public function onGetTrainingLevel(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('session') && $entity->session->has('course')) {
            $value = $entity->session->course->training_level->name;
        }

        return $value;
    }

    public function onGetTargetPopulations(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('session') && $entity->session->has('course')) {
            if (!empty($entity->session->course->target_populations)) {
                $targetPopulations = $entity->session->course->target_populations;
                foreach ($targetPopulations as $key => $item) {
                    $data[] = $item->name;
                }

                $value = implode(', ', $data);
            }
        }

        return $value;
    }

    public function onGetTrainingProviders(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('session') && $entity->session->has('course')) {
            if (!empty($entity->session->course->training_providers)) {
                $providers = $entity->session->course->training_providers;
                foreach ($providers as $key => $item) {
                    $data[] = $item->name;
                }

                $value = implode(', ', $data);
            }
        }

        return $value;
    }

    public function onGetCoursePrerequisites(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('session') && $entity->session->has('course')) {
            if (!empty($entity->session->course->course_prerequisites)) {
                $prerequisites = $entity->session->course->course_prerequisites;
                foreach ($prerequisites as $key => $item) {
                    $data[] = $item->name;
                }

                $value = implode(', ', $data);
            }
        }

        return $value;
    }

    public function onGetSpecialisations(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('session') && $entity->session->has('course')) {
            if (!empty($entity->session->course->specialisations)) {
                $specialisations = $entity->session->course->specialisations;
                foreach ($specialisations as $key => $item) {
                    $data[] = $item->name;
                }

                $value = implode(', ', $data);
            }
        }

        return $value;
    }

    public function onGetResultTypes(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('session') && $entity->session->has('course')) {
            if (!empty($entity->session->course->result_types)) {
                $types = $entity->session->course->result_types;
                foreach ($types as $key => $item) {
                    $data[] = $item->name;
                }

                $value = implode(', ', $data);
            }
        }

        return $value;
    }

    public function onGetAttachment(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('session') && $entity->session->has('course')) {
            if (!empty($entity->session->course['file_name'])) {
                $courseId = $entity->session->course->id;
                $link = $event->subject()->HtmlField->link($entity->session->course['file_name'], [
                    'plugin' => 'Institution',
                    'controller' => 'Institutions',
                    'action' => 'CourseCatalogue',
                    'download',
                    $this->paramsEncode(['id' => $courseId])
                ]);
                $value = $link;
            }
        }
        return $value;
    }

    private function setupTabElements()
    {
        $tabElements = $this->controller->getTrainingTabElements();
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());
    }
}
