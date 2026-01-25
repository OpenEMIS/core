<?php

namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\I18n\Date;
use Cake\Event\EventInterface;
use Cake\Validation\Validator;
use Cake\Http\ServerRequest;
use Cake\Datasource\ResultSetInterface;
use App\Model\Table\ControllerActionTable;
use App\Model\Traits\OptionsTrait;
use Cake\Log\Log;
use Cake\Utility\Inflector;
use Cake\ORM\Table;
use Cake\Collection\Collection;
use Cake\Collection\CollectionInterface;
use Cake\Controller\Component;

class InstitutionDepartmentsTable extends ControllerActionTable
{

    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('institution_departments');

        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('Managers', ['className' => 'User.Users']);
        $this->hasMany('DepartmentStaff', ['className' => 'Institution.DepartmentStaff',
            'foreignKey' => 'institution_department_id',
            'dependent' => true,
            'cascadeCallbacks' => true]);

        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['Departments' => ['id']
            ]
        ]);
        $this->addBehavior('Restful.RestfulAccessControl', [
            'DepartmentStaff' => ['index'],
        ]);
    }

    public function beforeMarshal(\Cake\Event\EventInterface $event, \ArrayObject $data, \ArrayObject $options)
    {
        if (empty($data['institution_id'])) {
            $data['institution_id'] = $this->getInstitutionID();
        }
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        $validator
            ->requirePresence('code', 'create')
            ->notEmptyString('code', 'Please enter a code.')
            ->requirePresence('name', 'create')
            ->notEmptyString('name', 'Please enter a name.')
            // then your unique rules...
            ->add('code', 'unique', [
                'rule'     => ['validateUnique', ['scope' => ['institution_id']]],
                'provider' => 'table',
                'message'  => __('This department code is already in use.')
            ])
            ->add('name', 'unique', [
                'rule'     => ['validateUnique', ['scope' => ['institution_id']]],
                'provider' => 'table',
                'message'  => __('This department name is already in use.')
            ]);

        return $validator;
    }


    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->field('institution_id', ['visible' => false]);
        $this->field('staff', [
            'label' => '',
            'override' => true,
            'type' => 'element',
            'element' => 'Institution.Departments/staff',
            'data' => [
                'students' => []
            ],
            'visible' => ['view' => true, 'edit' => false, 'index' => false]
        ]);
    }
    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['Model.Navigation.breadcrumb'] = 'onGetBreadcrumb';
        return $events;
    }

    public function onGetBreadcrumb(EventInterface $event, ServerRequest $request, Component $Navigation, $persona=false)
    {
        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);
        $url = ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'Departments', 'index',$encodedQueryString];
        $Navigation->substituteCrumb('Institution Departments', __('Departments'), $url);
    }

    /******************************************************************************************************************
     **
     ** index action methods
     **
     ******************************************************************************************************************/
    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $header = __(Inflector::humanize(Inflector::underscore($this->getAlias())));
        $this->controller->set('contentHeader', $header);


        $this->fields['institution_id']['visible'] = false; //POCOR-6971

        $this->fields['manager_id']['sort'] = ['field' => 'Managers.first_name'];
        $this->setFieldOrder([
            'code',
            'name',
            'manager_id'
        ]);

        if (is_null($this->request->getQuery('sort'))) {
            //POCOR-8475 starts
            //$this->request->getQuery['sort'] = 'created';
            //$this->request->getQuery['direction'] = 'desc';
            $this->request = $this->request->withQueryParams(array_merge($this->request->getQueryParams(), ['sort' => 'created']));
            $this->request = $this->request->withQueryParams(array_merge($this->request->getQueryParams(), ['direction' => 'desc']));
            //POCOR-8475 ends
        }
    }


    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $extra['auto_contain'] = false;
        $extra['auto_order'] = false;
        $institutionStaff = self::getDynamicTableInstance('Report.InstitutionStaff');
        $institutionID = $this->getQueryString('institution_id');
        if (!$institutionID) {
            $event->stopPropagation();
//            return $this->controller->redirect(['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => 'index']);

        }
        $query
            ->select([
                $this->aliasField('id'),
                $this->aliasField('name'),
                $this->aliasField('code'),
                $this->aliasField('manager_id'),
                $this->aliasField('created')
            ])
            ->contain([

                'Managers' => [
                    'fields' => [
                        'Managers.id',
                        'Managers.first_name',
                        'Managers.middle_name',
                        'Managers.third_name',
                        'Managers.last_name',
                        'Managers.preferred_name'
                    ]
                ]

            ])
            ->distinct([$this->aliasField('code')])
            ->where([$this->aliasField('institution_id') => $institutionID])
            ->order([
                $this->aliasField($this->request->getQuery('sort'))
                => $this->request->getQuery('direction')]); //POCOR-8475

        $sortList = ['code', 'name'/*,POCOR-5069 'StaffPositionGrades.order'*/, 'created', 'Assignees.first_name'];
        if (array_key_exists('sortWhitelist', $extra['options'])) {
            $sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
        }
        $extra['options']['sortWhitelist'] = $sortList;
        $params = $this->request->getQuery();
        if (empty($params)) {
            $extra['options']['direction'] = 'desc';
            $extra['options']['limit'] = 10;
            $extra['options']['sort'] = 'name';
        }
