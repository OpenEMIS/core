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
        // Starts POCOR-6592
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'guardian_id']);
        $this->addBehavior('OpenEmis.Autocomplete');
        $this->addBehavior('User.User');
        $this->addBehavior('User.AdvancedNameSearch');
        // Ends POCOR-6592
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
        // Starts POCOR-6593
        $feature = $this->request->data[$this->alias()]['feature'];
        if ($feature == 'Report.TrainingSessions') {
        $this->ControllerAction->field('session_start_date',['type' => 'date']);
        $this->ControllerAction->field('session_end_date',['type' => 'date']);
        $this->ControllerAction->field('area_education_id');
        }
        // Ends POCOR-6593
        //$this->ControllerAction->field('status');
        // Starts POCOR-6592
        if ($this->request->data[$this->alias()]['feature'] ==  'Report.EmployeeTrainingCard') {
        $this->ControllerAction->field('guardian_id');
        }else{
           $this->ControllerAction->field('status'); 
        }
        // Ends POCOR-6592
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
                        if ($courseId == -1 ) {
                            $options = ['-1' => _('All training sessions.')] + $options; //POCOR-6595
                        }
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
    
    /**
    * Add Autocomplete For staff
    * @author Akshay Patodi <akshay.patodi@mail.valuecoders.com>
    * @ticket POCOR-6592
    */
    // Starts POCOR-6592
    public function onUpdateFieldGuardianId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            
            if (isset($this->request->data[$this->alias()]['feature'])) {
                $feature = $this->request->data[$this->alias()]['feature'];

            if (in_array($feature, ['Report.EmployeeTrainingCard'])) {
            $attr['type'] = 'autocomplete';
            $attr['target'] = ['key' => 'guardian_id', 'name' => $this->aliasField('guardian_id')];
            $attr['noResults'] = __('No Guardian found.');
            $attr['attr'] = ['placeholder' => __('OpenEMIS ID, Identity Number or Name')];
            $action = 'Guardians';
            if ($this->controller->name == 'Reports') {
                $action = 'StudentGuardians';
            }
            $attr['url'] = ['controller' => $this->controller->name, 'action' => $action, 'ajaxUserStaffAutocomplete'];
            $requestData = $this->request->data;
            if (isset($requestData) && !empty($requestData[$this->alias()]['guardian_id'])) {
                $guardianId = $requestData[$this->alias()]['guardian_id'];
                $guardianName = $this->Users->get($guardianId)->name_with_id;

                $attr['attr']['value'] = $guardianName;
            }

            $iconSave = '<i class="fa fa-check"></i> ' . __('Save');
            $iconAdd = '<i class="fa kd-add"></i> ' . __('Create New');
            $attr['onNoResults'] = "$('.btn-save').html('" . $iconAdd . "').val('new')";
            $attr['onBeforeSearch'] = "$('.btn-save').html('" . $iconSave . "').val('save')";
            $attr['onSelect'] = "$('#reload').click();";
        }
        }
        } elseif ($action == 'index') {
            $attr['sort'] = ['field' => 'Guardians.first_name'];
        }
        return $attr;
    }

    public function implementedEvents()
    {

        $events = parent::implementedEvents();
        $events['ControllerAction.Model.ajaxUserStaffAutocomplete'] = 'ajaxUserStaffAutocomplete';
        return $events;
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'guardian_id':
                return __('Staff');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
    //ENDS POCOR-6592
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

     /**

     * Add Start Date, End Date, area Education Fields Added   

     * @author Akshay Patodi <akshay.patodi@mail.valuecoders.com>

     * @ticket POCOR-6593

     */
        // Starts POCOR-6593
     public function onUpdateFieldSessionStartDate(Event $event, array $attr, $action, Request $request)
    {
       
        $feature = $this->request->data[$this->alias()]['feature'];

        if ($feature!='Report.TrainingSessions') {

                    $attr['visible'] = false;
            }
                                
        return $attr;
    }

    public function onUpdateFieldSessionEndDate(Event $event, array $attr, $action, Request $request)
    {
       
        $feature = $this->request->data[$this->alias()]['feature'];

        if ($feature!='Report.TrainingSessions') {

                    $attr['visible'] = false;
            }
                                
        return $attr;
    }

    public function onUpdateFieldAreaEducationId(Event $event, array $attr, $action, Request $request)
    { 
        if (isset($this->request->data[$this->alias()]['feature'])) {
            $feature = $this->request->data[$this->alias()]['feature'];
           // $areaLevelId = $this->request->data[$this->alias()]['area_level_id'];//POCOR-6333
            if ($feature=='Report.TrainingSessions')  {
                
                $Areas = TableRegistry::get('areas');
                $entity = $attr['entity'];

                if ($action == 'add') {

                    $where = [];
                        
                        $areas = $Areas
                            ->find('list')
                           // ->where([$where])
                            ->order([$Areas->aliasField('order')]);
                        $areaOptions = $areas->toArray();
                        $attr['type'] = 'chosenSelect';
                        $attr['attr']['multiple'] = true;
                        //$attr['select'] = true;
                        /*POCOR-6333 starts*/
                        if (count($areaOptions) > 1) {
                            $attr['options'] = ['' => '-- ' . _('Select') . ' --', '-1' => _('All Areas')] + $areaOptions;
                        } else {
                            $attr['options'] = ['' => '-- ' . _('Select') . ' --'] + $areaOptions;
                        }
                        /*POCOR-6333 ends*/
                        $attr['onChangeReload'] = true;
                } else {
                    $attr['type'] = 'hidden';
                }
            }
        }
        return $attr;
    }

    // Ends POCOR-6593
}
