<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\ORM\TableRegistry;

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
        $this->ControllerAction->field('institution_status');
        $this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);
        $this->ControllerAction->field('trainer_name', ['type' => 'hidden']);  // POCOR-6569
        $this->ControllerAction->field('start_date', ['type' => 'hidden']);  // POCOR-6569
        $this->ControllerAction->field('end_date', ['type' => 'hidden']);  // POCOR-6569
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
                if (in_array($feature, ['Report.TrainingResults', 'Report.TrainingSessionParticipants', 'Report.TrainingTrainers', 'Report.TrainersSessions'])) { // POCOR-6569
                    $options = $this->Training->getCourseList();
                    $options = ['' => '-- ' . _('Select') . ' --', '-1' => _('All Training Courses')] + $options; //POCOR-6595

					// $options = ['-1' => __('All Training Courses')] + $options;

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
        $excludedFeature = ['Report.TrainingSessionParticipants', 'Report.TrainingTrainers', 'Report.TrainersSessions', 'Report.ReportTrainingNeedStatistics']; // POCOR-6569

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

    public function onUpdateFieldInstitutionStatus(Event $event, array $attr, $action, Request $request)
    {
        $includedFeature     = ['Report.ReportTrainingNeedStatistics'];
        $InstitutionStatuses = TableRegistry::get('Institution.Statuses');
        $statuses            = $InstitutionStatuses->findIdList();

        if ($action == 'add') {
            if (isset($this->request->data[$this->alias()]['feature'])) {
                $feature = $this->request->data[$this->alias()]['feature'];
                if (in_array($feature, $includedFeature)) {
                    $institution_statuses = ['-1' => __('All Statuses')];
                    $attr['type'] = 'select';
                    $attr['select'] = false;
                    $attr['options'] = $institution_statuses + $statuses;
                } else {
                    $attr['visible'] = false;
                }
                return $attr;
            }
        }
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        $includedFeature     = ['Report.ReportTrainingNeedStatistics'];
        if (isset($request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];

            if (in_array($feature, $includedFeature)) {
                $AcademicPeriodTable = TableRegistry::get('AcademicPeriod.AcademicPeriods');
                $academicPeriodOptions = $AcademicPeriodTable->getYearList();
                $currentPeriod = $AcademicPeriodTable->getCurrent();
                $attr['options'] = $academicPeriodOptions;
                $attr['type'] = 'select';
                $attr['select'] = false;
                $attr['onChangeReload'] = true;
                if (empty($request->data[$this->alias()]['academic_period_id'])) {
                    $request->data[$this->alias()]['academic_period_id'] = $currentPeriod;
                }
                return $attr;
            }
        }
    }

    /**
     * Add Trainer selection drop-down
     * @author Anand Malvi <anand.malvi@mail.valuecoders.com>
     * @ticket POCOR-6569
     */
    public function onUpdateFieldTrainerName(Event $event, array $attr, $action, Request $request)
    {
        $includedFeature = ['Report.TrainersSessions'];
        if (isset($request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
            if (in_array($feature, $includedFeature)) {
                $training_trainer_object = TableRegistry::get('Report.TrainingTrainers');
                $trainers = $training_trainer_object->getTrainers();
                $trainer_options = ['-1' => __('All Trainers')] + $trainers;
                $attr['options'] = $trainer_options;
                $attr['type']    = 'select';
                $attr['select']  = false;
                $attr['onChangeReload'] = true;
                return $attr;
            }
        }
    }

    /**
     * Add Start Date selection date picker
     * @author Anand Malvi <anand.malvi@mail.valuecoders.com>
     * @ticket POCOR-6569
     */
    public function onUpdateFieldStartDate(Event $event, array $attr, $action, $request)
    {
        $feature = $this->request->data[$this->alias()]['feature'];
        $includedFeature = ['Report.TrainersSessions'];
        if (in_array($feature, $includedFeature)) {
            $entity = $attr['entity'];
            $attr['type'] = 'date';
            // $attr['onChangeReload'] = true;
            return $attr;
        }
    }

    /**
     * Add End Date selection date picker
     * @author Anand Malvi <anand.malvi@mail.valuecoders.com>
     * @ticket POCOR-6569
     */
    public function onUpdateFieldEndDate(Event $event, array $attr, $action, $request)
    {
        $feature = $this->request->data[$this->alias()]['feature'];
        $includedFeature = ['Report.TrainersSessions'];
        if (in_array($feature, $includedFeature)) {
            $entity = $attr['entity'];
            $attr['type'] = 'date';
            return $attr;
        }
    }

    /**
     * Get training courses for drop-down option selection
     * Id   as key
     * Name as value
     * @author Anand Malvi <anand.malvi@mail.valuecoders.com>
     * @ticket POCOR-6569
     */
    public function getTrainingCourseList()
    {
        return $this->find('list', ['keyField' => 'id', 'valueField' => 'name'])->toArray();
    }
}