//        Log::debug(print_r($params, true));
    }

    /**
     * Get a dynamic table instance with all associations.
     *
     * @param string $tableName . POCOR-8231
     * @return \Cake\ORM\Table
     *
     */
    private static function getDynamicTableInstance(string $tableName): Table
    {
        // Parse plugin and table names if dot notation is used
        // Create a TableLocator instance
        $locator = TableRegistry::getTableLocator();

        try {
            // Try to get the table instance directly
            return $locator->get($tableName);
        } catch (\Exception $e) {
            Log::debug('Error: ' . $e->getMessage());
        }

        $parts = explode('.', $tableName);
        $plugin = count($parts) > 1 ? $parts[0] : null;
        $table = count($parts) > 1 ? $parts[1] : $parts[0];

        // Convert the table name to camel case as expected by CakePHP conventions
        $tableFullAlias = Inflector::camelize($tableName);
        $tableAlias = Inflector::camelize($table);

        // Create the fully qualified class name if a plugin is specified
        if ($plugin) {
            $className = $plugin . '\\Model\\Table\\' . $tableAlias . 'Table';
        } else {
            $className = 'App\\Model\\Table\\' . $tableAlias . 'Table';
        }

        // Check if the table instance already exists
        if (!$locator->exists($tableFullAlias)) {
            // Check if the specific table class exists
            if (!class_exists($className)) {
                $className = Table::class; // Fallback to generic Table class
            }

            // Configure a new table instance
            $locator->setConfig($tableAlias, [
                'className' => $className,
                'table' => $table,
                'alias' => $tableAlias,
            ]);
        }

        // Return the table instance
        return $locator->get($tableFullAlias);
    }

    /******************************************************************************************************************
     **
     ** addEdit action methods
     **
     ******************************************************************************************************************/

    public function addEditAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
        $this->setupFields($event, $entity);
    }

    public function setupFields(EventInterface $event, Entity $entity)
    {

        $this->fields['institution_id']['visible'] = false;
        $this->field('manager_id', ['entity' => $entity]);
        $this->setFieldOrder([
            'code',
            'name',
            'manager_id',
        ]);
    }

    public function addAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {

        if (!isset($entity['institution_id'])) {
            $entity['institution_id'] = $this->getInstitutionID();
        };

    }


    //POCOR-6925

    public function beforeSave($event, Entity $entity, ArrayObject $options)
    {
        if (!isset($entity->institution_id)) {
            $entity->institution_id = $this->getInstitutionID();
        }
    }

    public function onUpdateFieldManagerId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $entity = $attr['entity'];
            $InstitutionStaff = TableRegistry::getTableLocator()->get('Institution.Staff');
            $institutionId = $entity->institution_id ?? $this->getInstitutionID();
            $AcademicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');

            $raw = $InstitutionStaff->find('subjectStaffOptions',
                ['institution_id' => $institutionId,
                    'type' => -1])
                ->toList();
            $managerOptions = (new Collection($raw))
                ->combine('id', 'name')
                ->toArray();
            $attr['type'] = 'chosenSelect';
            $attr['attr']['multiple'] = false;
            $attr['select'] = false;
            $attr['options'] = ['' => '-- ' . __('Select Manager') . ' --'] + $managerOptions;
            $attr['onChangeReload'] = 'changeStatus';
        }
        return $attr;
    }

    public function onGetFieldLabel(EventInterface $event, $module, $field, $language, $autoHumanize = true)
    {
        return match ($field) {
            'manager_id' => __('Manager'),
            default => parent::onGetFieldLabel($event, $module, $field, $language, $autoHumanize),
        };
    }

    /**
     * Get Associations Details
     */
    public function findDepartmentDetails(Query $query, array $options)
    {
        return $query
            ->contain([
                'Managers',
                'DepartmentStaff.Staff.Users.Genders',
                'DepartmentStaff.Staff.StaffStatuses'
            ])
            ->formatResults(function (CollectionInterface $results) {
                return $results->map(function ($department) {
                    // Map each staff member to a flat array
                    $assignedStaff = collection($department->department_staff)
                        ->map(function ($department_staff) {
                            $staff = $department_staff->staff;
                            return [
                                'openemis_no' => $staff->user->openemis_no,
                                'name' => $staff->user->name,
                                'staff_status_name' => $staff->staff_status->name,
                                'gender_name' => $staff->user->gender->name,
                                'staff_id' => $staff->id,
                                'security_user_id' => $staff->user->id,
                                'encodedVar' => base64_encode(json_encode([
                                    'institution_staff_id' => $staff->id,
                                    'institution_department_id' => $department_staff->institution_department_id,
                                ])),
                            ];
                        })
                        ->toList();

                    // Replace the raw department_staff with our flat array
                    $department->assigned_staff = $assignedStaff;
                    unset($department->department_staff);

                    return $department;
                });
            });
    }

    public function afterSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
