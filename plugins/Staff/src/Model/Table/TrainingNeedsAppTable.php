<?php
namespace Staff\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Cake\Http\Session;
use Cake\Http\ServerRequest;
use Cake\Event\EventInterface;
use Cake\Datasource\ResultSetInterface;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;
use Cake\Log\Log;

class TrainingNeedsAppTable extends ControllerActionTable
{
    use OptionsTrait;

    const CATALOGUE = 'CATALOGUE';
    const NEED = 'NEED';

    // Workflow Steps - category
    const TO_DO = 1;
    const IN_PROGRESS = 2;
    const DONE = 3;

    private $trainingCourse = null;

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('TrainingCourses', ['className' => 'Training.TrainingCourses']);
        $this->belongsTo('TrainingNeedCategories', ['className' => 'Training.TrainingNeedCategories']);
        $this->belongsTo('TrainingNeedCompetencies', ['className' => 'Training.TrainingNeedCompetencies']);
        $this->belongsTo('TrainingNeedSubStandards', ['className' => 'Training.TrainingNeedSubStandards']);
        $this->belongsTo('TrainingPriorities', ['className' => 'Training.TrainingPriorities']);
        $this->belongsTo('Staff', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users']);

        $this->addBehavior('Institution.InstitutionWorkflowAccessControl');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Dashboard' => ['index']
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);

