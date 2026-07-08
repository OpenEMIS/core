<?php
namespace Institution\Model\Table;

use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Utility\Inflector;
use Cake\Controller\Component;
use Cake\Http\ServerRequest;
use Cake\ORM\TableRegistry;
use Cake\Datasource\ResultSetInterface;
use App\Model\Table\ControllerActionTable;
use Cake\Validation\Validator;
use Cake\I18n\Date;
use Cake\I18n\Time;
use App\Model\Traits\OptionsTrait;
use Cake\Utility\Text;
use Cake\ORM\Table;
use ArrayObject;

class StaffPositionProfilesTable extends ControllerActionTable
{
    use OptionsTrait;
    // Workflow Steps - category
    const TO_DO = 1;
    const IN_PROGRESS = 2;
    const DONE = 3;

    private $staffChangeTypesList = [];
    /*
    * 1,END_OF_ASSIGNMENT,End of Assignment
2,CHANGE_IN_FTE,Change in FTE
3,CHANGE_IN_STAFF_TYPE,Change in Staff Type
4,CHANGE_OF_START_DATE,Change of Start Date
5,CHANGE_OF_SHIFT,Change of Shift
6,HOMEROOM_TEACHER,Homeroom Teacher
    */

    private $workflowEvents = [
        [
            'value' => 'Workflow.onApprove',
            'text' => 'Approval of Change in Assignment',
            'description' => 'Performing this action will apply the proposed changes to the staff record.',
            'method' => 'OnApprove'
        ]
    ];

    private $associatedModelList = [
        'Institution.InstitutionClasses' => [
            'HomeroomTeacher'
        ],
        'Institution.StaffTransferOut' => [
            'StaffTransfer'
        ],
        'Institution.StaffLeave' => [
            'StaffLeave'
        ],

        'Institution.InstitutionStudentsReportCardsComments' => [
            'ReportCardsCommentByTheStaff'
        ],
        'Staff.StaffSubjects' => [
            'StaffTeachingSubject'
        ],
        'Institution.StaffAppraisals' => [
            'AppraisalsByTheStaff'
        ],
        'Institution.StaffBehaviours' => [
            'StaffBehaviours'
        ],
        'Staff.Salaries' => [
            'StaffSalaries'
        ]
    ];

    public function initialize(array $config): void
    {
        $this->setTable('institution_staff_position_profiles');
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('StaffChangeTypes', ['className' => 'Staff.StaffChangeTypes', 'foreignKey' => 'staff_change_type_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users']);
        $this->belongsTo('StaffTypes', ['className' => 'Staff.StaffTypes']);
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Positions', ['className' => 'Institution.InstitutionPositions', 'foreignKey' => 'institution_position_id']);
        $this->belongsTo('StaffTypes', ['className' => 'Staff.StaffTypes', 'foreignKey' => 'staff_type_id']);

        $this->staffChangeTypesList = $this->StaffChangeTypes->findCodeList();
//        $this->addBehavior('Institution.StaffValidation');
        $this->addBehavior('Workflow.Workflow');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Dashboard' => ['index']
        ]);
        $this->addBehavior('HighChart', [
            'staff_attendance' => [
                '_function' => 'getNumberOfStaffByAttendanceType',
                '_defaultColors' => false,
                'chart' => ['type' => 'column', 'borderWidth' => 1],
                'xAxis' => ['title' => ['text' => __('Years')]],
                'yAxis' => ['title' => ['text' => __('Total')]]
            ],
            'number_of_staff_by_type' => [
                '_function' => 'getNumberOfStaffByType',
                '_defaultColors' => false,
                'chart' => ['type' => 'column', 'borderWidth' => 1],
                'xAxis' => ['title' => ['text' => __('Position Type')]],
                'yAxis' => ['title' => ['text' => __('Total')]]
            ],
            'number_of_staff_by_position' => [
                '_function' => 'getNumberOfStaffByPosition',
                '_defaultColors' => false,
                'chart' => ['type' => 'column', 'borderWidth' => 1],
                'xAxis' => ['title' => ['text' => __('Position Title')]],
                'yAxis' => ['title' => ['text' => __('Total')]]
            ],
            'number_of_staff_by_year' => [
                '_function' => 'getNumberOfStaffByYear',
                '_defaultColors' => false,
                'chart' => ['type' => 'column', 'borderWidth' => 1],
                'xAxis' => ['title' => ['text' => __('Years')]],
                'yAxis' => ['title' => ['text' => __('Total')]]
            ],
            'institution_staff_gender' => [
                '_function' => 'getNumberOfStaffsByGender',
                '_defaultColors' => false,
            ],
            'institution_staff_qualification' => [
                '_function' => 'getNumberOfStaffsByQualification'
            ],
        ]);
        $this->addBehavior('User.User');
        $this->addBehavior('User.AdvancedNameSearch');
        /**
         * Advance Search Types.
         * AdvanceSearchBehavior must be included first before adding other types of advance search.
         * If no "belongsTo" relation from the main model is needed, include its foreign key name in AdvanceSearch->exclude options.
         */
        $advancedSearchFieldOrder = [
            'first_name', 'middle_name', 'third_name', 'last_name',
            'contact_number'
        ];

        /** START: Removed to resolve the tiket "POCOR-6367"
         *
         * Note: This code is commented due to remove the advanced search filter temporary base Because it is blocking the page.
         * Author : Anand Malvi
        $this->addBehavior('AdvanceSearch', [
            'exclude' => [
                'staff_id',
                'institution_id',
                'staff_type_id',
                'staff_status_id',
                'institution_position_id',
                'security_group_user_id'
            ],
            'order' => $advancedSearchFieldOrder
        ]);
        ** END : Removed to resolve the tiket "POCOR-6367" */
        $this->addBehavior('User.AdvancedIdentitySearch', [
            'associatedKey' => $this->aliasField('staff_id')
        ]);
        $this->addBehavior('User.AdvancedContactNumberSearch', [
            'associatedKey' => $this->aliasField('staff_id')
        ]);
        $this->addBehavior('User.AdvancedSpecificNameTypeSearch', [
            'modelToSearch' => $this->Users
        ]);

        $this->addBehavior('Institution.StaffValidation');
//        $this->addBehavior('ControllerAction.Image');

        // POCOR-4047 to get staff profile data