//        POCOR-8391 start
        if (!$entity->isNew()) {
            if (isset($entity->assigned_staff)) {
                $departmentId = $entity->id;
                $newStaff = [];
//                Log::debug(print_r(['$entity->assigned_staff' => $entity->assigned_staff],true));
                foreach ($entity->assigned_staff as $item) {
                    $staff = json_decode($this->urlsafeB64Decode($item['encodedVar']), true);
                    $newStaff[$staff['institution_staff_id']] = $staff;
                }
//                Log::debug(print_r(['$newStaff' => $newStaff],true));
                $departmentStaff = TableRegistry::getTableLocator()->get('Institution.DepartmentStaff');
                $where = [
                    $departmentStaff->aliasField('institution_department_id') => $departmentId,
                ];
                $existingStaff = $departmentStaff
                    ->find('all')
                    ->where($where)
                    ->toArray();

                foreach ($existingStaff as $departmentStaffEntity) {
                    $institution_staff_id = $departmentStaffEntity->institution_staff_id;
                    if (!isset($newStaff[$institution_staff_id])) { // if current student does not exists in the new list of students
                        $deleted = $this->DepartmentStaff->delete($departmentStaffEntity);
//                        Log::debug(print_r(['$deleted' => $deleted], true));
                    } else { // if student exists, then remove from the array to get the new student records to be added
                        unset($newStaff[$institution_staff_id]);
                    }
                }
//                Log::debug(print_r($newStaff, true));
                foreach ($newStaff as $staff) {
                    try {
                        $departmentStaffEntity = $this->DepartmentStaff->newEntity($staff);
                        $this->DepartmentStaff->save($departmentStaffEntity);
                    } catch (\Exception $exception) {
                        Log::debug($exception->getMessage());
                    }
                }
            }
        }
    }

    public function viewAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {
//        dd($entity);
        $this->fields['staff']['data']['staff'] = $entity->assigned_staff;
        $this->setFieldOrder([
            'code', 'name', 'manager_id', 'staff'
        ]);
        return $entity;

    }
    public function viewBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $query
            ->contain([
                'DepartmentStaff.Staff.Users.Genders',
                'DepartmentStaff.Staff.StaffStatuses'
            ])
            ->formatResults(function (CollectionInterface $results) {
                return $results->map(function ($department) {
                    // Map each staff member to a flat array
                    $assignedStaff = collection($department->department_staff)
                        ->map(function ($department_staff) use ($department) {
                            $staff = $department_staff->staff;
                            return [
                                'openemis_no' => $staff->user->openemis_no,
                                'name' => $staff->user->name,
                                'staff_status_name' => $staff->staff_status->name,
                                'gender_name' => $staff->user->gender->name,
                                'security_user_id' => $staff->user->id,
                                'staff_id' => $staff->id,
                                'institution_id' => $department->institution_id,
                            ];
                        })
                        ->toList();

                    // Replace the raw department_staff with our flat array
                    $department->assigned_staff = $assignedStaff;
                    unset($department->department_staff);

                    return $department;
                });
            });
    }
}