        return $validator
            // for future validation on each user can only submit need to one course at a time.
            // ->add('training_course_id', [
            //     'ruleUnique' => [
            //         'rule' => ['validateUnique', ['scope' => ['staff_id']]],
            //         'on' => function ($context) {
            //             //validate when only training_course_id is not 0
            //             return $context['data']['training_course_id'] != 0;
            //         },
            //         'provider' => 'table'
            //     ]
            // ])
            ->allowEmpty('training_need_category_id', function ($context) {
                if (array_key_exists('type', $context['data'])) {
                    $type = $context['data']['type'];
                    if ($type == self::CATALOGUE) {
                        return true;
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
            })
            ->requirePresence('training_need_competency_id', function ($context) {
                if (array_key_exists('type', $context['data'])) {
                    $type = $context['data']['type'];
                    if ($type == self::NEED) {
                        return true;
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
            })
            ->requirePresence('training_need_sub_standard_id', function ($context) {
                if (array_key_exists('type', $context['data'])) {
                    $type = $context['data']['type'];
                    if ($type == self::NEED) {
                        return true;
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
            });
    }

    public function onGetType(EventInterface $event, Entity $entity)
    {
        $typeOptions = $this->getTypes();

        return $typeOptions[$entity->type];
    }

    public function onGetTrainingCourseId(EventInterface $event, Entity $entity)
    {
        $isCatalogue = $this->isCatalogue($entity);

        $value = '';
        if ($isCatalogue) {
            return $entity->training_course->code_name;
        } else {
            $value = '<i class="fa fa-minus"></i>';
        }

        return $value;
    }

    public function onGetCourseCode(EventInterface $event, Entity $entity)
    {
        return $entity->training_course->code;
    }

    public function onGetCourseName(EventInterface $event, Entity $entity)
    {
        return $entity->training_course->name;
    }

    public function onGetCourseDescription(EventInterface $event, Entity $entity)
    {
        return $entity->training_course->description;
    }

    public function onGetTrainingRequirementId(EventInterface $event, Entity $entity)
    {
        return $entity->training_course->training_requirement->name;
    }

    public function onGetTrainingNeedCategoryId(EventInterface $event, Entity $entity)
    {
        $isCatalogue = $this->isCatalogue($entity);

        $value = '';
        if ($isCatalogue) {
            $value = '<i class="fa fa-minus"></i>';
        } else {
            $value = $entity->training_need_category->name;
        }

        return $value;
    }

    public function onGetTrainingNeedStandardId(EventInterface $event, Entity $entity)
    {
        $isNeed = $this->isNeed($entity);

        $value = '';
        if ($isNeed) {
            if ($entity->has('training_need_sub_standard') && !empty($entity->training_need_sub_standard)) {
                $value = $entity->training_need_sub_standard->training_need_standard->name;
            }
        } else {
            $value = '<i class="fa fa-minus"></i>';
        }

        return $value;
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('training_need_competency_id', ['visible' => false]);
        $this->field('training_need_sub_standard_id', ['visible' => false]);
        $this->field('training_priority_id', ['visible' => false]);
        $this->field('staff_id', ['visible' => false]);
        $this->field('reason', ['visible' => false]);
        $this->field('assignee_id', ['visible' => false]);

        $this->setFieldOrder(['type', 'training_course_id', 'training_need_category_id','assignee_id']);
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $session = $this->request->getSession();

        // POCOR-1893
        if ($this->controller->getName() == 'Profiles') {
            $sessionKey = 'Auth.User.id';
        } else {
            $sessionKey = 'Staff.Staff.id';
        }
        // end POCOR-1893

        if ($session->check($sessionKey)) {
            $staffId = $session->read($sessionKey);
        } else if($this->controller->getName() == 'Directories' && isset($this->request->getParam('pass')[1])) {
            $param = $this->paramsDecode($this->request->getParam('pass')[1]);
            $staffId = isset($param['staff_id']) ? $param['staff_id'] : '';
        }

        $extra['auto_contain_fields'] = [
            'TrainingCourses' => ['code']
        ];

        if($staffId == NULL){
            $staffId = '';
        }
        $query
            ->contain(['TrainingNeedSubStandards.TrainingNeedStandards'])
            ->where([$this->aliasField('staff_id') => $staffId])
            ->enableAutoFields(true);
    }

    public function viewEditBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra) {
        $query->contain([
            'TrainingNeedSubStandards.TrainingNeedStandards',
            'TrainingCourses.TrainingRequirements'
        ]);
    }

    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function editOnInitialize(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        if ($entity->has('training_need_sub_standard') && !empty($entity->training_need_sub_standard)) {
            $entity->training_need_standard_id = $entity->training_need_sub_standard->training_need_standard_id;
        }
    }

    public function addEditAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function onUpdateFieldType(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $typeOptions = $this->getTypes();

        if ($action == 'add') {
            $attr['type'] = 'select';
            $attr['onChangeReload'] = 'changeType';
            $attr['options'] = $typeOptions;
        } else if ($action == 'edit') {
            $entity = $attr['entity'];
            $type = $entity->type;

            $attr['type'] = 'readonly';
            $attr['value'] = $type;
            $attr['attr']['value'] = $typeOptions[$type];
        }

        return $attr;
    }

    public function onUpdateFieldTrainingCourseId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $entity = isset($attr['entity']) ? $attr['entity'] : null;
        $isCatalogue = $this->isCatalogue($entity);

        if ($action == 'view') {
            $attr['visible'] = false;
        } else if ($action == 'add' || $action == 'edit') {
            if ($isCatalogue) {
                $trainingCourseOptions = $this->Training->getCourseList();

                $trainingCourseId = null;
                if (isset($request->data[$this->getAlias()]['training_course_id'])) {
                    $trainingCourseId = $request->data[$this->getAlias()]['training_course_id'];
                } else if ($entity->has('training_course_id')) {
                    $trainingCourseId = $entity->training_course_id;
                }

                if (!empty($trainingCourseId)) {
                    $this->trainingCourse = $this->TrainingCourses->get($entity->training_course_id, ['contain' => 'TrainingRequirements']);
                } else {
                    $this->trainingCourse = null;
                }

                $attr['onChangeReload'] = 'changeCourse';
                $attr['options'] = $trainingCourseOptions;
            } else {
                $attr['type'] = 'hidden';
                $attr['attr']['value'] = 0;
            }
        }

        return $attr;
    }

    public function onUpdateFieldCourseCode(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $entity = isset($attr['entity']) ? $attr['entity'] : null;
        $isCatalogue = $this->isCatalogue($entity);

        if ($action == 'view') {
            if (!$isCatalogue) {
                $attr['visible'] = false;
            }
        } else if ($action == 'add' || $action == 'edit') {
            if ($isCatalogue) {
                if (!empty($this->trainingCourse)) {
                    $trainingCourseCode = $this->trainingCourse->code;
                } else {
                    $trainingCourseCode = '';
                }

                $attr['value'] = $trainingCourseCode;
                $attr['attr']['value'] = $trainingCourseCode;
                $attr['attr']['disabled'] = 'disabled';
            } else {
                $attr['visible'] = false;
            }
        }

        return $attr;
    }

    public function onUpdateFieldCourseName(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $entity = isset($attr['entity']) ? $attr['entity'] : null;
        $isCatalogue = $this->isCatalogue($entity);

        if ($action == 'view') {
            if (!$isCatalogue) {
                $attr['visible'] = false;
            }
        } else if ($action == 'add' || $action == 'edit') {
            if ($isCatalogue) {
                if (!empty($this->trainingCourse)) {
                    $trainingCourseName = $this->trainingCourse->name;
                } else {
                    $trainingCourseName = '';
                }

                $attr['value'] = $trainingCourseName;
                $attr['attr']['value'] = $trainingCourseName;
                $attr['attr']['disabled'] = 'disabled';
            } else {
                $attr['visible'] = false;
            }
        }

        return $attr;
    }

    public function onUpdateFieldCourseDescription(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $entity = isset($attr['entity']) ? $attr['entity'] : null;
        $isCatalogue = $this->isCatalogue($entity);

        if ($action == 'view') {
            if (!$isCatalogue) {
                $attr['visible'] = false;
            }
        } else if ($action == 'add' || $action == 'edit') {
            if ($isCatalogue) {
                if (!empty($this->trainingCourse)) {
                    $trainingCourseDescription = $this->trainingCourse->description;
                } else {
                    $trainingCourseDescription = '';
                }

                $attr['value'] = $trainingCourseDescription;
                $attr['attr']['value'] = $trainingCourseDescription;
                $attr['attr']['disabled'] = 'disabled';
            } else {
                $attr['visible'] = false;
            }
        }

        return $attr;
    }

    public function onUpdateFieldTrainingRequirementId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $entity = isset($attr['entity']) ? $attr['entity'] : null;
        $isCatalogue = $this->isCatalogue($entity);

        if ($action == 'view') {
            if (!$isCatalogue) {
                $attr['visible'] = false;
            }
        } else if ($action == 'add' || $action == 'edit') {
            if ($isCatalogue) {
                if (!empty($this->trainingCourse)) {
                    $trainingRequirementName = $this->trainingCourse->training_requirement->name;
                } else {
                    $trainingRequirementName = '';
                }

                $attr['value'] = $trainingRequirementName;
                $attr['attr']['value'] = $trainingRequirementName;
                $attr['attr']['disabled'] = 'disabled';
            } else {
                $attr['visible'] = false;
            }
        }

        return $attr;
    }

    public function onUpdateFieldTrainingNeedCategoryId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $entity = isset($attr['entity']) ? $attr['entity'] : null;
        $isNeed = $this->isNeed($entity);

        if ($action == 'view') {
            if (!$isNeed) {
                $attr['visible'] = false;
            }
        } else if ($action == 'add' || $action == 'edit') {
            if (!$isNeed) {
                $attr['type'] = 'hidden';
                $attr['attr']['value'] = 0;
            }
        }

        return $attr;
    }

    public function onUpdateFieldTrainingNeedCompetencyId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $entity = isset($attr['entity']) ? $attr['entity'] : null;
        $isNeed = $this->isNeed($entity);

        if ($action == 'view') {
            if (!$isNeed) {
                $attr['visible'] = false;
            }
        } else if ($action == 'add' || $action == 'edit') {
            if (!$isNeed) {
                $attr['type'] = 'hidden';
                $attr['attr']['value'] = 0;
            }
        }

        return $attr;
    }