//        $this->addBehavior('Institution.StaffProfile');
//        $this->addBehavior('StaffProfileBehavior');
        $this->addBehavior('Institution.InstitutionTab', [
            'appliedAction' => ['StaffPositionProfiles'=>['id']]
        ]);
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['Workflow.getEvents'] = 'getWorkflowEvents';
        $events['Workflow.beforeTransition'] = 'workflowBeforeTransition';
        $events['Model.Navigation.breadcrumb'] = 'onGetBreadcrumb';
        $events['ControllerAction.Model.getSearchableFields'] = ['callable' => 'getSearchableFields', 'priority' => 5];
        foreach ($this->workflowEvents as $event) {
            $events[$event['value']] = $event['method'];
        }
        return $events;
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator = $this->buildStaffValidation();
        $validator->setProvider('custom', $this);
        //Start:POCOR-6913
        $requestData = $this->request->getData();
        if($requestData['StaffPositionProfiles']['staff_change_type_id'] == 1){
            return $validator
            ->notEmpty('end_date')
            ->remove('start_date')
            ->requirePresence('FTE', 'This field cannot be left empty') //POCOR-9421
            ->requirePresence('staff_change_type_id')
            ->requirePresence('staff_type_id')
            ->requirePresence('assignee_id', 'This field cannot be left empty') //POCOR-9421

            ->add('start_date', 'customCompare', [
                'rule' => function ($value, $context) {
                    $staffChangeTypes = $this->staffChangeTypesList;
                    if ($context['data']['staff_change_type_id'] == $staffChangeTypes['CHANGE_OF_START_DATE']) {
                        $contextData = $context['data'];
                        if (!empty($contextData['end_date'])) {
                            $newStartDate = new Date($value);
                            $endDate = new Date($contextData['end_date']);
                            if ($newStartDate > $endDate) {
                                return vsprintf(__('Start Date cannot be later than %s'),[$endDate->format('d-m-Y')]);
                            } else {
                                return true;
                            }
                        } else {
                            return true;
                        }
                    } else {
                        return true;
                    }
                },
                'last' => true
            ])
            ->add('start_date', 'customFTE', [
                'rule' => function ($value, $context) {
                    $staffChangeTypes = $this->staffChangeTypesList;
                    if ($context['data']['staff_change_type_id'] == $staffChangeTypes['CHANGE_OF_START_DATE']) {
                        $contextData = $context['data'];
                        $institutionId = $contextData['institution_id'];
                        $institutionStaffId = $contextData['institution_staff_id'];
                        $institutionPositionId = $contextData['institution_position_id'];
                        $FTE = $contextData['FTE'];
                        $newStartDate = new Date($value); // new start_date
                        $InstitutionStaff = TableRegistry::getTableLocator()->get('Institution.Staff');
                        $originalStartDate = new Date($InstitutionStaff->get($institutionStaffId)->start_date);
                        // get the records that have same institution_id, same position_id, other than the records itself
                        // with the start_date before the record_original_start_date and end_date is after the new_start_date
                        $records = $InstitutionStaff->find()
                            ->where([
                                $InstitutionStaff->aliasField('institution_id') => $institutionId,
                                $InstitutionStaff->aliasField('id <>') => $institutionStaffId,
                                $InstitutionStaff->aliasField('institution_position_id') => $institutionPositionId,
                                $InstitutionStaff->aliasField('start_date <= ') => $originalStartDate,
                                'OR' => [
                                    $InstitutionStaff->aliasField('end_date >= ') => $newStartDate,
                                    $InstitutionStaff->aliasField('end_date IS NULL '),
                                ]
                            ])
                            ->toArray();
                        if (!empty($records)) {
                            foreach ($records as $record) {
                                $FTE = $record->FTE + $FTE;
                            }
                        }
                        if ($FTE <= 1) {
                            return true;
                        }
                        return false;
                    } else {
                        return true;
                    }
                },
                'message' => __('FTE is more than 100%'),
                'last' => true
            ])
            ;
        }else{
            return $validator
            ->allowEmpty('end_date')
            ->remove('start_date')
            ->requirePresence('FTE')
            ->requirePresence('staff_change_type_id')
            ->requirePresence('staff_type_id')
            ->add('start_date', 'customCompare', [
                'rule' => function ($value, $context) {
                    $staffChangeTypes = $this->staffChangeTypesList;
                    if ($context['data']['staff_change_type_id'] == $staffChangeTypes['CHANGE_OF_START_DATE']) {
                        $contextData = $context['data'];
                        if (!empty($contextData['end_date'])) {
                            $newStartDate = new Date($value);
                            $endDate = new Date($contextData['end_date']);
                            if ($newStartDate > $endDate) {
                                return vsprintf(__('Start Date cannot be later than %s'),[$endDate->format('d-m-Y')]);
                            } else {
                                return true;
                            }
                        } else {
                            return true;
                        }
                    } else {
                        return true;
                    }
                },
                'last' => true
            ])
            ->add('start_date', 'customFTE', [
                'rule' => function ($value, $context) {
                    $staffChangeTypes = $this->staffChangeTypesList;
                    if ($context['data']['staff_change_type_id'] == $staffChangeTypes['CHANGE_OF_START_DATE']) {
                        $contextData = $context['data'];
                        $institutionId = $contextData['institution_id'];
                        $institutionStaffId = $contextData['institution_staff_id'];
                        $institutionPositionId = $contextData['institution_position_id'];
                        $FTE = $contextData['FTE'];
                        $newStartDate = new Date($value); // new start_date
                        $InstitutionStaff = TableRegistry::getTableLocator()->get('Institution.Staff');
                        $originalStartDate = new Date($InstitutionStaff->get($institutionStaffId)->start_date);
                        // get the records that have same institution_id, same position_id, other than the records itself
                        // with the start_date before the record_original_start_date and end_date is after the new_start_date
                        $records = $InstitutionStaff->find()
                            ->where([
                                $InstitutionStaff->aliasField('institution_id') => $institutionId,
                                $InstitutionStaff->aliasField('id <>') => $institutionStaffId,
                                $InstitutionStaff->aliasField('institution_position_id') => $institutionPositionId,
                                $InstitutionStaff->aliasField('start_date <= ') => $originalStartDate,
                                'OR' => [
                                    $InstitutionStaff->aliasField('end_date >= ') => $newStartDate,
                                    $InstitutionStaff->aliasField('end_date IS NULL '),
                                ]
                            ])
                            ->toArray();
                        if (!empty($records)) {
                            foreach ($records as $record) {
                                $FTE = $record->FTE + $FTE;
                            }
                        }
                        if ($FTE <= 1) {
                            return true;
                        }
                        return false;
                    } else {
                        return true;
                    }
                },
                'message' => __('FTE is more than 100%'),
                'last' => true
            ]);
        }
        //END:POCOR-6913
    }

    public function validationIncludeEffectiveDate(Validator $validator)
    {
        $validator = $this->validationDefault($validator);
        $validator->setProvider('custom', $this);
        return $validator->requirePresence('effective_date');
    }


    public function getSearchableFields(EventInterface $event, ArrayObject $searchableFields)
    {
        $searchableFields[] = 'staff_id';
        $searchableFields[] = 'openemis_no';
    }

    /**
     * POCOR-8774
     * This beforeSave method implements logic to handle various staff change scenarios,
     * such as homeroom teacher changes, shifts, and other staff-related modifications.
     * Key functionalities:
     * - Manages security group associations for homeroom teacher changes.
     * - Updates `end_date` based on the type of staff change.
     * - Validates and updates associated data to ensure consistency.
     * POCOR-8853 fixed change FTE and change homeroom teacher
     */
    public function beforeSave(EventInterface $event, Entity $entity, ArrayObject $options)
    {
        $approved_status = $this->getApprovedStatus();
        $requestedStaffChangeCode = $this->getStaffChangeCode($entity);

        if ($requestedStaffChangeCode == 'HOMEROOM_TEACHER') { // Homeroom teacher change
            $this->setHomeroomTeacher($entity);
            if (!isset($entity->end_date)) {
                unset($entity->end_date);
            }
            $entity->status_id = $approved_status;

        }

        // Update the `end_date` based on StaffChangeTypes
        if ($requestedStaffChangeCode) {
            switch ($requestedStaffChangeCode) {
                case 'CHANGE_IN_STAFF_TYPE':
                    $entity->end_date = $entity->end_date ?? null;
                    break;
                case 'CHANGE_IN_FTE':
                    if ($entity->status_id != $approved_status) {
                        $entity->end_date = $this->convertToValidDate($entity->effective_date);
                    } else {
                        $entity->end_date = $this->getDefaultEndDate($entity);
                    }
                    break;
                case 'END_OF_ASSIGNMENT':
                case 'CHANGE_OF_START_DATE':
                    $entity->end_date = $entity->end_date ?? null;
                    break;
                default:
                    break;
            }
        }

        $associatedData = $this->getAssociatedData($entity);

        if (!empty($associatedData)) {
            $message = __('The record is not updated due to associated data encountered.');
            $this->Alert->error($message, ['type' => 'string', 'reset' => true]);
            $event->stopPropagation();
            return $this->controller->redirect($this->url('add'));
        } else {

            if ($requestedStaffChangeCode == 'CHANGE_OF_SHIFT') {
                //$entity->status_id = $approved_status;
                $entity->status_id = $entity->status_id; // POCOR-9722
                $AcademicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
                $InstitutionPositions = TableRegistry::getTableLocator()->get('Institution.InstitutionPositions');
                $periodId = $AcademicPeriods->getCurrent();

                if (!empty($entity->new_shift)) { // POCOR-7109
                    $InstitutionPositions->updateAll(
                        [
                            'shift_id' => $entity->new_shift,
                            'assignee_id' => $entity->assignee_id, // POCOR-9722
                            'modified_user_id' => 1,
                            'modified' => new Time('NOW')
                        ],
                        ['id' => $entity->institution_position_id]
                    );
                }
            }
        }

        return $entity;
    }

    private function getDefaultEndDate($entity)
    {
        $staffPositionProfilesRecord = $this->find()
            ->where([
                $this->aliasField('institution_staff_id') => $entity->institution_staff_id,
                $this->aliasField('staff_id') => $entity->staff_id,
            ])
            ->first();

        return $staffPositionProfilesRecord->end_date ? $staffPositionProfilesRecord->end_date : null;
    }

    private function getAssociatedData($entity)
    {
        $requestData = $this->request->getData();
        $staffChangeTypes = $this->staffChangeTypesList;
        $AcademicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');

        $associatedData = [];
        if ((array_key_exists($this->getAlias(), $requestData)) && $requestData[$this->getAlias()]['staff_change_type_id'] == $staffChangeTypes['CHANGE_OF_START_DATE']) {
            $staffId = $entity->staff_id;
            $institutionId = $entity->institution_id;
            $institutionPositionId = $entity->institution_position_id;
            $institutionStaffId = $entity->institution_staff_id;

            $InstitutionStaff = TableRegistry::getTableLocator()->get('Institution.Staff');
            $originalStartDate = new Date($InstitutionStaff->get($institutionStaffId)->start_date);
            $newStartDate = new Date($entity->start_date);

            if ($newStartDate > $originalStartDate) { // if new_start_date is later than original_start_date
                foreach ($this->associatedModelList as $model => $value) {
                    $params = new ArrayObject([
                        'staff_id' => $staffId,
                        'institution_id' => $institutionId,
                        'institution_position_id' => $institutionPositionId,
                        'original_start_date' => $originalStartDate,
                        'new_start_date' => $newStartDate
                    ]);

                    $associatedModel = TableRegistry::getTableLocator()->get($model);

                    $event = $associatedModel->dispatchEvent('Model.StaffPositionProfiles.getAssociatedModelData', [$params], $this);

                    if ($event->isStopped()) {
                        $mainEvent->stopPropagation();
                        return $event->getResult();
                    }

                    $result = $event->getResult();

                    // if no result will not added to the associated data
                    if (!empty($result)) {
                        $associatedData[$value[0]] = $result;
                    }

                }
            }
        }

        return $associatedData;
    }

    public function addAfterSave(EventInterface $event, $entity, $requestData, ArrayObject $extra)
    {
        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);
        if (!$entity->getErrors()) {
            $StaffTable = TableRegistry::getTableLocator()->get('Institution.Staff');
           // $url = $this->url('view');
            $url['plugin'] = 'Institution';
            $url['controller'] = 'Institutions';
            $url['action'] = 'Staff';
            $url[0] = 'view';
            $url[1] = $encodedQueryString;
            $url[2] = $this->paramsEncode(['id' => $entity['institution_staff_id']]);
            /*$url['?'] = [
            ]*/
            $event->stopPropagation();
            $this->Session->write('Institution.StaffPositionProfiles.addSuccessful', true);
          //  echo "<pre>"; print_r($url);die;
            return $this->controller->redirect($url);
        }

    }

    //POCOR-8447 Start
    public function editAfterSave(EventInterface $event, Entity $entity, ArrayObject $data, ArrayObject $options)
    {
        if(isset($entity->staff_change_type_id) && !empty($entity->staff_change_type_id)) {
            $StaffChangeTypes = TableRegistry::getTableLocator()->get('Staff.StaffChangeTypes');

            $StaffChangeTypesDataForShift = $StaffChangeTypes->find()
                    ->where([$StaffChangeTypes->aliasField('id') => $entity->staff_change_type_id])
                    ->first();
            if($StaffChangeTypesDataForShift->code == 'CHANGE_OF_SHIFT' || $StaffChangeTypesDataForShift->code == 'HOMEROOM_TEACHER'){
                if($StaffChangeTypesDataForShift->code == 'CHANGE_OF_SHIFT' && !empty($entity->new_shift)) {
                    $InstitutionPositions = TableRegistry::getTableLocator()->get('Institution.InstitutionPositions');
                    $shiftUpdate =   $InstitutionPositions->updateAll(
                        ['shift_id' => $entity->new_shift,'modified_user_id' => 1,'modified' => new Time('NOW')],    //field
                        [
                         'id' => $entity->institution_position_id, //condition update
                        ]
                    );
                    $StaffChangeTypesData = $StaffChangeTypes->find()
                        ->where([$StaffChangeTypes->aliasField('id') => $this->request->getData()['StaffPositionProfiles']['staff_change_type_id']])
                        ->first();
                    if($StaffChangeTypesData['code'] != 'END_OF_ASSIGNMENT'){
                        $event->stopPropagation();
                    }
                }
                //POCOR 7289 tables updation start for homeroom
                if ($entity->staff_change_type_id == 6) {
                    $InstitutionStaff = TableRegistry::getTableLocator()->get('Institution.Staff');
                    $SecurityGroupUsers = TableRegistry::getTableLocator()->get('Security.SecurityGroupUsers');
                    $SecurityGroups = TableRegistry::getTableLocator()->get('Security.SecurityGroups');
                    $SecurityGroupInstitutions = TableRegistry::getTableLocator()->get('Security.SecurityGroupInstitutions');
                    $SecurityGroupInstitutionData = $SecurityGroupInstitutions->find()
                        ->select(["security_group_id" => $SecurityGroups->aliasField('id')])
                        ->innerJoin(
                            [$SecurityGroups->getAlias() => $SecurityGroups->getTable()],
                            [
                                $SecurityGroups->aliasField('id=') . $SecurityGroupInstitutions->aliasField('security_group_id')
                            ]
                        )
                        ->where([$SecurityGroupInstitutions->aliasField('institution_id') => $entity->institution_id])
                        ->first();
                    $entity->security_group_id = $SecurityGroupInstitutionData->security_group_id;
                    //No homeroom teacher
                    if ($entity->homeroom_teacher == 0) {
                        $count = $InstitutionStaff->find()
                            ->where([
                                "institution_id" => $entity->institution_id,
                                "staff_id" => $entity->staff_id,
                                "is_homeroom" => 1,
                                "staff_status_id" => 1,
                                "id !=" => $entity->institution_staff_id

                            ])->count();

                        if ($count == 0) {

                            $securityGroupEntry = $SecurityGroupUsers->find()
                                ->where([
                                    'security_user_id' => $entity->staff_id,
                                    'security_group_id' => $entity->security_group_id,
                                    'security_role_id' => 5
                                ])->first();
                            if (isset($securityGroupEntry)) {
                                $SecurityGroupUsers->delete($securityGroupEntry);
                            }
                        }
                    }
                    // Homeroom Teacher
                    if ($entity->homeroom_teacher == 1) {
                        $id = $SecurityGroupUsers->find()
                            ->where([
                                'security_user_id' => $entity->staff_id,
                                'security_group_id' =>  $entity->security_group_id,
                                'security_role_id' => 5
                            ])->first();
                        if (!isset($id)) {

                            $user = $SecurityGroupUsers->newEntity([]);
                            $user->id = Text::uuid();
                            $user->security_user_id = $entity->staff_id;
                            $user->security_group_id = $entity->security_group_id;
                            $user->created_user_id = $entity->created_user_id;
                            $user->security_role_id = 5;
                            $user->created = $entity->created;
                            $SecurityGroupUsers->save($user);
                        }
                    }
                    //Both case
                    //$query=$InstitutionStaff->getQuery();
                    $query = $InstitutionStaff->find();//POCOR-8447
                    $query->update()
                        ->set(['is_homeroom' => $entity->homeroom_teacher])
                        ->where(['id' => $entity->institution_staff_id])
                        ->execute();
                    $StaffChangeTypesData = $StaffChangeTypes->find()
                                    ->where([$StaffChangeTypes->aliasField('id') => $this->request->getData()['StaffPositionProfiles']['staff_change_type_id']])
                                    ->first();

                    if($StaffChangeTypesData['code'] != 'END_OF_ASSIGNMENT'){
                        $event->stopPropagation();
                    }
                }
                //POCOR-7289 ends
                $institutionId = $this->getQueryString('institution_id');
                $url = $this->url('view');
                $url['action'] = 'Staff';
                $url[1] = $this->paramsEncode(['id' => $entity['institution_staff_id'], 'institution_id' =>$institutionId, 'staff_id' => $entity['staff_id'], 'user_id' => $entity['staff_id']]);
                $this->Alert->success('general.edit.success', ['reset' => true]);
                return $this->controller->redirect($url);
            }
        }
    }
    //POCOR-8447 End

    public function workflowBeforeTransition(EventInterface $event, $requestData)
    {
        $errors = true;
        $approved = $this->Workflow->getStepsByModelCode($this->getRegistryAlias(), 'APPROVED');
        $nextWorkflowStepId = $requestData['WorkflowTransitions']['workflow_step_id'];
        $id = $requestData['WorkflowTransitions']['model_reference'];
        if (in_array($nextWorkflowStepId, $approved)) {
            $data = $this->get($id)->toArray();
            $newEntity = $this->patchStaffProfile($data);

            if (is_null($newEntity)) {
                $message = ['StaffPositionProfiles.notExists'];
                $this->Session->write('Institution.StaffPositionProfiles.errors', $message);
            } else if ($newEntity->getErrors()) {
                $message = [];
                $errors = $newEntity->getErrors();
                foreach ($errors as $key => $value) {
                    $msg = 'Institution.Staff.'.$key;
                    if (is_array($value)) {
                        foreach ($value as $k => $v) {
                            $message[] = $msg.'.'.$k;
                        }
                    }
                }
                $this->Session->write('Institution.StaffPositionProfiles.errors', $message);
            } else {
                $errors = false;
            }

            if ($errors) {
                $event->stopPropagation();
                $url = $this->url('view');
                return $this->controller->redirect($url);
            }
        }
    }

    public function getWorkflowEvents(EventInterface $event, ArrayObject $eventsObject)
    {
        foreach ($this->workflowEvents as $key => $attr) {
            $attr['text'] = __($attr['text']);
            $attr['description'] = __($attr['description']);
            $eventsObject[] = $attr;
        }
    }

    public function onApprove(EventInterface $event, $id, Entity $workflowTransitionEntity)
    {
        $data = $this->get($id)->toArray();

        $newEntity = $this->patchStaffProfile($data);
        // reject all pending transfers

        $staff_change_code = $this->getStaffChangeCode($newEntity);
        if ($staff_change_code == 'CHANGE_IN_FTE') {
            $this->rejectPendingTransfer($data);
        }
        $InstitutionStaff = TableRegistry::getTableLocator()->get('Institution.Staff');
        $InstitutionStaff->save($newEntity);
    }

    private function rejectPendingTransfer(array $data)
    {
        // reject all pending transfers
        $staffId = $data['staff_id'];
        $InstitutionStaffTransfers = TableRegistry::getTableLocator()->get('Institution.InstitutionStaffTransfers');
        $doneStatus = $InstitutionStaffTransfers::DONE;

        $transferRecords = $InstitutionStaffTransfers->find()
            ->matching('Statuses', function ($q) use ($doneStatus) {
                return $q->where(['category <> ' => $doneStatus]);
            })
            ->where([$InstitutionStaffTransfers->aliasField('staff_id') => $staffId])
            ->all();
//        dd($transferRecords);
        if (!empty($transferRecords)) {
            foreach ($transferRecords as $key => $entity) {
                $workflowId = $entity->_matchingData['Statuses']->workflow_id;

                // get closed step based on done category and system defined
                $closedStepEntity = $this->Statuses->find()
                    ->matching('Workflows')
                    ->where([
                        $this->Statuses->aliasField('workflow_id') => $workflowId,
                        $this->Statuses->aliasField('category') => self::DONE,
                        $this->Statuses->aliasField('is_system_defined') => 1
                    ])
                    ->first();

                if (!empty($closedStepEntity)) {
                    $prevStep = $entity->status_id;

                    // update status_id and assignee_id
                    $entity->status_id = $closedStepEntity->id;
                    $InstitutionStaffTransfers->autoAssignAssignee($entity);

                    if ($InstitutionStaffTransfers->save($entity)) {
                        $WorkflowTransitions = TableRegistry::getTableLocator()->get('Workflow.WorkflowTransitions');
                        $prevStepEntity = $this->Statuses->get($prevStep);

                        $transition = [
                            'comment' => __('On approve Staff Change In Assignment'),
                            'prev_workflow_step_name' => $prevStepEntity->name,
                            'workflow_step_name' => $closedStepEntity->name,
                            'workflow_action_name' => 'Administration - Close Record',
                            'workflow_model_id' => $closedStepEntity->_matchingData['Workflows']->workflow_model_id,
                            'model_reference' => $entity->id,
                            'created_user_id' => 1,
                            'created' => new Time('NOW')
                        ];
                        $entity = $WorkflowTransitions->newEntity($transition);
                        $WorkflowTransitions->save($entity);
                    }
                }
            }
        }
    }

    private function patchStaffProfile(array $data)
    {
        $InstitutionStaff = TableRegistry::getTableLocator()->get('Institution.Staff');
        $newEntity = null;

        // Get the latest staff record entry
        $staffRecord = $InstitutionStaff->find()
            ->where([
                $InstitutionStaff->aliasField('id') => $data['institution_staff_id']
            ])
            ->first();

        // If the record exists
        if (!empty($staffRecord)) {

            unset($data['created']);
            unset($data['created_user_id']);
            unset($data['modified']);
            unset($data['modified_user_id']);
            unset($data['id']);
            $newEntity = $InstitutionStaff->patchEntity($staffRecord, $data, ['validate' => "AllowPositionType"]);
        }

        return $newEntity;
    }

    private function getStyling($oldValue, $newValue)
    {
        return '<span class="status past">'.$oldValue.'</span> <span class="transition-arrow"></span> <span class="status highlight">'.$newValue.'</span>';
    }

    public function onGetBreadcrumb(EventInterface $event, ServerRequest $request, Component $Navigation, $persona)
    {
        $url = [];

        if ($this->action != 'index') {
            $url = ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => $this->getAlias()];
        }

        $Navigation->substituteCrumb('Staff Position Profiles', 'Change in Assignment', $url);
    }

    public function onGetFTE(EventInterface $event, Entity $entity)
    {
        if ($this->action == 'view') {
            $oldValue = ($entity->institution_staff->FTE * 100). '%';
            $newValue = '100%';
            if ($entity->FTE < 1) {
                $newValue = ($entity->FTE * 100) . '%';
            }

            if ($newValue != $oldValue) {
                return $this->getStyling($oldValue, $newValue);
            } else {
                return $newValue;
            }
        }
    }

    public function onGetStartDate(EventInterface $event, Entity $entity)
    {
        if ($this->action == 'view') {
            $oldValue = $entity->institution_staff->start_date;
            $newValue = $entity->start_date;
            if ($newValue != $oldValue) {
                return $this->getStyling($this->formatDate($oldValue), $this->formatDate($newValue));
            } else {
                return $newValue;
            }
        }
    }

    public function onGetEndDate(EventInterface $event, Entity $entity)
    {
        //POCOR-6979
        $StaffChangeTypes = TableRegistry::getTableLocator()->get('Staff.StaffChangeTypes');
        $StaffChangeTypesDataForShift = $StaffChangeTypes->find()
                        ->where([$StaffChangeTypes->aliasField('id') => $entity->staff_change_type_id])
                        ->first();
        if ($this->action == 'view') {
            $oldValue = $entity->institution_staff->end_date;
            $newValue = $entity->end_date;
            if ($newValue !== null && $newValue instanceof \DateTimeInterface) {
                if ($newValue->format('Y-m-d H:i:s') === '1969-12-31 00:00:00') {
                    $newValue = '';
                }
            } else {
                $newValue = ''; // or handle null in a different way if needed
            }

            if ($newValue != $oldValue) {
                if (!empty($oldValue) && !empty($newValue)) {
                    // START POCOR-7216
                    return $this->getStyling($this->formatDate($oldValue), $this->formatDate($newValue));
                    // if($StaffChangeTypesDataForShift['code'] == 'CHANGE_OF_START_DATE' || $StaffChangeTypesDataForShift['code'] == 'CHANGE_IN_STAFF_TYPE'){
                    //     return $this->getStyling(__('Not Specified'), __('Not Specified'));
                    // }else{
                    //     return $this->getStyling($this->formatDate($oldValue), $this->formatDate($newValue));
                    // }
                } else if (!empty($newValue)) {
                    return $this->getStyling(__('Not Specified'), $this->formatDate($newValue));
                    // if($StaffChangeTypesDataForShift['code'] == 'CHANGE_OF_START_DATE' || $StaffChangeTypesDataForShift['code'] == 'CHANGE_IN_STAFF_TYPE'){
                    //     return $this->getStyling(__('Not Specified'), __('Not Specified'));
                    // }else{
                    //     return $this->getStyling(__('Not Specified'), $this->formatDate($newValue));
                    // }
                } else if (!empty($oldValue)) {

                    return $this->getStyling($this->formatDate($oldValue), __('Not Specified'));
                    // if($StaffChangeTypesDataForShift['code'] == 'CHANGE_OF_START_DATE' || $StaffChangeTypesDataForShift['code'] == 'CHANGE_IN_STAFF_TYPE'){
                    //     return $this->getStyling(__('Not Specified'), __('Not Specified'));
                    // }else{
                    //     return $this->getStyling($this->formatDate($oldValue), __('Not Specified'));
                    // }
                }
                // END POCOR-7216
            } else {
                if (!empty($newValue)) {
                    // START POCOR-7216

                    // if($StaffChangeTypesDataForShift['code'] == 'CHANGE_OF_START_DATE' || $StaffChangeTypesDataForShift['code'] == 'CHANGE_IN_STAFF_TYPE'){
                    //     return $this->getStyling(__('Not Specified'), __('Not Specified'));
                    // }else{
                    //     return $newValue;
                    // }
                    return $newValue;
                    // END POCOR-7216
                } else {
                    return __('Not Specified');
                }
            }
        }

    }

    public function onGetStaffTypeId(EventInterface $event, Entity $entity)
    {
        if ($this->action == 'view') {
            $oldValue = $entity->institution_staff->staff_type->name;
            $newValue = $entity->staff_type->name;
            if ($newValue != $oldValue) {
                return $this->getStyling(__($oldValue), __($newValue));
            } else {
                return __($newValue);
            }
        }
    }

    public function beforeAction(EventInterface $event, ArrayObject $extra): void
    {
        // Set the header of the page
        $institutionId = $this->getQueryString('institution_id');
        //$this->Institutions = TableRegistry::getTableLocator()->get('Institution.Institutions');
        if ($institutionId) {
            $institutionName = $this->Institutions->get($institutionId)->name;
        }
        $this->controller->set('contentHeader', $institutionName. ' - ' .__('Pending Change in Assignment'));

        $this->field('institution_staff_id', ['visible' => false]);
        $this->field('staff_id', ['before' => 'start_date']);
        $this->field('FTE', ['type' => 'select','visible' => ['view' => true, 'edit' => true, 'add' => true]]);
        $extra['config']['selectedLink'] = ['controller' => 'Institutions', 'action' => 'Staff', 'index'];
    }

    public function indexBeforeAction(EventInterface $event, ArrayObject $extra)
    {
        $this->Session->delete('Institution.StaffPositionProfiles.viewBackUrl');
        if (isset($extra['toolbarButtons']['add'])) {
            unset($extra['toolbarButtons']['add']);
        }
        $institutionId = $this->getQueryString('institution_id');

        $this->fields['staff_id']['order'] = 5;
        $this->fields['institution_position_id']['type'] = 'integer';
        $this->fields['staff_id']['type'] = 'integer';
        $this->fields['start_date']['type'] = 'date';
        $this->fields['institution_position_id']['order'] = 6;
        $this->fields['FTE']['visible'] = false;
        $this->controller->set('ngController', 'AdvancedSearchCtrl');
        $selectedStatus = $this->request->getQuery('staff_status_id');
    }

    public function indexBeforeQuery(EventInterface $event, Query $query, ArrayObject $extra)
    {
        $request = $this->request;
        $query->contain(['Positions']);
        $search = $this->getSearchKey();
        if (!empty($search)) {
            // function from AdvancedNameSearchBehavior
            $query = $this->addSearchConditions($query, ['alias' => 'Users', 'searchTerm' => $search]);
        }
    }

    public function addEditBeforePatch(EventInterface $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra)
    {
        if ($requestData[$this->getAlias()]['staff_change_type_id'] == $this->staffChangeTypesList['CHANGE_IN_FTE']) {
            $patchOptions['validate'] = 'IncludeEffectiveDate';

            $newFTE = $requestData[$this->getAlias()]['FTE'];
            $newEndDate = $requestData[$this->getAlias()]['effective_date'];
            $staffRecordEntity = $this->Session->read('Institution.StaffPositionProfiles.staffRecord');
            $entity->FTE = $staffRecordEntity->FTE;
            $entity->newFTE = $newFTE;
            if (empty($newEndDate)) {
                if ($entity->start_date < date('Y-m-d')) {
                    $requestData[$this->getAlias()]['end_date'] = date('Y-m-d');
                } else {
                    $requestData[$this->getAlias()]['end_date'] = $requestData[$this->getAlias()]['start_date'];
                }
            } else {
                $endDate = (new Date($newEndDate))->modify('-1 day');
                $requestData[$this->getAlias()]['end_date'] = $endDate->format('Y-m-d');
            }
        }

    }

    public function addEditAfterAction(EventInterface $event, Entity $entity, ArrayObject $extra)
    {

        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);
        $institutionId = $this->getQueryString('institution_id');

        $toolbarButtons = $extra['toolbarButtons'];
        $toolbarButtons['back']['url'] = [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'Staff',
            '0' => 'view',
            '1' => $this->paramsEncode(['id' => $entity->institution_staff_id, 'institution_id'=> $institutionId, 'staff_id' => $entity->staff_id,'user_id' => $entity->staff_id])
        ];

        // To investigate
        $this->field('id', ['type' => 'hidden', 'value' => $entity->id]);
        $this->field('status_id', ['type' => 'hidden']);
        $this->field('institution_staff_id', ['visible' => true, 'type' => 'hidden', 'value' => $entity->institution_staff_id]);
        $this->field('institution_id', ['type' => 'readonly', 'attr' => ['value' => $this->Institutions->get($entity->institution_id)->name]]);
        $this->field('staff_id', ['type' => 'readonly', 'attr' => ['value' => $this->Users->get($entity->staff_id)->name_with_id]]);
        $this->field('start_date', ['type' => 'readonly', 'entity' => $entity]);
        $this->field('staff_change_type_id');
        $this->field('staff_type_id', ['type' => 'select']);
        $this->field('current_staff_type', ['before' => 'staff_type_id']);
        $fteOptions = ['0.25' => '25%', '0.5' => '50%', '0.75' => '75%', '1' => '100%'];
        $this->field('FTE', ['type' => 'select', 'options' => $fteOptions, 'value' => $entity->FTE]);
        $this->field('institution_position_id', ['after' => 'staff_id', 'type' => 'readonly', 'attr' => ['value' => $this->Positions->get($this->getEntityProperty($entity, 'institution_position_id'))->name]]);
        $this->field('current_FTE', ['before' => 'FTE', 'type' => 'disabled', 'options' => $fteOptions]);
        $this->field('effective_date');
        $this->field('end_date');
        $this->field('current_shift');//POCOR-6928
        $this->field('new_shift');//POCOR-6928
        $this->field('current_shift_one');
         $this->field('current_FTE', ['before' => 'FTE', 'type' => 'disabled', 'options' => $fteOptions]);
        $homeroomOptions = [  '1'=>'Homeroom Teacher', '0'=>'Not Homeroom Teacher' ];//POCOR 7289
        $this->field('homeroom_teacher',['type' => 'select', 'options' => $homeroomOptions,'value'=>$entity->is_homeroom]);//POCOR-7289
        $this->field('current_homeroom_teacher', ['before'=>'homeroom_teacher','type'=>'disabled','options'=>$homeroomOptions]);//POCOR-7289
    }

    public function onUpdateFieldStaffChangeTypeId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $attr['type'] = 'select';
        $attr['onChangeReload'] = true;
