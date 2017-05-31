<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\ORM\ResultSet;
use Cake\Validation\Validator;
use Cake\Network\Request;
use Cake\Event\Event;
use Cake\Datasource\ResultSetInterface;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;

class StaffTrainingNeedsTable extends ControllerActionTable
{
    use OptionsTrait;

    const CATALOGUE = 'CATALOGUE';
    const NEED = 'NEED';

    // Workflow Steps - category
    const TO_DO = 1;
    const IN_PROGRESS = 2;
    const DONE = 3;

    private $course = null;

    public function initialize(array $config)
    {
        $this->table('staff_training_needs');
        parent::initialize($config);
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Courses', ['className' => 'Training.TrainingCourses', 'foreignKey' => 'course_id']);
        $this->belongsTo('TrainingNeedCategories', ['className' => 'Training.TrainingNeedCategories', 'foreignKey' => 'training_need_category_id']);
        $this->belongsTo('TrainingPriorities', ['className' => 'Training.TrainingPriorities', 'foreignKey' => 'training_priority_id']);
        $this->belongsTo('TrainingNeedCompetencies', ['className' => 'Training.TrainingNeedCompetencies', 'foreignKey' => 'training_need_competency_id']);
        $this->belongsTo('TrainingNeedSubStandards', ['className' => 'Training.TrainingNeedSubStandards', 'foreignKey' => 'training_need_sub_standard_id']);
        $this->belongsTo('Staff', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users']);
        $this->addBehavior('Workflow.Workflow');
        $this->addBehavior('Institution.InstitutionWorkflowAccessControl');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Dashboard' => ['index']
        ]);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            // for future validation on each user can only submit need to one course at a time.
            // ->add('course_id', [
            //     'ruleUnique' => [
            //         'rule' => ['validateUnique', ['scope' => ['staff_id']]],
            //         'on' => function ($context) {
            //             //validate when only course_id is not 0
            //             return $context['data']['course_id'] != 0;
            //         },
            //         'provider' => 'table'
            //     ]
            // ])
            ->add('type', 'notBlank', ['rule' => 'notBlank'])
            ->allowEmpty('training_need_category_id', function ($context) {
                if (array_key_exists('type', $context['data'])) {
                    $type = $context['data']['type'];
                    if ($type == 'CATALOGUE') {
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
                    if ($type == 'NEED') {
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
                    if ($type == 'NEED') {
                        return true;
                    } else {
                        return false;
                    }
                } else {
                    return false;
                }
            });
    }

    public function beforeAction()
    {
        $modelAlias = 'Needs';
        $userType = 'StaffUser';
        $this->controller->changeUserHeader($this, $modelAlias, $userType);
    }

    public function onGetType(Event $event, Entity $entity)
    {
        list($typeOptions) = array_values($this->_getSelectOptions());
        $currentAction = $this->action;
        if ($currentAction == 'index') {
            $entity = $this->setupValues($entity);
        }

        return $typeOptions[$entity->type];
    }

    public function onGetCourse(Event $event, Entity $entity)
    {
        $entity = $this->setupValues($entity);

        if ($entity->type == 'CATALOGUE') {
            return $entity->course->code_name;
        } else {
            return '-';
        }
    }

    public function onGetCourseCode(Event $event, Entity $entity)
    {
        return $entity->course->code;
    }

    public function onGetCourseName(Event $event, Entity $entity)
    {
        return $entity->course->name;
    }

    public function onGetCourseDescription(Event $event, Entity $entity)
    {
        return $entity->course->description;
    }

    public function onGetTrainingRequirementId(Event $event, Entity $entity)
    {
        return $entity->course->training_requirement->name;
    }

    public function onGetTrainingNeedCategoryId(Event $event, Entity $entity)
    {
        $entity = $this->setupValues($entity);

        if ($entity->type == 'CATALOGUE') {
            return '-';
        } else {
            return $entity->training_need_category->name;
        }
    }

    public function onGetTrainingNeedStandardId(Event $event, Entity $entity)
    {
        if ($entity->has('training_need_sub_standard') && !empty($entity->training_need_sub_standard)) {
            return $entity->training_need_sub_standard->training_need_standard->name;
        } else {
            return '-';
        }
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $session = $this->request->session();
        $sessionKey = 'Staff.Staff.id';

        if ($session->check($sessionKey)) {
            $staffId = $session->read($sessionKey);
        }

        $extra['auto_contain_fields'] = ['Courses' => ['code']];

        $query->contain(['TrainingNeedSubStandards.TrainingNeedStandards'])
            ->where([$this->aliasField('staff_id') => $staffId])
            ->autoFields(true);
    }

    public function indexAfterAction(Event $event, Query $query, ResultSet $resultSet, ArrayObject $extra)
    {
        $this->field('type');
        $this->field('course');
        $this->field('course_id', ['visible' => false]);
        $this->field('training_need_competency_id', ['visible' => false]);
        $this->field('training_need_sub_standard_id', ['visible' => false]);
        $this->field('reason', ['visible' => false]);
        $this->field('training_priority_id', ['visible' => false]);
        $this->field('staff_id', ['visible' => false]);
        $this->setFieldOrder(['status_id', 'assignee_id', 'type', 'course', 'training_need_category_id']);
    }

    public function viewEditBeforeQuery(Event $event, Query $query, ArrayObject $extra) {
        $query->contain([
            'TrainingNeedSubStandards.TrainingNeedStandards',
            'Courses.TrainingRequirements'
        ]);
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $entity = $this->setupValues($entity);
        $this->setupFields($entity);

        if ($entity->type == self::NEED) {
            $this->field('course_id', ['visible' => false]);
            $this->field('course_code', ['visible' => false]);
            $this->field('course_name', ['visible' => false]);
            $this->field('course_description', ['visible' => false]);
            $this->field('training_requirement_id', ['visible' => false]);
        } else if ($entity->type == self::CATALOGUE) {
            $this->field('training_need_competency_id', ['visible' => false]);
            $this->field('training_need_standard_id', ['visible' => false]);
            $this->field('training_need_sub_standard_id', ['visible' => false]);
        }
    }

    public function addOnInitialize(Event $event, Entity $entity, ArrayObject $extra)
    {
        $entity = $this->setupValues($entity);
    }

    public function editOnInitialize(Event $event, Entity $entity, ArrayObject $extra)
    {
        $entity = $this->setupValues($entity);
    }

    public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options, ArrayObject $extra)
    {
        if (array_key_exists('course_id', $data[$this->alias()])) {
            $courseId = $data[$this->alias()]['course_id'];
            if (!empty($courseId)) {
                $data[$this->alias()]['training_requirement_id'] = $this->Courses->get($courseId)->training_requirement_id;
            }
        }
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($entity);
    }