    public function onUpdateFieldTrainingNeedStandardId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $entity = isset($attr['entity']) ? $attr['entity'] : null;
        $isNeed = $this->isNeed($entity);

        if ($action == 'view') {
            if (!$isNeed) {
                $attr['visible'] = false;
            }
        } else if ($action == 'add' || $action == 'edit') {
            if ($isNeed) {
                $TrainingNeedStandards = TableRegistry::getTableLocator()->get('Training.TrainingNeedStandards');
                $trainingNeedStandardOptions = $TrainingNeedStandards
                    ->find('list')
                    ->find('visible')
                    ->find('order')
                    ->toArray();

                $attr['options'] = $trainingNeedStandardOptions;
                $attr['onChangeReload'] = 'changeTrainingNeedStandard';
            } else {
                $attr['visible'] = false;
            }
        }

        return $attr;
    }

    public function onUpdateFieldTrainingNeedSubStandardId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $entity = isset($attr['entity']) ? $attr['entity'] : null;
        $isNeed = $this->isNeed($entity);

        if ($action == 'view') {
            if (!$isNeed) {
                $attr['visible'] = false;
            }
        } else if ($action == 'add' || $action == 'edit') {
            if ($isNeed) {
                $trainingNeedStandardId = null;
                if (isset($this->request->getData()[$this->getAlias()]['training_need_standard_id'])) {
                    $trainingNeedStandardId = $this->request->getData()[$this->getAlias()]['training_need_standard_id'];
                } else if ($entity->has('training_need_standard_id')) {
                    $trainingNeedStandardId = $entity->training_need_standard_id;
                }

                if (!empty($trainingNeedStandardId)) {
                    $trainingNeedSubStandardOptions = $this->TrainingNeedSubStandards
                        ->find('list')
                        ->find('visible')
                        ->find('order')
                        ->where([
                            $this->TrainingNeedSubStandards->aliasField('training_need_standard_id') => $trainingNeedStandardId
                        ])
                        ->toArray();
                } else {
                    $trainingNeedSubStandardOptions = [];
                }

                $attr['options'] = $trainingNeedSubStandardOptions;
            } else {
                $attr['type'] = 'hidden';
                $attr['attr']['value'] = 0;
            }
        }

        return $attr;
    }

    public function onUpdateFieldStaffId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add') {
            $session = $request->getSession();
            $sessionKey = 'Staff.Staff.id';

            if ($session->check($sessionKey)) {
                $attr['attr']['value'] = $session->read($sessionKey);
            }
        } else if ($action == 'edit') {
            $entity = $attr['entity'];
            $staffId = $entity->staff_id;

            $attr['value'] = $staffId;
            $attr['attr']['value'] = $staffId;
        }

        return $attr;
    }

    private function setupFields(Entity $entity)
    {
        $this->field('type', [
            'entity' => $entity
        ]);
        $this->field('training_course_id', [
            'type' => 'select',
            'entity' => $entity
        ]);
        $this->field('course_code', [
            'type' => 'string',
            'entity' => $entity
        ]);
        $this->field('course_name', [
            'type' => 'string',
            'entity' => $entity
        ]);
        $this->field('course_description', [
            'type' => 'text',
            'entity' => $entity
        ]);
        $this->field('training_requirement_id', [
            'type' => 'string',
            'entity' => $entity
        ]);
        $this->field('training_need_category_id', [
            'type' => 'select',
            'entity' => $entity
        ]);
        $this->field('training_need_competency_id', [
            'type' => 'select',
            'entity' => $entity
        ]);
        $this->field('training_need_standard_id', [
            'type' => 'select',
            'entity' => $entity
        ]);
        $this->field('training_need_sub_standard_id', [
            'type' => 'select',
            'entity' => $entity
        ]);
        $this->field('training_priority_id', [
            'type' => 'select',
            'entity' => $entity
        ]);
        $this->field('staff_id', [
            'type' => 'hidden',
            'entity' => $entity
        ]);

        $this->setFieldOrder([
            'type', 'training_course_id', 'course_code', 'course_name', 'course_description', 'training_requirement_id',
            'training_need_category_id', 'training_need_competency_id', 'training_need_standard_id', 'training_need_sub_standard_id', 'training_priority_id',
            'staff_id', 'reason'
        ]);
    }

    private function getTypes()
    {
        $typeOptions = $this->getSelectOptions('StaffTrainingNeeds.types');
        return $typeOptions;
    }

    private function isCatalogue(Entity $entity = null)
    {
        return (!is_null($entity) && $entity->getOriginal('type') == self::CATALOGUE);
    }

    private function isNeed(Entity $entity = null)
    {
        return (!is_null($entity) && $entity->getOriginal('type') == self::NEED);
    }

    public function findWorkbench(Query $query, array $options)
    {
        $controller = $options['_controller'];
        $session = $controller->getRequest()->getSession();
        $userId = $session->read('Auth.User.id');
        // $userId = $this->getUserID();
        $Statuses = $this->Statuses;
        $doneStatus = self::DONE;
        $typeOptions = $this->getTypes();

        $query
            ->select([
                $this->aliasField('id'),
                $this->aliasField('type'),
                $this->aliasField('status_id'),
                $this->aliasField('staff_id'),
                $this->aliasField('training_course_id'),
                $this->aliasField('training_need_category_id'),
                $this->aliasField('modified'),
                $this->aliasField('created'),
                $this->Statuses->aliasField('name'),
                $this->Staff->aliasField('openemis_no'),
                $this->Staff->aliasField('first_name'),
                $this->Staff->aliasField('middle_name'),
                $this->Staff->aliasField('third_name'),
                $this->Staff->aliasField('last_name'),
                $this->Staff->aliasField('preferred_name'),
                $this->TrainingCourses->aliasField('code'),
                $this->TrainingCourses->aliasField('name'),
                $this->TrainingNeedCategories->aliasField('name'),
                $this->CreatedUser->aliasField('openemis_no'),
                $this->CreatedUser->aliasField('first_name'),
                $this->CreatedUser->aliasField('middle_name'),
                $this->CreatedUser->aliasField('third_name'),
                $this->CreatedUser->aliasField('last_name'),
                $this->CreatedUser->aliasField('preferred_name')
            ])
            ->contain([$this->TrainingCourses->getAlias(), $this->CreatedUser->getAlias(), $this->TrainingNeedCategories->getAlias(), $this->Staff->getAlias(),'Assignees'])
            ->matching($this->Statuses->getAlias(), function ($q) use ($Statuses, $doneStatus) {
                return $q->where([$Statuses->aliasField('category <> ') => $doneStatus]);
            })
            ->where([$this->aliasField('assignee_id') => $userId,
                    'Assignees.super_admin IS NOT' => 1]) //POCOR-7102
            ->order([$this->aliasField('created') => 'DESC'])
            ->formatResults(function (ResultSetInterface $results) use ($typeOptions) {
                return $results->map(function ($row) use ($typeOptions) {
                    $url = [
                        'plugin' => 'Directory',
                        'controller' => 'Directories',
                        'action' => 'TrainingNeeds',
                        'view',
                        $this->paramsEncode(['id' => $row->id]),
                        'user_id' => $row->staff_id
                    ];

                    if (is_null($row->modified)) {
                        $receivedDate = $this->formatDate($row->created);
                    } else {
                        $receivedDate = $this->formatDate($row->modified);
                    }

                    if ($row->type == self::CATALOGUE) {
                        $preTitle = $row->training_course->code_name;
                    } else if ($row->type == self::NEED) {
                        $preTitle = $row->training_need_category->name;
                    }

                    $row['url'] = $url;
                    $row['status'] = __($row->_matchingData['Statuses']->name);
                    $row['request_title'] = sprintf(__('%s from %s of %s'), $preTitle, __($typeOptions[$row->type]), $row->staff->name_with_id);
                    $row['received_date'] = $receivedDate;
                    $row['requester'] = $row->created_user->name_with_id;
                    return $row;
                });
            });

        return $query;
    }

    //POCOR-6925
    public function onUpdateFieldAssigneeId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $workflowModel = 'Staff > Training > Needs';
            $workflowModelsTable = TableRegistry::getTableLocator()->get('Workflow.WorkflowModels');
            $workflowStepsTable = TableRegistry::getTableLocator()->get('Workflow.WorkflowSteps');
            $Workflows = TableRegistry::getTableLocator()->get('Workflow.Workflows');
            $workModelId = $Workflows
                            ->find()
                            ->select(['id'=>$workflowModelsTable->aliasField('id'),
                            'workflow_id'=>$Workflows->aliasField('id'),
                            'is_school_based'=>$workflowModelsTable->aliasField('is_school_based')])
                            ->LeftJoin([$workflowModelsTable->getAlias() => $workflowModelsTable->getTable()],
                                [
                                    $workflowModelsTable->aliasField('id') . ' = '. $Workflows->aliasField('workflow_model_id')
                                ])
                            ->where([$workflowModelsTable->aliasField('name')=>$workflowModel])->first();
            $workflowId = $workModelId->workflow_id;
            $isSchoolBased = $workModelId->is_school_based;
            $workflowStepsOptions = $workflowStepsTable
                            ->find()
                            ->select([
                                'stepId'=>$workflowStepsTable->aliasField('id'),
                            ])
                            ->where([$workflowStepsTable->aliasField('workflow_id') => $workflowId])
                            ->first();
            $stepId = $workflowStepsOptions->stepId;
            $session = $request->getSession();
            if ($session->check('Institution.Institutions.id')) {
                $institutionId = $session->read('Institution.Institutions.id');
            }
            $institutionId = $institutionId;
            $assigneeOptions = [];
            if (!is_null($stepId)) {
                $WorkflowStepsRoles = TableRegistry::getTableLocator()->get('Workflow.WorkflowStepsRoles');
                $stepRoles = $WorkflowStepsRoles->getRolesByStep($stepId);
                if (!empty($stepRoles)) {
                    $SecurityGroupUsers = TableRegistry::getTableLocator()->get('Security.SecurityGroupUsers');
                    $Areas = TableRegistry::getTableLocator()->get('Area.Areas');
                    $Institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
                    if ($isSchoolBased) {
                        if (is_null($institutionId)) {
                            Log::write('debug', 'Institution Id not found.');
                        } else {
                            $institutionObj = $Institutions->find()->where([$Institutions->aliasField('id') => $institutionId])->contain(['Areas'])->first();
                            $securityGroupId = $institutionObj->security_group_id;
                            $areaObj = $institutionObj->area;
                            // School based assignee
                            $where = [
                                'OR' => [[$SecurityGroupUsers->aliasField('security_group_id') => $securityGroupId],
                                        ['Institutions.id' => $institutionId]],
                                $SecurityGroupUsers->aliasField('security_role_id IN ') => $stepRoles
                            ];
                            $schoolBasedAssigneeQuery = $SecurityGroupUsers
                                    ->find('userList', ['where' => $where])
                                    ->leftJoinWith('SecurityGroups.Institutions');
                            $schoolBasedAssigneeOptions = $schoolBasedAssigneeQuery->toArray();

                            // Region based assignee
                            $where = [$SecurityGroupUsers->aliasField('security_role_id IN ') => $stepRoles];
                            $regionBasedAssigneeQuery = $SecurityGroupUsers
                                        ->find('UserList', ['where' => $where, 'area' => $areaObj]);

                            $regionBasedAssigneeOptions = $regionBasedAssigneeQuery->toArray();
                            // End
                            $assigneeOptions = $schoolBasedAssigneeOptions + $regionBasedAssigneeOptions;
                        }
                    } else {
                        $where = [$SecurityGroupUsers->aliasField('security_role_id IN ') => $stepRoles];
                        $assigneeQuery = $SecurityGroupUsers
                                ->find('userList', ['where' => $where])
                                ->order([$SecurityGroupUsers->aliasField('security_role_id') => 'DESC']);
                        $assigneeOptions = $assigneeQuery->toArray();
                    }
                }
            }
            $attr['type'] = 'chosenSelect';
            $attr['attr']['multiple'] = false;
            $attr['select'] = false;
            $attr['options'] = ['' => '-- ' . __('Select Assignee') . ' --'] + $assigneeOptions;
            $attr['onChangeReload'] = 'changeStatus';
            return $attr;
        }
    }
}
