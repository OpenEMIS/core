<?php
namespace Training\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Network\Request;
use Cake\Datasource\ResultSetInterface;
use Cake\Event\Event;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;

class TrainingCoursesTable extends ControllerActionTable
{
    use OptionsTrait;

    // Workflow Steps - category
    const TO_DO = 1;
    const IN_PROGRESS = 2;
    const DONE = 3;

    CONST SELECT_TARGET_POPULATIONS = 1;
    CONST SELECT_ALL_TARGET_POPULATIONS = '-1';

    private $targetPopulationSelection = [];

    public function initialize(array $config)
    {
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

        $this->setDeleteStrategy('restrict');
        $this->addBehavior('Workflow.Workflow');
        $this->addBehavior('ControllerAction.FileUpload', [
            // 'name' => 'file_name',
            // 'content' => 'file_content',
            'size' => '10MB',
            'contentEditable' => true,
            'allowable_file_types' => 'all',
            'useDefaultName' => true
        ]);
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Dashboard' => ['index']
        ]);

        $this->targetPopulationSelection = $this->getSelectOptions($this->aliasField('target_population_selection'));
        $this->SENTooltipMessage = $this->getMessage('Training.TrainingCourses.special_education_needs');
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('code', [
                'ruleUnique' => [
                    'rule' => ['validateUnique'],
                    'provider' => 'table'
                ]
            ])
            ->requirePresence('target_populations')
            ->requirePresence('training_providers')
            ->requirePresence('result_types')
            ->add('duration', [
                'num' => [
                    'rule'  => 'numeric',
                    'message' =>  __('Duration must be positive with 3 digits at maximum')
                ],
                'bet' => [
                    'rule'  => ['range', 0, 999],
                    'message' => __('Duration must be positive with 3 digits at maximum')
                ]
            ])
            ->add('number_of_months', [
                'num' => [
                    'rule'  => 'numeric',
                    'message' =>  __('Experience must be positive with 3 digits at maximum')
                ],
                'bet' => [
                    'rule'  => ['range', 0, 999],
                    'message' => __('Experience must be positive with 3 digits at maximum')
                ]
            ])
            ->allowEmpty('file_content');
    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        // Type / Visible
        $visible = ['index' => false, 'view' => true, 'edit' => true, 'add' => true];
        $this->field('description', ['visible' => $visible]);
        $this->field('objective', ['visible' => $visible]);
        $this->field('duration', [
            'visible' => $visible,
            'attr' => [
                'label' => [
                    'text' => __('Duration') . ' <i class="fa fa-info-circle fa-lg fa-right icon-blue" tooltip-placement="bottom" uib-tooltip="' . __('Number of Hours') . '" tooltip-append-to-body="true" tooltip-class="tooltip-blue"></i>',
                    'escape' => false, //disable the htmlentities (on LabelWidget) so can show html on label.
                    'class' => 'tooltip-desc' //css class for label
                ]
            ]
        ]);
        $this->field('number_of_months', [
            'visible' => $visible,
            'attr' => [
                'label' => [
                    'text' => __('Experiences') . ' <i class="fa fa-info-circle fa-lg fa-right icon-blue" tooltip-placement="bottom" uib-tooltip="' . __('Number of Years') . '" tooltip-append-to-body="true" tooltip-class="tooltip-blue"></i>',
                    'escape' => false, //disable the htmlentities (on LabelWidget) so can show html on label.
                    'class' => 'tooltip-desc' //css class for label
                ]
            ]
        ]);
        $this->field('training_field_of_study_id', [
            'type' => 'select',
            'visible' => $visible
        ]);
        $this->field('training_course_type_id', [
            'type' => 'select',
            'visible' => $visible
        ]);
        $this->field('training_mode_of_delivery_id', [
            'type' => 'select',
            'visible' => $visible
        ]);
        $this->field('training_requirement_id', [
            'type' => 'select',
            'visible' => $visible
        ]);
        $this->field('training_level_id', [
            'type' => 'select',
            'visible' => $visible
        ]);
        $this->field('file_name', [
            'type' => 'hidden',
            'visible' => $visible
        ]);
        $this->field('file_content', ['visible' => $visible]);
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->setFieldOrder([
            'code', 'name', 'credit_hours'
        ]);
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $query->contain([
            'TargetPopulations', 'TrainingProviders', 'CoursePrerequisites', 'Specialisations', 'ResultTypes'
        ]);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra)
    {
        $keywords = ['target_populations', 'training_providers', 'result_types'];
        foreach ($keywords as $key => $value) {
            if (array_key_exists($this->alias(), $requestData) && array_key_exists($value, $requestData[$this->alias()])) {
                if ($requestData[$this->alias()][$value] != self::SELECT_ALL_TARGET_POPULATIONS && array_key_exists('_ids', $requestData[$this->alias()][$value]) && empty($requestData[$this->alias()][$value]['_ids'])) {
                    $requestData[$this->alias()][$value] = [];
                }
            }
        }

        $newOptions = ['associated' => ['TargetPopulations' ,'TrainingProviders', 'ResultTypes', 'CoursePrerequisites', 'Specialisations']]; //so during patch entity, it can get the necessary datas
        $arrayOptions = $patchOptions->getArrayCopy();
        $arrayOptions = array_merge_recursive($arrayOptions, $newOptions);
        $patchOptions->exchangeArray($arrayOptions);
    }

    public function addOnInitialize(Event $event, Entity $entity, ArrayObject $extra)
    {
        unset($this->request->query['course']);

        $entity->target_population_selection = self::SELECT_TARGET_POPULATIONS;
    }

    public function editOnInitialize(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->request->query['course'] = $entity->id;

        $isSelectAll = $this->checkIsSelectAll($entity);

        if ($isSelectAll) {
            $entity->target_population_selection = self::SELECT_ALL_TARGET_POPULATIONS;
        } else {
            $entity->target_population_selection = self::SELECT_TARGET_POPULATIONS;
        }
    }

    // POCOR-3989 Exclude the belongs to many model
    public function deleteOnInitialize(Event $event, Entity $entity, Query $query, ArrayObject $extra)
    {
        $extra['excludedModels'] = [
            $this->TargetPopulations->alias(),
            $this->TrainingProviders->alias(),
            $this->CoursePrerequisites->alias(),
            $this->Specialisations->alias(),
            $this->ResultTypes->alias(),
        ];
    }
    // End POCOR-3989

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        // select all target population
        $this->setAllTargetPopulations($entity);
    }

    public function onUpdateFieldSpecialEducationNeeds(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'edit' || $action == 'add') {
            $SENOptions = $this->getSelectOptions('general.yesno');
            $attr['options'] = $SENOptions;
            $attr['type'] = 'select';
            return $attr;
        }
    }

    public function onUpdateFieldCreditHours(Event $event, array $attr, $action, Request $request)
    {
        $creditHours = TableRegistry::get('Configuration.ConfigItems')->value('training_credit_hour');

        for ($i=1; $i <= $creditHours; $i++) {
            $attr['options'][$i] = $i;
        }

        return $attr;
    }

    public function onUpdateFieldTargetPopulationSelection(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $attr['options'] = $this->targetPopulationSelection;
            $attr['select'] = false;
            $attr['onChangeReload'] = true;
        }

        return $attr;
    }

    public function onUpdateFieldTargetPopulations(Event $event, array $attr, $action, Request $request)
    {
        $requestData = $request->data;
        $entity = $attr['entity'];
        $staffPositionTitleOptions = TableRegistry::get('Institution.StaffPositionTitles')->getList()->toArray();

        $targetPopulationSelection = null;
        if (isset($requestData[$this->alias()]['target_population_selection'])) {
            $targetPopulationSelection = $requestData[$this->alias()]['target_population_selection'];
        } else {
            $targetPopulationSelection = $entity->target_population_selection;
        }

        if ($targetPopulationSelection == self::SELECT_ALL_TARGET_POPULATIONS) {
            $attr['value'] = self::SELECT_ALL_TARGET_POPULATIONS;
            $attr['attr']['value'] = __('All Target Populations Selected');
            $attr['type'] = 'readonly';
        } else {
            $attr['options'] = $staffPositionTitleOptions;
        }

        return $attr;
    }

    public function onUpdateFieldTrainingProviders(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $attr['options'] = TableRegistry::get('Training.TrainingProviders')->getList()->toArray();
        }

        return $attr;
    }

    public function onUpdateFieldCoursePrerequisites(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $Courses = TableRegistry::get('Training.TrainingCourses');

            $id = $request->query('course');
            $excludes = [];
            if (!is_null($id)) {
                $excludes[$id] = $id;
            }

            $courseOptions = $this->Training->getCourseList(['excludes' => $excludes]);
            $attr['options'] = $courseOptions;
        }

        return $attr;
    }

    public function onUpdateFieldSpecialisations(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $attr['options'] = TableRegistry::get('Training.TrainingSpecialisations')->getList()->toArray();
        }

        return $attr;
    }

    public function onUpdateFieldResultTypes(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $attr['options'] = TableRegistry::get('Training.TrainingResultTypes')->getList()->toArray();
        }

        return $attr;
    }

    public function setupFields(Entity $entity)
    {
        $this->field('credit_hours', ['type' => 'select']);
        $this->field('target_population_selection', [
            'type' => 'select',
            'visible' => ['index' => false, 'view' => false, 'edit' => true, 'add' => true],
            'entity' => $entity
        ]);
        $this->field('target_populations', [
            'type' => 'chosenSelect',
            'placeholder' => __('Select Target Populations'),
            'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true],
            'attr' => ['required' => true], // to add red asterisk
            'entity' => $entity
        ]);
        $this->field('training_providers', [
            'type' => 'chosenSelect',
            'placeholder' => __('Select Providers'),
            'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true],
            'attr' => ['required' => true] // to add red asterisk
        ]);
        $this->field('course_prerequisites', [
            'type' => 'chosenSelect',
            'placeholder' => __('Select Courses'),
            'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]
        ]);
        $this->field('specialisations', [
            'type' => 'chosenSelect',
            'placeholder' => __('Select Specialisations'),
            'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true]
        ]);
        $this->field('result_types', [
            'type' => 'chosenSelect',
            'placeholder' => __('Select Result Types'),
            'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true],
            'attr' => ['required' => true] // to add red asterisk
        ]);
        $this->field('special_education_needs', [
            'visible' => ['index' => false, 'view' => true, 'edit' => true, 'add' => true],
            'attr' => [
                'label' => [
                    'text' => __('SEN') . ' <i class="fa fa-info-circle fa-lg fa-right icon-blue" tooltip-placement="bottom" uib-tooltip="' . __($this->SENTooltipMessage) . '" tooltip-append-to-body="true" tooltip-class="tooltip-blue"></i>',
                    'escape' => false, //disable the htmlentities (on LabelWidget) so can show html on label.
                    'class' => 'tooltip-desc' //css class for label
                ]

            ]
        ]);

        // Field order
        $this->setFieldOrder([
            'code', 'name', 'description', 'objective', 'credit_hours', 'duration', 'number_of_months', 'special_education_needs',
            'training_field_of_study_id', 'training_course_type_id', 'training_mode_of_delivery_id', 'training_requirement_id', 'training_level_id', 'target_population_selection',
            'target_populations', 'training_providers', 'course_prerequisites', 'specialisations', 'result_types',
            'file_name', 'file_content'
        ]);
    }

    public function findWorkbench(Query $query, array $options)
    {
        $controller = $options['_controller'];
        $session = $controller->request->session();

        $userId = $session->read('Auth.User.id');
        $Statuses = $this->Statuses;
        $doneStatus = self::DONE;

        $query
            ->select([
                $this->aliasField('id'),
                $this->aliasField('status_id'),
                $this->aliasField('code'),
                $this->aliasField('name'),
                $this->aliasField('modified'),
                $this->aliasField('created'),
                $this->Statuses->aliasField('name'),
                $this->CreatedUser->aliasField('openemis_no'),
                $this->CreatedUser->aliasField('first_name'),
                $this->CreatedUser->aliasField('middle_name'),
                $this->CreatedUser->aliasField('third_name'),
                $this->CreatedUser->aliasField('last_name'),
                $this->CreatedUser->aliasField('preferred_name')
            ])
            ->contain([$this->CreatedUser->alias()])
            ->matching($this->Statuses->alias(), function ($q) use ($Statuses, $doneStatus) {
                return $q->where([$Statuses->aliasField('category <> ') => $doneStatus]);
            })
            ->where([$this->aliasField('assignee_id') => $userId])
            ->order([$this->aliasField('created') => 'DESC'])
            ->formatResults(function (ResultSetInterface $results) {

                return $results->map(function ($row) {
                    $url = [
                        'plugin' => 'Training',
                        'controller' => 'Trainings',
                        'action' => 'Courses',
                        'view',
                        $this->paramsEncode(['id' => $row->id])
                    ];

                    if (is_null($row->modified)) {
                        $receivedDate = $this->formatDate($row->created);
                    } else {
                        $receivedDate = $this->formatDate($row->modified);
                    }

                    $row['url'] = $url;
                    $row['status'] = __($row->_matchingData['Statuses']->name);
                    $row['request_title'] = $row->code_name;
                    $row['received_date'] = $receivedDate;
                    $row['requester'] = $row->created_user->name_with_id;

                    return $row;
                });
            });

        return $query;
    }

    public function onGetFieldLabel(Event $event, $module, $field, $language, $autoHumanize = true)
    {
        if ($field == 'special_education_needs') {
            $tooltipMessage = __('');
            return __('SEN') . '&nbsp;&nbsp;<i class="fa fa-info-circle fa-lg icon-blue" tooltip-placement="bottom" uib-tooltip="' . __($this->SENTooltipMessage) . '" tooltip-append-to-body="true" tooltip-class="tooltip-blue"></i>';
        } else {
            return parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize);
        }
    }

    public function onGetSpecialEducationNeeds(Event $event, Entity $entity)
    {
        if ($this->action == 'index') {
            return $entity->special_education_needs == 1 ? '<i class="fa fa-check"></i>' : '<i class="fa fa-close"></i>';
        } elseif ($this->action == 'view') {
            $SENOptions = $this->getSelectOptions('general.yesno');
            return $SENOptions[$entity->special_education_needs];    
        }
    }

    public function onGetTargetPopulations(Event $event, Entity $entity)
    {
        // Select all target populations
        $isSelectAll = $this->checkIsSelectAll($entity);

        if ($this->action == 'view' && $isSelectAll) {
            $StaffPositionTitles = TableRegistry::get('Institution.StaffPositionTitles');
            $list = $StaffPositionTitles
                ->find('list')
                ->find('order')
                ->toArray();

            return (!empty($list))? implode(', ', $list) : ' ';
        }
    }

    private function setAllTargetPopulations($entity)
    {
        if ($entity->has('target_population_selection') && $entity->target_population_selection == self::SELECT_ALL_TARGET_POPULATIONS) {
            $TrainingCoursesTargetPopulations = TableRegistry::get('Training.TrainingCoursesTargetPopulations');
            $entityId = $entity->id;

            $trainingCoursesTargetPopulationData = [
                'training_course_id' => $entityId,
                'target_population_id' => self::SELECT_ALL_TARGET_POPULATIONS
            ];

            $trainingCoursesTargetPopulationEntity = $TrainingCoursesTargetPopulations->newEntity($trainingCoursesTargetPopulationData);

            if ($TrainingCoursesTargetPopulations->save($trainingCoursesTargetPopulationEntity)) {
            } else {
                $TrainingCoursesTargetPopulations->log($trainingCoursesTargetPopulationEntity->errors(), 'debug');
            }
        }
    }

    private function checkIsSelectAll($entity)
    {
        // will check if the training course is a select all target population
        $TrainingCoursesTargetPopulations = TableRegistry::get('Training.TrainingCoursesTargetPopulations');

        $isSelectAll = $TrainingCoursesTargetPopulations
            ->find()
            ->where([
                $TrainingCoursesTargetPopulations->aliasField('training_course_id') => $entity->id,
                $TrainingCoursesTargetPopulations->aliasField('target_population_id') => self::SELECT_ALL_TARGET_POPULATIONS
            ])
            ->count();

        return $isSelectAll;
    }
}
