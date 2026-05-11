<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\EventInterface;
use Cake\Http\ServerRequest;
use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use Cake\ORM\TableRegistry;
use Cake\I18n\Date;

use App\Model\Traits\OptionsTrait;

class TrainingsTable extends AppTable
{
    use OptionsTrait;

    public function initialize(array $config): void
    {
        $this->setTable('training_courses');
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
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'is_guardian']);
        $this->addBehavior('OpenEmis.Autocomplete');
        $this->addBehavior('User.User');
        $this->addBehavior('User.AdvancedNameSearch');
        // Ends POCOR-6592
    }
    

    //POCOR - 7415 start
    public function addBeforeAction(EventInterface $event)
    {
        $this->ControllerAction->field('area_education_id', ['type' => 'hidden', 'attr' => ['label'=>'Area Name']]);
    }
    //POCOR - 7415 end

    public function validationDefault(Validator $validator): Validator {
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

    public function beforeAction(EventInterface $event)
    {
        // fix header and breadcrumbs
        $controllerName = $this->controller->getName();
        $reportName = __('Trainings');
        $this->controller->Navigation->substituteCrumb($this->getAlias(), $reportName);
        $this->controller->set('contentHeader', __($controllerName).' - '.$reportName);
        $this->fields = [];
        $feature = $this->request->getData()[$this->getAlias()]['feature'];
        $this->ControllerAction->field('feature', ['select' => false]);
        if ($feature == 'Report.TrainingSessionParticipants'){//POCOR-6828 change position of field
            $this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);
        }
        $this->ControllerAction->field('training_course_id', ['type' => 'hidden']);
        $this->ControllerAction->field('training_session_id', ['type' => 'hidden']);
        $this->ControllerAction->field('training_need_type', ['type' => 'hidden']);
        // Starts POCOR-6593
        if ($feature == 'Report.TrainingSessions') {
            $this->ControllerAction->field('session_start_date',['type' => 'date']);
            $this->ControllerAction->field('session_end_date',['type' => 'date']);
            $this->ControllerAction->field('area_education_id');
        }
        // Ends POCOR-6593
        //Start:POCOR-6829 
        if($feature == 'Report.TrainingTrainers'){
            $this->ControllerAction->field('academic_period_id');
            $this->ControllerAction->field('training_course_id');
            $this->ControllerAction->field('training_session_id');
		    $this->ControllerAction->field('format');
        }
        //End:POCOR-6829
        // Starts POCOR-6592
        if ($this->request->getData()[$this->getAlias()]['feature'] ==  'Report.EmployeeTrainingCard') {
            $this->ControllerAction->field('is_guardian');
            // $this->ControllerAction->field('format'); 
        }else if ($feature == 'Report.TrainingSessionParticipants'){//POCOR-6828 starts add condition for report TrainingSessionParticipants
            $this->ControllerAction->field('status'); 
            $this->ControllerAction->field('institution_status');
            $this->ControllerAction->field('format'); //POCOR-6828 ends
            //$this->ControllerAction->field('format'); 
        }else if ($feature != 'Report.TrainingResults'){
            $this->ControllerAction->field('status'); 
           // $this->ControllerAction->field('format'); 
            $this->ControllerAction->field('institution_status');
            $this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);
        }
        
        if ($feature == 'Report.TrainingResults') {
            $this->ControllerAction->field('status');// POCOR-6596
            $this->ControllerAction->field('session_name', ['type' => 'hidden']); // POCOR-6596
        }    
        $this->ControllerAction->field('start_date', ['type' => 'hidden']);  // POCOR-6569
        $this->ControllerAction->field('end_date', ['type' => 'hidden']);  // POCOR-6569
        // perivous (Ends POCOR-6592) latest ticket (POCOR-6827) only change the field order 
        if($feature == 'Report.TrainersSessions'){
            $this->ControllerAction->field('trainer_name', ['type' => 'hidden']);  // POCOR-6569
        }
        
        // Start POCOR-6596 Changed position of format field 
        if ($feature == 'Report.TrainingResults') {
            $this->ControllerAction->field('institution_status');
            $this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);
            $this->ControllerAction->field('area_id', ['type' => 'hidden']); // POCOR-6596
            //$this->ControllerAction->field('format');// POCOR-6596
        }
        $this->ControllerAction->field('format');
        // End POCOR-6596 Changed position of format field
    }

    public function onUpdateFieldFeature(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $option = $this->controller->getFeatureOptions($this->getAlias());
            $attr['options'] = $this->controller->getFeatureOptions($this->getAlias());
            $attr['onChangeReload'] = true;
            if (!isset($this->request->getData($this->getAlias())['feature'])) {
                $option = $attr['options'];
                reset($option);
                $defaultFeatureValue = key($option);
                $this->request = $this->request->withData($this->getAlias() . '.feature', $defaultFeatureValue);
            }
            return $attr;
        }
    }

    public function onUpdateFieldTrainingNeedType(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            if (isset($this->request->getData($this->getAlias())['feature'])) {
                $feature = $this->request->getData($this->getAlias())['feature'];

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

    public function onUpdateFieldTrainingCourseId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            if (isset($this->request->getData($this->getAlias())['feature'])) {
                $feature = $this->request->getData($this->getAlias())['feature'];
                if (in_array($feature, ['Report.TrainingResults', 'Report.TrainingSessionParticipants', 'Report.TrainingTrainers', 'Report.TrainersSessions'])) { // POCOR-6569
                    $options = $this->Training->getCourseList();
                    $options = ['' => '-- ' . __('Select') . ' --', '-1' => __('All Training Courses')] + $options; //POCOR-6595
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

    public function addOnChangeTrainingCourseId(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        if (array_key_exists($this->getAlias(), (array) $data)) {
            if (array_key_exists('training_session_id', (array) $data[$this->getAlias()])) {
                unset($data[$this->getAlias()]['training_session_id']);
            }
        }
    }

    public function onUpdateFieldTrainingSessionId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            if (isset($this->request->getData($this->getAlias())['feature'])) {
                $feature = $this->request->getData($this->getAlias())['feature'];
                if (in_array($feature, ['Report.TrainingSessionParticipants', 'Report.TrainingTrainers'])) {
                    if (!empty($this->request->getData($this->getAlias())['training_course_id'])) {
                        $courseId = $this->request->getData($this->getAlias())['training_course_id'];
                        $options = $this->Training->getSessionList(['training_course_id' => $courseId]);
                        //POCOR-6828 Starts
                        $attr['type'] = 'chosenSelect';
                        $attr['attr']['multiple'] = false;
                        $attr['select'] = true;
                        $attr['options'] = ['' => '-- ' . ('Select') . ' --', '-1' => ('All training sessions')] + $options;
                        $attr['onChangeReload'] = true;//POCOR-6828 Ends
                    } else {
                        $attr['type'] = 'hidden';
                    }
                }
            }
            return $attr;
        }
    }
    //POCOR-6637::START
    public function addAfterAction(EventInterface $event, Entity $entity)
    {
        if ($entity->has('feature')) {
            $feature = $entity->feature;

            $fieldsOrder = ['feature'];
            switch ($feature) { 
                case 'Report.ReportTrainingNeedStatistics': 
                    $fieldsOrder[] = 'academic_period_id';
                    $fieldsOrder[] = 'institution_status';
                    $fieldsOrder[] = 'format';
                case 'Report.TrainersSessions': 
                    $fieldsOrder[] = 'training_course_id';
                    $fieldsOrder[] = 'start_date';
                    $fieldsOrder[] = 'end_date';
                    $fieldsOrder[] = 'trainer_name';
                    $fieldsOrder[] = 'format';
                    break;
                default:
                    break;
            }

            $this->ControllerAction->setFieldOrder($fieldsOrder);
        }
    }
    //POCOR-6637::END

    public function onUpdateFieldStatus(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $excludedFeature = ['Report.TrainingSessionParticipants', 'Report.TrainingTrainers', 'Report.TrainersSessions', 'Report.ReportTrainingNeedStatistics','Report.TrainingEmployeeQualification']; // POCOR-6569

        if ($action == 'add') {
            if (isset($this->request->getData($this->getAlias())['feature'])) {
                $feature = $this->request->getData($this->getAlias())['feature'];

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
    public function onUpdateFieldIsGuardian(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            if (isset($this->request->getData($this->getAlias())['feature'])) {
                $feature = $this->request->getData($this->getAlias())['feature'];
                if (in_array($feature, ['Report.EmployeeTrainingCard'])) {
                    $attr['type'] = 'autocomplete';
                    $attr['target'] = ['key' => 'guardian_id', 'name' => $this->aliasField('guardian_id')];
                    $attr['noResults'] = __('No Guardian found.');
                    $attr['attr'] = ['placeholder' => __('OpenEMIS ID, Identity Number or Name')];
                    $action = 'Guardians';
                    if ($this->controller->getName() == 'Reports') {
                        $action = 'Trainings';
                    }
                    //POCOR-8249 change ajax file 
                    $attr['url'] = ['controller' => $this->controller->getName(), 'action' => $action, 'ajaxUserAutocomplete'];
                    $requestData = $this->request->getData();
                    if (isset($requestData) && !empty($requestData[$this->getAlias()]['is_guardian'])) {
                        $guardianId = $requestData[$this->getAlias()]['guardian_id'];
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

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['ControllerAction.Model.ajaxUserAutocomplete'] = 'ajaxUserAutocomplete';
        return $events;
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        switch ($field) {
            case 'feature':
                return __('Feature');
            case 'format':
                return __('Format');
            case 'academic_period_id':
                return __('Academic Period');
            case 'training_need_type':
                return __('Training Need Type');
            case 'training_course_id':
                return __('Training Course');
            case 'status':
                return __('Status');
            case 'training_session_id':
                return __('Training Session');
            case 'is_guardian':
                return __('Staff');
            case 'session_start_date':
                return __('Session Start Date');
            case 'session_end_date':
                return __('Session End Date');
            case 'session_name':
                return __('Session Name');
            case 'start_date':
                return __('Start Date');
            case 'end_date':
                return __('End Date');
            case 'area_id':
                return __('Area');
            case 'institution_status':
                return __('Institution Status');    
            case 'trainer_name':
                return __('Trainer Name');
            default:
                return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }
    //ENDS POCOR-6592
    public function onUpdateFieldInstitutionStatus(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $includedFeature     = ['Report.ReportTrainingNeedStatistics'];
        $InstitutionStatuses = TableRegistry::getTableLocator()->get('Institution.Statuses');
        $statuses            = $InstitutionStatuses->findIdList();

        if ($action == 'add') {
            if (isset($this->request->getData($this->getAlias())['feature'])) {
                $feature = $this->request->getData($this->getAlias())['feature'];
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

    public function onUpdateFieldAcademicPeriodId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $includedFeature = ['Report.ReportTrainingNeedStatistics','Report.TrainingTrainers','Report.TrainingSessionParticipants'];//POCOR-6828 add 'Report.TrainingSessionParticipants'
        if (isset($request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];
            if (in_array($feature, $includedFeature)) {
                $AcademicPeriodTable = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
                $academicPeriodOptions = $AcademicPeriodTable->getYearList();
                $currentPeriod = $AcademicPeriodTable->getCurrent();
                $attr['options'] = $academicPeriodOptions;
                $attr['type'] = 'select';
                $attr['select'] = false;
                $attr['onChangeReload'] = true;
                if (empty($request->getData($this->getAlias())['academic_period_id'])) {
                    $request->getData($this->getAlias())['academic_period_id'] = $currentPeriod;
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
    public function onUpdateFieldTrainerName(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $includedFeature = ['Report.TrainersSessions'];
        if (isset($request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];
            $trainingCourseId = $this->request->getData($this->getAlias())['training_course_id'];
            $startDate = date("Y-m-d", strtotime($this->request->getData($this->getAlias())['start_date'])); 
            $endDate = date("Y-m-d", strtotime($this->request->getData($this->getAlias())['end_date']));
            if (in_array($feature, $includedFeature)) {
                /*$training_trainer_object = TableRegistry::getTableLocator()->get('Report.TrainingTrainers');
                $trainers = $training_trainer_object->getTrainers();*/
                // POCOR-6827 start
                $training_trainer = TableRegistry::getTableLocator()->get('Training.TrainingSessionTrainers');
                $training_session_object = TableRegistry::getTableLocator()->get('Training.TrainingSessions');
                $training_Session = TableRegistry::getTableLocator()->get('Training.TrainingSessionTrainers');
                $session = TableRegistry::getTableLocator()->get('Training.TrainingSessions');
                $getCourses = TableRegistry::getTableLocator()->get('Training.TrainingCourses');
                $trainer = $training_Session->find('list', ['keyField' => 'id', 'valueField' => 'name'])
                        ->select([
                            'id' => $training_Session->aliasField('id'),
                            'name' => $training_Session->aliasField('name')
                        ])
                        ->leftJoin([$session->getAlias() => $session->getTable()], 
                            [$session->aliasField('id = ') . $training_Session->aliasField('training_session_id')
                        ])
                        ->leftJoin([$getCourses->getAlias() => $getCourses->getTable()], 
                            [$getCourses->aliasField('id = ') . $session->aliasField('training_course_id')
                        ])
                        ->where([
                            $session->aliasField('training_course_id')=>$trainingCourseId,
                            $session->aliasField('start_date') . ' >= ' => $startDate,
                            $session->aliasField('end_date') . ' <= ' => $endDate,
                        ])
                        ->group([$training_trainer->aliasField('trainer_id')])
                        ->enableHydration(false)
                        ->toArray();
                $trainer_options = ['-1' => __('All Trainer')] + $trainer;
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
     * @ticket POCOR-6596
     */
    public function onUpdateFieldStartDate(EventInterface $event, array $attr, $action, $request)
    {
        $feature = $this->request->getData($this->getAlias())['feature'];
        $includedFeature = ['Report.TrainersSessions', 'Report.TrainingResults'];
        if (in_array($feature, $includedFeature)) {
            $entity = $attr['entity'];
            $attr['type'] = 'date';
            $attr['onChangeReload'] = true;  // POCOR-6827
            // $attr['onChangeReload'] = true;
            return $attr;
        }
    }

    /**
     * Add End Date selection date picker
     * @author Anand Malvi <anand.malvi@mail.valuecoders.com>
     * @ticket POCOR-6596
     */
    public function onUpdateFieldEndDate(EventInterface $event, array $attr, $action, $request)
    {
        $feature = $this->request->getData($this->getAlias())['feature'];
        $includedFeature = ['Report.TrainersSessions', 'Report.TrainingResults'];
        if (in_array($feature, $includedFeature)) {
            $entity = $attr['entity'];
            $attr['type'] = 'date';
            $attr['onChangeReload'] = true;  // POCOR-6827
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
    public function onUpdateFieldSessionStartDate(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $feature = $this->request->getData($this->getAlias())['feature'];
        if ($feature!='Report.TrainingSessions') {
            $attr['visible'] = false;
        }
        return $attr;
    }

    public function onUpdateFieldSessionEndDate(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $feature = $this->request->getData($this->getAlias())['feature'];
        if ($feature!='Report.TrainingSessions') {
            $attr['visible'] = false;
        }
        return $attr;
    }

    public function onUpdateFieldAreaEducationId(EventInterface $event, array $attr, $action, ServerRequest $request)
    { 
        if (isset($this->request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];
           // $areaLevelId = $this->request->getData($this->getAlias())['area_level_id'];//POCOR-6333
            if ($feature=='Report.TrainingSessions')  {
                $Areas = TableRegistry::getTableLocator()->get('Area.Areas');
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
                        $attr['options'] = ['' => '-- ' . __('Select') . ' --', '-1' => __('All Areas')] + $areaOptions;
                    } else {
                        $attr['options'] = ['' => '-- ' . __('Select') . ' --'] + $areaOptions;
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

    /**
     * Add Trainer selection drop-down
     * @author Anand Malvi <anand.malvi@mail.valuecoders.com>
     * @ticket POCOR-6596
     */
    public function onUpdateFieldSessionName(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $includedFeature = ['Report.TrainingResults'];
        if (isset($request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];
            if (in_array($feature, $includedFeature)) {
                /************** START::POCOR-6830 */
                if ($action == 'add') {
                    $courseId = $this->request->getData($this->getAlias())['training_course_id'];
                    $training_session_object = TableRegistry::getTableLocator()->get('Training.TrainingSessions');
                    $session = $training_session_object->getCourses($courseId);
                    $attr['type'] = 'chosenSelect';
                    $attr['attr']['multiple'] = false;
                    $attr['select'] = true;
                    $attr['options'] = ['' => '-- ' . __('Select') . ' --', '-1' => __('All Session')] + $session;
                    $attr['onChangeReload'] = true;
                } else {
                    $attr['type'] = 'hidden';
                }
            }
        }
        return $attr; /************** END::POCOR-6830 */
    }

    /**
     * Add Area selection drop-down with multiple select feature
     * @author Anand Malvi <anand.malvi@mail.valuecoders.com>
     * @ticket POCOR-6596
     */
    public function onUpdateFieldAreaId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $includedFeature = ['Report.TrainingResults'];
        if (isset($request->getData($this->getAlias())['feature'])) {
            $feature = $this->request->getData($this->getAlias())['feature'];
            if (in_array($feature, $includedFeature)) {
                $Areas = TableRegistry::getTableLocator()->get('Area.Areas');
                $area_options = $Areas->getAreas();
                $attr['type'] = 'chosenSelect';
                $attr['attr']['multiple'] = true;
                $attr['select'] = false;
                $attr['options'] = ['-1' => __('All Areas')] + $area_options;
                return $attr;
            }
        }
    }

    //POCOR-8249
    public function ajaxUserAutocomplete()
    {
        $this->controller->autoRender = false;
        $this->ControllerAction->autoRender = false;
        $Users = TableRegistry::getTableLocator()->get('Security.Users');
        if ($this->request->is(['ajax'])) {
            $term = $this->request->getQuery['term'];

            $UserIdentitiesTable = TableRegistry::getTableLocator()->get('User.Identities');

            $query = $Users
                ->find()
                ->select([
                    $Users->aliasField('openemis_no'),
                    $Users->aliasField('first_name'),
                    $Users->aliasField('middle_name'),
                    $Users->aliasField('third_name'),
                    $Users->aliasField('last_name'),
                    $Users->aliasField('preferred_name'),
                    $Users->aliasField('id')
                ])
                ->leftJoin(
                    [$UserIdentitiesTable->getAlias() => $UserIdentitiesTable->getTable()],
                    [
                        $UserIdentitiesTable->aliasField('security_user_id') . ' = ' . $Users->aliasField('id')
                    ]
                )
                ->group([
                    $Users->aliasField('id')
                ])
                ->limit(100);

            $term = trim($term);

            if (!empty($term)) {
                $query = $this->addSearchConditions($query, ['alias' => 'Users', 'searchTerm' => $term, 'OR' => ['`Identities`.number LIKE ' => $term . '%']]);
            }

            $list = $query->all();

            $data = [];
            foreach ($list as $obj) {
                $label = sprintf('%s - %s', $obj->openemis_no, $obj->name);
                $data[] = ['label' => $label, 'value' => $obj->id];
            }

            echo json_encode($data);
            die;
        }
    }
    
}
