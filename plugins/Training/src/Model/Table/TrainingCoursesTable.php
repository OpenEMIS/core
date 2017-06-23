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
    CONST SELECT_ALL_TARGET_POPULATIONS = 2;

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
                if (array_key_exists('_ids', $requestData[$this->alias()][$value]) && empty($requestData[$this->alias()][$value]['_ids'])) {
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
    }

    public function editOnInitialize(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->request->query['course'] = $entity->id;
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
            // $attr['options'] = TableRegistry::get('Institution.StaffPositionTitles')->getList()->toArray();
            $attr['options'] = $this->targetPopulationSelection;
            $attr['select'] = false;
            $attr['onChangeReload'] = 'changeTargetPopulationSelection';

        }

        return $attr;
    }

    public function addEditOnChangeTargetPopulationSelection(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
// pr('addEditOnChangeTargetPopulationSelection');
        $requestData = $this->request->data;

        // $options = TableRegistry::get('Institution.StaffPositionTitles')->getList()->toArray();
        // if (array_key_exists($this->alias(), $requestData) && $requestData[$this->alias()]['target_population_selection'] == self::ALL_TARGET_POPULATIONS) {
        //     if (!empty($options)) {
        //         foreach ($options as $key => $targetName) {
        //             $requestData[$this->alias()]['target_populations']['_ids'][] = $key;
        //             // $attr['value'][] = $key;
        //             // $attr['attr']['value'][] = $targetName;
        //             $attr['attr']['value'][] = $key;
        //             // pr($key. ' ' . $targetName);
        //         }
        //     }
        //     // $attr['type'] = 'readonly';
        // }

        // pr($requestData[$this->alias()]['target_populations']);
        // die;

        /*
        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('subjects', $request->data[$this->alias()])) {
                    unset($data[$this->alias()]['subjects']);
                }
            }
        }
        */
    }

    public function getAllTargetPopulations ($options)
    {
        $targetPopulation = [];
        foreach ($options as $targetId => $targetName) {
            // pr($targetId . ' ' . $targetName);
            $targetPopulation [] = $targetId;
        }
        // pr($options);
        // pr($targetPopulation);
        // die;
        return $targetPopulation;
    }

    public function onUpdateFieldTargetPopulations(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $options = TableRegistry::get('Institution.StaffPositionTitles')->getList()->toArray();
            $attr['options'] = $options;
            $entity = $attr['entity'];

            $requestData = $this->request->data;
            $targetPopulations = [];
            if (!empty($entity->target_populations)) {
                foreach ($entity->target_populations as $targetObj) {
                    $targetPopulations[] = $targetObj->id;
                }
            }

            if (array_key_exists($this->alias(), $requestData) && $requestData[$this->alias()]['target_population_selection'] == self::SELECT_ALL_TARGET_POPULATIONS) {
                if (!empty($options)) {
                    $targetPopulations = $this->getAllTargetPopulations($options);

                }
            } else if (array_key_exists($this->alias(), $requestData) && $requestData[$this->alias()]['target_population_selection'] == self::SELECT_TARGET_POPULATIONS) {
                $targetPopulations = [];
            }

            $attr['value'] = $targetPopulations;
            $attr['attr']['value'] = $targetPopulations;
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
        $this->field('target_population_selection', ['type' => 'select']);
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

        // Field order
        $this->setFieldOrder([
            'code', 'name', 'description', 'objective', 'credit_hours', 'duration', 'number_of_months',
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
}
