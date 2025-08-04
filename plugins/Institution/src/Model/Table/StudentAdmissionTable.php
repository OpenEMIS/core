<?php

namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\Datasource\ResultSetInterface;
use Cake\Event\Event;
use Cake\Validation\Validator;
use Cake\Http\ServerRequest;
use Cake\Controller\Component;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use Cake\ORM\RulesChecker;
use App\Model\Table\ControllerActionTable;
use Workflow\Model\Behavior\WorkflowBehavior;
use Cake\Log\Log;
use Cake\Utility\Text;
use Cake\Routing\Router;
use Cake\I18n\FrozenTime;
use App\Controller\DashboardController;
use Cake\I18n\FrozenDate;
use Cake\ORM\Table;

// POCOR-8286 start

// POCOR-8286 end

class StudentAdmissionTable extends ControllerActionTable
{
    // Workflow Steps - category
    const TO_DO = 1;
    const IN_PROGRESS = 2;
    const DONE = 3;

    private $workflowEvents = [
        [
            'value' => 'Workflow.onApprove',
            'text' => 'Approval of Student Admission',
            'description' => 'Performing this action will enroll the student into the institution.',
            'method' => 'OnApprove',
            'unique' => true
        ],
        [
            'value' => 'Workflow.onCancel',
            'text' => 'Cancellation of Student Admission',
            'description' => 'Performing this action will remove the student from the institution.',
            'method' => 'onCancel',
            'unique' => true
        ],//POCOR-8434 starts
        [
            'value' => 'Workflow.onTriggerPendingEnrolment',
            'text' => 'Trigger Pending Enrolment Workflow',
            'description' => 'Performing this action will system will trigger pending enrolment workflow for the student.',
            'method' => 'onTriggerPendingEnrolment',
            'unique' => true
        ]//POCOR-8434 ends
    ];

