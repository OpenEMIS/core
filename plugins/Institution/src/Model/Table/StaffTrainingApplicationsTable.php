<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\ResultSet;
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
        $this->belongsTo('Courses', ['className' => 'Training.TrainingCourses', 'foreignKey' => 'training_course_id']);
        $this->belongsTo('Staff', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users']);
        $this->addBehavior('Institution.InstitutionWorkflowAccessControl');
        // $this->addBehavior('Restful.RestfulAccessControl', [
        //     'Dashboard' => ['index']
        // ]);

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
        $query = $this->getQueryString();

        if (isset($extra['redirect']['query'])) {
            unset($extra['redirect']['query']);
        }

        if ($query) {
            $courseId = $query['course_id'];

            // check if user has already added this course before
            $existingApplication = $this->find()
                ->where([
                    $this->aliasField('staff_id') => $extra['staffId'],
                    $this->aliasField('training_course_id') => $courseId
                ])
                ->first();

            if (empty($existingApplication)) {
                if ($this->saveCourse($courseId, $extra)) {
                    $this->Alert->success('general.add.success');
                    $event->stopPropagation();
                    return $this->controller->redirect($extra['redirect']);

                } else {
                    $this->Alert->error('general.add.failed');
                }
            } else {
                $this->Alert->warning('general.exists');
            }
        }

        $event->stopPropagation();
        return $this->controller->redirect($extra['redirect']);
    }

    private function saveCourse($courseId, ArrayObject $extra)
    {
        $staffId = $extra['staffId'];
        $institutionId = $extra['institutionId'];

        $application = [];
        $application['staff_id'] = $staffId;
        $application['training_course_id'] = $courseId;
        $application['status_id'] = 0;
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

        if (isset($extra['toolbarButtons']['add']['url'])) {
            $extra['toolbarButtons']['add']['url']['action'] = 'StaffTrainingCourses';
            $extra['toolbarButtons']['add']['url'][0] = 'index';
        }
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query
            ->contain(['Courses.TrainingFieldStudies', 'Courses.TrainingLevels'])
            ->where([$this->aliasField('staff_id') => $extra['staffId']]);

        $extra['auto_contain_fields'] = ['Courses' => ['credit_hours']];
    }

    public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        $this->field('training_course_id');
        $this->field('field_of_study');
        $this->field('credit_hours');
        $this->field('training_level');
        $this->field('assignee_id', ['type' => 'hidden']);
        $this->field('staff_id', ['type' => 'hidden']);

        $this->setFieldOrder([
            'training_course_id', 'field_of_study', 'credit_hours', 'training_level'
        ]);
    }

    public function viewBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain(['Courses.TrainingFieldStudies', 'Courses.TrainingCourseTypes', 'Courses.TrainingModeDeliveries', 'Courses.TrainingRequirements', 'Courses.TrainingLevels']);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->fields = [];
        $this->field('name');
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

        $this->setFieldOrder([
            'name', 'description', 'objective', 'credit_hours', 'duration', 'experiences',
            'training_field_of_study_id', 'training_course_type_id', 'training_mode_of_delivery_id', 'training_requirement_id', 'training_level_id',
            // 'target_populations', 'training_providers', 'course_prerequisites', 'specialisations',
        ]);
    }

    public function onGetName(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('course')) {
            $value = $entity->course->name;
        }

        return $value;
    }

    public function onGetDescription(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('course')) {
            $value = $entity->course->description;
        }

        return $value;
    }

    public function onGetObjective(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('course')) {
            $value = $entity->course->objective;
        }

        return $value;
    }

    public function onGetCreditHours(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('course')) {
            $value = $entity->course->credit_hours;
        }

        return $value;
    }

    public function onGetDuration(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('course')) {
            $value = $entity->course->duration;
        }

        return $value;
    }

    public function onGetExperiences(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('course')) {
            $value = $entity->course->number_of_months;
        }

        return $value;
    }

    public function onGetFieldOfStudy(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('course')) {
            $value = $entity->course->training_field_study->name;
        }

        return $value;
    }

    public function onGetCourseType(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('course')) {
            $value = $entity->course->training_course_type->name;
        }

        return $value;
    }

    public function onGetModeOfDelivery(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('course')) {
            $value = $entity->course->training_mode_delivery->name;
        }

        return $value;
    }

    public function onGetTrainingRequirement(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('course')) {
            $value = $entity->course->training_requirement->name;
        }

        return $value;
    }

    public function onGetTrainingLevel(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('course')) {
            $value = $entity->course->training_level->name;
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
