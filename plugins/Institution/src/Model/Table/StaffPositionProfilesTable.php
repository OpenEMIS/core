<?php
namespace Institution\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Utility\Inflector;
use Cake\Controller\Component;
use Cake\Network\Request;
use Cake\ORM\TableRegistry;
use Cake\Datasource\ResultSetInterface;
use App\Model\Table\ControllerActionTable;
use Cake\Validation\Validator;
use Cake\I18n\Date;
use Cake\I18n\Time;
use App\Model\Traits\OptionsTrait;

class StaffPositionProfilesTable extends ControllerActionTable
{
    use OptionsTrait;
    // Workflow Steps - category
    const TO_DO = 1;
    const IN_PROGRESS = 2;
    const DONE = 3;

    private $staffChangeTypesList = [];

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

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        $validator = $this->buildStaffValidation();
        //Start:POCOR-6913
        if($this->request->data['StaffPositionProfiles']['staff_change_type_id'] == 1){ 
            return $validator   
            ->notEmpty('end_date')      
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
                        $InstitutionStaff = TableRegistry::get('Institution.Staff');    
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
                        $InstitutionStaff = TableRegistry::get('Institution.Staff');    
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
        return $validator->requirePresence('effective_date');
    }

    public function initialize(array $config)
    {
//        print_r($config);die();
        $this->table('institution_staff_position_profiles');
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'staff_id']);
        $this->belongsTo('StaffChangeTypes', ['className' => 'Staff.StaffChangeTypes', 'foreignKey' => 'staff_change_type_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users']);
        $this->belongsTo('StaffTypes', ['className' => 'Staff.StaffTypes']);
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Positions', ['className' => 'Institution.InstitutionPositions', 'foreignKey' => 'institution_position_id']);
        $this->belongsTo('StaffTypes', ['className' => 'Staff.StaffTypes', 'foreignKey' => 'staff_type_id']);

