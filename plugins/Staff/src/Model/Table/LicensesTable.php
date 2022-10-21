<?php
namespace Staff\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\ResultSet;
use Cake\Network\Request;
use Cake\Validation\Validator;
use Cake\Event\Event;
use Cake\Database\ValueBinder;
use Cake\Datasource\ResultSetInterface;
use App\Model\Table\ControllerActionTable;
use Cake\Log\Log;

class LicensesTable extends ControllerActionTable
{
    // Workflow Steps - category
    const TO_DO = 1;
    const IN_PROGRESS = 2;
    const DONE = 3;

    public function initialize(array $config)
    {
        $this->table('staff_licenses');
        parent::initialize($config);

        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('LicenseTypes', ['className' => 'FieldOption.LicenseTypes']);
        $this->belongsTo('Assignees', ['className' => 'User.Users']);

        $this->belongsToMany('Classifications', [
            'className' => 'FieldOption.LicenseClassifications',
            'joinTable' => 'staff_licenses_classifications',
            'foreignKey' => 'staff_license_id',
            'targetForeignKey' => 'license_classification_id',
            'through' => 'Staff.StaffLicensesClassifications',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

        $this->addBehavior('Workflow.Workflow');
        $this->addBehavior('AcademicPeriod.Period');
        $this->addBehavior('HighChart', [
            'institution_staff_licenses' => [
                '_function' => 'getNumberOfStaffByLicenses'
            ]
        ]);
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Dashboard' => ['index']
        ]);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        return $validator
            ->add('issue_date', 'ruleCompareDate', [
                'rule' => ['compareDate', 'expiry_date', false]
            ]);
    }