    public function onUpdateFieldType(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            list($typeOptions, $selectedType) = array_values($this->_getSelectOptions());

            $attr['type'] = 'select';
            $attr['onChangeReload'] = 'changeType';
            $attr['options'] = $typeOptions;
        } else if ($action == 'edit') {
            list($typeOptions, $selectedType) = array_values($this->_getSelectOptions());
            $attr['type'] = 'readonly';
            $attr['attr']['value'] = $typeOptions[$selectedType];
        }

        return $attr;
    }

    public function onUpdateFieldTrainingNeedCategoryId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'view') {
            $selectedType = $attr['attr']['type_value'];
            if ($selectedType == self::CATALOGUE) {
                $attr['visible'] = false;
            }
        } else if ($action == 'add' || $action == 'edit') {
            list(, $selectedType) = array_values($this->_getSelectOptions());

            if ($selectedType == self::NEED) {
                $attr['type'] = 'select';
            } else {
                $attr['visible'] = false;
            }
        }

        return $attr;
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        $dataArray = $data->getArrayCopy();
        if (array_key_exists('type', $dataArray) && $dataArray['type'] != self::NEED) {
            $data['training_need_category_id'] = 0;
        }
    }

    public function onUpdateFieldCourseId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'view') {
            $attr['visible'] = false;
        } else if ($action == 'add' || $action == 'edit') {

            if ($action == 'edit') {
                $attr['select'] = false;
            }

            list(, $selectedType) = array_values($this->_getSelectOptions());

            if ($selectedType == self::CATALOGUE) {
                $courseOptions = $this->Training->getCourseList();
                $selectedCourse = (array_key_exists('course', $this->request->query) && array_key_exists($this->request->query['course'], $courseOptions))? $this->request->query['course']: null;

                if (empty($selectedCourse)) {
                    if (array_key_exists('entity', $attr)) {
                        $entity = $attr['entity'];
                        if ($entity->has('course_id') && !empty($entity->course_id)) {
                            $selectedCourse = $entity->course_id;
                        }
                    }
                }

                if (!is_null($selectedCourse)) {
                    $this->course = $this->Courses
                        ->find()
                        ->matching('TrainingRequirements')
                        ->where([
                            $this->Courses->aliasField('id') => $selectedCourse
                        ])
                        ->first();
                }

                $attr['type'] = 'select';
                $attr['onChangeReload'] = 'changeCourse';
                $attr['options'] = $courseOptions;
            } else {
                $attr['type'] = 'hidden';
                $attr['attr']['value'] = 0;
            }
        }

        return $attr;
    }

    public function onUpdateFieldCourseCode(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            list(, $selectedType) = array_values($this->_getSelectOptions());

            if ($selectedType == self::CATALOGUE) {
                $attr['attr']['disabled'] = 'disabled';
                if (!is_null($this->course)) {
                    $attr['value'] = $this->course->code;
                    $attr['attr']['value'] = $this->course->code;
                }
            } else {
                $attr['visible'] = false;
            }
        }

        return $attr;
    }

    public function onUpdateFieldCourseName(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            list(, $selectedType) = array_values($this->_getSelectOptions());

            if ($selectedType == self::CATALOGUE) {
                $attr['attr']['disabled'] = 'disabled';
                if (!is_null($this->course)) {
                    $attr['value'] = $this->course->name;
                    $attr['attr']['value'] = $this->course->name;
                }
            } else {
                $attr['visible'] = false;
            }
        }

        return $attr;
    }

    public function onUpdateFieldCourseDescription(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            list(, $selectedType) = array_values($this->_getSelectOptions());

            if ($selectedType == self::CATALOGUE) {
                $attr['attr']['disabled'] = 'disabled';
                if (!is_null($this->course)) {
                    $attr['attr']['value'] = $this->course->description;
                }
            } else {
                $attr['visible'] = false;
            }
        }

        return $attr;
    }

    public function onUpdateFieldTrainingRequirementId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            list(, $selectedType) = array_values($this->_getSelectOptions());

            if ($selectedType == self::CATALOGUE) {
                $attr['type'] = 'readonly';
                if (!is_null($this->course)) {
                    $attr['attr']['value'] = $this->course->_matchingData['TrainingRequirements']->name;
                }
            } else {
                $attr['visible'] = false;
            }
        }

        return $attr;
    }

    public function onUpdateFieldTrainingNeedCompetencyId(Event $event, array $attr, $action, Request $request)
    {
        list(, $selectedType) = array_values($this->_getSelectOptions());

        if ($selectedType == self::NEED) {
            $query = $this->TrainingNeedCompetencies
                    ->find('list')
                    ->find('visible')
                    ->order($this->TrainingNeedCompetencies->aliasField('order'))
                    ->toArray();

            $attr['type'] = 'select';
            $attr['options'] = $query;
        } else {
            $attr['visible'] = false;
        }
        return $attr;
    }

    public function onUpdateFieldTrainingNeedStandardId(Event $event, array $attr, $action, Request $request)
    {
        list(, $selectedType) = array_values($this->_getSelectOptions());

        if ($selectedType == self::NEED) {
            $TrainingNeedStandards = TableRegistry::get('Training.TrainingNeedStandards');

            $query = $TrainingNeedStandards
                    ->find('list')
                    ->find('visible')
                    ->order($TrainingNeedStandards->aliasField('order'))
                    ->toArray();

            $selectedStandard = array_key_exists('standard', $this->request->query)? $this->request->query['standard']: '';
            
            $attr['type'] = 'select';
            $attr['onChangeReload'] = 'changeStandard';
            $attr['options'] = $query;

            if ($action == 'edit') {
                if (array_key_exists('entity', $attr)) {
                    $entity = $attr['entity'];
                    if ($entity->has('training_need_sub_standard') && !empty($entity->training_need_sub_standard)) {
                        $attr['default'] = $attr['entity']->training_need_sub_standard->training_need_standard->id;
                        $attr['select'] = false;
                    }
                }
            }
        } else {
            $attr['visible'] = false;
        }
        
        return $attr;
    }

    public function onUpdateFieldTrainingNeedSubStandardId(Event $event, array $attr, $action, Request $request)
    {
        list(, $selectedType) = array_values($this->_getSelectOptions());

        if ($selectedType == self::NEED) {
            $selectedStandard = array_key_exists('standard', $this->request->query)? $this->request->query['standard']: '';

            if (empty($selectedStandard)) {
                if (array_key_exists('entity', $attr)) {
                    $entity = $attr['entity'];
                    if ($entity->has('training_need_sub_standard') && !empty($entity->training_need_sub_standard)) {
                        if ($entity->training_need_sub_standard->has('training_need_standard') && !empty($entity->training_need_sub_standard->training_need_standard)) {
                            $selectedStandard = $entity->training_need_sub_standard->training_need_standard->id;
                        }
                    }
                }
            }

            $query = [];
            if ($selectedStandard) {
                $query = $this->TrainingNeedSubStandards
                    ->find('list')
                    ->find('visible')
                    ->where([$this->TrainingNeedSubStandards->aliasField('training_need_standard_id') => $selectedStandard])
                    ->order($this->TrainingNeedSubStandards->aliasField('order'))
                    ->toArray();
            }

            $attr['type'] = 'select';
            $attr['options'] = $query;
        } else {
            $attr['visible'] = false;
        }
        return $attr;
    }


    public function onUpdateFieldStaffId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $session = $request->session();
            $sessionKey = 'Staff.Staff.id';

            if ($session->check($sessionKey)) {
                $attr['attr']['value'] = $session->read($sessionKey);
            }
        }

        return $attr;
    }

    public function addEditOnChangeType(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->request;
        unset($request->query['type']);
        unset($request->query['course']);

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('type', $request->data[$this->alias()])) {
                    $request->query['type'] = $request->data[$this->alias()]['type'];
                }
            }
            $data[$this->alias()]['status_id'] = $entity->status_id;
        }
    }

    public function addEditOnChangeCourse(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->request;
        unset($request->query['type']);
        unset($request->query['course']);

        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('type', $request->data[$this->alias()])) {
                    $request->query['type'] = $request->data[$this->alias()]['type'];
                }
                if (array_key_exists('course_id', $request->data[$this->alias()])) {
                    $request->query['course'] = $request->data[$this->alias()]['course_id'];
                }
            }
            $data[$this->alias()]['status_id'] = $entity->status_id;
        }
    }

    public function addEditOnChangeStandard(Event $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        $request = $this->request;
        unset($request->query['standard']);
        
        if ($request->is(['post', 'put'])) {
            if (array_key_exists($this->alias(), $request->data)) {
                if (array_key_exists('training_need_standard_id', $request->data[$this->alias()])) {
                    $request->query['standard'] = $request->data[$this->alias()]['training_need_standard_id'];
                }
            }
        }
    }

    public function setupValues(Entity $entity)
    {
        if (!isset($entity->id)) {  // new record
            // list(, $selectedType) = array_values($this->_getSelectOptions());
            $entity->type = '';
        } else {    // existing record
            if ($entity->training_need_category_id == 0) {
                $entity->type = self::CATALOGUE;
                $course = $this->Courses->get($entity->course_id);
            } else {
                $entity->type = self::NEED;
            }
        }
        $this->request->query['type'] = $entity->type;

        return $entity;
    }

    public function setupFields(Entity $entity)
    {
        $this->field('type');
        $this->field('training_need_category_id', [
            'type' => 'select',
            'attr' => ['type_value' => $entity->type]
        ]);
        $this->field('course_id', [
            'type' => 'select',
            'entity' => $entity
        ]);
        $this->field('course_code');
        $this->field('course_name');
        $this->field('course_description', ['type' => 'text']);
        $this->field('training_requirement_id', ['type' => 'select']);
        $this->field('training_priority_id', ['type' => 'select']);

        $this->field('training_need_competency_id', ['type' => 'select']);
        $this->field('training_need_standard_id', [
            'type' => 'select',
            'entity' => $entity
        ]);
        $this->field('training_need_sub_standard_id', [
            'type' => 'select',
            'select' => false,
            'entity' => $entity
        ]);

        $this->field('staff_id', ['type' => 'hidden']);

        $this->setFieldOrder([
            'type', 'training_need_category_id', 'course_id', 'course_code', 'course_name', 'course_description',
            'training_need_competency_id', 'training_need_standard_id', 'training_need_sub_standard_id',
            'training_requirement_id', 'training_priority_id',
            'reason', 'staff_id'
        ]);
    }

    public function _getSelectOptions()
    {
        //Return all required options and their key
        $typeOptions = $this->getSelectOptions($this->aliasField('types'));
        // $selectedType = $this->queryString('type', $typeOptions);
        $selectedType = array_key_exists('type', $this->request->query)? $this->request->query['type']: '';

        return compact('typeOptions', 'selectedType');
    }

    private function setupTabElements()
    {
        $tabElements = $this->controller->getTrainingTabElements();
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        $this->setupTabElements();
        // pr($this->fields);
    }

    public function findWorkbench(Query $query, array $options)
    {
        $controller = $options['_controller'];
        $session = $controller->request->session();

        $userId = $session->read('Auth.User.id');
        $Statuses = $this->Statuses;
        $doneStatus = self::DONE;
        $typeOptions = $this->getSelectOptions($this->aliasField('types'));

        $query
            ->select([
                $this->aliasField('id'),
                $this->aliasField('status_id'),
                $this->aliasField('course_id'),
                $this->aliasField('training_need_category_id'),
                $this->aliasField('modified'),
                $this->aliasField('created'),
                $this->Statuses->aliasField('name'),
                $this->Courses->aliasField('code'),
                $this->Courses->aliasField('name'),
                $this->CreatedUser->aliasField('openemis_no'),
                $this->CreatedUser->aliasField('first_name'),
                $this->CreatedUser->aliasField('middle_name'),
                $this->CreatedUser->aliasField('third_name'),
                $this->CreatedUser->aliasField('last_name'),
                $this->CreatedUser->aliasField('preferred_name')
            ])
            ->contain([$this->Courses->alias(), $this->CreatedUser->alias()])
            ->matching($this->Statuses->alias(), function ($q) use ($Statuses, $doneStatus) {
                return $q->where([$Statuses->aliasField('category <> ') => $doneStatus]);
            })
            ->where([$this->aliasField('assignee_id') => $userId])
            ->order([$this->aliasField('created') => 'DESC'])
            ->formatResults(function (ResultSetInterface $results) use ($typeOptions) {
                return $results->map(function ($row) use ($typeOptions) {
                    $url = [
                        'plugin' => 'Staff',
                        'controller' => 'Staff',
                        'action' => 'TrainingNeeds',
                        'view',
                        $this->paramsEncode(['id' => $row->id])
                    ];

                    if (is_null($row->modified)) {
                        $receivedDate = $this->formatDate($row->created);
                    } else {
                        $receivedDate = $this->formatDate($row->modified);
                    }

                    if ($row->training_need_category_id == 0) {
                        $row->type = self::CATALOGUE;
                    } else {
                        $row->type = self::NEED;
                    }

                    $row['url'] = $url;
                    $row['status'] = __($row->_matchingData['Statuses']->name);
                    $row['request_title'] = sprintf(__('%s from %s'), $row->code_name, __($typeOptions[$row->type]));
                    $row['received_date'] = $receivedDate;
                    $row['requester'] = $row->created_user->name_with_id;
                    return $row;
                });
            });

        return $query;
    }
}