    public function initialize(array $config): void
    {
        $this->setTable('institution_student_admission');

        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('Statuses', ['className' => 'Workflow.WorkflowSteps', 'foreignKey' => 'status_id']);
        $this->belongsTo('Assignees', ['className' => 'User.Users', 'foreignKey' => 'assignee_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);

        $this->addBehavior('Workflow.Workflow');
        $this->addBehavior('Institution.InstitutionWorkflowAccessControl');
        $this->addBehavior('User.AdvancedNameSearch');
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Dashboard' => ['index'],
            'Students' => ['index', 'add']
        ]);

        //$this->hasMany('StudentCustomFieldValues', ['className' => 'StudentCustomField.StudentAdmissionCustomFieldValues', 'dependent' => true, 'cascadeCallbacks' => true, 'foreignKey' => 'institution_student_admission_id']);
        $this->hasMany('AdmissionStudentCustomFieldValues', [
            'className' => 'StudentCustomField.StudentAdmissionCustomFieldValues',
            'dependent' => true,
            'cascadeCallbacks' => true,
            'foreignKey' => 'institution_student_admission_id'
        ]);
        //$this->hasMany('CustomFieldValues', ['className' => 'StudentCustomField.StudentAdmissionCustomFieldValues', 'foreignKey' => 'institution_student_admission_id']);
        $this->hasMany('AdmissionCustomFieldValues', [
            'className' => 'StudentCustomField.StudentAdmissionCustomFieldValues',
            'foreignKey' => 'institution_student_admission_id'
        ]);
        //$this->hasMany('StudentCustomFields', ['className' => 'StudentCustomField.StudentCustomFields', 'foreignKey' => 'student_id']);

        //POCOR-8434 add custome fileds record in pending admission starts
        $request = Router::getRequest();
        if (
            $request !== null &&
            ($param = $request->getParam('pass')[0] ?? null) !== 'excel' &&
            !in_array($request->getParam('action'), ['saveStudentData', 'Promotion', 'Transfer', 'Undo', 'ImportUsers', 'ImportStudentAdmission'])
        ) {
            $this->addBehavior('CustomField.Record', [
                'model' => 'Institution.StudentAdmission',
                'behavior' => 'Student',
                'fieldKey' => 'student_custom_field_id',
                'tableColumnKey' => 'student_custom_table_column_id',
                'tableRowKey' => 'student_custom_table_row_id',
                'fieldClass' => ['className' => 'StudentCustomField.StudentCustomFields'],
                'formKey' => 'student_custom_form_id',
                'filterKey' => 'student_custom_filter_id',
                'formFieldClass' => ['className' => 'StudentCustomField.StudentCustomFormsFields'],
                // 'formFilterClass' => ['className' => 'StudentCustomField.StudentCustomFormsFilters'],
                'recordKey' => 'institution_student_admission_id',
                //'recordKey' => 'student_id',
                //'fieldValueClass' => ['className' => 'StudentCustomField.StudentCustomFieldValues', 'foreignKey' => 'student_id', 'dependent' => true, 'cascadeCallbacks' => true],//old
                'fieldValueClass' => ['className' => 'StudentCustomField.StudentAdmissionCustomFieldValues', 'foreignKey' => 'institution_student_admission_id', 'dependent' => true, 'cascadeCallbacks' => true],
                //'tableCellClass' => ['className' => 'StudentCustomField.StudentCustomTableCells', 'foreignKey' => 'student_id', 'dependent' => true, 'cascadeCallbacks' => true, 'saveStrategy' => 'replace']
                'tableCellClass' => null
            ]);//POCOR-8434 ends
        }

        $this->toggle('add', true);
        $this->addBehavior('Institution.InstitutionTab',
            ['appliedAction' => ['StudentAdmission' => ['id']]
            ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);
        $validator
            ->add('academic_period_id', [
                'ruleCheckValidAcademicPeriodId' => [
                    'rule' => ['checkValidAcademicPeriodId'],
                    'on' => function ($context) {
                        if (array_key_exists('academic_period_id', $context['data']) && !empty($context['data']['academic_period_id'])) {
                            $AcademicPeriods = self::getDynamicTableInstance('AcademicPeriod.AcademicPeriods'); // POCOR-8286
                            $academicPeriodExists = $AcademicPeriods->exists([$AcademicPeriods->getPrimaryKey() => $context['data']['academic_period_id']]);

                            return $academicPeriodExists;
                        }
                        return false;
                    }
                ]
            ])
            ->add('start_date', [
                'ruleCompareDate' => [
                    'rule' => ['compareDate', 'end_date', false],
                    'on' => function ($context) {
                        return (array_key_exists('end_date', $context['data']) && !empty($context['data']['end_date']));
                    }
                ],
                'ruleCheckProgrammeEndDateAgainstStudentStartDate' => [
                    'rule' => ['checkProgrammeEndDateAgainstStudentStartDate', 'start_date']
                ],
                'ruleInAcademicPeriod' => [
                    'rule' => ['inAcademicPeriod', 'academic_period_id', []],
                    'on' => function ($context) {
                        if (array_key_exists('academic_period_id', $context['data']) && !empty($context['data']['academic_period_id'])) {
                            $AcademicPeriods = self::getDynamicTableInstance('AcademicPeriod.AcademicPeriods');
                            $academicPeriodExists = $AcademicPeriods->exists([$AcademicPeriods->getPrimaryKey() => $context['data']['academic_period_id']]);

                            return $academicPeriodExists;
                        }
                        return false;
                    }
                ]
            ])
            ->add('end_date', [
                'ruleInAcademicPeriod' => [
                    'rule' => ['inAcademicPeriod', 'academic_period_id', []],
                    'on' => function ($context) {
                        if (array_key_exists('academic_period_id', $context['data']) && !empty($context['data']['academic_period_id'])) {
                            $AcademicPeriods = self::getDynamicTableInstance('AcademicPeriod.AcademicPeriods');
                            $academicPeriodExists = $AcademicPeriods->exists([$AcademicPeriods->getPrimaryKey() => $context['data']['academic_period_id']]);

                            return $academicPeriodExists;
                        }
                        return false;
                    }
                ]
            ])
            ->add('student_id', [
                'ruleCheckPendingAdmissionExist' => [
                    'rule' => ['checkPendingAdmissionExist'],
                    'on' => 'create',
                    'last' => true
                ],
                'ruleStudentNotEnrolledInAnyInstitutionAndSameEducationSystem' => [
                    'rule' => ['studentNotEnrolledInAnyInstitutionAndSameEducationSystem', []],
                    'on' => function ($context) {
                        //POCOR-6172-HINDOL[START]
                        $ConfigItems = self::getDynamicTableInstance('Configuration.ConfigItems'); // POCOR-8286
                        $multipleInstitutions = $ConfigItems->value('multiple_institutions_student_enrollment');
                        $multipleInstitutions = ($multipleInstitutions == "1") ? true : false;
//                        $this->log($multipleInstitutions);
                        if ($multipleInstitutions) return false;
                        //POCOR-6172-HINDOL[END]
                        if (array_key_exists('institution_id', $context['data']) && !empty($context['data']['institution_id']) && array_key_exists('education_grade_id', $context['data']) && !empty($context['data']['education_grade_id'])) {
                            $Institutions = self::getDynamicTableInstance('Institution.Institutions');
                            $institutionExists = $Institutions->exists([$Institutions->getPrimaryKey() => $context['data']['institution_id']]);

                            $EducationGrades = self::getDynamicTableInstance('Education.EducationGrades');
                            $educationGradeExists = $EducationGrades->exists([$EducationGrades->getPrimaryKey() => $context['data']['education_grade_id']]);

                            return ($institutionExists && $educationGradeExists && $context['newRecord']);
                        }
                        return false;
                    },
                    'last' => true
                ],
                'ruleStudentNotCompletedGrade' => [
                    'rule' => ['studentNotCompletedGrade', []],
                    'on' => 'create',
                    'last' => true
                ],
                'ruleCheckAdmissionAgeWithEducationCycleGrade' => [
                    'rule' => ['checkAdmissionAgeWithEducationCycleGrade'],
                    'on' => function ($context) {
                        if (array_key_exists('academic_period_id', $context['data']) && !empty($context['data']['academic_period_id']) && array_key_exists('education_grade_id', $context['data']) && !empty($context['data']['education_grade_id'])) {
                            $AcademicPeriods = self::getDynamicTableInstance('AcademicPeriod.AcademicPeriods');
                            $academicPeriodExists = $AcademicPeriods->exists([$AcademicPeriods->getPrimaryKey() => $context['data']['academic_period_id']]);

                            $EducationGrades = self::getDynamicTableInstance('Education.EducationGrades');
                            $educationGradeExists = $EducationGrades->exists([$EducationGrades->getPrimaryKey() => $context['data']['education_grade_id']]);

                            return ($academicPeriodExists && $educationGradeExists && $context['newRecord']);
                        }
                        return false;
                    },
                    'last' => true
                ]
            ])
            ->add('date_of_birth', 'ruleCheckAdmissionAgeWithEducationCycleGrade', [
                'rule' => ['checkAdmissionAgeWithEducationCycleGrade'],
                'on' => 'create'
            ])
            ->add('gender_id', 'ruleCompareStudentGenderWithInstitution', [
                'rule' => ['compareStudentGenderWithInstitution'],
                'on' => function ($context) {
                    if (array_key_exists('institution_id', $context['data']) && !empty($context['data']['institution_id'])) {
                        $Institutions = self::getDynamicTableInstance('Institution.Institutions');
                        $institutionExists = $Institutions->exists([$Institutions->getPrimaryKey() => $context['data']['institution_id']]);

                        return ($institutionExists && $context['newRecord']);
                    }
                    return false;
                }
            ])
            ->add('education_grade_id', [
                'ruleCheckEducationGradeExist' => [
                    'rule' => ['checkEducationGradeExist'],
                    'on' => function ($context) {
                        if (array_key_exists('institution_id', $context['data']) && !empty($context['data']['institution_id']) && array_key_exists('education_grade_id', $context['data']) && !empty($context['data']['education_grade_id'])) {
                            $Institutions = self::getDynamicTableInstance('Institution.Institutions');
                            $institutionExists = $Institutions->exists([$Institutions->getPrimaryKey() => $context['data']['institution_id']]);

                            $EducationGrades = self::getDynamicTableInstance('Education.EducationGrades');
                            $educationGradeExists = $EducationGrades->exists([$EducationGrades->getPrimaryKey() => $context['data']['education_grade_id']]);

                            return ($institutionExists && $educationGradeExists && $context['newRecord']);
                        }
                        return false;
                    },
                    'last' => true
                ],
                'ruleCheckProgrammeEndDate' => [
                    'rule' => ['checkProgrammeEndDate', 'education_grade_id']
                ]
            ])
            ->allowEmpty('institution_class_id')
            ->add('institution_class_id', [
                'ruleCheckValidClassId' => [
                    'rule' => ['checkValidClassId'],
                    'on' => function ($context) {
                        if (array_key_exists('institution_class_id', $context['data']) && !empty($context['data']['institution_class_id'])) {
                            if (array_key_exists('institution_id', $context['data']) && !empty($context['data']['institution_id'])) {
                                if (array_key_exists('education_grade_id', $context['data']) && !empty($context['data']['education_grade_id'])) {
                                    if (array_key_exists('academic_period_id', $context['data']) && !empty($context['data']['academic_period_id'])) {
                                        $InstitutionClasses = self::getDynamicTableInstance('Institution.InstitutionClasses');
                                        $institutionClassExists = $InstitutionClasses->exists([$InstitutionClasses->getPrimaryKey() => $context['data']['institution_class_id']]);

                                        $Institutions = self::getDynamicTableInstance('Institution.Institutions');
                                        $institutionExists = $Institutions->exists([$Institutions->getPrimaryKey() => $context['data']['institution_id']]);

                                        $EducationGrades = self::getDynamicTableInstance('Education.EducationGrades');
                                        $educationGradeExists = $EducationGrades->exists([$EducationGrades->getPrimaryKey() => $context['data']['education_grade_id']]);

                                        $AcademicPeriods = self::getDynamicTableInstance('AcademicPeriod.AcademicPeriods');
                                        $academicPeriodExists = $AcademicPeriods->exists([$AcademicPeriods->getPrimaryKey() => $context['data']['academic_period_id']]);

                                        return ($institutionClassExists && $institutionExists && $educationGradeExists && $academicPeriodExists);
                                    }
                                }
                            }
                        }
                        return false;
                    },
                    'last' => true
                ],
                'ruleClassMaxLimit' => [
                    'rule' => ['checkInstitutionClassMaxLimit'],
                    'on' => function ($context) {
                        if (array_key_exists('institution_class_id', $context['data']) && !empty($context['data']['institution_class_id'])) {
                            $InstitutionClasses = self::getDynamicTableInstance('Institution.InstitutionClasses');
                            $institutionClassExists = $InstitutionClasses->exists([$InstitutionClasses->getPrimaryKey() => $context['data']['institution_class_id']]);

                            return $institutionClassExists;
                        }
                        return false;
                    }
                ]
            ]);
        //POCOR-7716 start
        // for removing check for status_id during import
        // ->add('status_id', 'ruleCheckStatusIdValid', [
        //     'rule' => ['checkStatusIdValid'],
        //     'provider' => 'table',
        //     'on' => function ($context) {
        //         if (array_key_exists('action_type', $context['data']) && $context['data']['action_type'] == 'imported') {
        //             return true;
        //         }
        //         return false;
        //     }

        // ]);
        //POCOR-7716 end
        return $validator;
    }

    // foreign key rules

    /**
     * POCOR-8391 added
     * Get a dynamic table instance with all associations.
     *
     * @param string $tableName
     * @return \Cake\ORM\Table
     */
    private static function getDynamicTableInstance(string $tableName): Table
    {
        // Parse plugin and table names if dot notation is used
        $locator = TableRegistry::getTableLocator();
        try {
            return $locator->get($tableName);
        } catch (\Exception $exception) {

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

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $excludedFields = ['assignee_id'];//POCOR-7716
        foreach ($this->associations() as $assoc) {
            $associatedModel = $assoc->getTarget();
            $fieldName = $assoc->getForeignKey();
            //$associatedModel = TableRegistry::get($assoc->className());//not use in cakephp 4
            //$fieldName = $assoc->foreignKey();//not use in cakephp 4
            if (!in_array($fieldName, $excludedFields)) {
                $rules->add($rules->existsIn($fieldName, $associatedModel, $fieldName . ' does not exists.'));
            }
        }

        return $rules;
    }

    public function implementedEvents(): array
    {
        $events = parent::implementedEvents();
        $events['Model.Students.afterSave'] = 'studentsAfterSave';
        $events['Model.Students.afterDelete'] = 'studentsAfterDelete';
        $events['Workflow.getEvents'] = 'getWorkflowEvents';
        $events['Model.Navigation.breadcrumb'] = 'onGetBreadcrumb';
        foreach ($this->workflowEvents as $event) {
            $events[$event['value']] = $event['method'];
        }
        return $events;
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
        // add student into institution_students
        $entity = $this->get($id);
//        Log::debug('calling ensure on approve')
        $this->ensureInstitutionStudentExists($entity);
    }

    //POCOR-8434 Starts

    /**
     * Ensures that an InstitutionStudent record exists for an approved admission.
     *
     * If no matching student record is found, attempts to create one.
     * If creation fails, reverts the admission's status to its previous state.
     *
     * @param \Cake\ORM\Entity $entity The StudentAdmission entity
     * @author Khindol Madraimov <khindol.madraimov@gmail.com>
     * @since POCOR-9163
     **/
    private function ensureInstitutionStudentExists($entity): void
    {
        $statuses = self::getDynamicTableInstance('Workflow.WorkflowModels')
            ->getWorkflowStatusSteps('Institution.StudentAdmission', 'APPROVED');

        if (!in_array($entity->status_id, array_keys($statuses))) {
            return;
        }

        $InstitutionStudents = self::getDynamicTableInstance('Institution.InstitutionStudents');
        $conditions = [
            'student_id' => $entity->student_id,
            'academic_period_id' => $entity->academic_period_id,
            'start_date' => $entity->start_date,
            'end_date' => $entity->end_date,
        ];

        $existing = $InstitutionStudents->find()->where($conditions)->first();
        if ($existing) {
            return;
        }

        $studentEntity = $this->addInstitutionStudent($entity);
//        Log::debug(print_r([__FUNCTION__ => $entity], true));
//        Log::debug(print_r([__FUNCTION__ => $studentEntity], true));
        if ($studentEntity) {
            Log::info("InstitutionStudent created for student_id {$entity->student_id}");

        } else {
            Log::warning("Failed to create InstitutionStudent for student_id {$entity->student_id}");
            if (!empty($entity->previous('status_id'))) {
                $entity->status_id = $entity->previous('status_id');
                $this->save($entity);
                Log::info("Reverted status_id for admission ID {$entity->id} to previous value.");
            }
        }
    }

    public function addInstitutionStudent($entity)
    {
        $Students = self::getDynamicTableInstance('Institution.Students');
        $StudentStatuses = self::getDynamicTableInstance('Student.StudentStatuses');
        $statuses = $StudentStatuses->findCodeList();

        $incomingStudent = [
            'student_status_id' => $statuses['CURRENT'],
            'student_id' => $entity->student_id,
            'education_grade_id' => $entity->education_grade_id,
            'academic_period_id' => $entity->academic_period_id,
            'start_date' => $entity->start_date,
            'end_date' => $entity->end_date,
            'institution_id' => $entity->institution_id
        ];
        if (!empty($entity->institution_class_id)) {
            $incomingStudent['class'] = $entity->institution_class_id;
        }
        $newEntity = $Students->newEntity($incomingStudent);
        try{
            $Students->save($newEntity);
        } catch (\Exception $exception) {
            Log::debug($exception->getMessage());
        }
        if(!$newEntity->hasErrors()){
            return $newEntity;
        }else{
            return null;
        }
    }

    //POCOR-8434 Ends

    public function onCancel(Event $event, $id, Entity $workflowTransitionEntity)
    {
        $entity = $this->get($id);
        $Students = self::getDynamicTableInstance('Institution.Students');
        $StudentStatuses = self::getDynamicTableInstance('Student.StudentStatuses');
        $statuses = $StudentStatuses->findCodeList();

        $newStudentRecord = $Students->find()
            ->where([
                $Students->aliasField('institution_id') => $entity->institution_id,
                $Students->aliasField('student_id') => $entity->student_id,
                $Students->aliasField('academic_period_id') => $entity->academic_period_id,
                $Students->aliasField('education_grade_id') => $entity->education_grade_id,
                $Students->aliasField('student_status_id') => $statuses['CURRENT']
            ])
            ->first();

        if (!empty($newStudentRecord)) {
            // delete student record in the new institution
            $Students->delete($newStudentRecord);
        }
    }

    public function onTriggerPendingEnrolment(Event $event, $id, Entity $workflowTransitionEntity)
    {
        // add student into institution_students_enrolment
        $entity = $this->get($id);
        $this->triggerPendingEnrolmentForStudent($entity);
    }

    public function triggerPendingEnrolmentForStudent(Entity $entity)
    {
        $WorkflowsTbl = self::getDynamicTableInstance('Workflow.Workflows');
        $WorkflowStepsTbl = self::getDynamicTableInstance('Workflow.WorkflowSteps');
        $WorkflowsRes = $WorkflowStepsTbl
            ->find()
            ->innerJoin([$WorkflowsTbl->getAlias() => $WorkflowsTbl->getTable()],
                [
                    $WorkflowsTbl->aliasField('id = ') . $WorkflowStepsTbl->aliasField('workflow_id')
                ])
            ->where([
                $WorkflowsTbl->aliasField('code') => 'STUDENT-Enrolment-1001',
                $WorkflowStepsTbl->aliasField('name') => 'Open'
            ])->first();

        $StudentEnrolments = self::getDynamicTableInstance('Institution.StudentEnrolment');

        $enrolmentArr = [
            'start_date' => $entity->start_date,
            'end_date' => $entity->end_date,
            'student_id' => $entity->student_id,
            'status_id' => $WorkflowsRes->id,
            'assignee_id' => $this->Auth->user('id'),
            'institution_id' => $entity->institution_id,
            'academic_period_id' => $entity->academic_period_id,
            'education_grade_id' => $entity->education_grade_id,
            'test_score' => '',
            'interview_score' => '',
            'comment' => '',
        ];
        if (!empty($entity->institution_class_id)) {
            $enrolmentArr['institution_class_id'] = $entity->institution_class_id;
        } else {
            $enrolmentArr['institution_class_id'] = 'NULL';
        }

        $newEntity = $StudentEnrolments->newEntity($enrolmentArr);
        $StudentEnrolments->save($newEntity);
    }

    public function studentsAfterSave(Event $event, $student)
    {

        $StudentStatuses = self::getDynamicTableInstance('Student.StudentStatuses');
        $statusList = $StudentStatuses->findCodeList();
        $Enrolled = $statusList['CURRENT'];
        $Promoted = $statusList['PROMOTED'];
        $Graduated = $statusList['GRADUATED'];
        $Withdraw = $statusList['WITHDRAWN'];
        $institution_id = $student->institution_id;
        $student_id = $student->student_id;

        if ($student->isNew()) { // add
            // close other pending admission applications (in same education system) if the student is successfully enrolled in one school
            if ($student->student_status_id == $Enrolled) {
                $educationSystemId = $this->EducationGrades->getEducationSystemId($student->education_grade_id);
                $educationGradesToUpdate = $this->EducationGrades->getEducationGradesBySystem($educationSystemId);
                //POCOR-6500 starts

                self::assignStudentRoleGroup($institution_id, $student_id);//POCOR-7146

                //POCOR-6500 ends
                // get the first step in 'REJECTED' workflow statuses
                $workflowEntity = $this->getWorkflow($this->getRegistryAlias());
                $WorkflowModelsTable = self::getDynamicTableInstance('Workflow.WorkflowModels');
                $statuses = $WorkflowModelsTable->getWorkflowStatusSteps('Institution.StudentAdmission', 'REJECTED');
                ksort($statuses);
                $rejectedStatusId = key($statuses);
                $rejectedStatusEntity = $this->Statuses->get($rejectedStatusId);

                if (!empty($rejectedStatusEntity)) {
                    $doneStatus = self::DONE;
                    $pendingAdmissions = $this->find()
                        ->innerJoinWith($this->Statuses->getAlias(), function ($q) use ($doneStatus) {
                            return $q->where(['category <> ' => $doneStatus]);
                        })
                        ->where([
                            $this->aliasField('student_id') => $student->student_id,
                            $this->aliasField('education_grade_id IN') => $educationGradesToUpdate
                        ])
                        ->toArray();

                    foreach ($pendingAdmissions as $entity) {
                        $prevStep = $entity->status_id;

                        // update status_id and assignee_id
                        $entity->status_id = $rejectedStatusEntity->id;
                        $saved = false;
                        try {
                            $this->autoAssignAssignee($entity);
                            $this->save($entity);
                            $saved = true;
                        } catch (\Exception $exception) {
                            try {
                                $this->save($entity);
                                $saved = true;
                            } catch (\Exception $exception) {
                                $entity->assignee_id = $entity->created_user_id;
                                $this->save($entity);
                                $saved = true;
                            }
                        }
                        if ($saved) {
                            // add workflow transition
                            $WorkflowTransitions = self::getDynamicTableInstance('Workflow.WorkflowTransitions');
                            $prevStepEntity = $this->Statuses->get($prevStep);

                            $transition = [
                                'comment' => __('On Student Admission into another Institution'),
                                'prev_workflow_step_name' => $prevStepEntity->name,
                                'workflow_step_name' => $rejectedStatusEntity->name,
                                'workflow_action_name' => 'Administration - Reject Record',
                                'workflow_model_id' => $workflowEntity->workflow_model_id,
                                'model_reference' => $entity->id,
                                'created_user_id' => 1,
                                'created' => new FrozenTime('NOW')
                            ];
                            $transitionEntity = $WorkflowTransitions->newEntity($transition);
                            $WorkflowTransitions->save($transitionEntity);
                        }
                    }
                }
            }

        } else { // edit

            // to cater logic if during undo promoted / graduate (without immediate enrolled record), there is still pending admission / transfer
            if ($student->getDirty('student_status_id')) {
                $oldStatus = $student->getOriginal('student_status_id');
                $newStatus = $student->student_status_id;
                $UndoPromotion = $oldStatus == $Promoted && $newStatus == $Enrolled;
                $UndoGraduation = $oldStatus == $Graduated && $newStatus == $Enrolled;
                $UndoWithdraw = $oldStatus == $Withdraw && $newStatus == $Enrolled;

                if ($UndoPromotion || $UndoGraduation || $UndoWithdraw) {
                    $this->removePendingAdmission($student->student_id, $student->institution_id);
                }
            }
        }
    }

    /**
     * POCOR-7146
     * POCOR-7224 refactored
     * @author for refactioring Khindol Madraimov <khindol.madraimov@gmail.com>
     * assign Role and group to student while creating student
     **/
    private static function assignStudentRoleGroup($institution_id, $student_id)
    {
        // POCOR-8683 removed logs
        $student_role_id = self::getStudentSecurityRoleId();
        $security_group_id = self::getInstitutionSecurityGroupId($institution_id);
        //check student already exist
        $student_security_groups = self::getStudentSecurityGroups($student_id, $student_role_id);
        //Log::write('debug', $student_security_groups);
        //check that the student is not in other groups
        if (sizeof($student_security_groups) == 0) {
            //Log::write('debug', $student_id);
            //Log::write('debug', $security_group_id);
            //Log::write('debug', $student_role_id);
            self::createNewStudentSecurityGroup($student_id, $security_group_id, $student_role_id);
            return;
        }
        //update user's security_group_id in security_group_users table
        $previous_security_group_id = self::getPreviousSecurityGroupId($institution_id, $student_id);
        // Log::write('debug', $previous_security_group_id);
        //check that the student is should be transferred
        if (in_array($previous_security_group_id, $student_security_groups)) {
            self::makeStudentSecurityGroupTransfer($student_id, $security_group_id, $previous_security_group_id, $student_role_id);
            return;
        }
        //if he/she is not transferred - create new security group
        self::createNewStudentSecurityGroup($student_id, $security_group_id, $student_role_id);
        return;
    }

    /**
     * @return int
     * @author for refactioring Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private static function getStudentSecurityRoleId()
    {
        $securityRolesTbl = self::getDynamicTableInstance('Security.SecurityRoles');
        $securityRoles = $securityRolesTbl->find()
            ->where([
                $securityRolesTbl->aliasField('code') => 'STUDENT',
            ])->first();
        $student_role_id = $securityRoles->id;
        return $student_role_id;
    }

    /**
     * @param $institution_id
     * @return integer
     * @author for refactioring Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private static function getInstitutionSecurityGroupId($institution_id)
    {
        $institutionTbl = self::getDynamicTableInstance('Institution.Institutions');
        $security_group_id = null;
        $institutions = $institutionTbl->find()
            ->where([
                $institutionTbl->aliasField('id') => $institution_id
            ])->first();
        if (!empty($institutions)) {
            $security_group_id = $institutions->security_group_id;
        }
        if ($security_group_id != null) {
            $securityGroupInstitutionsTbl = self::getDynamicTableInstance('Security.SecurityGroupInstitutions');
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
                    'institution_id' => $institution_id,
                    'created_user_id' => 1,
                    'created' => new FrozenTime('NOW')
                ];
                $securityGroupInstitutionsEntity = $securityGroupInstitutionsTbl->newEntity($security_group_ins_data);
                $securityGroupInstitutionsTbl->save($securityGroupInstitutionsEntity);
            }
        }
        return $security_group_id;
    }

    /**
     * @param $student_id
     * @param $student_role_id
     * @return array
     * @author for refactioring Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private static function getStudentSecurityGroups($student_id, $student_role_id)
    {
        $securityGroupUsersTbl = self::getDynamicTableInstance('Security.SecurityGroupUsers');
        $countSecurityGroupStudent = $securityGroupUsersTbl->find('all')
            ->select('security_group_id')
            ->where([
                $securityGroupUsersTbl->aliasField('security_user_id') => $student_id,
                $securityGroupUsersTbl->aliasField('security_role_id') => $student_role_id
            ])
            ->extract('security_group_id')
            ->toArray();
        return $countSecurityGroupStudent;
    }

    /**
     * @param $student_id
     * @param $security_group_id
     * @param $student_role_id
     * @author for refactioring Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private static function createNewStudentSecurityGroup($student_id, $security_group_id, $student_role_id)
    {
        $id = Text::uuid();
        $securityGroupUsersTbl = self::getDynamicTableInstance('Security.SecurityGroupUsers');
        // POCOR-9100 start
        $presentCount = $securityGroupUsersTbl->find('all')
            ->where([
                $securityGroupUsersTbl->aliasField('security_group_id') => $security_group_id,
                $securityGroupUsersTbl->aliasField('security_user_id') => $student_role_id,
                $securityGroupUsersTbl->aliasField('security_role_id') => $student_id,
            ])->count();
        if ($presentCount > 0) {
            return $presentCount;
        }
        // POCOR-9100 end
        $security_group_data = [
            'id' => $id,
            'security_group_id' => $security_group_id,
            'security_user_id' => $student_id,
            'security_role_id' => $student_role_id,
            'created_user_id' => 1,
            'created' => new FrozenTime('NOW')
        ];
        $securityGroupUsersEntity = $securityGroupUsersTbl->newEntity($security_group_data);
        $newEntity = $securityGroupUsersTbl->save($securityGroupUsersEntity);
        return $newEntity;
    }

    /**
     * @param $institution_id
     * @param $student_id
     * @param $institutionTbl
     * @return mixed
     * @author for refactioring Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private static function getPreviousSecurityGroupId($institution_id, $student_id)
    {
        $previous_security_group_id = 0;
        $institutionTbl = self::getDynamicTableInstance('Institution.Institutions');
        $InstitutionStudentsTbl = self::getDynamicTableInstance('Institution.InstitutionStudents');
        $TransfersTbl = self::getDynamicTableInstance('Institution.InstitutionStudentTransfers');
        $StudentTransfers = $InstitutionStudentsTbl
            ->find()
            ->select([
                $InstitutionStudentsTbl->aliasField('student_id'),
                $TransfersTbl->aliasField('institution_id'),
                $TransfersTbl->aliasField('previous_institution_id')
            ])
            ->leftJoin([$TransfersTbl->getAlias() => $TransfersTbl->getTable()],
                [
                    $TransfersTbl->aliasField('student_id') . '=' . $student_id,
                    $TransfersTbl->aliasField('institution_id') => $institution_id
                ]
            )
            ->where([
                $InstitutionStudentsTbl->aliasField('student_id') => $student_id,
                $InstitutionStudentsTbl->aliasField('institution_id') => $institution_id,
                $InstitutionStudentsTbl->aliasField('student_status_id') => 1//for enrolled status
            ])
            ->first();
        if (!empty($StudentTransfers)) {
            if (!empty($StudentTransfers->institution_student_transfers['previous_institution_id'])) {
                $PreviousInstitutions = $institutionTbl->find()
                    ->where([
                        $institutionTbl->aliasField('id') => $StudentTransfers->institution_student_transfers['previous_institution_id']
                    ])
                    ->first();
                $previous_security_group_id = $PreviousInstitutions->security_group_id;
            }
        }
        return $previous_security_group_id;
    }

    /**
     * @param $student_id
     * @param $security_group_id
     * @param $previous_security_group_id
     * @param $student_role_id
     * @author for refactioring Khindol Madraimov <khindol.madraimov@gmail.com>
     */
    private static function makeStudentSecurityGroupTransfer($student_id, $security_group_id, $previous_security_group_id, $student_role_id)
    {
        $securityGroupUsersTbl = self::getDynamicTableInstance('Security.SecurityGroupUsers');
        $securityGroupUsersTbl->updateAll(
            [
                'security_group_id' => $security_group_id,
                'created' => new FrozenTime('NOW')
            ],
            [
                'security_group_id' => $previous_security_group_id,
                'security_user_id' => $student_id,
                'security_role_id' => $student_role_id
            ]
        );
    }

    protected function removePendingAdmission($studentId, $institutionId)
    {
        $StudentTransfers = self::getDynamicTableInstance('Institution.InstitutionStudentTransfers');
        $doneStatus = self::DONE;

        //remove all pending transfer requests
        //could not include grade / academic period because not always valid. (promotion/graduation/repeat and transfer/admission can be done on different grade / academic period)
        $pendingTransfers = $StudentTransfers->find()
            ->innerJoinWith($StudentTransfers->Statuses->getAlias(), function ($q) use ($doneStatus) {
                return $q->where(['category <> ' => $doneStatus]);
            })
            ->where([
                $StudentTransfers->aliasField('student_id') => $studentId,
                $StudentTransfers->aliasField('previous_institution_id') => $institutionId
            ])
            ->toArray();

        if (!empty($pendingTransfers)) {
            foreach ($pendingTransfers as $entity) {
                $StudentTransfers->delete($entity);
            }
        }

        //remove all pending admission requests
        $pendingAdmissions = $this->find()
            ->innerJoinWith($this->Statuses->getAlias(), function ($q) use ($doneStatus) {
                return $q->where(['category <> ' => $doneStatus]);
            })
            ->where([$this->aliasField('student_id') => $studentId])
            ->toArray();

        if (!empty($pendingAdmissions)) {
            foreach ($pendingAdmissions as $entity) {
                $this->delete($entity);
            }
        }
    }

    //POCOR-7738 start

    public function studentsAfterDelete(Event $event, Entity $student)
    {
        // check for enrolled status and delete admission record
        $this->removePendingAdmission($student->student_id, $student->institution_id);
    }

    //POCOR-7738 end

    public function onGetBreadcrumb(Event $event, ServerRequest $request, Component $Navigation, $persona)
    {
        // POCOR-8286 start
        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);

        $studentsUrl = [
            'plugin' => 'Institution',
            'controller' => 'Institutions',
            'action' => 'Students',
            0 => 'index',
            1 => $encodedQueryString
        ];
        // POCOR-8286 end
        $previousTitle = Inflector::humanize(Inflector::underscore($this->getAlias()));

        $Navigation->substituteCrumb($previousTitle, __('Students'), $studentsUrl);
        $Navigation->addCrumb($previousTitle);

    }

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $toolbarButtons = $extra['toolbarButtons'];
        $session = $this->request->getSession();
        $paramInstitutionId = $this->request->getAttribute('param')['institutionId'];
        $getInstitutionId = $this->getQueryString('institution_id');
        $institutionId = !empty($paramInstitutionId) ? $this->ControllerAction->paramsDecode($paramInstitutionId)['id'] : $getInstitutionId;

        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);
        if ($this->action == 'index') {
            $toolbarButtons['back']['label'] = '<i class="fa kd-back"></i>';
            $toolbarButtons['back']['attr'] = [
                'title' => __('Back'),
                'class' => 'btn btn-xs btn-default',
                'data-toggle' => 'tooltip',
                'data-placement' => 'bottom',
                'escape' => false
            ];
            $toolbarButtons['back']['url'] = [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'Students',
                0 => 'index',
                1 => $encodedQueryString
                //'institutionId' => $this->paramsEncode(['id' => $institutionId]),
            ];

        } elseif ($this->action == 'edit') {
            $toolbarButtons['back']['url'][0] = 'index';
            if ($toolbarButtons['back']['url']['controller'] == 'Dashboard') {
                $toolbarButtons['back']['url']['action'] = 'index';
                unset($toolbarButtons['back']['url'][0]);
            }

            unset($toolbarButtons['back']['url'][1]);
        }
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('comment', ['type' => 'hidden']);
        $this->field('start_date', ['type' => 'hidden']);
        $this->field('end_date', ['type' => 'hidden']);
        $this->setFieldOrder(['status_id', 'assignee_id', 'student_id', 'academic_period_id', 'education_grade_id', 'institution_class_id']);
        $this->addStudentsExtraButtons($extra['toolbarButtons']); // POCOR-9155

    }

    private function addStudentsExtraButtons($toolbarButtons1): void // POCOR-9155
    {
// back button
        // Generate encoded query string once
        $queryString = $this->getQueryString();
        $encodedQueryString = $this->paramsEncode($queryString);

// Common button attributes
        $baseBtnAttr = [
            'class' => 'btn btn-xs btn-default',
            'data-toggle' => 'tooltip',
            'data-placement' => 'bottom',
            'escape' => false,
        ];

// Add back button
        $toolbarButtons = $toolbarButtons1->getArrayCopy();
        $toolbarButtons['back'] = [
            'type' => 'button',
            'label' => '<i class="fa kd-back"></i>',
            'attr' => array_merge($baseBtnAttr, ['title' => __('Back')]),
            'url' => [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => 'Students',
                0 => 'index',
                1 => $encodedQueryString
            ]
        ];

// Define all extra toolbar buttons
        $extraButtons = [
            'add' => [
                'permission' => ['Institutions', 'Students', 'add'],
                'action' => 'Students',
                'icon' => '<i class="fa fa-plus"></i>',
                'title' => __('Add')
            ],
            'graduate' => [
                'permission' => ['Institutions', 'Promotion', 'add'],
                'action' => 'Promotion',
                'icon' => '<i class="fa kd-graduate"></i>',
                'title' => __('Promotion / Repeating / Graduation')
            ],
            'transfer' => [
                'permission' => ['Institutions', 'Transfer', 'add'],
                'action' => 'Transfer',
                'icon' => '<i class="fa kd-transfer"></i>',
                'title' => __('Transfer')
            ],
            'undo' => [
                'permission' => ['Institutions', 'Undo', 'add'],
                'action' => 'Undo',
                'icon' => '<i class="fa kd-undo"></i>',
                'title' => __('Undo')
            ],
        ];
        $extraButtons['bulkAdmission'] = [
            'permission' => ['Institutions', 'Students', 'add'],
            'action' => 'BulkStudentAdmission',
            'next_action' => 'edit',
            'icon' => '<i class="fa kd-transfer"></i>',
            'title' => __('Bulk Admission')
        ];

        foreach ($extraButtons as $key => $config) {
            if (!empty($config['external'])) {
                $toolbarButtons[$key] = [
                    'type' => 'link',
                    'label' => $config['icon'],
                    'attr' => array_merge($baseBtnAttr, [
                        'title' => $config['title'],
                        'target' => '_blank'
                    ]),
                    'url' => $config['url']
                ];
                continue;
            }

            if (!empty($config['permission']) &&
                !$this->AccessControl->check($config['permission'])) {
                continue;
            }

            $url = [
                'plugin' => 'Institution',
                'controller' => 'Institutions',
                'action' => $config['action'],
                0 => $config['next_action'] ?? 'add',
                1 => $encodedQueryString
            ];

            if (!empty($config['extraParams'])) {
                $url = array_merge($url, ['?' => $config['extraParams']]);
            }

            $toolbarButtons[$key] = [
                'type' => 'button',
                'label' => $config['icon'],
                'attr' => array_merge($baseBtnAttr, ['title' => $config['title']]),
                'url' => $url
            ];
        }

        $toolbarButtons1->exchangeArray($toolbarButtons);
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        // search
        $search = $this->getSearchKey();
        if (!empty($search)) {
            $nameConditions = $this->getNameSearchConditions(['alias' => 'Users', 'searchTerm' => $search]);
            $extra['OR'] = $nameConditions; // to be merged with auto_search 'OR' conditions
        }
    }

    // POCOR-9100 changed sending email proc

    public function editAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('student_id', ['type' => 'readonly', 'attr' => ['value' => $this->Users->get($entity->student_id)->name_with_id]]);
        $this->field('institution_id', ['type' => 'readonly', 'attr' => ['value' => $this->Institutions->get($entity->institution_id)->code_name]]);
        $this->field('academic_period_id', ['type' => 'readonly', 'attr' => ['value' => $this->AcademicPeriods->get($entity->academic_period_id)->name]]);
        $this->field('education_grade_id', ['type' => 'readonly', 'attr' => ['value' => $this->EducationGrades->get($entity->education_grade_id)->programme_grade_name]]);
        $this->field('institution_class_id', ['entity' => $entity]);
        $this->field('start_date', ['entity' => $entity]);
        $this->field('end_date', ['entity' => $entity]);
        $this->setFieldOrder(['student_id', 'academic_period_id', 'education_grade_id', 'institution_class_id', 'start_date', 'end_date', 'comment']);
    }

    /*
     * POCOR-9100 sending email about admission
     */

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $this->field('openemis_no');//POCOR-7738
        $this->setFieldOrder(['status_id', 'assignee_id', 'student_id', 'academic_period_id', 'education_grade_id', 'institution_class_id', 'start_date', 'end_date', 'comment']);
    }

    public function onGetStudentId(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('user')) {
            //POCOR-7738 start
            if (($this->request->getParam('pass'))[0] == "view") {
                $value = $entity->user->name;
            } else {
                $value = $entity->user->name_with_id;
            }
            //POCOR-7738 end
        }
        return $value;
    }

    public function onGetOpenemisNo(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('user')) {
            $value = $entity->user->openemis_no;
        }
        $encodedUserId = $this->paramsEncode(['id' => $entity->student_id]);
        $url = [
            'plugin' => 'Directory',
            'controller' => 'Directories',
            'action' => 'Directories',
            'view',
            $encodedUserId
        ];
        return $event->getSubject()->HtmlField->link($value, $url);
    }


    //POCOR-6925

    public function onUpdateFieldStartDate(Event $event, array $attr, $action, $request)
    {
        if ($action == 'edit') {
            $entity = $attr['entity'];

            $academicPeriodId = $entity->academic_period_id;
            $periodStartDate = $this->AcademicPeriods->get($academicPeriodId)->start_date;
            $periodEndDate = $this->AcademicPeriods->get($academicPeriodId)->end_date;

            $attr['type'] = 'date';
            $attr['date_options'] = [
                'startDate' => $periodStartDate->format('d-m-Y'),
                'endDate' => $periodEndDate->format('d-m-Y'),
                'todayBtn' => false
            ];
            return $attr;
        }
    }

    public function onUpdateFieldEndDate(Event $event, array $attr, $action, $request)
    {
        if ($action == 'edit') {
            $endDate = $attr['entity']->end_date;
            $attr['type'] = 'readonly';
            $attr['value'] = $endDate->format('d-m-Y');
            $attr['attr']['value'] = $endDate->format('d-m-Y');
            return $attr;
        }
    }

    public function onUpdateFieldInstitutionClassId(Event $event, array $attr, $action, $request)
    {
        if ($action == 'edit') {
            $entity = $attr['entity'];
            $Classes = self::getDynamicTableInstance('Institution.InstitutionClasses');

            $options = $Classes->find('list')
                ->innerJoinWith('ClassGrades')
                ->where([
                    $Classes->aliasField('institution_id') => $entity->institution_id,
                    $Classes->aliasField('academic_period_id') => $entity->academic_period_id,
                    'ClassGrades.education_grade_id' => $entity->education_grade_id
                ])
                ->toArray();

            $attr['type'] = 'select';
            $attr['options'] = $options;
            return $attr;
        }
    }

    /**
     *
     * Ensures that the `start_date` and `end_date` of the admission entity fall within
     * the defined academic period. If either date is missing or invalid (i.e., outside
     * the academic period boundaries), the method auto-corrects the values to appropriate defaults:
     *
     * - `start_date` is set to today if within the academic period, otherwise to the period's start date.
     * - `end_date` is always set to the academic period's end date if missing or invalid.
     *
     * This logic enforces date consistency for admissions relative to their academic period.
     *
     * @author Khindol Madraimov <khindol.madraimov@gmail.com>
     * @since POCOR-9163
     */
    public function beforeSave($event, Entity $entity, ArrayObject $options)
    {
        $entity = $this->checkStartEndDates($entity);
        return $entity;
    }

    /**
     * @param Entity $entity
     * @return Entity
     */
    private function checkStartEndDates(Entity $entity): Entity
    {
        if (!empty($entity->academic_period_id)) {
            $academicPeriodId = $entity->academic_period_id;
            if ($this->AcademicPeriods->exists([$this->AcademicPeriods->getPrimaryKey() => $academicPeriodId])) {
                $academicPeriod = $this->AcademicPeriods->get($academicPeriodId);
                $periodStartDate = $academicPeriod->start_date;
                $periodEndDate = $academicPeriod->end_date;

                $today = FrozenDate::today();

                // START DATE
                if (
                    empty($entity->start_date) ||
                    $entity->start_date < $periodStartDate ||
                    $entity->start_date > $periodEndDate
                ) {
                    $entity->start_date = ($today >= $periodStartDate && $today <= $periodEndDate)
                        ? $today
                        : $periodStartDate;
                }

                // END DATE
                if (
                    empty($entity->end_date) ||
                    $entity->end_date < $periodStartDate ||
                    $entity->end_date > $periodEndDate
                ) {
                    $entity->end_date = $periodEndDate;
                }
            }
        }
        return $entity;
    }

    public function beforeMarshal(Event $event, ArrayObject $data, ArrayObject $options)
    {
        //this is meant to force gender_id validation
        $data = $this->checkGender($data);

        // For third party restful call
        if ($data->offsetExists('action_type') && $data['action_type'] == 'third_party') {
            if ($data->offsetExists('academic_period_id')) {
                if ($this->AcademicPeriods->exists([$this->AcademicPeriods->getPrimaryKey() => $data['academic_period_id']])) {
                    $data['end_date'] = $this->AcademicPeriods->get($data['academic_period_id'])->end_date->format('Y-m-d');
                }
            }

            if (!$data->offsetExists('institution_class_id')) {
                $data['institution_class_id'] = null;
            }

            $data['status_id'] = WorkflowBehavior::STATUS_OPEN;
            $data['assignee_id'] = WorkflowBehavior::AUTO_ASSIGN;
        }
        //POCOR-7716 start(setting default admission status during import)
        if ($data['action_type'] == 'imported') {
            //getting default value of student admission status
            $ConfigItems = self::getDynamicTableInstance('Configuration.ConfigItems');
            $configItemResult = $ConfigItems->find()->where([
                $ConfigItems->aliasField('code') => "student_admission_status"
            ])->first();
            $studentStatus = !empty($configItemResult->value) ? $configItemResult->value : $configItemResult->default_value;
            $workflows = self::getDynamicTableInstance('Workflow.Workflows');
            $workflowSteps = self::getDynamicTableInstance('Workflow.WorkflowSteps');
            $workflowResults = $workflows->find()
                ->select(['workflowSteps_id' => $workflowSteps->aliasField('id')])
                ->LeftJoin([$workflowSteps->getAlias() => $workflowSteps->getTable()], [
                    $workflowSteps->aliasField('workflow_id =') . $workflows->aliasField('id'),
                    $workflowSteps->aliasField('name') => 'Approved'
                ])
                ->where([
                    $workflows->aliasField('name') => 'Student Admission'
                ])
                ->first();
            if ($studentStatus == 0) {// for enrolled
                $data['status_id'] = $workflowResults->workflowSteps_id;
            } else {// for other statuses
                $data['status_id'] = $workflowSteps->get($studentStatus)->id;
            }
            //POCOR-7716 end
        }
    }

    /**
     * @param ArrayObject $data
     * @return ArrayObject
     */
    private function checkGender(ArrayObject $data): ArrayObject
    {
        if ($data->offsetExists('student_id') && !empty($data['student_id'])) {
            if ($this->Users->exists([$this->Users->getPrimaryKey() => $data['student_id']])) {
                $studentId = $data['student_id'];

                if (!$data->offsetExists('gender_id')) {
                    $query = $this->Users->get($studentId);
                    $data['gender_id'] = $query->gender_id;
                }
            }
        }
        return $data;
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options): void
    {
        if ($entity->isNew()) {
            if ($entity->has('action_type') && $entity->action_type == 'imported') { // Import logic
                $WorkflowActions = TableRegistry::get('Workflow.WorkflowActions');
                $triggeringStep = $WorkflowActions->getEventTriggeringStep('Institution.StudentAdmission', 'Workflow.onApprove');

                if (!empty($triggeringStep) && $entity->status_id == $triggeringStep) {
                    if ($this->save($entity)) {
                        $this->addInstitutionStudent($entity);
                    }
                }
            } else if ($entity->has('action_type') && $entity->action_type == 'default') { // AngularJs logic
                // auto approve admission and add student into the institution
                $superAdmin = Hash::get($_SESSION['Auth'], 'User.super_admin');
                $executePermission = isset($_SESSION['Permissions']) && Hash::check($_SESSION['Permissions'], 'Institutions.StudentAdmission.execute');

                // creator must be admin or have 'Student Admission -> Execute' permission
                if ($superAdmin || $executePermission) {
                    $workflowEntity = $this->getWorkflow($this->getRegistryAlias());

                    // get the first step in 'APPROVED' workflow statuses
                    $WorkflowModelsTable = self::getDynamicTableInstance('Workflow.WorkflowModels');
                    $statuses = $WorkflowModelsTable->getWorkflowStatusSteps('Institution.StudentAdmission', 'APPROVED');
                    ksort($statuses);
                    $approvedStatusId = key($statuses);
                    $approvedStatusEntity = $this->Statuses->get($approvedStatusId);

                    if (!empty($approvedStatusEntity)) {
                        $prevStepEntity = $this->Statuses->get($entity->status_id);

                        // update status_id and assignee_id of admission record
                        $entity->status_id = $approvedStatusEntity->id;
                        $saved = false;
                        try {
                            $this->autoAssignAssignee($entity);
                            $this->save($entity);
                            $saved = true;
                        } catch (\Exception $exception) {
                            try {
                                $this->save($entity);
                                $saved = true;
                            } catch (\Exception $exception) {
                                $entity->assignee_id = $entity->created_user_id;
                                $this->save($entity);
                                $saved = true;
                            }
                        }
                        if ($saved) {
                            // add workflow transition
                            $transition = [
                                'comment' => __('On Auto Approve Student Admission'),
                                'prev_workflow_step_name' => $prevStepEntity->name,
                                'workflow_step_name' => $approvedStatusEntity->name,
                                'workflow_action_name' => 'Administration - Approve Record',
                                'workflow_model_id' => $workflowEntity->workflow_model_id,
                                'model_reference' => $entity->id,
                                'created_user_id' => 1,
                                'created' => new FrozenTime('NOW')
                            ];

                            $WorkflowTransitions = self::getDynamicTableInstance('Workflow.WorkflowTransitions');
                            $transitionEntity = $WorkflowTransitions->newEntity($transition);
                            $WorkflowTransitions->save($transitionEntity);
                        }
                    }
                }
            }
        }
        $this->sendStudentAdmissionAlert($entity);
        $this->ensureInstitutionStudentExists($entity);
    }

    private function sendStudentAdmissionAlert($entity)
    {
        if (property_exists($entity, 'modified_user_id') && $entity->modified_user_id) {
            $userId = $entity->modified_user_id;
        } else {
            $userId = $entity->created_user_id;
        }

        $alertsTable = self::getDynamicTableInstance('Alert.Alerts');
        $alertRulesTable = self::getDynamicTableInstance('Alert.AlertRules');
        $systemProcessesTable = self::getDynamicTableInstance('SystemProcesses');


        $alert = $alertsTable
            ->find()
            ->where([$alertsTable->aliasField('process_name') => 'AlertStudentAdmission',
                $alertsTable->aliasField('frequency') => 'Once'])
            ->first();
        if (!$alert) {
            Log::debug('No Alerts for AlertStudentAdmission');
            return;
        }
        if (!is_array($alert)) {
            $alert = $alert->toArray();
        }
        $activeRules = $alertRulesTable->find()
            ->where([
                $alertRulesTable->aliasField('feature') => $alert['name'],
                $alertRulesTable->aliasField('enabled') => 1
            ])
            ->toArray();

//        return;
        foreach ($activeRules as $rule) {
            if (!is_array($rule)) {
                $rule = $rule->toArray();
            }
            DashboardController::triggerSystemProcess($systemProcessesTable, $rule, $alert['process_name'], $userId, ['admission_id' => (int) $entity->id, 'status_id' => (int) $entity->status_id]);
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
            ->contain([$this->Users->getAlias(), $this->Institutions->getAlias(), $this->CreatedUser->getAlias(), 'Assignees'])
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
                        'action' => 'StudentAdmission',
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
                    $row['request_title'] = sprintf(__('Admission of student %s'), $row->user->name_with_id);
                    $row['institution'] = $row->institution->code_name;
                    $row['received_date'] = $receivedDate;
                    $row['requester'] = $row->created_user->name_with_id;

                    return $row;
                });
            });

        return $query;
    }

    public function findByQueue(Query $query, array $options)
    {
        $classId = isset($options['institution_class_id']) ? $options['institution_class_id'] : null;
        $conditions = [];

        if (!is_null($classId)) {
            $query
                ->select([
                    'pending_queue' => $query->func()->count('StudentAdmission.id')
                ])
                ->where([
                    $this->aliasField('institution_class_id') => $classId
                ]);

            $results = $query->first();

            if (!empty($results->pending_queue)) {
                $query
                    ->contain([
                        'InstitutionClasses' => [
                            'fields' => [
                                'name',
                                'capacity',
                                'total_male_students',
                                'total_female_students'
                            ]
                        ]
                    ])
                    ->formatResults(function (ResultSetInterface $results) {
                        return $results->map(function ($row) {
                            $row['institution_class']['vacancy'] = $row['institution_class']['capacity'] - ($row['institution_class']['total_male_students'] + $row['institution_class']['total_female_students']);
                            return $row;
                        });
                    });
            }
        } else {
            $query->where(['1=0']);
        }
        return $query;
    }

    public function onUpdateFieldAssigneeId(Event $event, array $attr, $action, ServerRequest $request)
    {
        if ($action == 'add' || $action == 'edit') {
            $workflowModel = 'Institutions > Students > Student Admission';
            $workflowModelsTable = self::getDynamicTableInstance('Workflow.WorkflowModels');
            $workflowStepsTable = self::getDynamicTableInstance('Workflow.WorkflowSteps');
            $Workflows = self::getDynamicTableInstance('Workflow.Workflows');
            $workModelId = $Workflows
                ->find()
                ->select(['id' => $workflowModelsTable->aliasField('id'),
                    'workflow_id' => $Workflows->aliasField('id'),
                    'is_school_based' => $workflowModelsTable->aliasField('is_school_based')])
                ->LeftJoin([$workflowModelsTable->getAlias() => $workflowModelsTable->getTable()],
                    [
                        $workflowModelsTable->aliasField('id') . ' = ' . $Workflows->aliasField('workflow_model_id')
                    ])
                ->where([$workflowModelsTable->aliasField('name') => $workflowModel])->first();
            $workflowId = $workModelId->workflow_id;
            $isSchoolBased = $workModelId->is_school_based;
            $workflowStepsOptions = $workflowStepsTable
                ->find()
                ->select([
                    'stepId' => $workflowStepsTable->aliasField('id'),
                ])
                ->where([$workflowStepsTable->aliasField('workflow_id') => $workflowId])
                ->first();
            $stepId = $workflowStepsOptions->stepId;
            /*$session = $request->getSession();
            if ($session->check('Institution.Institutions.id')) {
                $institutionId = $session->read('Institution.Institutions.id');
            }*/
            $institutionId = $getInstitutionId = $this->getQueryString('institution_id');
            $assigneeOptions = [];
            if (!is_null($stepId)) {
                $WorkflowStepsRoles = self::getDynamicTableInstance('Workflow.WorkflowStepsRoles');
                $stepRoles = $WorkflowStepsRoles->getRolesByStep($stepId);
                if (!empty($stepRoles)) {
                    $SecurityGroupUsers = self::getDynamicTableInstance('Security.SecurityGroupUsers');
                    $Areas = self::getDynamicTableInstance('Area.Areas');
                    $Institutions = self::getDynamicTableInstance('Institution.Institutions');
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

    public function getModelAlertData($threshold)
    {
        $dayBefore = $threshold['value'];
        $workflowCategory = $threshold['workflow_steps'];
        $sqlConditions = [
            1 => ('DATEDIFF( NOW(),InstitutionCases.created)' . '>' . $dayBefore), // before
        ];
        $caseResults = $this->find()
            ->contain(['Institutions', 'Assignees', 'Statuses'])
            ->where([
                $this->Statuses->aliasField('id In') => $workflowCategory,
                $this->aliasField('modified is null'),
                $this->aliasField('modified_user_id is null'),
                $sqlConditions
            ]);
        return $caseResults->toArray();
    }

}
