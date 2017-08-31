<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;

use App\Model\Traits\OptionsTrait;

class TrainingsTable extends AppTable
{
    use OptionsTrait;

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

        $this->addBehavior('Excel', ['pages' => false]);
        $this->addBehavior('Report.ReportList');
    }

    public function validationDefault(Validator $validator) {
        $validator = parent::validationDefault($validator);

        return $validator
            ->notEmpty('training_course_id', __('This field cannot be left empty'), function ($context) {
                if (isset($context['data']['feature'])) {
                    return in_array($context['data']['feature'], ['Report.TrainingResults', 'Report.TrainingSessionParticipants', 'Report.TrainingTrainers']);
                }
                return false;
            })
            ->notEmpty('training_session_id', __('This field cannot be left empty'), function ($context) {
                if (isset($context['data']['feature'])) {
                    return in_array($context['data']['feature'], ['Report.TrainingSessionParticipants', 'Report.TrainingTrainers']);
                }
                return false;
            });
    }

    public function beforeAction(Event $event)
    {
        // fix header and breadcrumbs
        $controllerName = $this->controller->name;
        $reportName = __('Trainings');
        $this->controller->Navigation->substituteCrumb($this->alias(), $reportName);
        $this->controller->set('contentHeader', __($controllerName).' - '.$reportName);

        $this->fields = [];
        $this->ControllerAction->field('feature', ['select' => false]);
        $this->ControllerAction->field('training_course_id', ['type' => 'hidden']);
        $this->ControllerAction->field('training_session_id', ['type' => 'hidden']);
        $this->ControllerAction->field('training_need_type', ['type' => 'hidden']);
        $this->ControllerAction->field('status');
        $this->ControllerAction->field('format');
    }

    public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $attr['options'] = $this->controller->getFeatureOptions($this->alias());
            $attr['onChangeReload'] = true;
            if (!isset($this->request->data[$this->alias()]['feature'])) {
                $option = $attr['options'];
                reset($option);
                $this->request->data[$this->alias()]['feature'] = key($option);
            }
            return $attr;
        }
    }

    public function onUpdateFieldTrainingNeedType(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            if (isset($this->request->data[$this->alias()]['feature'])) {
                $feature = $this->request->data[$this->alias()]['feature'];

                if (in_array($feature, ['Report.TrainingNeeds'])) {
                    $options = $this->getSelectOptions('StaffTrainingNeeds.types');
                    $attr['type'] = 'select';
                    $attr['select'] = false;
                    $attr['options'] = $options;
                    return $attr;
                }
            }
        }
    }

    public function onUpdateFieldTrainingCourseId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            if (isset($this->request->data[$this->alias()]['feature'])) {
                $feature = $this->request->data[$this->alias()]['feature'];
                if (in_array($feature, ['Report.TrainingResults', 'Report.TrainingSessionParticipants', 'Report.TrainingTrainers'])) {
                    $options = $this->Training->getCourseList();

                    if (empty($this->request->data[$this->alias()]['training_course_id'])) {
                        reset($options);
                        $this->request->data[$this->alias()]['training_course_id'] = key($options);
                    }

                    $attr['type'] = 'select';
                    $attr['select'] = false;
                    $attr['options'] = $options;
                    $attr['onChangeReload'] = 'changeTrainingCourseId';
                    return $attr;
                }
            }
        }
    }

    public function addOnChangeTrainingCourseId(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        if (array_key_exists($this->alias(), $data)) {
            if (array_key_exists('training_session_id', $data[$this->alias()])) {
                unset($data[$this->alias()]['training_session_id']);
            }
        }
    }

    public function onUpdateFieldTrainingSessionId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            if (isset($this->request->data[$this->alias()]['feature'])) {
                $feature = $this->request->data[$this->alias()]['feature'];

                if (in_array($feature, ['Report.TrainingSessionParticipants', 'Report.TrainingTrainers'])) {
                    if (!empty($this->request->data[$this->alias()]['training_course_id'])) {
                        $courseId = $this->request->data[$this->alias()]['training_course_id'];
                        $options = $this->Training->getSessionList(['training_course_id' => $courseId]);
                    } else {
                        $options = [];
                    }

                    $attr['options'] = $options;
                    $attr['type'] = 'select';
                    $attr['select'] = false;
                    return $attr;
                }
            }
        }
    }

    public function onUpdateFieldStatus(Event $event, array $attr, $action, Request $request)
    {
        $excludedFeature = ['Report.TrainingSessionParticipants', 'Report.TrainingTrainers'];

        if ($action == 'add') {
            if (isset($this->request->data[$this->alias()]['feature'])) {
                $feature = $this->request->data[$this->alias()]['feature'];

                switch ($feature) {
                    case 'Report.TrainingNeeds':
                        $modelAlias = 'Institution.StaffTrainingNeeds';
                        break;
                    case 'Report.TrainingCourses':
                        $modelAlias = 'Training.TrainingCourses';
                        break;
                    case 'Report.TrainingSessions':
                        $modelAlias = 'Training.TrainingSessions';
                        break;
                    case 'Report.TrainingResults':
                        $modelAlias = 'Training.TrainingSessionResults';
                        break;
                    case 'Report.StaffTrainingApplications':
                        $modelAlias = 'Training.TrainingApplications';
                        break;
                }

                // POCOR-4072 participant and trainer report doesnt need workflow status.
                if (in_array($feature, $excludedFeature)) {
                    $attr['visible'] = false;
                } else {
                    $workflowStatuses = $this->Workflow->getWorkflowStatuses($modelAlias);
                    $workflowStatuses = ['-1' => __('All Statuses')] + $workflowStatuses;

                    $attr['type'] = 'select';
                    $attr['select'] = false;
                    $attr['options'] = $workflowStatuses;
                }
                // End POCOR-4072

                return $attr;
            }
        }
    }
}