//      POCOR-8853 start made it once changeable to avoid trash data
        $data = $request->getData($this->getAlias());
        if(!isset($data)) {
            $attr['value'] = 0;
            $attr['attr']['value'] = 0;
        }
        //      POCOR-8853 end
        return $attr;
    }

    public function onUpdateFieldCurrentStaffType(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $requestData = $request->getData();
            $staffChangeTypes = $this->staffChangeTypesList;
            if($requestData[$this->getAlias()]['staff_change_type_id'] == ''){
                $attr['visible'] = false;
            }else if ($requestData[$this->getAlias()]['staff_change_type_id'] == $staffChangeTypes['CHANGE_IN_STAFF_TYPE'] || $requestData[$this->getAlias()]['staff_change_type_id'] == 3) {
                $attr['visible'] = true;
                $attr['type'] = 'disabled';
                if ($this->Session->check('Institution.StaffPositionProfiles.staffRecord')) {
                    $entity = $this->Session->read('Institution.StaffPositionProfiles.staffRecord');
                    $attr['attr']['value'] = $this->StaffTypes->get($entity->staff_type_id)->name;
                }
            } else {
                $attr['visible'] = false;
            }
        }
        return $attr;
    }

    public function onUpdateFieldStaffTypeId(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $requestData = $request->getData();
            $staffChangeTypes = $this->staffChangeTypesList;
            if($requestData[$this->getAlias()]['staff_change_type_id'] == ''){
                $attr['visible'] = false;
            }else if ($requestData[$this->getAlias()]['staff_change_type_id'] == $staffChangeTypes['CHANGE_IN_STAFF_TYPE'] || $requestData[$this->getAlias()]['staff_change_type_id'] == 3) {
                $attr['type'] = 'select';
                $options = $this->StaffTypes->getList()->toArray();
                if ($this->Session->check('Institution.StaffPositionProfiles.staffRecord')) {
                    $entity = $this->Session->read('Institution.StaffPositionProfiles.staffRecord');
                    if (isset($options[$entity->staff_type_id])) {
                        unset($options[$entity->staff_type_id]);
                    }
                }
                $attr['options'] = $options;
            } else {
                $attr['type'] = 'hidden';
                if ($this->Session->check('Institution.StaffPositionProfiles.staffRecord')) {
                    $entity = $this->Session->read('Institution.StaffPositionProfiles.staffRecord');
                    $attr['value'] = $entity->staff_type_id;
                }
            }
        }
        return $attr;
    }

    public function onUpdateFieldCurrentFTE(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        // POCOR-8553 cleaned some code
        $data = $request->getData($this->getAlias());
//        if(!isset($data)) {
//            $attr['visible'] = false;
//        }
        if ($action == 'add' || $action == 'edit') {
            $staffChangeTypes = $this->staffChangeTypesList;
            if (isset($data)) {
                if($data['staff_change_type_id'] == ''){
                    $attr['visible'] = false;
                }
                // else if ($requestData[$this->getAlias()]['staff_change_type_id'] == $staffChangeTypes['CHANGE_IN_FTE']) {
                else if ($data['staff_change_type_id'] != '' && $data['staff_change_type_id'] == 2) {
                    $attr['visible'] = true;
                    if ($this->Session->check('Institution.StaffPositionProfiles.staffRecord')) {
                        $entity = $this->Session->read('Institution.StaffPositionProfiles.staffRecord');
                        $options = $attr['options'];
                        $fteString = strval($entity->FTE);
                        $fteval = rtrim($fteString, '.0');
                        $attr['attr']['value'] = $options[$fteval];
                    }
                } else {
                    $attr['visible'] = false;
                }
            }
        }
        return $attr;
    }

    public function onUpdateFieldFTE(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        // POCOR-8853 cleaned some code
        $data = $request->getData($this->getAlias());
//        if(!isset($data)) {
//            $attr['visible'] = false;
//        }

        if ($action == 'add' || $action == 'edit') {
            $staffChangeTypes = $this->staffChangeTypesList;
            if (isset($data)) {
                if($data['staff_change_type_id'] == ''){
                    $attr['visible'] = false;
                }
                else if ($data['staff_change_type_id'] == $staffChangeTypes['CHANGE_IN_FTE'] || $data['staff_change_type_id'] == 2) {
                    $attr['type'] = 'select';
                    if (isset($attr['options'])) {
                        $options = $attr['options'];
                        if ($this->Session->check('Institution.StaffPositionProfiles.staffRecord')) {
                            $entity = $this->Session->read('Institution.StaffPositionProfiles.staffRecord');
                            $fteval = $entity->FTE == '1.00' ? 1 : $entity->FTE;
                            if (isset($options[$fteval])) {
                                unset($options[$fteval]);
                            }
                        }
                        $attr['options'] = $options;
                    }
                } else {
                    $attr['type'] = 'hidden';
                    if ($this->Session->check('Institution.StaffPositionProfiles.staffRecord')) {
                        $entity = $this->Session->read('Institution.StaffPositionProfiles.staffRecord');
                        $attr['value'] = $entity->FTE;
                    }
                }
            }
        }
        return $attr;
    }

    public function onUpdateFieldEffectiveDate(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $requestData = $request->getData();
            $staffChangeTypes = $this->staffChangeTypesList;
            if($requestData[$this->getAlias()]['staff_change_type_id'] == ''){
                $attr['visible'] = false;
            }
            else if ($requestData[$this->getAlias()]['staff_change_type_id'] == $staffChangeTypes['CHANGE_IN_FTE'] || $requestData[$this->getAlias()]['staff_change_type_id'] == 2) {
                $attr['type'] = 'date';
                if ($this->Session->check('Institution.StaffPositionProfiles.staffRecord')) {
                    $entity = $this->Session->read('Institution.StaffPositionProfiles.staffRecord');
                    $startDateClone = clone ($entity->start_date);
                    $startDate = $startDateClone->modify('+1 day');
                    $attr['date_options']['startDate'] = $startDate->format('d-m-Y');
                }
                $attr['value'] = (new Date())->modify('+1 day');
            } else {
                $attr['type'] = 'hidden';
            }
        }
        return $attr;
    }

    public function onUpdateFieldStartDate(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        // $entity = $attr['entity'];

        // // start_date
        // if (!$entity->has('start_date')) {
        //     $requestData = $this->request->data;
        //     $startDate = new Date($requestData[$this->alias()]['start_date']);
        // } else {
        //     $startDate = $entity->start_date;
        // }

        // $staffChangeTypes = $this->staffChangeTypesList;
        // // echo "<pre>";print_r($request->data);die;
        // if($request->data[$this->alias()]['staff_change_type_id'] == ''){
        //     $attr['visible'] = false;
        // }
        // else if($request->data[$this->alias()]['staff_change_type_id'] == '' || ($request->data[$this->alias()]['staff_change_type_id'] == 1 || $request->data[$this->alias()]['staff_change_type_id'] == 2)){
        //     $attr['type'] = 'date';
        //     $attr['value'] = $startDate->format('Y-m-d');
        // }else if ($request->data[$this->alias()]['staff_change_type_id'] == $staffChangeTypes['CHANGE_OF_START_DATE']) {
        //     $attr['type'] = 'date';
        //     $attr['value'] = $startDate->format('Y-m-d');
        // } else {
        //     $attr['value'] = $startDate->format('Y-m-d');
        //     $attr['attr']['value'] = $this->formatDate($startDate);
        // }

        // return $attr;
        $entity = $attr['entity'];
        // start_date
        if (!$entity->has('start_date')) {
            $requestData = $this->request->getData();
            $startDate = new Date($requestData[$this->getAlias()]['start_date']);
        } else {
            $startDate = $entity->start_date;
        }
        $requestDataNew = $request->getData();
        $staffChangeTypes = $this->staffChangeTypesList;
        if($requestDataNew[$this->getAlias()]['staff_change_type_id'] == ''){
            $attr['visible'] = false;
        }
        else if($requestDataNew[$this->getAlias()]['staff_change_type_id'] == '' || ($requestDataNew[$this->getAlias()]['staff_change_type_id'] == 4)){
            $attr['type'] = 'date';
            $attr['value'] = $startDate->format('Y-m-d');
            $attr['attr']['value'] = $this->formatDate($startDate);
        } else {
            $getStaffStartData = TableRegistry::getTableLocator()->get('Institution.InstitutionStaff');
            $getStaffStartDateData = $getStaffStartData->find()
            ->where([
                $getStaffStartData->aliasField('staff_id') => $entity->staff_id,
                $getStaffStartData->aliasField('institution_id') => $entity->institution_id,
                $getStaffStartData->aliasField('id') => $entity->institution_staff_id
            ])
            ->order([$getStaffStartData->aliasField('start_date') => 'DESC'])
            ->first();
            $startDate = $getStaffStartDateData->start_date;

            $attr['value'] = !empty($startDate) ? $startDate->format('Y-m-d') : null; //POCOR-9421
            $attr['attr']['value'] = $this->formatDate($startDate);
            $attr['type'] = 'hidden';
        }

        return $attr;
    }

    public function onUpdateFieldEndDate(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $requestData = $request->getData();
            $staffChangeTypes = $this->staffChangeTypesList;
            if($requestData[$this->getAlias()]['staff_change_type_id'] == ''){
                $attr['visible'] = false;
            }
            // else if ($requestData[$this->alias()]['staff_change_type_id'] == $staffChangeTypes['END_OF_ASSIGNMENT']) {
            else if ($requestData[$this->getAlias()]['staff_change_type_id'] == 1) {
                $attr['type'] = 'date';
                if ($this->Session->check('Institution.StaffPositionProfiles.staffRecord')) {
                    $entity = $this->Session->read('Institution.StaffPositionProfiles.staffRecord');

                    $InstitutionSubjectStaff = TableRegistry::getTableLocator()->get('Institution.InstitutionSubjectStaff');
                    $latestSubjectStartDate = $InstitutionSubjectStaff->find()
                        ->where([
                            $InstitutionSubjectStaff->aliasField('staff_id') => $entity->staff_id,
                            $InstitutionSubjectStaff->aliasField('institution_id') => $entity->institution_id,
                            $InstitutionSubjectStaff->aliasField('start_date') . ' IS NOT NULL'
                        ])
                        ->order([$InstitutionSubjectStaff->aliasField('start_date') => 'DESC'])
                        ->first();

                    if (!empty($latestSubjectStartDate)) {
                        // restrict earliest end of assignment date to the day after latest subject start date
                        // $earliestEndDate = $latestSubjectStartDate->start_date->modify('+1 day');
                        $earliestEndDate = $entity->start_date->modify('+1 day'); //POCOR-6636 There should be a validation where the end date cannot be earlier than start date
                    } else {
                        $earliestEndDate = $entity->start_date;
                    }

                    $attr['date_options']['startDate'] = $earliestEndDate->format('d-m-Y');
                    if(isset($entity->end_date)){
                        $attr['value'] = $entity->end_date;
                    }
                }
            } else {
                $attr['type'] = 'hidden';
                if ($this->Session->check('Institution.StaffPositionProfiles.staffRecord')) {
                    $entity = $this->Session->read('Institution.StaffPositionProfiles.staffRecord');
                    if (!empty($entity->end_date)) {
                        $attr['value'] = $entity->end_date->format('Y-m-d');
                    } else {
                        $attr['value'] = '';
                    }
                }
            }
        }
        return $attr;
    }

    public function viewBeforeAction(EventInterface $event, $extra)
    {
        $queryString = $this->getQueryString();
        $institutionId = $queryString['institution_id'];
        $encodedQueryString = $this->paramsEncode($queryString);
        if (isset($extra['toolbarButtons']['back'])) {
            $url = $this->url('view');
            $url['action'] = 'Staff';
            $url[0] = 'view';
            //$url[1] = $encodedQueryString;
            $url[1] = $this->paramsEncode(['institution_id' => $institutionId,'id' => $queryString['institution_staff_id']]); // POCOR-8853
            $url[2] = $this->paramsEncode(['id' => $queryString['institution_staff_id']]); // POCOR-8853
            unset($url[2]);
           // echo "<pre>"; print_r($url); die;
            $extra['toolbarButtons']['back']['url'] = $url;
        }

        if ($this->Session->check('Institution.StaffPositionProfiles.errors')) {
            $errors = $this->Session->read('Institution.StaffPositionProfiles.errors');
            $this->Alert->error('StaffPositionProfiles.errorApproval');
            foreach ($errors as $error) {
                $this->Alert->error($error);
            }
            $this->Session->delete('Institution.StaffPositionProfiles.errors');
        }
        $url = $this->url('view');
        $url['action'] = 'StaffPositionProfiles';
        $url[0] = $encodedQueryString;
        $url[1] = $this->paramsEncode(['id' => $queryString['institution_staff_id']]); // POCOR-8853
       // return $this->controller->redirect($url);

    }
    public function viewAfterAction(EventInterface $event, Entity $entity, $extra)
    {
        $StaffTable = TableRegistry::getTableLocator()->get('Institution.Staff');
        $staffEntity = $StaffTable->find()
            ->contain(['StaffTypes'])
            ->where([$StaffTable->aliasField('id') => $entity->institution_staff_id])
            ->first();
        $entity->institution_staff = $staffEntity;
        $queryString = $this->getQueryString();
        $institutionId = $queryString['institution_id'];
        if (isset($extra['toolbarButtons']['back'])) {
            $url = $this->url('view');
            $url['action'] = 'Staff';
            $url[0] = 'view';
            $url[1] = $this->paramsEncode(['institution_id' => $institutionId,'id' => $entity['institution_staff_id'], 'staff_id' => $entity['staff_id'],'user_id' => $entity['staff_id']]);
            $url[2] = $this->paramsEncode(['id' => $entity['institution_staff_id']]);
            unset($url[2]);
            $extra['toolbarButtons']['back']['url'] = $url;
        }
    }

    private function initialiseVariable($entity)
    {
        $institutionStaffId = $this->request->getQuery('institution_staff_id');

        $institutionStaffId = $this->paramsDecode($institutionStaffId)['id'];

        if (is_null($institutionStaffId)) {
            return true;
        }
        $InstitutionStaff = TableRegistry::getTableLocator()->get('Institution.Staff');

        $staff = $InstitutionStaff->get($institutionStaffId);

        $approvedStatus = $this->Workflow->getStepsByModelCode($this->getRegistryAlias(), 'APPROVED');
        $closedStatus = $this->Workflow->getStepsByModelCode($this->getRegistryAlias(), 'CLOSED');

        $statuses = array_merge($approvedStatus, $closedStatus);

        $staffPositionProfilesRecord = $this->find()
            ->where([
                $this->aliasField('institution_staff_id') => $staff->id,
                $this->aliasField('status_id').' NOT IN ' => $statuses
            ])
            ->first();
        if (empty($staffPositionProfilesRecord)) {
            $entity->institution_staff_id = $staff->id;
            $entity->staff_id = $staff->staff_id;
            $entity->institution_position_id = $staff->institution_position_id;
            $entity->institution_id = $staff->institution_id;
            $entity->start_date = $staff->start_date;
            $entity->end_date = $staff->end_date;
            $entity->staff_type_id = $staff->staff_type_id;
            $entity->FTE = $staff->FTE;
            $this->Session->write('Institution.StaffPositionProfiles.staffRecord', $staff);
            $requestData = $this->request->getData();
            $requestData['staff_status_id'] = $entity->staff_id;
            $requestData['institution_position_id'] = $entity->institution_position_id;
            $requestData['institution_id'] = $entity->institution_id;
            $requestData['staff_change_type_id'] = $this->staffChangeTypesList;
            //$this->request = $this->request->withData($this->getAlias(), $requestData);
            // Ensure staff_change_type_id is set correctly
        if (!isset($requestData['staff_change_type_id'])) {
            $requestData['staff_change_type_id'] = ''; // Set a default value if not present
        }

        // Use the entity's set method to assign the request data
        $entity->set($requestData);
            return false;
        } else {
            return $staffPositionProfilesRecord;
        }
    }

    public function editOnInitialize(EventInterface $event, Entity $entity)
    {
        $staffEntity = TableRegistry::getTableLocator()->get('Institution.Staff')->get($entity->institution_staff_id);
        $this->Session->write('Institution.StaffPositionProfiles.staffRecord', $staffEntity);
        //$data = $this->request->getData();
        //$data['staff_change_type_id'] = $entity->staff_change_type_id;
        //$this->request = $this->request->withData($this->getAlias(),$data);
        //$this->request->data[$this->alias()]['staff_change_type_id'] = $entity->staff_change_type_id;
        $this->request = $this->request->withData($this->getAlias(). '.staff_change_type_id',$entity->staff_change_type_id);
    }

    public function addOnInitialize(EventInterface $event, Entity $entity)
    {

        $queryString = $this->getQueryString();
        $institutionId = $this->getQueryString('institution_id');
        $institution_staff_id = $queryString['id'];
        $encodedQueryString = $this->paramsEncode($queryString);
        $addOperation = $this->initialiseVariable($entity);
        if ($addOperation) {
            $institutionStaffId = $this->request->getQuery('institution_staff_id');
            if (is_null($institutionStaffId)) {
                $url = $this->url('index');
            } else {
                $staffTableViewUrl = $this->url('view');
                $staffTableViewUrl['action'] = 'StaffPositionProfiles';
                $staffTableViewUrl[0] = 'view';
                $staffTableViewUrl[1] = $encodedQueryString;
                $this->Session->write('Institution.StaffPositionProfiles.viewBackUrl', $staffTableViewUrl);
                $url = $this->url('view');
                $url[0] = 'view';
                $url[1] = $this->paramsEncode(['institution_id' => $institutionId,'id' => $addOperation->id,'institution_staff_id' => $institution_staff_id]);
            }
            $event->stopPropagation();
            return $this->controller->redirect($url);
        }
    }


    public function findWorkbench(Query $query, array $options)
    {
        $controller = $options['_controller'];
        $session = $controller->getRequest()->getSession();

        $userId = $session->read('Auth.User.id');
        $Statuses = $this->Statuses;
        $doneStatus = self::DONE;

        $query
            ->select([
                $this->aliasField('id'),
                $this->aliasField('status_id'),
                $this->aliasField('institution_id'),
                $this->aliasField('modified'),
                $this->aliasField('created'),
                $this->Statuses->aliasField('name'),
                $this->Users->aliasField('openemis_no'),
                $this->Users->aliasField('first_name'),
                $this->Users->aliasField('middle_name'),
                $this->Users->aliasField('third_name'),
                $this->Users->aliasField('last_name'),
                $this->Users->aliasField('preferred_name'),
                $this->Institutions->aliasField('code'),
                $this->Institutions->aliasField('name'),
                $this->CreatedUser->aliasField('openemis_no'),
                $this->CreatedUser->aliasField('first_name'),
                $this->CreatedUser->aliasField('middle_name'),
                $this->CreatedUser->aliasField('third_name'),
                $this->CreatedUser->aliasField('last_name'),
                $this->CreatedUser->aliasField('preferred_name')
            ])
            ->contain([$this->Users->getAlias(), $this->Institutions->getAlias(), $this->CreatedUser->getAlias(),'Assignees'])
            ->matching($this->Statuses->getAlias(), function ($q) use ($Statuses, $doneStatus) {
                return $q->where([$Statuses->aliasField('category <> ') => $doneStatus]);
            })
            ->where([$this->aliasField('assignee_id') => $userId,
                'Assignees.super_admin IS NOT' => 1]) //POCOR-7102
            ->order([$this->aliasField('created') => 'DESC'])
            ->formatResults(function (ResultSetInterface $results) {
                return $results->map(function ($row) {
                    $url = [
                        'plugin' => 'Institution',
                        'controller' => 'Institutions',
                        'action' => 'StaffPositionProfiles',
                        'view',
                        $this->paramsEncode(['id' => $row->id, 'institution_id' => $row->institution_id]),
                        'institution_id' => $row->institution_id
                    ];

                    if (is_null($row->modified)) {
                        $receivedDate = $this->formatDate($row->created);
                    } else {
                        $receivedDate = $this->formatDate($row->modified);
                    }

                    $row['url'] = $url;
                    $row['status'] = __($row->_matchingData['Statuses']->name);
                    $row['request_title'] = $row->user->name_with_id;
                    $row['institution'] = $row->institution->code_name;
                    $row['received_date'] = $receivedDate;
                    $row['requester'] = $row->created_user->name_with_id;

                    return $row;
                });
            });

        return $query;
    }

    /**
    * function will get current shifts data of selected staff
    * @author Poonam Kharka <poonam.kharka@mail.valuecoders.com>
    * @return string
    * @ticket POCOR-6928 starts
    */
    public function onUpdateFieldCurrentShift(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $InstitutionStaffShifts = TableRegistry::getTableLocator()->get('Institution.InstitutionStaffShifts');
        $InstitutionShifts = TableRegistry::getTableLocator()->get('Institution.InstitutionShifts');
        $ShiftOptions = TableRegistry::getTableLocator()->get('Institution.ShiftOptions');
        $shifts = [];
        $requestData = $request->getData();
        if ($action == 'add' || $action == 'edit') {
            $staffChangeTypes = $this->staffChangeTypesList;
            if($requestData[$this->getAlias()]['staff_change_type_id'] == ''){
                $attr['visible'] = false;
            }else if ($requestData[$this->getAlias()]['staff_change_type_id'] == $staffChangeTypes['CHANGE_OF_SHIFT'] || $requestData[$this->getAlias()]['staff_change_type_id'] == 5) {
                $attr['visible'] = true;
                $attr['type'] = 'readOnly';
                if ($this->Session->check('Institution.StaffPositionProfiles.staffRecord')) {
                    $entity = $this->Session->read('Institution.StaffPositionProfiles.staffRecord');
                    $InstitutionStaff = TableRegistry::getTableLocator()->get('Institution.InstitutionStaff');
                    $InstitutionPositions = TableRegistry::getTableLocator()->get('Institution.InstitutionPositions');
                    $staffId = $entity->staff_id;
                    $staffShifts  = $InstitutionStaff
                            ->find()
                            ->select(['shift_name' =>  $ShiftOptions->aliasField('name')])
                            ->leftJoin([$InstitutionPositions->getAlias() => $InstitutionPositions->getTable()],[
                                    $InstitutionPositions->aliasField('id = ') . $InstitutionStaff->aliasField('institution_position_id')
                            ])
                            ->leftJoin([$ShiftOptions->getAlias() => $ShiftOptions->getTable()],[
                                $ShiftOptions->aliasField('id = ') . $InstitutionPositions->aliasField('shift_id')
                            ])
                            ->where([$InstitutionStaff->aliasField('staff_id') => $entity->staff_id,
                                    $InstitutionPositions->aliasField('id') => $entity->institution_position_id])
                            ->first();
                    $shifts = '';
                    if (!empty($staffShifts)) {
                        $shifts= $staffShifts->shift_name;
                    }
                   // $allShift = implode(",",$shifts);
                    $attr['attr']['value'] = $shifts;
                }
            } else {
                $attr['visible'] = false;
            }
        }
        return $attr;
    }

    /**
    * function will get list of all shifts data
    * @author Poonam Kharka <poonam.kharka@mail.valuecoders.com>
    * @return string
    * @ticket POCOR-6928 starts
    */
    public function onUpdateFieldNewShift(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        //POCOR-7109 start
        $requestData = $request->getData();
        $institutionId = $requestData[$this->getAlias()]['institution_id'];
        if($institutionId == ''){
            $institutionId = $this->getQueryString('institution_id');
        }
        $AcademicPeriods = TableRegistry::getTableLocator()->get('AcademicPeriod.AcademicPeriods');
        $periodId = $AcademicPeriods->getCurrent();
        $InstitutionShifts = TableRegistry::getTableLocator()->get('Institution.InstitutionShifts');
        $ShiftOptions = TableRegistry::getTableLocator()->get('Institution.ShiftOptions');
        $optionList = $ShiftOptions->find('list')
                        ->leftJoin([$InstitutionShifts->getAlias() => $InstitutionShifts->getTable()],
                        [$InstitutionShifts->aliasField('shift_option_id = ') . $ShiftOptions->aliasField('id')])
                        ->where([
                            $InstitutionShifts->aliasField('institution_id')=>$institutionId,
                            $InstitutionShifts->aliasField('academic_period_id')=>$periodId])
                        ->toArray();
        //POCOR-7109 end
        if ($action == 'add' || $action == 'edit') {
            $staffChangeTypes = $this->staffChangeTypesList;
            if($requestData[$this->getAlias()]['staff_change_type_id'] == ''){
                $attr['visible'] = false;
            }else if ($requestData[$this->getAlias()]['staff_change_type_id'] == $staffChangeTypes['CHANGE_OF_SHIFT'] || $requestData[$this->getAlias()]['staff_change_type_id'] == 5) {
                $attr['type'] = 'chosenSelect';
                $attr['attr']['multiple'] = false;
                //$attr['select'] = false;
                $attr['options'] = ['id' => '' . __('Select Shift') . ' --']+$optionList;//POCOR-7109
                $attr['onChangeReload'] = 'changeStatus';
            } else {
                $attr['type'] = 'hidden';
                if ($this->Session->check('Institution.StaffPositionProfiles.staffRecord')) {
                    $entity = $this->Session->read('Institution.StaffPositionProfiles.staffRecord');
                    $attr['value'] = $entity->staff_type_id;
                }
            }
        }
        return $attr;
    }
    /** POCOR-6928 ends*/

    public function onUpdateFieldCurrentShiftOne(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        $InstitutionStaffShifts = TableRegistry::getTableLocator()->get('Institution.InstitutionStaffShifts');
        $InstitutionShifts = TableRegistry::getTableLocator()->get('Institution.InstitutionShifts');
        $ShiftOptions = TableRegistry::getTableLocator()->get('Institution.ShiftOptions');
        $shifts = [];
        $requestData = $request->getData();
        if ($action == 'add' || $action == 'edit') {
            $staffChangeTypes = $this->staffChangeTypesList;
            if($requestData[$this->getAlias()]['staff_change_type_id'] == ''){
                $attr['visible'] = false;
            }else if ($requestData[$this->getAlias()]['staff_change_type_id'] == $staffChangeTypes['CHANGE_OF_SHIFT'] || $requestData[$this->getAlias()]['staff_change_type_id'] == 5) {
                $attr['visible'] = true;
                $attr['type'] = 'hidden';
                if ($this->Session->check('Institution.StaffPositionProfiles.staffRecord')) {
                    $entity = $this->Session->read('Institution.StaffPositionProfiles.staffRecord');
                    $staffShifts  = $InstitutionStaffShifts
                            ->find()
                            ->select(['shift_name' =>  $ShiftOptions->aliasField('name')])
                            ->leftJoin([$InstitutionShifts->getAlias() => $InstitutionShifts->getTable()],[
                                    $InstitutionShifts->aliasField('id = ') . $InstitutionStaffShifts->aliasField('shift_id')
                            ])
                            ->leftJoin([$ShiftOptions->getAlias() => $ShiftOptions->getTable()],[
                                $ShiftOptions->aliasField('id = ') . $InstitutionShifts->aliasField('shift_option_id')
                            ])
                            ->where([$InstitutionStaffShifts->aliasField('staff_id') => $entity->staff_id])
                            ->toArray();
                    if (!empty($staffShifts)) {
                        foreach ($staffShifts as $shift) {
                            $shifts[] = $shift->shift_name;
                        }
                    }
                    $allShift = implode(",",$shifts);
                    $attr['attr']['value'] = $allShift;
                }
            } else {
                $attr['visible'] = false;
            }
        }
        return $attr;
    }

    public function addBeforeSave(EventInterface $event, Entity $entity, ArrayObject $requestData, ArrayObject $options)
    {
        $staff_change_type_code = $this->getStaffChangeCodeFromRequest();
        if(($staff_change_type_code == 'HOMEROOM_TEACHER')){
            $entity->assignee_id = -1;
            return $entity->assignee_id;
        }
    }

     //Pocor 7289 homeroom teachers option start
     public function onUpdateFieldCurrentHomeroomTeacher(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $staffChangeTypes = $this->staffChangeTypesList;
            $requestData = $request->getData();
            // POCOR-8853 start
            $alias = $this->getAlias();
            $data = $requestData[$alias];
            $attr['visible'] = false;
            if (isset($data)) {
                if ($data['staff_change_type_id'] == $staffChangeTypes['HOMEROOM_TEACHER']) {
                    $attr['visible'] = true;
                    $attr['enabled'] = false;
                    if ($this->Session->check('Institution.StaffPositionProfiles.staffRecord')) {
                        $entity = $this->Session->read('Institution.StaffPositionProfiles.staffRecord');
                        $options = $attr['options'];
                        $entity->is_homeroom = $entity->is_homeroom ?? 0; // POCOR-7753
                        $attr['attr']['value'] = $options[$entity->is_homeroom];
                       }
                }

            }

            // POCOR-8853 end
        }
        return $attr;
    }

    public function onUpdateFieldHomeroomTeacher(EventInterface $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $staffChangeTypes = $this->staffChangeTypesList;
            $requestData = $request->getData();
            // POCOR-8853 start
            $alias = $this->getAlias();
            $data = $requestData[$alias];
            $attr['visible'] = false;
            $attr['type'] = 'hidden';
            if (isset($data)) {
                if ($data['staff_change_type_id'] == $staffChangeTypes['HOMEROOM_TEACHER']) {
                    $attr['type'] = 'select';
                    if (isset($attr['options'])) {
                        $options = $attr['options'];
                        $attr['visible'] = true;
                        if ($this->Session->check('Institution.StaffPositionProfiles.staffRecord')) {
                            $entity = $this->Session->read('Institution.StaffPositionProfiles.staffRecord');
                            unset($options[$entity->is_homeroom]);
                        }
                        $attr['select'] = false;
                        // POCOR-8853 end
                        $attr['options'] = $options;
                    }
                } else {
                    $attr['type'] = 'hidden';
                    if ($this->Session->check('Institution.StaffPositionProfiles.staffRecord')) {
                        $entity = $this->Session->read('Institution.StaffPositionProfiles.staffRecord');
                        $attr['value'] = $entity->is_homeroom;
                    }
                }
            }
        }
        return $attr;
    }