    public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        $this->field('comments', ['visible' => false]);
        $this->field('license_type_id', ['after' => 'assignee_id']);
    }

    public function viewEditBeforeQuery(Event $event, Query $query)
    {
        $query->contain(['Users', 'LicenseTypes', 'Classifications']);
    }

    public function viewAfterAction(Event $event, Entity $entity)
    {
        $this->setupFields($entity);
    }

    public function editOnInitialize(Event $event, Entity $entity)
    {
        $this->request->data[$this->alias()]['license_type_id'] = $entity->license_type_id;
    }

    public function addEditAfterAction(Event $event, Entity $entity)
    {
        $this->setupFields($entity);
    }

    /*POCOR-5833 starts*/
    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->fields['license_type_id']['type'] = 'select';
        $this->fields['license_type_id']['attr']['value'] = $entity->license_type_id;
        $this->field('classifications', [
            'type' => 'chosenSelect',
            'fieldNameKey' => 'classifications',
            'fieldName' => $this->alias() . '.classifications._ids',
            'placeholder' => $this->getMessage($this->aliasField('select_classification'))
        ]);

        $this->setFieldOrder(['license_type_id', 'classifications', 'license_number', 'issue_date', 'expiry_date', 'issuer', 'comments']);
    }
    /*POCOR-5833 ends*/

    public function afterAction(Event $event, ArrayObject $extra)
    {
        $this->setupTabElements();
    }

    public function onUpdateFieldLicenseTypeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add') {
            $attr['onChangeReload'] = 'changeLicenseType';
        }
        /*PCORO-5833 starts*/
        if ($action == 'edit') {
            $staffId = $this->Session->read('Staff.Staff.id');
            $licenseTypeId = $request->data['Licenses']['license_type_id'];
            $StaffLicensesTable = TableRegistry::get('staff_licenses');
            $WorkflowSteps = TableRegistry::get('Workflow.WorkflowSteps');
            $Workflows = TableRegistry::get('Workflow.Workflows');
            $WorkflowsFilters = TableRegistry::get('Workflow.WorkflowsFilters');
            $LicenseTypes = TableRegistry::get('FieldOption.LicenseTypes');
            $getData = $StaffLicensesTable->find()
                        ->select([$WorkflowSteps->aliasField('workflow_id')])
                        ->leftJoin([$WorkflowSteps->alias() => $WorkflowSteps->table()], [
                            $StaffLicensesTable->aliasField('status_id = ') . $WorkflowSteps->aliasField('id')
                        ])
                        ->where([
                            $StaffLicensesTable->aliasField('staff_id') => $staffId,
                            $StaffLicensesTable->aliasField('license_type_id') => $licenseTypeId
                        ])->first();
            $selectedModel = $getData->WorkflowSteps['workflow_id'];
            $filterOptions = $LicenseTypes->find('list', ['keyField' => 'id', 'valueField' => 'name'])
                            ->leftJoin([$WorkflowsFilters->alias() => $WorkflowsFilters->table()], [
                                $WorkflowsFilters->aliasField('filter_id = ') . $LicenseTypes->aliasField('id'),
                            ])
                            ->where([$WorkflowsFilters->aliasField('workflow_id = ') => $selectedModel])
                            ->toArray();
            if (!empty($filterOptions)) {
                $attr['options'] = $filterOptions;
            } else {
                $filterOptions = $LicenseTypes->find('list', 
                                    ['keyField' => 'id', 'valueField' => 'name'])
                                ->where([$LicenseTypes->aliasField('id = ') => $licenseTypeId])
                                ->toArray();
                                
                $attr['options'] = $filterOptions;
            }
        }
        /*POCOR-5833 ends*/
        return $attr;
    }

    public function onUpdateFieldClassifications(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $classificationOptions = [];

            if (array_key_exists($this->alias(), $request->data) && array_key_exists('license_type_id', $request->data[$this->alias()])) {
                $licenseTypeId = $request->data[$this->alias()]['license_type_id'];

                if (!empty($licenseTypeId)) {
                    $classificationOptions = $this->Classifications
                        ->find('list')
                        ->find('visible')
                        ->find('order')
                        ->where([$this->Classifications->aliasField('license_type_id') => $licenseTypeId])
                        ->toArray();
                }
            }

            if (empty($classificationOptions)) {
                $attr['type'] = 'select';
                $attr['options'] = ['' => $this->getMessage('general.select.noOptions')];
            } else {
                $attr['options'] = $classificationOptions;
            }
        }

        return $attr;
    }

    // Use for Mini dashboard (Institution Staff)
    public function getNumberOfStaffByLicenses($params = [])
    {
        $query = $params['query'];
        $table = $params['table'];

        $StaffTableQuery = clone $query;
        $staffTableInnerJoinQuery = $StaffTableQuery->select([$table->aliasField('staff_id')]);
        $staffTable = TableRegistry::get('Institution.Staff');
        $innerJoinArray = [
            'StaffUser.Staff__staff_id = '. $this->aliasField('staff_id'),
            ];
        $licenseRecord = $this->find();
        $licenseCount = $licenseRecord
            ->contain(['Users', 'LicenseTypes'])
            ->select([
                'license' => 'LicenseTypes.name',
                'count' => $licenseRecord->func()->count($this->aliasField('staff_id'))
            ])
            ->join([
                'StaffUser' => [
                    'table' => $staffTableInnerJoinQuery,
                    'type' => 'INNER',
                    'conditions' => $innerJoinArray
                ]
            ])
            ->group('license')
            ->toArray()
            ;
        $dataSet = [];
        foreach ($licenseCount as $value) {
            //Compile the dataset
            $dataSet[] = [$value['license'], $value['count']];
        }
        $params['dataSet'] = $dataSet;
        return $params;
    }

    private function setupTabElements()
    {
        $tabElements = $this->controller->getProfessionalTabElements();
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', $this->alias());
    }

    private function setupFields(Entity $entity)
    {
        $this->field('license_type_id', [
            'type' => 'select'
        ]);
        $this->field('classifications', [
            'type' => 'chosenSelect',
            'fieldNameKey' => 'classifications',
            'fieldName' => $this->alias() . '.classifications._ids',
            'placeholder' => $this->getMessage($this->aliasField('select_classification'))
        ]);

        $this->setFieldOrder(['license_type_id', 'classifications', 'license_number', 'issue_date', 'expiry_date', 'issuer', 'comments']);
    }

    public function findWorkbench(Query $query, array $options)
    {
        $controller = $options['_controller'];
        $session = $controller->request->session();

        $userId = $session->read('Auth.User.id');
        $institutionId = $session->read('Institution.Institutions.id');
        $Statuses = $this->Statuses;
        $doneStatus = self::DONE;

        $query
            ->select([
                $this->aliasField('id'),
                $this->aliasField('status_id'),
                $this->aliasField('staff_id'),
                $this->aliasField('license_number'),
                $this->aliasField('license_type_id'),
                $this->aliasField('modified'),
                $this->aliasField('created'),
                $this->Statuses->aliasField('name'),
                $this->Users->aliasField('openemis_no'),
                $this->Users->aliasField('first_name'),
                $this->Users->aliasField('middle_name'),
                $this->Users->aliasField('third_name'),
                $this->Users->aliasField('last_name'),
                $this->Users->aliasField('preferred_name'),
                $this->LicenseTypes->aliasField('name'),
                $this->CreatedUser->aliasField('openemis_no'),
                $this->CreatedUser->aliasField('first_name'),
                $this->CreatedUser->aliasField('middle_name'),
                $this->CreatedUser->aliasField('third_name'),
                $this->CreatedUser->aliasField('last_name'),
                $this->CreatedUser->aliasField('preferred_name')
            ])
            ->contain([$this->LicenseTypes->alias(), $this->Users->alias(), $this->CreatedUser->alias()])
            ->matching($this->Statuses->alias(), function ($q) use ($Statuses, $doneStatus) {
                return $q->where([$Statuses->aliasField('category <> ') => $doneStatus]);
            })
            ->where([$this->aliasField('assignee_id') => $userId])
            ->order([$this->aliasField('created') => 'DESC'])
            ->formatResults(function (ResultSetInterface $results) use ($institutionId) {
                return $results->map(function ($row) use ($institutionId) {
                    $url = [
                        'plugin' => 'Directory',
                        'controller' => 'Directories',
                        'action' => 'StaffLicenses',
                        'view',
                        $this->paramsEncode(['id' => $row->id]),
                        'user_id' => $row->staff_id
                    ];

                    if (is_null($row->modified)) {
                        $receivedDate = $this->formatDate($row->created);
                    } else {
                        $receivedDate = $this->formatDate($row->modified);
                    }

                    $row['url'] = $url;
                    $row['status'] = __($row->_matchingData['Statuses']->name);
                    if ($row->has('license_number') && strlen($row->license_number) > 0) {
                        $row['request_title'] = sprintf(__('%s of %s for %s'), $row->license_type->name, $row->license_number, $row->user->name_with_id);
                    } else {
                        $row['request_title'] = sprintf(__('%s for %s'), $row->license_type->name, $row->user->name_with_id);
                    }                    
                    $row['received_date'] = $receivedDate;
                    $row['requester'] = $row->created_user->name_with_id;

                    return $row;
                });
            });

        return $query;
    }

    public function getModelAlertData($threshold)
    {
        $thresholdArray = json_decode($threshold, true);

        $conditions = [
            1 => ('DATEDIFF(' . $this->aliasField('expiry_date') . ', NOW())' . ' BETWEEN 0 AND ' . $thresholdArray['value']), // before
            2 => ('DATEDIFF(NOW(), ' . $this->aliasField('expiry_date') . ')' . ' BETWEEN 0 AND ' . $thresholdArray['value']), // after
        ];

        // will do the comparison with threshold when retrieving the absence data
        $licenseData = $this->find()
            ->select([
                'LicenseTypes.name',
                'license_number',
                'issue_date',
                'expiry_date',
                'issuer',
                'Users.id',
                'Users.openemis_no',
                'Users.first_name',
                'Users.middle_name',
                'Users.third_name',
                'Users.last_name',
                'Users.preferred_name',
                'Users.email',
                'Users.address',
                'Users.postal_code',
                'Users.date_of_birth',
            ])
            ->contain(['Statuses', 'Users', 'LicenseTypes', 'Assignees'])
            ->where([
                $this->aliasField('license_type_id') => $thresholdArray['license_type'],
                $this->aliasField('expiry_date') . ' IS NOT NULL',
                $conditions[$thresholdArray['condition']]
            ])
            ->hydrate(false)
            ;

        return $licenseData->toArray();
    }

    //POCOR-6925
    public function onUpdateFieldAssigneeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $workflowModel = 'Staff > Professional Development > Licenses';
            $workflowModelsTable = TableRegistry::get('workflow_models');
            $workflowStepsTable = TableRegistry::get('workflow_steps');
            $Workflows = TableRegistry::get('Workflow.Workflows');
            $workModelId = $Workflows
                            ->find()
                            ->select(['id'=>$workflowModelsTable->aliasField('id'),
                            'workflow_id'=>$Workflows->aliasField('id'),
                            'is_school_based'=>$workflowModelsTable->aliasField('is_school_based')])
                            ->LeftJoin([$workflowModelsTable->alias() => $workflowModelsTable->table()],
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
            $session = $request->session();
            if ($session->check('Institution.Institutions.id')) {
                $institutionId = $session->read('Institution.Institutions.id');
            }
            $institutionId = $institutionId;
            $assigneeOptions = [];
            if (!is_null($stepId)) {
                $WorkflowStepsRoles = TableRegistry::get('Workflow.WorkflowStepsRoles');
                $stepRoles = $WorkflowStepsRoles->getRolesByStep($stepId);
                if (!empty($stepRoles)) {
                    $SecurityGroupUsers = TableRegistry::get('Security.SecurityGroupUsers');
                    $Areas = TableRegistry::get('Area.Areas');
                    $Institutions = TableRegistry::get('Institution.Institutions');
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