//        $this->staffChangeTypesList = $this->StaffChangeTypes->findCodeList();
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
    }

    public function implementedEvents()
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

    public function getSearchableFields(Event $event, ArrayObject $searchableFields)
    {
        $searchableFields[] = 'staff_id';
        $searchableFields[] = 'openemis_no';
    }


    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)
    {
      //POCOR-6979
        $StaffChangeTypes = TableRegistry::get('Staff.StaffChangeTypes');
        $StaffChangeTypesDataForShift = $StaffChangeTypes->find()
                        ->where([$StaffChangeTypes->aliasField('id') => $entity->staff_change_type_id])
                        ->first();

        /* START POCOR-7216 */
 
        // if($StaffChangeTypesDataForShift['code'] == 'CHANGE_IN_STAFF_TYPE'){
        //     $entity->end_date = date('Y-m-d');
        // }
        // else if($StaffChangeTypesDataForShift['code'] == 'CHANGE_IN_FTE'){
        //     $entity->end_date = $entity->effective_date;
        //     if(empty($entity->end_date)){
        //         $staffPositionProfilesRecord = $this->find()
        //         ->where([
        //             $this->aliasField('institution_staff_id') => $entity->institution_staff_id,
        //             $this->aliasField('staff_id') => $entity->staff_id,
        //         ])
        //         ->first();
        //         $entity->end_date = $staffPositionProfilesRecord->end_date->format('Y-m-d');
        //         // echo "<pre>";print_r($staffPositionProfilesRecord->end_date->format('Y-m-d'));die;
        //     }
        //     $entity->end_date =  date("Y-m-d", strtotime($entity->end_date) );
        // }
        // else if($StaffChangeTypesDataForShift['code'] == 'END_OF_ASSIGNMENT'){
        //     $entity->end_date = $entity->end_date;
        // }
        // else if($StaffChangeTypesDataForShift['code'] == 'CHANGE_OF_START_DATE'){
        //     $entity->end_date = date('Y-m-d');
        // }else{
        //     $entity->end_date = $entity->start_date;
        // }

        if($StaffChangeTypesDataForShift['code'] == 'CHANGE_IN_STAFF_TYPE'){
            $entity->end_date = $entity->end_date;
        }
        else if($StaffChangeTypesDataForShift['code'] == 'CHANGE_IN_FTE'){
            $entity->end_date = $entity->effective_date;
            if(empty($entity->end_date)){
                $staffPositionProfilesRecord = $this->find()
                ->where([
                    $this->aliasField('institution_staff_id') => $entity->institution_staff_id,
                    $this->aliasField('staff_id') => $entity->staff_id,
                ])
                ->first();
                $entity->end_date = $staffPositionProfilesRecord->end_date->format('Y-m-d');
                // echo "<pre>";print_r($staffPositionProfilesRecord->end_date->format('Y-m-d'));die;
            }
            $entity->end_date =  date("Y-m-d", strtotime($entity->end_date) );
        }
        else if($StaffChangeTypesDataForShift['code'] == 'END_OF_ASSIGNMENT'){
            $entity->end_date = $entity->end_date;
        }
        else if($StaffChangeTypesDataForShift['code'] == 'CHANGE_OF_START_DATE'){
            $entity->end_date = $entity->end_date;
        }else{
            $entity->end_date = $entity->start_date;
        }

        /* END POCOR-7216 */

        // get associated data
        $associatedData = $this->getAssociatedData($entity);
        
        // if there is an associated data, redirect to add page and show alert message
        // in the next version will redirect to associated page and show the associated data
        if (!empty($associatedData)) {
            $message = __('The record is not updated due to associated data encountered.');
            $this->Alert->error($message, ['type' => 'string', 'reset' => true]);
            $event->stopPropagation();
            return $this->controller->redirect($this->url('add'));
        } else { /**POCOR-6928- added else condition when staff_change_type_id is CHANGE_OF_SHIFT*/
            $StaffChangeTypes = TableRegistry::get('Staff.StaffChangeTypes');

            $StaffChangeTypesDataForShift = $StaffChangeTypes->find()
                        ->where([$StaffChangeTypes->aliasField('id') => $entity->staff_change_type_id])
                        ->first();
                //POCOR-7006 
            if($StaffChangeTypesDataForShift->code == 'CHANGE_OF_SHIFT'){
                //End of POCOR-7006
                $StaffChangeTypes = TableRegistry::get('Staff.StaffChangeTypes');
                $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
                $InstitutionShifts = TableRegistry::get('Institution.InstitutionShifts');
                $InstitutionStaffShifts = TableRegistry::get('Institution.InstitutionStaffShifts');
                $ShiftOptions = TableRegistry::get('Institution.ShiftOptions');
                $InstitutionPositions = TableRegistry::get('Institution.InstitutionPositions');
                $periodId = $AcademicPeriods->getCurrent();
                if(!empty( $entity->new_shift)){ //POCOR-7109
                    $shiftUpdate =   $InstitutionPositions->updateAll(
                                ['shift_id' => $entity->new_shift,'modified_user_id' => 1,'modified' => new Time('NOW')],    //field
                                [
                                 'id' => $entity->institution_position_id, //condition update
                                ] 
                            );
                    
                    $StaffChangeTypesData = $StaffChangeTypes->find()
                        ->where([$StaffChangeTypes->aliasField('id') => $this->request->data['StaffPositionProfiles']['staff_change_type_id']])
                        ->first();
                if($StaffChangeTypesData['code'] != 'END_OF_ASSIGNMENT'){
                    $event->stopPropagation();
                }
                //POCOR-6979[END]
                $url = $this->url('view');
                $url['action'] = 'Staff';
                $url[1] = $this->paramsEncode(['id' => $entity['institution_staff_id']]);
                return $this->controller->redirect($url);   
            }
                //POCOR-6979[START]   
            }
        }
        /**POCOR-6928 ends*/    }

    private function getAssociatedData($entity)
    {
        $requestData = $this->request->data;
        $staffChangeTypes = $this->staffChangeTypesList;
        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');

        $associatedData = [];
        if ((array_key_exists($this->alias(), $requestData)) && $requestData[$this->alias()]['staff_change_type_id'] == $staffChangeTypes['CHANGE_OF_START_DATE']) {
            $staffId = $entity->staff_id;
            $institutionId = $entity->institution_id;
            $institutionPositionId = $entity->institution_position_id;
            $institutionStaffId = $entity->institution_staff_id;

            $InstitutionStaff = TableRegistry::get('Institution.Staff');
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

                    $associatedModel = TableRegistry::get($model);

                    $event = $associatedModel->dispatchEvent('Model.StaffPositionProfiles.getAssociatedModelData', [$params], $this);

                    if ($event->isStopped()) {
                        $mainEvent->stopPropagation();
                        return $event->result;
                    }

                    $result = $event->result;

                    // if no result will not added to the associated data
                    if (!empty($result)) {
                        $associatedData[$value[0]] = $result;
                    }

                }
            }
        }

        return $associatedData;
    }

    public function addAfterSave(Event $event, $entity, $requestData, ArrayObject $extra)
    {
        
        if (!$entity->errors()) {
            $StaffTable = TableRegistry::get('Institution.Staff');
            $url = $this->url('view');
            $url['action'] = 'Staff';
            $url[1] = $this->paramsEncode(['id' => $entity['institution_staff_id']]);
            $event->stopPropagation();
            $this->Session->write('Institution.StaffPositionProfiles.addSuccessful', true);
            return $this->controller->redirect($url);
        }
    }

    public function workflowBeforeTransition(Event $event, $requestData)
    {
        $errors = true;
        $approved = $this->Workflow->getStepsByModelCode($this->registryAlias(), 'APPROVED');
        $nextWorkflowStepId = $requestData['WorkflowTransitions']['workflow_step_id'];
        $id = $requestData['WorkflowTransitions']['model_reference'];
        if (in_array($nextWorkflowStepId, $approved)) {
            $data = $this->get($id)->toArray();
            $newEntity = $this->patchStaffProfile($data);
            if (is_null($newEntity)) {
                $message = ['StaffPositionProfiles.notExists'];
                $this->Session->write('Institution.StaffPositionProfiles.errors', $message);
            } else if ($newEntity->errors()) {
                $message = [];
                $errors = $newEntity->errors();
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

    public function getWorkflowEvents(Event $event, ArrayObject $eventsObject)
    {
        foreach ($this->workflowEvents as $key => $attr) {
            $attr['text'] = __($attr['text']);
            $attr['description'] = __($attr['description']);
            $eventsObject[] = $attr;
        }
    }

    public function onApprove(Event $event, $id, Entity $workflowTransitionEntity)
    {
        $data = $this->get($id)->toArray();
        $newEntity = $this->patchStaffProfile($data);

        // reject all pending transfers
        $this->rejectPendingTransfer($data);

        $InstitutionStaff = TableRegistry::get('Institution.Staff');
        $InstitutionStaff->save($newEntity);
    }

    private function rejectPendingTransfer(array $data)
    {

        // reject all pending transfers
        $staffId = $data['staff_id'];

        $InstitutionStaffTransfers = TableRegistry::get('Institution.InstitutionStaffTransfers');
        $doneStatus = $InstitutionStaffTransfers::DONE;

        $transferRecords = $InstitutionStaffTransfers->find()
            ->matching('Statuses', function ($q) use ($doneStatus) {
                return $q->where(['category <> ' => $doneStatus]);
            })
            ->where([$InstitutionStaffTransfers->aliasField('staff_id') => $staffId])
            ->all();

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
                        $WorkflowTransitions = TableRegistry::get('Workflow.WorkflowTransitions');
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
        $InstitutionStaff = TableRegistry::get('Institution.Staff');
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

    public function onGetBreadcrumb(Event $event, Request $request, Component $Navigation, $persona)
    {
        $url = [];

        if ($this->action != 'index') {
            $url = ['plugin' => 'Institution', 'controller' => 'Institutions', 'action' => $this->alias];
        }

        $Navigation->substituteCrumb('Staff Position Profiles', 'Change in Assignment', $url);
    }

    public function onGetFTE(Event $event, Entity $entity)
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

    public function onGetStartDate(Event $event, Entity $entity)
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

    public function onGetEndDate(Event $event, Entity $entity)
    {
        //POCOR-6979
        $StaffChangeTypes = TableRegistry::get('Staff.StaffChangeTypes');
        $StaffChangeTypesDataForShift = $StaffChangeTypes->find()
                        ->where([$StaffChangeTypes->aliasField('id') => $entity->staff_change_type_id])
                        ->first();
        if ($this->action == 'view') {
            $oldValue = $entity->institution_staff->end_date;
            $newValue = $entity->end_date;
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

    public function onGetStaffTypeId(Event $event, Entity $entity)
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

    public function beforeAction(Event $event, ArrayObject $extra)
    {

        // Set the header of the page
        $institutionId = $this->Session->read('Institution.Institutions.id');
        $institutionName = $this->Institutions->get($institutionId)->name;
        $this->controller->set('contentHeader', $institutionName. ' - ' .__('Pending Change in Assignment'));

        $this->field('institution_staff_id', ['visible' => false]);
        $this->field('staff_id', ['before' => 'start_date']);
        $this->field('FTE', ['type' => 'select','visible' => ['view' => true, 'edit' => true, 'add' => true]]);
        $extra['config']['selectedLink'] = ['controller' => 'Institutions', 'action' => 'Staff', 'index'];
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
//        print_r($extra);die();
        $this->Session->delete('Institution.StaffPositionProfiles.viewBackUrl');
        if (isset($extra['toolbarButtons']['add'])) {
            unset($extra['toolbarButtons']['add']);
        }
        $session = $this->Session;
        $institutionId = $session->read('Institution.Institutions.id');

        $this->fields['staff_id']['order'] = 5;
        $this->fields['institution_position_id']['type'] = 'integer';
        $this->fields['staff_id']['type'] = 'integer';
        $this->fields['start_date']['type'] = 'date';
        $this->fields['institution_position_id']['order'] = 6;
        $this->fields['FTE']['visible'] = false;

        $this->controller->set('ngController', 'AdvancedSearchCtrl');
        $selectedStatus = $this->request->query('staff_status_id');
//        print_r($selectedStatus);die();

    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $request = $this->request;
        $query->contain(['Positions']);
        $search = $this->getSearchKey();
        if (!empty($search)) {
            // function from AdvancedNameSearchBehavior
            $query = $this->addSearchConditions($query, ['alias' => 'Users', 'searchTerm' => $search]);
        }
    }

    public function addEditBeforePatch(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $patchOptions, ArrayObject $extra)
    {
        if ($requestData[$this->alias()]['staff_change_type_id'] == $this->staffChangeTypesList['CHANGE_IN_FTE']) {
            $patchOptions['validate'] = 'IncludeEffectiveDate';

            $newFTE = $requestData[$this->alias()]['FTE'];
            $newEndDate = $requestData[$this->alias()]['effective_date'];
            $staffRecordEntity = $this->Session->read('Institution.StaffPositionProfiles.staffRecord');
            $entity->FTE = $staffRecordEntity->FTE;
            $entity->newFTE = $newFTE;

            if (empty($newEndDate)) {
                if ($entity->start_date < date('Y-m-d')) {
                    $requestData[$this->alias()]['end_date'] = date('Y-m-d');
                } else {
                    $requestData[$this->alias()]['end_date'] = $requestData[$this->alias()]['start_date'];
                }
            } else {
                $endDate = (new Date($newEndDate))->modify('-1 day');
                $requestData[$this->alias()]['end_date'] = $endDate->format('Y-m-d');
            }
        }
    }

    public function addEditAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $toolbarButtons = $extra['toolbarButtons'];
        $toolbarButtons['back']['url'] = [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'Staff',
            '0' => 'view',
            '1' => $this->paramsEncode(['id' => $entity->institution_staff_id])
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
    }

    public function onUpdateFieldStaffChangeTypeId(Event $event, array $attr, $action, Request $request)
    {
        $attr['type'] = 'select';
        $attr['onChangeReload'] = true;
        return $attr;
    }

    public function onUpdateFieldCurrentStaffType(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $staffChangeTypes = $this->staffChangeTypesList;
            if($request->data[$this->alias()]['staff_change_type_id'] == ''){
                $attr['visible'] = false;
            }else if ($request->data[$this->alias()]['staff_change_type_id'] == $staffChangeTypes['CHANGE_IN_STAFF_TYPE'] || $request->data[$this->alias()]['staff_change_type_id'] == 3) {
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

    public function onUpdateFieldStaffTypeId(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $staffChangeTypes = $this->staffChangeTypesList;
            if($request->data[$this->alias()]['staff_change_type_id'] == ''){
                $attr['visible'] = false;
            }else if ($request->data[$this->alias()]['staff_change_type_id'] == $staffChangeTypes['CHANGE_IN_STAFF_TYPE'] || $request->data[$this->alias()]['staff_change_type_id'] == 3) {
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

    public function onUpdateFieldCurrentFTE(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $staffChangeTypes = $this->staffChangeTypesList;
            if (isset($request->data[$this->alias()])) {
                // echo "<pre>";print_r($request->data);die;
                if($request->data[$this->alias()]['staff_change_type_id'] == ''){
                    $attr['visible'] = false;
                }
                // else if ($request->data[$this->alias()]['staff_change_type_id'] == $staffChangeTypes['CHANGE_IN_FTE']) {
                else if ($request->data[$this->alias()]['staff_change_type_id'] != '' && $request->data[$this->alias()]['staff_change_type_id'] == 2) {
                    $attr['visible'] = true;
                    if ($this->Session->check('Institution.StaffPositionProfiles.staffRecord')) {
                        $entity = $this->Session->read('Institution.StaffPositionProfiles.staffRecord');
                        $options = $attr['options'];
                        $attr['attr']['value'] = $options[strval($entity->FTE)];
                    }
                } else {
                    $attr['visible'] = false;
                }
            }
        }
        return $attr;
    }

    public function onUpdateFieldFTE(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $staffChangeTypes = $this->staffChangeTypesList;
            if (isset($request->data[$this->alias()])) {
                if($request->data[$this->alias()]['staff_change_type_id'] == ''){
                    $attr['visible'] = false;
                }
                else if ($request->data[$this->alias()]['staff_change_type_id'] == $staffChangeTypes['CHANGE_IN_FTE'] || $request->data[$this->alias()]['staff_change_type_id'] == 2) {
                    $attr['type'] = 'select';
                    if (isset($attr['options'])) {
                        $options = $attr['options'];
                        if ($this->Session->check('Institution.StaffPositionProfiles.staffRecord')) {
                            $entity = $this->Session->read('Institution.StaffPositionProfiles.staffRecord');
                            if (isset($options[strval($entity->FTE)])) {
                                unset($options[strval($entity->FTE)]);
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

    public function onUpdateFieldEffectiveDate(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $staffChangeTypes = $this->staffChangeTypesList;
            if($request->data[$this->alias()]['staff_change_type_id'] == ''){
                $attr['visible'] = false;
            }
            else if ($request->data[$this->alias()]['staff_change_type_id'] == $staffChangeTypes['CHANGE_IN_FTE'] || $request->data[$this->alias()]['staff_change_type_id'] == 2) {
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

    public function onUpdateFieldStartDate(Event $event, array $attr, $action, Request $request)
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
            $requestData = $this->request->data;
            $startDate = new Date($requestData[$this->alias()]['start_date']);
        } else {
            $startDate = $entity->start_date;
        }

        $staffChangeTypes = $this->staffChangeTypesList;
        if($request->data[$this->alias()]['staff_change_type_id'] == ''){
            $attr['visible'] = false;
        }
        else if($request->data[$this->alias()]['staff_change_type_id'] == '' || ($request->data[$this->alias()]['staff_change_type_id'] == 4)){
            $attr['type'] = 'date';
            $attr['value'] = $startDate->format('Y-m-d');
            $attr['attr']['value'] = $this->formatDate($startDate);
        } else {
            $getStaffStartData = TableRegistry::get('institution_staff');
            $getStaffStartDateData = $getStaffStartData->find()
            ->where([
                $getStaffStartData->aliasField('staff_id') => $entity->staff_id,
                $getStaffStartData->aliasField('institution_id') => $entity->institution_id
            ])
            ->order([$getStaffStartData->aliasField('start_date') => 'DESC'])
            ->first();
            $startDate = $getStaffStartDateData->start_date;

            $attr['value'] = $startDate->format('Y-m-d');
            $attr['attr']['value'] = $this->formatDate($startDate);
            $attr['type'] = 'hidden';
        }

        return $attr;
    }

    public function onUpdateFieldEndDate(Event $event, array $attr, $action, Request $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $staffChangeTypes = $this->staffChangeTypesList;
            // echo "<pre>";print_r($request->data);die;
            if($request->data[$this->alias()]['staff_change_type_id'] == ''){
                $attr['visible'] = false;
            }
            // else if ($request->data[$this->alias()]['staff_change_type_id'] == $staffChangeTypes['END_OF_ASSIGNMENT']) {
            else if ($request->data[$this->alias()]['staff_change_type_id'] == 1) {
                $attr['type'] = 'date';
                if ($this->Session->check('Institution.StaffPositionProfiles.staffRecord')) {
                    $entity = $this->Session->read('Institution.StaffPositionProfiles.staffRecord');

                    $InstitutionSubjectStaff = TableRegistry::get('Institution.InstitutionSubjectStaff');
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

    public function viewBeforeAction(Event $event, $extra)
    {
        if (isset($extra['toolbarButtons']['back']) && $this->Session->check('Institution.StaffPositionProfiles.viewBackUrl')) {
            $url = $this->Session->read('Institution.StaffPositionProfiles.viewBackUrl');
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
    }

    public function viewAfterAction(Event $event, Entity $entity, $extra)
    {
        $StaffTable = TableRegistry::get('Institution.Staff');
        $staffEntity = $StaffTable->find()
            ->contain(['StaffTypes'])
            ->where([$StaffTable->aliasField('id') => $entity->institution_staff_id])
            ->first();
        $entity->institution_staff = $staffEntity;
    }

    private function initialiseVariable($entity)
    {
        $institutionStaffId = $this->request->query('institution_staff_id');

        $institutionStaffId = $this->paramsDecode($institutionStaffId)['id'];

        if (is_null($institutionStaffId)) {
            return true;
        }
        $InstitutionStaff = TableRegistry::get('Institution.Staff');
        $staff = $InstitutionStaff->get($institutionStaffId);
        $approvedStatus = $this->Workflow->getStepsByModelCode($this->registryAlias(), 'APPROVED');
        $closedStatus = $this->Workflow->getStepsByModelCode($this->registryAlias(), 'CLOSED');

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
            $this->request->data[$this->alias()]['staff_id'] = $entity->staff_id;
            $this->request->data[$this->alias()]['institution_position_id'] = $entity->institution_position_id;
            $this->request->data[$this->alias()]['institution_id'] = $entity->institution_id;
            $this->request->data[$this->alias()]['staff_change_type_id'] = '';
            return false;
        } else {
            return $staffPositionProfilesRecord;
        }
    }

    public function editOnInitialize(Event $event, Entity $entity)
    {
        $staffEntity = TableRegistry::get('Institution.Staff')->get($entity->institution_staff_id);
        $this->Session->write('Institution.StaffPositionProfiles.staffRecord', $staffEntity);
        $this->request->data[$this->alias()]['staff_change_type_id'] = $entity->staff_change_type_id;
    }

    public function addOnInitialize(Event $event, Entity $entity)
    {

        $addOperation = $this->initialiseVariable($entity);
        if ($addOperation) {
            $institutionStaffId = $this->request->query('institution_staff_id');
            if (is_null($institutionStaffId)) {
                $url = $this->url('index');
            } else {
                $staffTableViewUrl = $this->url('view');
                $staffTableViewUrl['action'] = 'Staff';
                $staffTableViewUrl[1] = $institutionStaffId;
                $this->Session->write('Institution.StaffPositionProfiles.viewBackUrl', $staffTableViewUrl);
                $url = $this->url('view');
                $url[1] = $this->paramsEncode(['id' => $addOperation->id]);
            }
            $event->stopPropagation();
            return $this->controller->redirect($url);
        }
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
            ->contain([$this->Users->alias(), $this->Institutions->alias(), $this->CreatedUser->alias(),'Assignees'])
            ->matching($this->Statuses->alias(), function ($q) use ($Statuses, $doneStatus) {
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
                        $this->paramsEncode(['id' => $row->id]),
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
    public function onUpdateFieldCurrentShift(Event $event, array $attr, $action, Request $request)
    {
        $InstitutionStaffShifts = TableRegistry::get('Institution.InstitutionStaffShifts');
        $InstitutionShifts = TableRegistry::get('Institution.InstitutionShifts');
        $ShiftOptions = TableRegistry::get('Institution.ShiftOptions');
        $shifts = [];
        if ($action == 'add' || $action == 'edit') {
            $staffChangeTypes = $this->staffChangeTypesList;
            if($request->data[$this->alias()]['staff_change_type_id'] == ''){
                $attr['visible'] = false;
            }else if ($request->data[$this->alias()]['staff_change_type_id'] == $staffChangeTypes['CHANGE_OF_SHIFT'] || $request->data[$this->alias()]['staff_change_type_id'] == 5) {
                $attr['visible'] = true;
                $attr['type'] = 'readOnly';
                if ($this->Session->check('Institution.StaffPositionProfiles.staffRecord')) {
                    $entity = $this->Session->read('Institution.StaffPositionProfiles.staffRecord');
                    $InstitutionStaff = TableRegistry::get('institution_staff');
                    $InstitutionPositions = TableRegistry::get('Institution.InstitutionPositions');
                    $staffId = $entity->staff_id;
                    $staffShifts  = $InstitutionStaff
                            ->find()
                            ->select(['shift_name' =>  $ShiftOptions->aliasField('name')])
                            ->leftJoin([$InstitutionPositions->alias() => $InstitutionPositions->table()],[
                                    $InstitutionPositions->aliasField('id = ') . $InstitutionStaff->aliasField('institution_position_id')
                            ])
                            ->leftJoin([$ShiftOptions->alias() => $ShiftOptions->table()],[
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
    public function onUpdateFieldNewShift(Event $event, array $attr, $action, Request $request)
    {
        //POCOR-7109 start
        $institutionId = $request->data[$this->alias()]['institution_id'];
        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $periodId = $AcademicPeriods->getCurrent();
        $InstitutionShifts = TableRegistry::get('Institution.InstitutionShifts');
        $ShiftOptions = TableRegistry::get('Institution.ShiftOptions');
        $optionList = $ShiftOptions->find('list')
                        ->leftJoin([$InstitutionShifts->alias() => $InstitutionShifts->table()],
                        [$InstitutionShifts->aliasField('shift_option_id = ') . $ShiftOptions->aliasField('id')])
                        ->where([$InstitutionShifts->aliasField('institution_id')=>$institutionId,$InstitutionShifts->aliasField('academic_period_id')=>$periodId])
                        ->toArray();
        //POCOR-7109 end
        if ($action == 'add' || $action == 'edit') {
            $staffChangeTypes = $this->staffChangeTypesList;
            if($request->data[$this->alias()]['staff_change_type_id'] == ''){
                $attr['visible'] = false;
            }else if ($request->data[$this->alias()]['staff_change_type_id'] == $staffChangeTypes['CHANGE_OF_SHIFT'] || $request->data[$this->alias()]['staff_change_type_id'] == 5) {
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

    public function onUpdateFieldCurrentShiftOne(Event $event, array $attr, $action, Request $request)
    {
        $InstitutionStaffShifts = TableRegistry::get('Institution.InstitutionStaffShifts');
        $InstitutionShifts = TableRegistry::get('Institution.InstitutionShifts');
        $ShiftOptions = TableRegistry::get('Institution.ShiftOptions');
        $shifts = [];
        if ($action == 'add' || $action == 'edit') {
            $staffChangeTypes = $this->staffChangeTypesList;
            if($request->data[$this->alias()]['staff_change_type_id'] == ''){
                $attr['visible'] = false;
            }else if ($request->data[$this->alias()]['staff_change_type_id'] == $staffChangeTypes['CHANGE_OF_SHIFT'] || $request->data[$this->alias()]['staff_change_type_id'] == 5) {
                $attr['visible'] = true;
                $attr['type'] = 'hidden';
                if ($this->Session->check('Institution.StaffPositionProfiles.staffRecord')) {
                    $entity = $this->Session->read('Institution.StaffPositionProfiles.staffRecord');
                    $staffShifts  = $InstitutionStaffShifts
                            ->find()
                            ->select(['shift_name' =>  $ShiftOptions->aliasField('name')])
                            ->leftJoin([$InstitutionShifts->alias() => $InstitutionShifts->table()],[
                                    $InstitutionShifts->aliasField('id = ') . $InstitutionStaffShifts->aliasField('shift_id')
                            ])
                            ->leftJoin([$ShiftOptions->alias() => $ShiftOptions->table()],[
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

    public function addBeforeSave(Event $event, Entity $entity, ArrayObject $requestData, ArrayObject $options)
    {
        if($requestData[$this->alias()]['staff_change_type_id']==5){
            $entity->assignee_id = -1;
            return $entity->assignee_id;
        }
    }
}