// POCOR-8853 start
    /**
     * @return false|mixed
     */
    private function getApprovedStatus(): mixed
    {
        $approved_status = $this->Workflow->getStepsByModelCode($this->getRegistryAlias(), 'APPROVED');
        if (is_array($approved_status)) {
            $approved_status = reset($approved_status);
        }
        return $approved_status;
    }

    private function getStaffChangeCode(Entity $entity): string
    {
        $staffChangeTypesList = $this->staffChangeTypesList;
        $staff_change_type_id = $entity->staff_change_type_id;
        $code = array_search($staff_change_type_id, $staffChangeTypesList);
        return $code; // POCOR-8853
    }
    private function getStaffChangeCodeFromRequest(): string
    {
        $staffChangeTypesList = $this->staffChangeTypesList;
        $requestData = $this->request->getData();
        $alias = $this->getAlias();
        $data = $requestData[$alias];
        $staff_change_type_id = $data['staff_change_type_id'];
        $code = array_search($staff_change_type_id, $staffChangeTypesList);
        return $code; // POCOR-8853
    }

    /**
     * @param string $requestedStaffChangeCode
     * @param Entity $entity
     * @param Table $SecurityGroupInstitutions
     * @param Table $InstitutionStaff
     * @param Table $SecurityGroupUsers
     * @param mixed $approved_status
     * @return void
     */
    private function setHomeroomTeacher(Entity $entity): void
    {
        $InstitutionStaff = TableRegistry::getTableLocator()->get('Institution.Staff');
        $SecurityGroupUsers = TableRegistry::getTableLocator()->get('Security.SecurityGroupUsers');

        $institution_id = $entity->institution_id;
        $security_group_id = $this->getInstitutionSecurityGroupId($institution_id);

        $staff_id = $entity->staff_id;
        $institution_staff_id = $entity->institution_staff_id;
        $homeroom_teacher_security_role_id = self::getHomeroomTeacherSecurityRoleId();
        $homeroom_teacher = $entity->homeroom_teacher;
        if ($homeroom_teacher == 0) {
            // CHECK THAT THE CURRENT STAFF DOES NOT HAVE ANOTHER HOMEROOM ASSIGNED POSITIONS
            $anotherHomeroomPositionsCount = $InstitutionStaff->find()
                ->where([
                    "institution_id" => $institution_id,
                    "staff_id" => $staff_id,
                    "is_homeroom" => 1,
                    "staff_status_id" => 1,
                    "id !=" => $institution_staff_id
                ])->count();
//            dd($anotherHomeroomPositionsCount);
            if ($anotherHomeroomPositionsCount == 0) {
                $securityGroupEntry = $SecurityGroupUsers->find()
                    ->where([
                        'security_user_id' => $staff_id,
                        'security_group_id' => $security_group_id,
                        'security_role_id' => $homeroom_teacher_security_role_id
                    ])->first();
                if (isset($securityGroupEntry)) {
                    $SecurityGroupUsers->delete($securityGroupEntry);
                }
            }
        }

        if ($homeroom_teacher == 1) {
            $homeroom_teacher_institution_security_group = $SecurityGroupUsers->find()
                ->where([
                    'security_user_id' => $staff_id,
                    'security_group_id' => $security_group_id,
                    'security_role_id' => $homeroom_teacher_security_role_id
                ])->first();
            if (!isset($homeroom_teacher_institution_security_group)) {
                $homeroom_teacher_institution_security_group = $SecurityGroupUsers->newEntity([]);
                $homeroom_teacher_institution_security_group->id = Text::uuid();
                $homeroom_teacher_institution_security_group->security_user_id = $staff_id;
                $homeroom_teacher_institution_security_group->security_group_id = $security_group_id;
                $homeroom_teacher_institution_security_group->created_user_id = $entity->created_user_id;
                $homeroom_teacher_institution_security_group->security_role_id = $homeroom_teacher_security_role_id;
                $homeroom_teacher_institution_security_group->created = $entity->created;
                $SecurityGroupUsers->save($homeroom_teacher_institution_security_group);
            }
        }

        $InstitutionStaff->query()
            ->update()
            ->set(['is_homeroom' => $homeroom_teacher])
            ->where(['id' => $institution_staff_id])
            ->execute();
    }

    private
    static function getInstitutionSecurityGroupId($institutionId)
    {
        $institutionTbl = self::getDynamicTableInstance('institutions');
        $security_group_id = null;
        $institutions = $institutionTbl->find()
            ->where([
                $institutionTbl->aliasField('id') => $institutionId
            ])->first();
        if (!empty($institutions)) {
            $security_group_id = $institutions->security_group_id;
        }
        if ($security_group_id != null) {
            $securityGroupInstitutionsTbl = self::getDynamicTableInstance('security_group_institutions');
            $securityGroupInstitutions = $securityGroupInstitutionsTbl->find()
                ->where([
                    $securityGroupInstitutionsTbl->aliasField('security_group_id') => $security_group_id,
                    $securityGroupInstitutionsTbl->aliasField('institution_id') => $institutions->id
                ])
                ->first();
            //save security group for institution
            if (empty($securityGroupInstitutions)) {
                $security_group_ins_data = [
                    'security_group_id' => $security_group_id,
                    'institution_id' => $institutionId,
                    'created_user_id' => 1,
                    'created' => new Time('NOW')
                ];
                $securityGroupInstitutionsEntity = $securityGroupInstitutionsTbl->newEntity($security_group_ins_data);
                $securityGroupInstitutionsTbl->save($securityGroupInstitutionsEntity);
            }
        }
        return $security_group_id;
    }

    private
    static function getHomeroomTeacherSecurityRoleId(): int
    {
        $securityRolesTbl = self::getDynamicTableInstance('security_roles');
        $securityRoles = $securityRolesTbl->find()
            ->where([
                $securityRolesTbl->aliasField('code') => 'HOMEROOM_TEACHER',
            ])->first();
        $security_role_id = $securityRoles->id;
        return $security_role_id;
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
//            Log::debug('Error: ' . $e->getMessage());
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

    private function convertToValidDate($dateString)
    {
        if (!$dateString) {
            return $dateString; // Return original value if null or empty
        }

        // Define the expected formats
        $formats = ['Y-m-d', 'd-m-Y'];

        foreach ($formats as $format) {
            $dateTime = \DateTime::createFromFormat($format, $dateString);
            if ($dateTime && $dateTime->format($format) === $dateString) {
                return $dateTime->format('Y-m-d'); // Return in the desired format
            }
        }

        // Log an error or handle invalid date format
        // For example, you could log this using a logging mechanism
        // error_log("Invalid date format: " . $dateString);

        // Return null if no valid format found
        return null;
    }
// POCOR-8853 end
}

