<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\I18n\Time;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\ResultSet;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;
use Cake\Validation\Validator;
use Cake\Chronos\Date;
use Cake\Datasource\ResultSetInterface;
use Cake\Core\Configure;
use Cake\Log\Log;

use App\Model\Table\ControllerActionTable;

class StudentsTable extends ControllerActionTable
{
    const PENDING_TRANSFERIN = -1;
    const PENDING_TRANSFEROUT = -2;
    const PENDING_ADMISSION = -3;
    const PENDING_WITHDRAW = -4;
    const IN_QUEUE = -10;

    private $dashboardQuery = null;
    // POCOR-6129 custome fields code
    private $_dynamicFieldName = 'custom_field_data';
    // POCOR-6129 custome fields code

    public function initialize(array $config)
    {
        $this->table('institution_students');
        parent::initialize($config);

        // Associations
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('PreviousInstitutionStudents', ['className' => 'Institution.Students', 'foreignKey' => 'previous_institution_student_id']);
        /* $this->hasMany('StudentGuardians',['className' => 'StudentGuardian.StudentGuardians', 'foreignKey' => 'student_id']); */

        // Behaviors
        $this->addBehavior('Year', ['start_date' => 'start_year', 'end_date' => 'end_year']);
        $this->addBehavior('AcademicPeriod.Period');
        $this->addBehavior('User.User');
        $this->addBehavior('User.AdvancedNameSearchStudent');//POCOR-6647 using copy behavior of AdvancedNameSearchBehavior
        $this->addBehavior('Institution.StudentCascadeDelete'); // for cascade delete on student related tables from an institution
        $this->addBehavior('AcademicPeriod.AcademicPeriod'); // to make sure it is compatible with v4
        $this->addBehavior('User.MoodleCreateUser');

        $this->addBehavior('Excel', [
            'excludes' => ['start_year', 'end_year', 'previous_institution_student_id'],
            'pages' => ['index'],
            'autoFields' => false
        ]);

        $this->addBehavior('HighChart', [
            'student_attendance' => [
                '_function' => 'getNumberOfStudentsByAttendanceType',
                '_defaultColors' => false,
                'chart' => ['type' => 'column', 'borderWidth' => 1],
                'xAxis' => ['title' => ['text' => __('Education')]],
                'yAxis' => ['title' => ['text' => __('Total')]]
            ],
            'number_of_students_by_year' => [
                '_function' => 'getNumberOfStudentsByYear',
                '_defaultColors' => false,
                'chart' => ['type' => 'column', 'borderWidth' => 1],
                'xAxis' => ['title' => ['text' => __('Years')]],
                'yAxis' => ['title' => ['text' => __('Total')]]
            ],
            'number_of_students_by_stage' => [
                '_function' => 'getNumberOfStudentsByStage',
                '_defaultColors' => false,
                'chart' => ['type' => 'column', 'borderWidth' => 1],
                'xAxis' => ['title' => ['text' => __('Education')]],
                'yAxis' => ['title' => ['text' => __('Total')]]
            ],
            'institution_student_gender' => [
                '_function' => 'getNumberOfStudentsByGender',
                '_defaultColors' => false,
            ],
            'institution_student_age' => [
                '_function' => 'getNumberOfStudentsByAge'
            ],
            'institution_class_student_grade' => [
                '_function' => 'getNumberOfStudentsByGradeByInstitution'
            ]
        ]);
        $this->addBehavior('Import.ImportLink', ['import_model' => 'ImportStudentAdmission']);

        /**
         * Advance Search Types.
         * AdvanceSearchBehavior must be included first before adding other types of advance search.
         * If no "belongsTo" relation from the main model is needed, include its foreign key name in AdvanceSearch->exclude options.
         */
        $advancedSearchFieldOrder = [
            'first_name', 'middle_name', 'third_name', 'last_name',
            'contact_number', 'identity_type', 'identity_number'
        ];

        $this->addBehavior('AdvanceSearch', [
            'exclude' => [
                'student_id',
                'institution_id',
                'education_grade_id',
                'academic_period_id',
                'student_status_id',
                'previous_institution_student_id'
            ],
            'order' => $advancedSearchFieldOrder
        ]);

        $this->addBehavior('User.AdvancedIdentitySearch', [
            'associatedKey' => $this->aliasField('student_id')
        ]);
        $this->addBehavior('User.AdvancedContactNumberSearch', [
            'associatedKey' => $this->aliasField('student_id')
        ]);
        $this->addBehavior('User.AdvancedSpecificNameTypeSearch', [
            'modelToSearch' => $this->Users
        ]);
        /**
         * End Advance Search Types
         */
        $this->addBehavior('ControllerAction.Image'); // To be verified
        if (!in_array('Risks', (array)Configure::read('School.excludedPlugins'))) {
            $this->addBehavior('Risk.Risks');
        }

        $this->addBehavior('Restful.RestfulAccessControl', [
            'InstitutionStudents' => ['add']
        ]);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Model.InstitutionStudentRisks.calculateRiskValue'] = 'institutionStudentRiskCalculateRiskValue';
        $events['ControllerAction.Model.getSearchableFields'] = ['callable' => 'getSearchableFields', 'priority' => 5];
        return $events;
    }

    public function getSearchableFields(Event $event, ArrayObject $searchableFields)
    {
        $searchableFields[] = 'student_id';
        $searchableFields[] = 'openemis_no';
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);

        $validator
            ->add('start_date', 'ruleCompareDate', [
                'rule' => ['compareDate', 'end_date', false]
            ])
            ->add('end_date', [
            ])
            ->add('student_status_id', [
            ])
            ->add('academic_period_id', [
            ])
            ->allowEmpty('student_name')
            ->add('student_name', 'ruleStudentNotEnrolledInAnyInstitutionAndSameEducationSystem', [
                'rule' => ['studentNotEnrolledInAnyInstitutionAndSameEducationSystem', []],
                'on' => 'create',
                'last' => true
            ])
            ->add('student_name', 'ruleStudentNotCompletedGrade', [
                'rule' => ['studentNotCompletedGrade', []],
                'on' => 'create',
                'last' => true
            ])
            ->add('student_name', 'ruleCheckAdmissionAgeWithEducationCycleGrade', [
                'rule' => ['checkAdmissionAgeWithEducationCycleGrade'],
                'on' => 'create'
            ])
            ->allowEmpty('class')
            ->add('class', 'ruleClassMaxLimit', [
                'rule' => ['checkInstitutionClassMaxLimit'],
                'on' => function ($context) {
                    return (!empty($context['data']['class']) && $context['newRecord']);
                }
            ])
            ->add('gender_id', 'rulecompareStudentGenderWithInstitution', [
                'rule' => ['compareStudentGenderWithInstitution']
            ])
            ->add('education_grade_id', 'ruleCheckProgrammeEndDate', [
                'rule' => ['checkProgrammeEndDate', 'education_grade_id'],
                'on' => 'create'
            ])
            ->add('start_date', 'ruleCheckProgrammeEndDateAgainstStudentStartDate', [
                'rule' => ['checkProgrammeEndDateAgainstStudentStartDate', 'start_date'],
                'on' => 'create'
            ])
            ;
        return $validator;
    }

    // to be verified
    public function validationNewStudent(Validator $validator)
    {
        $validator = $this->validationDefault($validator);
        $validator = $validator->remove('student_name');
        return $validator;
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $institutionId = $this->Session->read('Institution.Institutions.id');

        $Classes = TableRegistry::get('Institution.InstitutionClasses');
        $ClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
        $periodId = $this->request->query['academic_period_id'];
        $StudentGuardians = TableRegistry::get('Student.StudentGuardians');
        $GuardianRelations = TableRegistry::get('Student.GuardianRelations');
        $IdentityTypes = TableRegistry::get('FieldOption.IdentityTypes');
        $UserIdentities = TableRegistry::get('User.Identities');
        $ContactTypes = TableRegistry::get('FieldOption.ContactTypes');
        $UserContact = TableRegistry::get('User.Contacts');
        $GuardianContact = TableRegistry::get('User.Contacts');
        $areaAdministrative = TableRegistry::get('area_administratives');
        $StudentStatuses = TableRegistry::get('student_statuses');
        /*POCOR-6562 starts*/
        if ($periodId > 0) {
            $condition[$this->aliasField('academic_period_id')] = $periodId;
        }
        $query
            ->contain([
                'Users.Genders',
                'Institutions',
                'StudentStatuses',
                'AcademicPeriods',
                'Users.MainNationalities'
            ])
            ->select([
                'student_id' => 'Users.id',
                'openemis_no' => 'Users.openemis_no',
                'identity_number' => 'Users.identity_number',
                'username' => 'Users.username',
                'gender_name' => 'Genders.name',
                'birthplace_area' => $areaAdministrative->aliasField('name'),
                'address' => 'Users.address',
                'date_of_birth' => 'Users.date_of_birth',
                'code' => 'Institutions.code',
                'institution_name' => 'Institutions.name',
                'student_status' => 'StudentStatuses.name',
                'student_status_ID' => 'StudentStatuses.id', //POCOR-7011
                'academic_period' => 'AcademicPeriods.name',
                'previous_institution_student_id' => $this->aliasField('previous_institution_student_id'),
                'student_first_name' => 'Users.first_name',
                'student_middle_name' => 'Users.middle_name',
                'student_third_name' => 'Users.third_name',
                'student_last_name' => 'Users.last_name',
                'nationalities' => 'MainNationalities.name',
                'guardian_relation' => 'GuardianRelations.name',
                'guardian_name' => $this->Users->find()->func()->concat([
                    'GuardianUser.first_name' => 'literal',
                    "  ",
                    'GuardianUser.last_name' => 'literal'
                ]),
                'guardian_contact' => 'GuardianCont.value',
                'identity_type' => $IdentityTypes->aliasField('name'),
                'identity_number' => $UserIdentities->aliasField('number'),
                'contact_type' => $ContactTypes->aliasField('name'),
                'contact_number' => $UserContact->aliasField('value'),
                'institution_id' => 'Institutions.id',//POCOR-6338
            ])
            ->leftJoin([$StudentGuardians->alias() => $StudentGuardians->table()], [
                $StudentGuardians->aliasField('student_id = ') . $this->aliasField('student_id')
            ])
            ->leftJoin([$this->Users->alias() => $this->Users->table()], [
                $this->Users->aliasField('id = ') . $this->aliasField('student_id')
            ])
            ->leftJoin(['GuardianUser' => 'security_users'], [
                'GuardianUser.id = '.$StudentGuardians->aliasField('guardian_id')
            ])
            ->leftJoin([$GuardianRelations->alias() => $GuardianRelations->table()], [
                $GuardianRelations->aliasField('id = ') . $StudentGuardians->aliasField('guardian_relation_id')
            ])
            ->leftJoin(['GuardianCont' => $GuardianContact->table()], [
                'GuardianCont.security_user_id = '. $StudentGuardians->aliasField('guardian_id')
            ])
            ->leftJoin([$UserIdentities->alias() => $UserIdentities->table()], [
                $UserIdentities->aliasField('security_user_id = ') . $this->Users->aliasField('id')
            ])
            ->leftJoin([$IdentityTypes->alias() => $IdentityTypes->table()], [
                $IdentityTypes->aliasField('id = ') . $UserIdentities->aliasField('identity_type_id')
            ])
            ->leftJoin([$UserContact->alias() => $UserContact->table()], [
                $UserContact->aliasField('security_user_id = ') . $this->Users->aliasField('id')
            ])
            ->leftJoin([$areaAdministrative->alias() => $areaAdministrative->table()], [
                $areaAdministrative->aliasField('id = ') . $this->Users->aliasField('birthplace_area_id')
            ])
            ->leftJoin([$ContactTypes->alias() => $ContactTypes->table()], [
                $ContactTypes->aliasField('id = ') . $UserContact->aliasField('contact_type_id')
            ])// POCOR-6338 starts
            ->leftJoin([$StudentStatuses->alias() => $StudentStatuses->table()], [
                $StudentStatuses->aliasField('id != ') => 3
            ])
            ->where([
                $this->aliasField('institution_id') => $institutionId,
                $condition
            ])
            ->group($this->aliasField('student_id'))
            ->order([$this->aliasField('created') => 'DESC']);

            // print($query);die;
        /*POCOR-6562 ends*/
        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) use($periodId, $institutionId, $Classes, $ClassStudents) {
            return $results->map(function ($row) use($periodId, $institutionId, $Classes, $ClassStudents) {
                // POCOR-6338 starts
                $Users = TableRegistry::get('security_users');
                /*POCOR-6543 starts*/
                $InstitutionStudents = TableRegistry::get('institution_students');
                $InstitutionStudentsCurrentData = $this
                    ->find()
                    ->contain(['StudentStatuses', 'EducationGrades'])
                    ->select([
                        $this->aliasField('id'),
                        $this->aliasField('student_status_id'),
                        $this->aliasField('previous_institution_student_id'),
                        'education_grade' => 'EducationGrades.name',
                        'class_name' => $Classes->aliasField('name')
                    ])
                    ->leftJoin([$ClassStudents->alias() => $ClassStudents->table()], [
                        $ClassStudents->aliasField('student_id = ') . $this->aliasField('student_id'),
                        $ClassStudents->aliasField('institution_id = ') . $this->aliasField('institution_id'),
                        $ClassStudents->aliasField('education_grade_id = ') . $this->aliasField('education_grade_id'),
                        $ClassStudents->aliasField('student_status_id = ') . $this->aliasField('student_status_id'),
                        $ClassStudents->aliasField('academic_period_id = ') . $this->aliasField('academic_period_id')
                    ])
                    ->leftJoin([$Classes->alias() => $Classes->table()], [
                        $Classes->aliasField('id = ') . $ClassStudents->aliasField('institution_class_id')
                    ])
                    ->where([
                        $this->aliasField('student_id') => $row->student_id,
                        $this->aliasField('institution_id') => $institutionId,
                        $this->aliasField('academic_period_id') => $periodId
                    ])
                    ->order([$this->aliasField('created') => 'DESC'])
                    ->autoFields(true)
                    ->first();

                    //POCOR-6457[START]
                    $UserIdentities = TableRegistry::get('user_identities');
                    $IdentityType = TableRegistry::get('FieldOption.IdentityTypes');
                    $identity = $IdentityType->getDefaultEntity();
                    $UserIdentitiesData = $UserIdentities
                    ->find()
                        ->select([
                            $UserIdentities->aliasField('number')
                        ])
                        ->where([
                            $UserIdentities->aliasField('identity_type_id') => $identity->id,
                            $UserIdentities->aliasField('security_user_id') => $row->student_id
                        ])
                        ->first();
                    //POCOR-6457[END]
                
                    if (!empty($InstitutionStudentsCurrentData->previous_institution_student_id)) {
                        $previousInstStdId = $this
                                            ->find()
                                            ->select([
                                                $this->aliasField('id'),
                                                $this->aliasField('student_status_id')
                                            ])
                                            ->where([
                                                $this->aliasField('student_id') => $row->student_id,
                                                $this->aliasField('id') => $InstitutionStudentsCurrentData->previous_institution_student_id
                                            ])->first();
                        if (!empty($previousInstStdId) && $previousInstStdId->student_status_id == 8 && $row->student_status_ID == 1) { //POCOR-7011
                            $row['student_status'] = "Enrolled (Repeater)";
                        } /*POCOR-6562 starts*/else {
                            $row['student_status'] = $InstitutionStudentsCurrentData->student_status->name;
                        }/*POCOR-6562 ends*/
                    }

                $row['education_grade'] = $InstitutionStudentsCurrentData->education_grade->name;
                $row['start_date'] = $InstitutionStudentsCurrentData->start_date;
                $row['end_date']   = $InstitutionStudentsCurrentData->end_date;
                $row['class_name'] = $InstitutionStudentsCurrentData->class_name;
                //POCOR-6457[START]
                $row['default_identity_number'] = (!empty($UserIdentitiesData)) ? $UserIdentitiesData->number: '';
                //POCOR-6457[END]
                /*POCOR-6543 ends*/               
                // POCOR-6129 custome fields code
                $Guardians = TableRegistry::get('student_custom_field_values');
                $studentCustomFieldOptions = TableRegistry::get('student_custom_field_options');
                $studentCustomFields = TableRegistry::get('student_custom_fields');

                $guardianData = $Guardians->find()
                ->select([
                    'id'                             => $Guardians->aliasField('id'),
                    'student_id'                     => $Guardians->aliasField('student_id'),
                    'student_custom_field_id'        => $Guardians->aliasField('student_custom_field_id'),
                    'text_value'                     => $Guardians->aliasField('text_value'),
                    'number_value'                   => $Guardians->aliasField('number_value'),
                    'decimal_value'                  => $Guardians->aliasField('decimal_value'),
                    'textarea_value'                 => $Guardians->aliasField('textarea_value'),
                    'date_value'                     => $Guardians->aliasField('date_value'),
                    'time_value'                     => $Guardians->aliasField('time_value'),
                    'checkbox_value_text'            => $studentCustomFieldOptions->aliasField('name'),
                    'question_name'                  => 'studentCustomField.name',
                    'field_type'                     => 'studentCustomField.field_type',
                    'field_description'              => 'studentCustomField.description',
                    'question_field_type'            => 'studentCustomField.field_type',
                ])->leftJoin(
                    ['studentCustomField' => 'student_custom_fields'],
                    [
                        'studentCustomField.id = '.$Guardians->aliasField('student_custom_field_id')
                    ]
                ) ->leftJoin(
                    [$studentCustomFieldOptions->alias() => $studentCustomFieldOptions->table()],
                    [
                        $studentCustomFieldOptions->aliasField('student_custom_field_id') => $Guardians->aliasField('student_custom_field_id')
                    ]
                )
                ->where([
                    $Guardians->aliasField('student_id') => $row['student_id'],
                ])->toArray();

                $existingCheckboxValue = '';
                foreach ($guardianData as $guadionRow) {
                    $fieldType = $guadionRow->field_type;
                    if ($fieldType == 'TEXT') {
                        $row[$this->_dynamicFieldName.'_'.$guadionRow->student_custom_field_id] = $guadionRow->text_value;
                    } else if ($fieldType == 'CHECKBOX') {
                        $Guardians_data_for_checkbox = TableRegistry::get('student_custom_field_values');
                        $studentCustomFieldOptions_for_checkbox = TableRegistry::get('student_custom_field_options');

                        $Guardians_data_for_checkbox_data = $Guardians_data_for_checkbox->find()
                            ->select([
                                $Guardians_data_for_checkbox->aliasField('student_custom_field_id'),
                                $Guardians_data_for_checkbox->aliasField('student_id'),
                                $Guardians_data_for_checkbox->aliasField('number_value'),
                            ])
                            ->where([
                                $Guardians_data_for_checkbox->aliasField('student_custom_field_id') => $guadionRow->student_custom_field_id,
                                $Guardians_data_for_checkbox->aliasField('student_id') => $guadionRow->student_id,
                            ])->toArray();

                        
                        $checkbox_data_arr = [];
                        foreach($Guardians_data_for_checkbox_data AS $Guardians_data_for_checkbox_data_value){
                            $studentCustomFieldOptions_Data = $studentCustomFieldOptions_for_checkbox->find()
                                ->select([
                                    $studentCustomFieldOptions_for_checkbox->aliasField('id'),
                                    $studentCustomFieldOptions_for_checkbox->aliasField('name'),
                                    
                                ])
                                ->where([
                                    $studentCustomFieldOptions_for_checkbox->aliasField('id') => $Guardians_data_for_checkbox_data_value->number_value
                                ])->first();
                            $checkbox_data_arr[] = $studentCustomFieldOptions_Data->name;
                        }

                        // $existingCheckboxValue = trim($row[$this->_dynamicFieldName.'_'.$guadionRow->student_custom_field_id], ',') .','. $guadionRow->checkbox_value_text;
                        $row[$this->_dynamicFieldName.'_'.$guadionRow->student_custom_field_id] = implode(',', $checkbox_data_arr) ;
                    } else if ($fieldType == 'NUMBER') {
                        $row[$this->_dynamicFieldName.'_'.$guadionRow->student_custom_field_id] = $guadionRow->number_value;
                    } else if ($fieldType == 'DECIMAL') {
                        $row[$this->_dynamicFieldName.'_'.$guadionRow->student_custom_field_id] = $guadionRow->decimal_value;
                    } else if ($fieldType == 'TEXTAREA') {
                        $row[$this->_dynamicFieldName.'_'.$guadionRow->student_custom_field_id] = $guadionRow->textarea_value;
                    } else if ($fieldType == 'DROPDOWN') {
                        $studentCustomFieldOptionsData = $studentCustomFieldOptions->find()
                        ->select([
                            'dropdown_value_text' => $studentCustomFieldOptions->aliasField('name')
                        ])
                        ->where([
                            $studentCustomFieldOptions->aliasField('id') => $guadionRow->number_value,
                        ])->toArray();
                        $row[$this->_dynamicFieldName.'_'.$guadionRow->student_custom_field_id] = $studentCustomFieldOptionsData[0]->dropdown_value_text;
                    } else if ($fieldType == 'DATE') {
                        $row[$this->_dynamicFieldName.'_'.$guadionRow->student_custom_field_id] = date('Y-m-d', strtotime($guadionRow->date_value));
                    } else if ($fieldType == 'TIME') {
                        $row[$this->_dynamicFieldName.'_'.$guadionRow->student_custom_field_id] = date('h:i A', strtotime($guadionRow->time_value));
                    } else if ($fieldType == 'COORDINATES') {
                        $row[$this->_dynamicFieldName.'_'.$guadionRow->student_custom_field_id] = $guadionRow->text_value;
                    } else if ($fieldType == 'NOTE') {
                        $row[$this->_dynamicFieldName.'_'.$guadionRow->student_custom_field_id] = $guadionRow->field_description;
                    }
                }
                // echo "<pre>";print_r($row);die;
                // POCOR-6129 custome fields code

                return $row;
            });
        });
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $IdentityType = TableRegistry::get('FieldOption.IdentityTypes');
        $identity = $IdentityType->getDefaultEntity();

        $extraField[] = [
            'key' => 'Institutions.code',
            'field' => 'code',
            'type' => 'string',
            'label' => '',
        ];

        $extraField[] = [
            'key' => 'Institutions.name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Institution')
        ];

        $extraField[] = [
            'key' => 'Users.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => 'OpenEMIS ID'
        ];
        
        $extraField[] = [
            'key' => 'Users.username',
            'field' => 'username',
            'type' => 'string',
            'label' => __('Username')
        ];

        $extraField[] = [
            'key' => 'Users.gender_id',
            'field' => 'gender_name',
            'type' => 'string',
            'label' => ''
        ];

        $extraField[] = [
            'key' => 'Users.date_of_birth',
            'field' => 'date_of_birth',
            'type' => 'date',
            'label' => ''
        ];

        //POCOR-6457[START]
        // $extraField[] = [
        //     'key' => 'Users.identity_number',
        //     'field' => 'identity_number',
        //     'type' => 'string',
        //     'label' => __($identity->name)
        // ];

        $extraField[] = [
            'key' => 'areaAdministrative.name',
            'field' => 'birthplace_area',
            'type' => 'string',
            'label' => __('Birthplace Area')
        ];

        $extraField[] = [
            'key' => 'Users.address',
            'field' => 'address',
            'type' => 'string',
            'label' => __('Address')
        ];
        
        // commented in POCOR-6578
        /* $extraField[] = [
            'key' => 'Users.identity_number',
            'field' => 'identity_number',
            'type' => 'string',
            'label' => __($identity->name)
        ]; */
        $extraField[] = [
            'key' => 'IdentityTypes.name',
            'field' => 'identity_type',
            'type' => 'string',
            'label' => __('Identity Type')
        ];
        $extraField[] = [
            'key' => 'Users.identity_number',
            'field' => 'identity_number',
            'type' => 'integer',
            'label' => __('Identity Number')
        ];
        //POCOR-6457[END]

        $extraField[] = [
            'key' => 'MainNationalities.name',
            'field' => 'nationalities',
            'type' => 'string',
            'label' => __('Nationalities')
        ];

        $extraField[] = [
            'key' => '',
            'field' => 'student_status',
            'type' => 'string',
            'label' => __('Student Status')
        ];

        $extraField[] = [
            'key' => 'Users.student_name',
            'field' => 'student_name',
            'type' => 'string',
            'label' => __('Student')
        ];

        $extraField[] = [
            'key' => 'EducationGrades.name',
            'field' => 'education_grade',
            'type' => 'string',
            'label' => __('Education Grades')
        ];

        $extraField[] = [
            'key' => 'InstitutionClasses.name',
            'field' => 'class_name',
            'type' => 'string',
            'label' => __('Class')
        ];

        $extraField[] = [
            'key' => 'AcademicPeriods.name',
            'field' => 'academic_period',
            'type' => 'string',
            'label' => __('Academic Period')
        ];

        $extraField[] = [
            'key' => 'InstitutionStudents.start_date',
            'field' => 'start_date',
            'type' => 'date',
            'label' => ''
        ];

        $extraField[] = [
            'key' => 'InstitutionStudents.end_date',
            'field' => 'end_date',
            'type' => 'date',
            'label' => ''
        ];

        $extraField[] = [
            'key' => '',
            'field' => 'guardian_name',
            'type' => 'string',
            'label' => __('Guardian Name')
        ];

        $extraField[] = [
            'key' => 'GuardianRelations.name',
            'field' => 'guardian_relation',
            'type' => 'string',
            'label' => __('Guardian Relations')
        ];
        
        $extraField[] = [
            'key' => 'GuardianCont.value',
            'field' => 'guardian_contact',
            'type' => 'integer',
            'label' => __('Guardian Contact')
        ];

        $extraField[] = [
            'key' => 'ContactTypes.name',
            'field' => 'contact_type',
            'type' => 'string',
            'label' => __('Contact Type')
        ];
        $extraField[] = [
            'key' => 'Users.value',
            'field' => 'contact_number',
            'type' => 'integer',
            'label' => __('Contact Number')
        ];

        // dump($extraField);die;

        // POCOR-6129 custome fields code
        // $InfrastructureCustomFields = TableRegistry::get('student_custom_fields');
        // $customFieldData = $InfrastructureCustomFields->find()->select([
        //     'custom_field_id' => $InfrastructureCustomFields->aliasfield('id'),
        //     'custom_field' => $InfrastructureCustomFields->aliasfield('name')
        // ])->group($InfrastructureCustomFields->aliasfield('id'))->toArray();


        /**
         * Get all those custom fields of a student which are which are selected in "Parents and Guardian Informations" in page tab
         * Page: Administartion > System Setup > Custom Fields > Student > Page
         * @author Anand Malvi <anand.malvi@mail.valuecoders.com>
         * Ticket: POCOR-6531
         */
        // START: POCOR-6531 - Anand Malvi <anand.malvi@mail.valuecoders.com>
        $student_custom_fields_table = TableRegistry::get('student_custom_fields');
        $customFieldData = $student_custom_fields_table->find()->select([
            'custom_field_id' => $student_custom_fields_table->aliasfield('id'),
            'custom_field' => $student_custom_fields_table->aliasfield('name')
        ])->innerJoin(
            ['StudentCustomFormsFields' => 'student_custom_forms_fields' ], // Class Object => table_name
            ['StudentCustomFormsFields.student_custom_field_id = ' . $student_custom_fields_table->aliasField('id'), // Where
        ])
        ->group($student_custom_fields_table->aliasfield('id'))
        ->toArray();
        // END: POCOR-6531 - Anand Malvi <anand.malvi@mail.valuecoders.com>
        if(!empty($customFieldData)) {
            foreach($customFieldData as $data) {
                $custom_field_id = $data->custom_field_id;
                $custom_field = $data->custom_field;
                $extraField[] = [
                    'key' => '',
                    'field' => $this->_dynamicFieldName.'_'.$custom_field_id,
                    'type' => 'string',
                    'label' => __($custom_field)
                ];
            }
        }
        // POCOR-6129 custome fields code

        $fields->exchangeArray($extraField);
    }


    // public function onExcelRenderIdentities(Event $event, Entity $entity, array $attr) {
    //     $str = '';
    //     if(!empty($entity['user']['identities'])) {
    //         $identities = $entity['user']['identities'];
    //         foreach ($identities as $identity) {
    //             $number = $identity['number'];
    //             $identityType = $identity['identity_type']['name'];
    //             $str .= '('.$identityType.') '.$number.', ';
    //         }
    //     }
    //     if (!empty($str)) {
    //         $str = substr($str, 0, -2);
    //     }
    //     return $str;
    // }

    public function onExcelRenderNationalities(Event $event, Entity $entity, array $attr)
    {
        $str = '';
        if (!empty($entity['user']['nationalities'])) {
            $nationalities = $entity['user']['nationalities'];
            foreach ($nationalities as $nationality) {
                if (isset($nationality['nationalities_look_up']['name'])) {
                    $str .= $nationality['nationalities_look_up']['name'].', ';
                }
            }
        }
        if (!empty($str)) {
            $str = substr($str, 0, -2);
        }
        return $str;
    }

    public function onExcelGetStudentName(Event $event, Entity $entity)
    {
        $studentName = [];
        ($entity->student_first_name) ? $studentName[] = $entity->student_first_name : '';
        ($entity->student_middle_name) ? $studentName[] = $entity->student_middle_name : '';
        ($entity->student_third_name) ? $studentName[] = $entity->student_third_name : '';
        ($entity->student_last_name) ? $studentName[] = $entity->student_last_name : '';

        return implode(' ', $studentName);
    }

    // returns error message if validation false
    public function validateEnrolledInAnyInstitution($studentId, $systemId, $options = [])
    {
        $newOptions['getInstitutions'] = true;
        $options = array_merge($options, $newOptions);

        // targetInstitutionId is used to determine the error message, whether it is enrolled in 'this' or 'other' institution
        $targetInstitutionId = (array_key_exists('targetInstitutionId', $options))? $options['targetInstitutionId']: null;

        $enrolledInstitutionIds = $this->enrolledInAnyInstitution($studentId, $systemId, $options);

        if (is_array($enrolledInstitutionIds) && !empty($enrolledInstitutionIds)) {
            if (!empty($targetInstitutionId) && in_array($targetInstitutionId, $enrolledInstitutionIds)) {
                // 'Student is already enrolled in target school.'
                return $this->getMessage('Institution.Students.student_name.ruleStudentNotEnrolledInAnyInstitutionAndSameEducationSystem.inTargetSchool');
            } else {
                // 'Student is already enrolled in another school.'
                return $this->getMessage('Institution.Students.student_name.ruleStudentNotEnrolledInAnyInstitutionAndSameEducationSystem.inAnotherSchool');
            }
        } else {
            return false;
        }
    }

    private function enrolledInAnyInstitution($studentId, $systemId, $options = [])
    {
        $newOptions['select'] = ['institution_id', 'education_grade_id'];
        $options = array_merge($options, $newOptions);
        $getInstitutions = (array_key_exists('getInstitutions', $options))? $options['getInstitutions']: false;

        $EducationGradesTable = TableRegistry::get('Education.EducationGrades');

        $options['studentId'] = $studentId;
        $enrolledRecords = $this->find('byStatus', $options)->toArray();

        $existingRecordsInSameSystem = [];
        foreach ($enrolledRecords as $key => $value) {
            $enrolledRecords[$key]->education_system_id = $EducationGradesTable->getEducationSystemId($value->education_grade_id);
            if ($value->education_system_id == $systemId) {
                $existingRecordsInSameSystem[] = $value;
            }
        }

        // returns a true/false if !getInstitutions else returns an array of institution_ids
        if (!$getInstitutions) {
            return !empty($existingRecordsInSameSystem);
        } else {
            $institutionIds = [];
            foreach ($existingRecordsInSameSystem as $key => $value) {
                $institutionIds[$value->institution_id] = $value->institution_id;
            }
            return $institutionIds;
        }
    }

    public function findByStatus(Query $query, array $options)
    {
        $studentId = $options['studentId'];
        $statusCode = 'CURRENT';
        if (array_key_exists('code', $options)) {
            $statusCode = $options['code'];
        }
        $status = $this->StudentStatuses->getIdByCode($statusCode);

        $conditions = [
            $this->aliasField('student_id') => $studentId,
            $this->aliasField('student_status_id') => $status
        ];

        if (array_key_exists('excludeInstitutions', $options) && !empty($options['excludeInstitutions'])) {
            $conditions[$this->aliasField('institution_id') . ' NOT IN '] = $options['excludeInstitutions'];
        }

        if (array_key_exists('select', $options) && !empty($options['select'])) {
            $query->select($options['select']);
        }

        $query->where($conditions);
        return $query;
    }

    public function findWithClass(Query $query, array $options)
    {
        $institutionId = $options['institution_id'];
        $periodId = $options['period_id'];

        $ClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
        $Classes = TableRegistry::get('Institution.InstitutionClasses');

        return $query
            ->select([$Classes->aliasField('name')])
            ->leftJoin(
                [$ClassStudents->alias() => $ClassStudents->table()],
                [
                    $ClassStudents->aliasField('student_id = ') . $this->aliasField('student_id'),
                    $ClassStudents->aliasField('education_grade_id = ') . $this->aliasField('education_grade_id'),
                    $ClassStudents->aliasField('student_status_id = ') . $this->aliasField('student_status_id')
                ]
            )
            ->leftJoin(
                [$Classes->alias() => $Classes->table()],
                [
                    $Classes->aliasField('id = ') . $ClassStudents->aliasField('institution_class_id'),
                    $Classes->aliasField('academic_period_id') => $periodId,
                    $Classes->aliasField('institution_id') => $institutionId
                ]
            );
    }

    public function findTripPassengers(Query $query, array $options)
    {
        $queryString = array_key_exists('querystring', $options) ? $options['querystring'] : [];
        $institutionId = array_key_exists('institution_id', $queryString) ? $queryString['institution_id'] : 0;
        $academicPeriodId = array_key_exists('academic_period_id', $queryString) ? $queryString['academic_period_id'] : 0;

        $query
            ->select([
                $this->aliasField('id'),
                $this->Users->aliasField('openemis_no'),
                $this->Users->aliasField('first_name'),
                $this->Users->aliasField('middle_name'),
                $this->Users->aliasField('third_name'),
                $this->Users->aliasField('last_name'),
                $this->Users->aliasField('preferred_name')
            ])
            ->contain($this->Users->alias())
            ->where([
                $this->aliasField('institution_id') => $institutionId,
                $this->aliasField('academic_period_id') => $academicPeriodId
            ])
            ->formatResults(function (ResultSetInterface $results) {
                $returnResult = [];

                foreach ($results as $result) {
                    $returnResult[] = ['value' => $result->id, 'text' => $result->user->name_with_id];
                }

                return $returnResult;
            });

        return $query;
    }

    		
    //Start:POCOR-6931	
    public function beforeSave(Event $event, Entity $entity, ArrayObject $options)	
    { 	
        if(strpos($_SERVER['REQUEST_URI'], "/restful/v2/Institution-Students.json") !== false){
            if ($entity->isNew()) {	
                $studentCurrentV1 = $this->find('all',['conditions'=>['student_status_id' => $entity->student_status_id, 'student_id'=> $entity->student_id]])->first();	
                
                $dobYear =  date('Y', strtotime($entity->date_of_birth));
                $currentYear =  date('Y', strtotime(date('Y-m-d')));

                $yearDiff = $currentYear - $dobYear;
                $ConfigItemTable = TableRegistry::get('config_items');	
                $ConfigItemAgePlus =   $ConfigItemTable->find('all',['conditions' =>['code' => 'admission_age_plus']])->first();	
                $ConfigItemAgeMinus =   $ConfigItemTable->find('all',['conditions' =>['code' => 'admission_age_minus']])->first();	
                $EducationGradesTable = TableRegistry::get('education_grades');	
                $EducationGrades =   $EducationGradesTable->find('all',['conditions' =>['id' => $entity->education_grade_id]])->first();	
                $maxAge = ($EducationGrades->admission_age + $ConfigItemAgePlus->value);	
                $minAge = $EducationGrades->admission_age - $ConfigItemAgeMinus->value;	
                
                $studentCurrent = $this->find('all',['conditions'=>['student_id'=> $entity->student_id, 'education_grade_id'=> $entity->education_grade_id ]])->first();	
                if(!empty($studentCurrentV1)){	
                    if($entity->student_status_id == 1){
                        $response["message"][] ="Student is already enrolled.";	
                        $entity->errors($response);	
                        return false;	
                    }
                }elseif($yearDiff > $maxAge || $yearDiff < $minAge ){	
                    $response["message"][] ="Student age is out of age range for this education grade.";	
                    $entity->errors($response);	
                    return false;	
                }else{	
                    
                }	
                
            }	
        }
    }	
    //End:POCOR-6931

    public function beforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('previous_institution_student_id', ['type' => 'hidden']);
        $this->triggerAutomatedStudentWithdrawalShell();

        $session = $this->request->session();
        $institutionId = !empty($this->request->param('institutionId')) ? $this->paramsDecode($this->request->param('institutionId'))['id'] : $session->read('Institution.Institutions.id');
        $assignedStudentToInstitution = $this->find()->where(['institution_id'=>$institutionId])->count();
        $session->write('is_any_student', $assignedStudentToInstitution);

        // Start POCOR-5188
		$is_manual_exist = $this->getManualUrl('Personal','Students','Students - Overview');       
		if(!empty($is_manual_exist)){
			$btnAttr = [
				'class' => 'btn btn-xs btn-default icon-big',
				'data-toggle' => 'tooltip',
				'data-placement' => 'bottom',
				'escape' => false,
				'target'=>'_blank'
			];

			$helpBtn['url'] = $is_manual_exist['url'];
			$helpBtn['type'] = 'button';
			$helpBtn['label'] = '<i class="fa fa-question-circle"></i>';
			$helpBtn['attr'] = $btnAttr;
			$helpBtn['attr']['title'] = __('Help');
			$extra['toolbarButtons']['help'] = $helpBtn;
		}
		// End POCOR-5188

    }

    public function beforeDelete(Event $event, Entity $entity)
    {
        $studentStatuses = $this->StudentStatuses->findCodeList();
        // if user tries to delete record that is not enrolled
        if ($entity->student_status_id != $studentStatuses['CURRENT']) { 
            $event->stopPropagation();
            return false;
        }
        $body = array();

        $body = [
            'institution_student_id' => !empty($entity->student_id) ? $entity->student_id : NULL,
            'institution_id' => !empty($entity->institution_id) ? $entity->institution_id : NULL,
        ];
        if(!empty($this->action) && $this->action == 'remove') {
            $Webhooks = TableRegistry::get('Webhook.Webhooks');
            if ($this->Auth->user()) {
                $username = $this->Auth->user()['username'];
                $Webhooks->triggerShell('student_delete', ['username' => $username], $body);
            }
        }
    }

    public function indexBeforeAction(Event $event, ArrayObject $extra)
    {
        // permission checking for import button
        $hasImportAdmissionPermission = $this->AccessControl->check(['Institutions', 'ImportStudentAdmission', 'add']);
        $hasImportBodyMassPermission = $this->AccessControl->check(['Institutions', 'ImportStudentBodyMasses', 'add']);
        $hasImportGuardianPermission = $this->AccessControl->check(['Institutions', 'ImportStudentGuardians', 'add']);

        if (!$hasImportAdmissionPermission && $hasImportBodyMassPermission) {
            if ($this->behaviors()->has('ImportLink')) {
                $this->behaviors()->get('ImportLink')->config([
                   'import_model' => 'ImportStudentBodyMasses'
                ]);
            }
        }

        if (!$hasImportAdmissionPermission && !$hasImportBodyMassPermission) {
            if ($this->behaviors()->has('ImportLink')) {
                $this->behaviors()->get('ImportLink')->config([
                   'import_model' => 'ImportStudentGuardians'
                ]);
            }
        }

        $session = $this->request->session();
        $institutionId = !empty($this->request->param('institutionId')) ? $this->paramsDecode($this->request->param('institutionId'))['id'] : $session->read('Institution.Institutions.id');

        $this->field('academic_period_id', ['visible' => false]);
        $this->field('class', ['after' => 'education_grade_id']);
        $this->field('student_status_id', ['after' => 'class']);
        $this->fields['start_date']['visible'] = false;
        $this->fields['end_date']['visible'] = false;
        $this->fields['class']['sort'] = ['field' => 'InstitutionClasses.name'];
        $this->fields['student_id']['sort'] = ['field' => 'Users.first_name'];

        $this->controller->set('ngController', 'AdvancedSearchCtrl');

        $StudentStatusesTable = $this->StudentStatuses;
        $status = $StudentStatusesTable->findCodeList();
        $selectedStatus = $this->request->query('status_id');

        // To redirect to Pending statuses page
        $pendingStatuses = [
            self::PENDING_ADMISSION => 'StudentAdmission',
            self::PENDING_TRANSFERIN => 'StudentTransferIn',
            self::PENDING_TRANSFEROUT => 'StudentTransferOut',
            self::PENDING_WITHDRAW => 'StudentWithdraw',
            self::IN_QUEUE => 'StudentStatusUpdates',
        ];

        if (array_key_exists($selectedStatus, $pendingStatuses)) {
            $url = ['plugin' => 'Institution', 'controller' => 'Institutions', 'institutionId' => $this->paramsEncode(['id' => $institutionId])];
            $url['action'] = $pendingStatuses[$selectedStatus];
            $event->stopPropagation();
            return $this->controller->redirect($url);
        }

        // from onUpdateToolbarButtons
        $btnAttr = [
            'class' => 'btn btn-xs btn-default icon-big',
            'data-toggle' => 'tooltip',
            'data-placement' => 'bottom',
            'escape' => false
        ];
        $buttons = $extra['indexButtons'];

        $extraButtons = [
            'graduate' => [
                'permission' => ['Institutions', 'Promotion', 'add'],
                'action' => 'Promotion',
                'icon' => '<i class="fa kd-graduate"></i>',
                'title' => __('Promotion / Graduation')
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
            ]
        ];

        foreach ($extraButtons as $key => $attr) {
            if ($this->AccessControl->check($attr['permission'])) {
                $button = [
                    'type' => 'button',
                    'attr' => $btnAttr,
                    'url' => [0 => 'add']
                ];
                $button['url']['action'] = $attr['action'];
                $button['attr']['title'] = $attr['title'];
                $button['label'] = $attr['icon'];

                $extra['toolbarButtons'][$key] = $button;
            }
        }

        //POCOR-6248 starts    
        $ConfigItemTable = TableRegistry::get('Configuration.ConfigItems');
        $ConfigItem =   $ConfigItemTable
                            ->find()
                            ->where([
                                $ConfigItemTable->aliasField('type') => 'Columns for Student List Page'
                            ])
                            ->all();
        if(!empty($ConfigItem)){
            foreach ($ConfigItem as $item) {
                if($item->code == 'student_photo'){
                    $this->field('photo_name', ['visible' => false]);
                    if($item->value == 1){
                        $this->field('photo_content', ['visible' => true]);
                    }else{
                        $this->field('photo_content', ['visible' => false]);
                    }
                }

                if($item->code == 'student_openEMIS_ID'){
                    if($item->value == 1){
                        $this->field('openemis_no', ['visible' => true, 'before' => 'student_id']);
                    }else{
                        $this->field('openemis_no', ['visible' => false, 'before' => 'student_id']);
                    }
                }

                if($item->code == 'student_name'){
                    if($item->value == 1){
                        $this->field('student_id', ['visible' => true, 'before' => 'education_grade_id']);
                    }else{
                        $this->field('student_id', ['visible' => false, 'before' => 'education_grade_id']);
                    } 
                }

                if($item->code == 'student_education_code'){
                    if($item->value == 1){
                        $this->field('education_grade_id', ['visible' => true, 'before' => 'class']);
                    }else{
                        $this->field('education_grade_id', ['visible' => false, 'before' => 'class']);
                    } 
                }

                if($item->code == 'student_class'){
                    if($item->value == 1){
                        $this->field('class', ['visible' => true, 'before' => 'student_status_id']);
                    }else{
                        $this->field('class', ['visible' => false, 'before' => 'student_status_id']);
                    } 
                }

                if($item->code == 'student_status'){
                    if($item->value == 1){
                        $this->field('student_status_id', ['visible' => true, 'after' => 'class']);
                    }else{
                        $this->field('student_status_id', ['visible' => false, 'after' => 'class']);
                    } 
                }

                if($item->code == 'student_identity_number'){
                    if($item->value == 1){
                        if(!empty($item->value_selection)){
                            //get data from Identity Type table 
                            $typesIdentity = $this->getIdentityTypeData($item->value_selection);
                            $this->field($typesIdentity->identity_type, ['visible' => true, 'after' => 'student_status_id']);
                        }
                    }else{
                        $this->field($typesIdentity->identity_type, ['visible' => false, 'after' => 'student_status_id']);
                    }
                }
            }    
        }   
        //POCOR-6248 ends
    }

    public function indexBeforeQuery(Event $event, Query $query, ArrayObject $extra)
    {
        $request = $this->request;
        $query->contain(['EducationGrades']);

        // Student Statuses
        $statusOptions = $this->StudentStatuses
            ->find('list')
            ->toArray();
        $StudentStatusesTable = $this->StudentStatuses;
        $pendingStatus = [
            self::PENDING_TRANSFERIN => __('Pending Transfer In'),
            self::PENDING_TRANSFEROUT => __('Pending Transfer Out'),
            self::PENDING_ADMISSION => __('Pending Admission'),
            self::PENDING_WITHDRAW => __('Pending Withdraw'),
            self::IN_QUEUE => __('In Queue'),
        ];

        $statusOptions = $statusOptions + $pendingStatus;

        // Academic Periods
        $academicPeriodOptions = $this->AcademicPeriods->getYearList();

        // Education Grades
        $InstitutionEducationGrades = TableRegistry::get('Institution.InstitutionGrades');
        $session = $this->Session;
        $institutionId = $session->read('Institution.Institutions.id');

		if (empty($request->query['academic_period_id'])) {
            $request->query['academic_period_id'] = $this->AcademicPeriods->getCurrent();
        }
        $selectedStatus = $this->queryString('status_id', $statusOptions);
        $selectedAcademicPeriod = $this->queryString('academic_period_id', $academicPeriodOptions);

        $educationGradesOptions = $InstitutionEducationGrades
            ->find('list', [
                    'keyField' => 'EducationGrades.id',
                    'valueField' => 'EducationGrades.name'
                ])
            ->select([
                    'EducationGrades.id', 'EducationGrades.name'
                ])
            //->contain(['EducationGrades'])
			->contain(['EducationGrades.EducationProgrammes.EducationCycles.EducationLevels.EducationSystems'])
            ->where(['institution_id' => $institutionId])
            ->where(['EducationSystems.academic_period_id' => $selectedAcademicPeriod])
            ->order(['EducationGrades.name'=>'ASC']) //POCOR-7247
            ->group('education_grade_id')
            ->toArray();

        $educationGradesOptions = ['-1' => __('All Grades')] + $educationGradesOptions;

        // Query Strings

        $selectedEducationGrades = $this->queryString('education_grade_id', $educationGradesOptions);

        // Advanced Select Options
        $this->advancedSelectOptions($statusOptions, $selectedStatus);
        $studentTable = $this;
        $this->advancedSelectOptions($academicPeriodOptions, $selectedAcademicPeriod, [
            'message' => '{{label}} - ' . $this->getMessage($this->aliasField('noStudents')),
            'callable' => function ($id) use ($studentTable, $institutionId) {
                return $studentTable->find()->where(['institution_id'=>$institutionId, 'academic_period_id'=>$id])->count();
            }
        ]);

        $request->query['academic_period_id'] = $selectedAcademicPeriod;

        // To add the academic_period_id to export
        if (isset($extra['toolbarButtons']['export']['url'])) {
            $extra['toolbarButtons']['export']['url']['academic_period_id'] = $selectedAcademicPeriod;
        }

        $this->advancedSelectOptions($educationGradesOptions, $selectedEducationGrades);

        if ($selectedEducationGrades != -1) {
            $query->where([$this->aliasField('education_grade_id') => $selectedEducationGrades]);
        }

        $query->where([$this->aliasField('academic_period_id') => $selectedAcademicPeriod]);

        // Start: sort by class column
        $session = $request->session();
        $institutionId = $session->read('Institution.Institutions.id');

        $query->find('withClass', ['institution_id' => $institutionId, 'period_id' => $selectedAcademicPeriod]);

        $sortList = ['InstitutionClasses.name'];
        if (array_key_exists('sortWhitelist', $extra['options'])) {
            $sortList = array_merge($extra['options']['sortWhitelist'], $sortList);
        }
        $extra['options']['sortWhitelist'] = $sortList;
        // End

        $search = $this->getSearchKey();

        if (!empty($search)) {
            // function from AdvancedNameSearchBehavior
             /**

             * pass table alias name for identity number in search function

             * @author Akshay Patodi <akshay.patodi@mail.valuecoders.com>

             * @ticket POCOR-6532

             */
            // Starts POCOR-6532
           $UserIdentitiesnumber = TableRegistry::get('User.Identities');
           $useridentites = $UserIdentitiesnumber->aliasField('number');
            
            $query = $this->addSearchConditions($query, ['alias' => 'Users', 'aliasidentity' => 'Identities', 'searchTerm' => $search]);
            // Ends POCOR-6532
            $query->where([$this->aliasField('student_status_id') => $selectedStatus]);
        } else {
            //POCOR-5690 remove check isAdvancedSearchEnabled for search data from list
            //if (!$this->isAdvancedSearchEnabled() && $selectedStatus != -1) {
            if ($selectedStatus != -1) {
                $query->where([$this->aliasField('student_status_id') => $selectedStatus]);
            }
        }

        //select specific field that is used on the page, photo_content is generated by LazyEagerLoader (javascript)
        //the rest of fields are called by onGet function.
        //POCOR-6248 starts
        $IdentityTypes = TableRegistry::get('FieldOption.IdentityTypes');
        $UserIdentities = TableRegistry::get('User.Identities');
        $ConfigItemTable = TableRegistry::get('Configuration.ConfigItems');
        $ConfigItem =   $ConfigItemTable
                            ->find()
                            ->where([
                                $ConfigItemTable->aliasField('code') => 'student_identity_number',
                                $ConfigItemTable->aliasField('value') => 1
                            ])
                            ->first();

        if(!empty($ConfigItem)){
            //value_selection
            //get data from Identity Type table 
            $typesIdentity = $this->getIdentityTypeData($ConfigItem->value_selection);
            if(!empty($typesIdentity)){                
                $query
                    ->select([
                        $this->aliasField('id'),
                        'Users.id',
                        'Users.openemis_no',
                        'Users.first_name',
                        'Users.middle_name',
                        'Users.third_name',
                        'Users.last_name',
                        'Users.preferred_name',
                        'student_status_id',
                        'identity_type' => $IdentityTypes->aliasField('name'),

                        //start:POCRO-6622 quates is removed with ` for loading issue in student on mv-moe server
                        "`". $typesIdentity->identity_type . "`" => $UserIdentities->aliasField('number') //POCRO-6583 added single quote as identity_type was not working for some clients
                        //end:POCRO-6622 
                    ])
                    /**

                     * Add identity number like in the query and hide default identity id

                     * @author Akshay Patodi <akshay.patodi@mail.valuecoders.com>

                     * @ticket POCOR-6532

                     */
                    // Starts POCOR-6532
                    ->leftJoin(
                                [$UserIdentities->alias() => $UserIdentities->table()],
                                [
                                    $UserIdentities->aliasField('security_user_id = ') . $this->aliasField('student_id'),
                                    $UserIdentities->aliasField('identity_type_id = ') . $typesIdentity->id //POCOR-7115 uncomment line
                                ]
                            )
                    ->leftJoin(
                        [$IdentityTypes->alias() => $IdentityTypes->table()],
                        [
                            $IdentityTypes->aliasField('id = ') . $UserIdentities->aliasField('identity_type_id'),
                           // $IdentityTypes->aliasField('id = ') . $typesIdentity->id
                        ]
                    );
                     // Ends POCOR-6532
            }
        } else {
            $query->select([
                $this->aliasField('id'),
                'Users.id',
                'Users.openemis_no',
                'Users.first_name',
                'Users.middle_name',
                'Users.third_name',
                'Users.last_name',
                'Users.preferred_name',
                'student_status_id'
            ])
            //POCOR-6645 starts - applied join to get result when not $ConfigItem
            ->leftJoin([$UserIdentities->alias() => $UserIdentities->table()], [
                $UserIdentities->aliasField('security_user_id = ') . $this->aliasField('student_id')
            ])
            ->leftJoin([$IdentityTypes->alias() => $IdentityTypes->table()], [
                $IdentityTypes->aliasField('id = ') . $UserIdentities->aliasField('identity_type_id')
            ]);
            //POCOR-6645 ends
        }//POCOR-6248 ends
  
        // POCOR-2869 implemented to hide the retrieval of records from another school resulting in duplication - proper fix will be done in SOJOR-437
        $query->group([$this->aliasField('student_id'), $this->aliasField('academic_period_id'), $this->aliasField('institution_id'), $this->aliasField('education_grade_id'), $this->aliasField('student_status_id')]);
          
        // POCOR-2547 sort list of staff and student by name
        if (!isset($request->query['sort'])) {
            $query->order([$this->Users->aliasField('first_name'), $this->Users->aliasField('last_name')]);
        }

        $this->controller->set(compact('statusOptions', 'academicPeriodOptions', 'educationGradesOptions'));
    }
    //POCOR-6248 starts
    public function getIdentityTypeData($value_selection)
    {
        $IdentityTypes = TableRegistry::get('FieldOption.IdentityTypes');
        $typesIdentity =   $IdentityTypes
                            ->find()
                            ->select([
                                'id' => $IdentityTypes->aliasField('id'),
                                'identity_type' => $IdentityTypes->aliasField('name')
                            ])
                            ->where([
                                $IdentityTypes->aliasField('id') => $value_selection
                            ])
                            ->first();
        return  $typesIdentity;
    }//POCOR-6248 ends

    public function indexAfterAction(Event $event, Query $query, ResultSet $resultSet, ArrayObject $extra)
    {
        foreach ($query->toArray() as $key => $value)
        {
            $periodId = $value['academic_period']['id'];//POCOR-6530
            $InstitutionStudents = TableRegistry::get('InstitutionStudents');

            $InstitutionStudentsCurrentData = $InstitutionStudents
            ->find()
            ->select([
                'InstitutionStudents.id', 'InstitutionStudents.student_status_id', 'InstitutionStudents.previous_institution_student_id'
            ])
            ->where([
                $InstitutionStudents->aliasField('student_id') => $value["_matchingData"]["Users"]->id,
                $InstitutionStudents->aliasField('academic_period_id') => $periodId //POCOR-6530
            ])
            ->order([$InstitutionStudents->aliasField('InstitutionStudents.created') => 'DESC'])
            ->autoFields(true)
            ->first();
            /*POCOR-6400 starts*/
            if (!empty($InstitutionStudentsCurrentData->previous_institution_student_id)) {
                $studentStatusId = $InstitutionStudentsCurrentData->student_status_id;
                $statuses = $this->StudentStatuses->findCodeList();
                $code = array_search($studentStatusId, $statuses);

                if ($code != 'WITHDRAWN' && $code != 'TRANSFERRED' && $code != 'PROMOTED') {
                    /**POCOR-6530 starts */
                    $previousPeriodId = $periodId - 1;
                    $previousInstitutionStudentId = $InstitutionStudentsCurrentData->previous_institution_student_id;
                    $previousYearRecord = $InstitutionStudents
                        ->find()
                        ->select([
                            'InstitutionStudents.id', 'InstitutionStudents.student_status_id'
                        ])
                        ->where([
                            $InstitutionStudents->aliasField('academic_period_id') => $previousPeriodId,
                            $InstitutionStudents->aliasField('id') => $previousInstitutionStudentId
                        ])->first();
                    /**POCOR-6530 ends */
                    if (!empty($previousYearRecord) && $previousYearRecord->student_status_id == 8) {
                        $query->toArray()[$key]->student_status->name = "Enrolled (Repeater)";
                    }
                }
            }
            /*POCOR-6400 ends*/
        }
        $this->dashboardQuery = clone $query;
    }

    public function viewBeforeAction(Event $event, ArrayObject $extra)
    {
        $this->field('photo_content', ['type' => 'image', 'before' => 'openemis_no']);
        $this->field('openemis_no', ['type' => 'readonly', 'order' => 1]);
        $this->fields['student_id']['order'] = 10;
        $extra['toolbarButtons']['back']['url']['action'] = 'StudentProgrammes';
    }

    public function viewAfterAction(Event $event, Entity $entity, ArrayObject $extra)
    {
        $studentStatusId = $entity->student_status_id;
        $statuses = $this->StudentStatuses->findCodeList();
        $code = array_search($studentStatusId, $statuses);

        if ($code == 'WITHDRAWN' || $code == 'TRANSFERRED') {
            $this->field('reason', ['type' => 'custom_status_reason']);
            $this->field('comment');
            $this->setFieldOrder([
                'photo_content', 'openemis_no', 'student_id', 'student_status_id', 'reason', 'comment'
            ]);

        }

        if ($code != 'CURRENT') { // only enrolled students can be edited or removed
            $this->toggle('remove', false);
            $this->toggle('edit', false);
        }

        $this->Session->write('Student.Students.id', $entity->student_id);
        $this->Session->write('Student.Students.name', $entity->user->name);
        $this->setupTabElements($entity);
    }

    public function editBeforeQuery(Event $event, Query $query)
    {
        $query->contain(['Users', 'EducationGrades', 'AcademicPeriods', 'StudentStatuses']);
    }

    public function editAfterAction(Event $event, Entity $entity)
    {
        // Start PHPOE-1897
        $statuses = $this->StudentStatuses->findCodeList();
        if ($entity->student_status_id != $statuses['CURRENT']) {
            $event->stopPropagation();
            $urlParams = $this->url('view');
            return $this->controller->redirect($urlParams);
        // End PHPOE-1897
        } else {
            $this->field('student_id', [
                'type' => 'readonly',
                'order' => 10,
                'attr' => ['value' => $entity->user->name_with_id]
            ]);

            $this->field('education_grade_id', ['type' => 'readonly', 'attr' => ['value' => $entity->education_grade->programme_grade_name]]);
            $this->field('academic_period_id', ['type' => 'readonly', 'attr' => ['value' => $entity->academic_period->name]]);
            $this->field('student_status_id', ['type' => 'readonly', 'attr' => ['value' => $entity->student_status->name]]);

            $period = $entity->academic_period;
            $dateOptions = [
                'startDate' => $period->start_date->format('d-m-Y'),
                'endDate' => $period->end_date->format('d-m-Y')
            ];

            $this->fields['start_date']['date_options'] = $dateOptions;
            $this->fields['end_date']['date_options'] = $dateOptions;

            $this->Session->write('Student.Students.id', $entity->student_id);
            $this->Session->write('Student.Students.name', $entity->user->name);
            $this->setupTabElements($entity);
        }
    }

    public function afterAction(Event $event, ArrayObject $extra)
    {
        if ($this->action == 'index') {
            $InstitutionArray = [];
            $institutionStudentQuery = clone $this->dashboardQuery;
            $studentCount = $institutionStudentQuery->group([$this->aliasField('student_id')])->count();
            unset($institutionStudentQuery);

            //Get Gender
            $InstitutionArray[__('Gender')] = $this->getDonutChart(
                'institution_student_gender',
                ['query' => $this->dashboardQuery, 'key' => __('Gender')]
            );

            // Get Age
            $InstitutionArray[__('Age')] = $this->getDonutChart(
                'institution_student_age',
                ['query' => $this->dashboardQuery, 'key' => __('Age')]
            );

            // Get Grades
            $InstitutionArray[__('Grade')] = $this->getDonutChart(
                'institution_class_student_grade',
                ['query' => $this->dashboardQuery, 'key' => __('Grade')]
            );

            $indexDashboard = 'dashboard';

            $indexElements = (isset($this->controller->viewVars['indexElements']))?$this->controller->viewVars['indexElements'] :[] ;

            $indexElements[] = ['name' => 'Institution.Students/controls', 'data' => [], 'options' => [], 'order' => 0];

            if (!$this->isAdvancedSearchEnabled()) { //function to determine whether dashboard should be shown or not
                $AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
                $currentYearId = $AcademicPeriod->getCurrent();
                $periodId = $this->request->query['academic_period_id'];
                if ($currentYearId == $periodId) {
                $indexElements[] = [
                    'name' => $indexDashboard,
                    'data' => [
                        'model' => 'students',
                        'modelCount' => $studentCount,
                        'modelArray' => $InstitutionArray,
                    ],
                    'options' => [],
                    'order' => 2
                ];
                }
            }

            foreach ($indexElements as $key => $value) {
                if ($value['name']=='OpenEmis.ControllerAction/index') {
                    $indexElements[$key]['order'] = 3;
                } elseif ($value['name']=='OpenEmis.pagination') {
                    $indexElements[$key]['order'] = 4;
                }
            }

            $extra['elements'] = array_merge($extra['elements'], $indexElements);
        }
    }

    public function afterSave(Event $event, Entity $entity, ArrayObject $options)
    {
        $listeners = [
            TableRegistry::get('Institution.StudentAdmission'),
            TableRegistry::get('Institution.StudentTransferIn'),
            TableRegistry::get('Institution.StudentTransferOut'),
            TableRegistry::get('Institution.InstitutionClassStudents'),
            TableRegistry::get('Institution.InstitutionSubjectStudents'),
            TableRegistry::get('Institution.StudentUser'),
            $this->Users
        ];
        $this->dispatchEventToModels('Model.Students.afterSave', [$entity], $this, $listeners);

        //if new record has no previous_institution_student_id value yet, then try to update it.
        if (!$entity->has('previous_institution_student_id')) {
            $prevInstitutionStudent = $this
                                ->find()
                                ->where([
                                    $this->aliasField('student_id') => $entity->student_id,
                                    $this->aliasField('id <> ') => $entity->id,
                                ])
                                ->order([
                                    'created' => 'desc',
                                    'start_date' => 'desc'
                                ])
                                ->first();

            if ($prevInstitutionStudent) { //if has previous record.
                $this->updateAll(
                    ['previous_institution_student_id' => $prevInstitutionStudent->id],
                    ['id' => $entity->id]
                );
            }
        }

		if($entity->isNew()) {
			$bodyData = $this->find('all',
									[ 'contain' => [
										'Institutions',
										'EducationGrades',
										'AcademicPeriods',
										'StudentStatuses',
										'Users',
										'Users.Genders',
										'Users.MainNationalities',
										'Users.Identities.IdentityTypes',
										'Users.AddressAreas',
										'Users.BirthplaceAreas',
										'Users.Contacts.ContactTypes'
									],
						])->where([
							$this->aliasField('student_id') => $entity->student_id
						]);
            if (!empty($bodyData)) {
                foreach ($bodyData as $key => $value) {
                    $user_id = $value->user->id;
                    $openemis_no = $value->user->openemis_no;
                    $first_name = $value->user->first_name;
                    $middle_name = $value->user->middle_name;
                    $third_name = $value->user->third_name;
                    $last_name = $value->user->last_name;
                    $preferred_name = $value->user->preferred_name;
                    $gender = $value->user->gender->name;
                    $nationality = $value->user->main_nationality->name;
                    // POCOR-6283 start
                    $dateOfBirth = $value->user->date_of_birth; 
                    // commented because date can be converted directly no need to use loop
                    /* if(!empty($value->user->date_of_birth)) {
                        foreach ($value->user->date_of_birth as $key => $date) {
                            $dateOfBirth = $date;
                        }
                    } */
                    // POCOR-6283 end
                    $address = $value->user->address;
                    $postalCode = $value->user->postal_code;
                    $addressArea = $value->user->address_area->name;
                    $birthplaceArea = $value->user->birthplace_area->name;
                    $role = $value->user->is_student;

                    $contactValue = [];
                    $contactType = [];
                    if(!empty($value->user['contacts'])) {
                        foreach ($value->user['contacts'] as $key => $contact) {
                            $contactValue[] = $contact->value;
                            $contactType[] = $contact->contact_type->name;
                        }
                    }

                    $identityNumber = [];
                    $identityType = [];
                    if(!empty($value->user['identities'])) {
                        foreach ($value->user['identities'] as $key => $identity) {
                            $identityNumber[] = $identity->number;
                            $identityType[] = $identity->identity_type->name;
                        }
                    }

                    $username = $value->user->username;
                    $institution_id = $value->institution->id;
                    $institutionName = $value->institution->name;
                    $institutionCode = $value->institution->code;
                    $educationGrade = $value->education_grade->name;
                    $academicCode = $value->academic_period->code;
                    $academicGrade = $value->academic_period->name;
                    $studentStatus = $value->student_status->name;
                    if(!empty($value->start_date)) {
                        $i=0;
                        foreach ($value->start_date as $key => $date) {
                            if($i==0){
                                $startDate = $date;
                            }
                            $i++;
                        }
                    }

                    if(!empty($value->end_date)) {
                        $i=0;
                        foreach ($value->end_date as $key => $date) {
                            if($i==0){
							  $endDate = $date;
                           $i++; }
						}
					}
				}
			}
			$bodys = array();
			$bodys = [
				'security_users_id' => !empty($user_id) ? $user_id : NULL,
				'security_users_openemis_no' => !empty($openemis_no) ? $openemis_no : NULL,
				'security_users_first_name' =>	!empty($first_name) ? $first_name : NULL,
				'security_users_middle_name' => !empty($middle_name) ? $middle_name : NULL,
				'security_users_third_name' => !empty($third_name) ? $third_name : NULL,
				'security_users_last_name' => !empty($last_name) ? $last_name : NULL,
				'security_users_preferred_name' => !empty($preferred_name) ? $preferred_name : NULL,
				'security_users_gender' => !empty($gender) ? $gender : NULL,
				'security_users_date_of_birth' => !empty($dateOfBirth) ? date("d-m-Y", strtotime($dateOfBirth)) : NULL,
				'security_users_address' => !empty($address) ? $address : NULL,
				'security_users_postal_code' => !empty($postalCode) ? $postalCode : NULL,
				'area_administrative_name_birthplace' => !empty($addressArea) ? $addressArea : NULL,
				'area_administrative_name_address' => !empty($birthplaceArea) ? $birthplaceArea : NULL,
				'contact_type_name' => !empty($contactType) ? $contactType : NULL,
				'user_contact_type_value' => !empty($contactValue) ? $contactValue : NULL,
				'nationality_name' => !empty($nationality) ? $nationality : NULL,
				'identity_type_name' => !empty($identityType) ? $identityType : NULL,
				'user_identities_number' => !empty($identityNumber) ? $identityNumber : NULL,
				'security_user_username' => !empty($username) ? $username : NULL,
				'institutions_id' => !empty($institution_id) ? $institution_id : NULL,
				'institutions_code' => !empty($institutionCode) ? $institutionCode : NULL,
				'institutions_name' => !empty($institutionName) ? $institutionName : NULL,
				'academic_period_code' => !empty($academicCode) ? $academicCode : NULL,
				'academic_period_name' => !empty($academicGrade) ? $academicGrade : NULL,
				'education_grade_name' => !empty($educationGrade) ? $educationGrade : NULL,
				'student_status_name' => !empty($studentStatus) ? $studentStatus : NULL,
				'institution_students_start_date' => !empty($startDate) ? date("d-m-Y", strtotime($startDate)) : NULL,
				'institution_students_end_date' => !empty($endDate) ? date("d-m-Y", strtotime($endDate)) : NULL,
                'role_name' => ($role == 1) ? 'student' : NULL
			];
            //POCOR-7078 start
            $studentCustomFieldValues = TableRegistry::get('student_custom_field_values');
            $studentCustomFieldOptions = TableRegistry::get('student_custom_field_options');
            $studentCustomFields = TableRegistry::get('student_custom_fields');
            $studentCustomData = $studentCustomFieldValues->find()
                ->select([
                        'id'                             => $studentCustomFieldValues->aliasField('id'),
                        'custom_id'                      => 'studentCustomField.id',
                        'student_id'                     => $studentCustomFieldValues->aliasField('student_id'),
                        'student_custom_field_id'        => $studentCustomFieldValues->aliasField('student_custom_field_id'),
                        'text_value'                     => $studentCustomFieldValues->aliasField('text_value'),
                        'number_value'                   => $studentCustomFieldValues->aliasField('number_value'),
                        'decimal_value'                  => $studentCustomFieldValues->aliasField('decimal_value'),
                        'textarea_value'                 => $studentCustomFieldValues->aliasField('textarea_value'),
                        'date_value'                     => $studentCustomFieldValues->aliasField('date_value'),
                        'time_value'                     => $studentCustomFieldValues->aliasField('time_value'),
                        'option_value_text'              => $studentCustomFieldOptions->aliasField('name'),
                        'name'                           => 'studentCustomField.name',
                        'field_type'                     => 'studentCustomField.field_type',
                    ])->leftJoin(
                    ['studentCustomField' => 'student_custom_fields'],
                    [
                        'studentCustomField.id = '.$studentCustomFieldValues->aliasField('student_custom_field_id')
                    ])
                    ->leftJoin(
                    [$studentCustomFieldOptions->alias() => $studentCustomFieldOptions->table()],
                    [
                        $studentCustomFieldOptions->aliasField('student_custom_field_id = ') . $studentCustomFieldValues->aliasField('student_custom_field_id'),
                        $studentCustomFieldOptions->aliasField('id = ') . $studentCustomFieldValues->aliasField('number_value')
                    ])
                    ->where([
                    $studentCustomFieldValues->aliasField('student_id') => $user_id,
                    ])->hydrate(false)->toArray();
            $custom_field = array();
            $count = 0;
            if(!empty($studentCustomData)){
                foreach ($studentCustomData as $val) {
                    $custom_field['custom_field'][$count]["id"] = (!empty($val['custom_id']) ? $val['custom_id'] : '');
                    $custom_field['custom_field'][$count]["name"]= (!empty($val['name']) ? $val['name'] : '');
                    $fieldTypes[$count] = (!empty($val['field_type']) ? $val['field_type'] : '');
                    $fieldType = $fieldTypes[$count];
                    if($fieldType == 'TEXT'){
                        $custom_field['custom_field'][$count]["text_value"] = (!empty($val['text_value']) ? $val['text_value'] : '');
                    }else if ($fieldType == 'CHECKBOX') {
                        $custom_field['custom_field'][$count]["checkbox_value"] = (!empty($val['option_value_text']) ? $val['option_value_text'] : '');
                    }else if ($fieldType == 'NUMBER') {
                        $custom_field['custom_field'][$count]["number_value"] = (!empty($val['number_value']) ? $val['number_value'] : '');
                    }else if ($fieldType == 'DECIMAL') {
                        $custom_field['custom_field'][$count]["decimal_value"] = (!empty($val['decimal_value']) ? $val['decimal_value'] : '');
                    }else if ($fieldType == 'TEXTAREA') {
                        $custom_field['custom_field'][$count]["textarea_value"] = (!empty($val['textarea_value']) ? $val['textarea_value'] : '');
                    }else if ($fieldType == 'DROPDOWN') {
                        $custom_field['custom_field'][$count]["dropdown_value"] = (!empty($val['option_value_text']) ? $val['option_value_text'] : '');
                    }else if ($fieldType == 'DATE') {
                        $custom_field['custom_field'][$count]["date_value"] = date('Y-m-d', strtotime($val->date_value));
                    }else if ($fieldType == 'TIME') {
                        $custom_field['custom_field'][$count]["time_value"] = date('h:i A', strtotime($val->time_value));
                    }else if ($fieldType == 'COORDINATES') {
                        $custom_field['custom_field'][$count]["cordinate_value"] = (!empty($val['text_value']) ? $val['text_value'] : '');
                    }
                    $count++;
                }
            }
            $body = array_merge($bodys, $custom_field);//POCOR-7078 end
			$Webhooks = TableRegistry::get('Webhook.Webhooks');
			if (!empty($entity->created_user_id)) {
				$Webhooks->triggerShell('student_create', ['username' => ''], $body);
			}
		}
    }

    public function onGetStudentId(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('user')) {
            $value = $entity->user->name;
        } else {
            $value = $entity->_matchingData['Users']->name;
        }
        return $value;
    }

    public function onGetEducationGradeId(Event $event, Entity $entity)
    {
        $value = '';
        if ($entity->has('education_grade')) {
            $value = $entity->education_grade->programme_grade_name;
        }
        return $value;
    }

    public function onGetClass(Event $event, Entity $entity)
    {
        $value = '';

        if ($entity->has('InstitutionClasses')) {
            $value = $entity->InstitutionClasses['name'];
        }

        return $value;
    }

    public function onGetCustomStatusReasonElement(Event $event, $action, $entity, $attr, $options = [])
    {
        if ($this->action == 'view') {
            $studentStatusId = $entity->student_status_id;
            $statuses = $this->StudentStatuses->findCodeList();
            $code = array_search($studentStatusId, $statuses);
            $institutionId = $entity->institution_id;
            $educationGradeId = $entity->education_grade_id;
            $studentId = $entity->getOriginal('student_id'); // student_id is changed in onGetStudentId
            $academicPeriodId = $entity->academic_period_id;

            switch ($code) {
                case 'TRANSFERRED':
                    $StudentTransfersTable = TableRegistry::get('Institution.InstitutionStudentTransfers');
                    $approvedStatuses = $StudentTransfersTable->getStudentTransferWorkflowStatuses('APPROVED');

                    $transferReason = $StudentTransfersTable->find()
                        ->matching('StudentTransferReasons')
                        ->where([
                            $StudentTransfersTable->aliasField('student_id') => $studentId,
                            $StudentTransfersTable->aliasField('previous_institution_id') => $institutionId,
                            $StudentTransfersTable->aliasField('previous_education_grade_id') => $educationGradeId,
                            $StudentTransfersTable->aliasField('previous_academic_period_id') => $academicPeriodId,
                            $StudentTransfersTable->aliasField('status_id IN ') => $approvedStatuses
                        ])
                        ->first();

                    $entity->comment = $transferReason->comment;

                    return $transferReason->_matchingData['StudentTransferReasons']->name;
                    break;

                case 'WITHDRAWN':
                    $WithdrawRequestsTable = TableRegistry::get('Institution.WithdrawRequests');
                    $WorkflowModelsTable = TableRegistry::get('Workflow.WorkflowModels');
                    $approvedStatus = $WorkflowModelsTable->getWorkflowStatusSteps('Institution.StudentWithdraw', 'APPROVED');

                    $withdrawReason = $WithdrawRequestsTable->find()
                        ->matching('StudentWithdrawReasons')
                        ->where([
                            $WithdrawRequestsTable->aliasField('student_id') => $studentId,
                            $WithdrawRequestsTable->aliasField('academic_period_id') => $academicPeriodId,
                            $WithdrawRequestsTable->aliasField('institution_id') => $institutionId,
                            $WithdrawRequestsTable->aliasField('education_grade_id') => $educationGradeId,
                            $WithdrawRequestsTable->aliasField('status_id').' IN ' => $approvedStatus,
                        ])
                        ->first();

                    $comment = '';
                    $studentWithdrawReason = '';
                    if (!empty($withdrawReason)) {
                        $comment = $withdrawReason->comment;
                        $studentWithdrawReason = $withdrawReason->_matchingData['StudentWithdrawReasons']->name;
                    }

                    $entity->comment = $comment;
                    return $studentWithdrawReason;
                    break;
            }
        }
    }

    public function onGetComment(Event $event, Entity $entity)
    {
        if ($this->action == 'view') {
            return nl2br($entity->comment);
        }
    }

    // Start PHPOE-1897
    public function onUpdateActionButtons(Event $event, Entity $entity, array $buttons)
    {
        $buttons = parent::onUpdateActionButtons($event, $entity, $buttons);
        if (isset($buttons['view'])) {
            $url = $this->url('view');
            $userId = $this->paramsEncode(['id' => $entity->_matchingData['Users']->id]);
            $buttons['view']['url'] = array_merge($url, ['action' => 'StudentUser', $userId]);
            $buttons['view']['url'] = $this->setQueryString($buttons['view']['url'], ['institution_student_id' => $entity->id]);

            // POCOR-3125 history button permission to hide and show the link
            if ($this->AccessControl->check(['StudentHistories', 'index'])) {
                $institutionId = $this->paramsEncode(['id' => $entity->institution->id]);

                $icon = '<i class="fa fa-history"></i>';
                $url = [
                    'plugin' => 'Institution',
                    'institutionId' => $institutionId,
                    'controller' => 'StudentHistories',
                    'action' => 'index'
                ];

                $buttons['history'] = $buttons['view'];
                $buttons['history']['label'] = $icon . __('History');
                $buttons['history']['url'] = $this->ControllerAction->setQueryString($url, [
                    'security_user_id' => $entity->_matchingData['Users']->id,
                    'user_type' => 'Student'
                ]);
            }
            // end POCOR-3125 history button permission
        }

        // Remove in POCOR-3010
        if (isset($buttons['edit'])) {
            unset($buttons['edit']);
        }

        /*POCOR-6634 starts - added remove button functionality*/
        if (isset($buttons['remove'])) {
            $institutionId = $entity->institution->id;
            $studentId = $entity->_matchingData['Users']['id'];
            $periodId = $entity->academic_period->id;
            $gradeId = $entity->education_grade->id;
            $toBeDeleteId = $this->find()
                            ->where([
                                $this->aliasField('institution_id') => $institutionId,
                                $this->aliasField('academic_period_id') => $periodId,
                                $this->aliasField('education_grade_id') => $gradeId,
                                $this->aliasField('student_id') => $studentId
                            ])
                            ->first()->id;
            $encodedId = $this->paramsEncode([
                            'id' => $toBeDeleteId
                        ]);
            $attr = [
                'role' => 'menuitem',
                'tabindex' => -1,
                'escape' => false,
                'data-toggle' => 'modal',
                'data-target' => '#delete-modal',
                'field-target' => '#recordId',
                'field-value' => $encodedId,
                'onclick' => 'ControllerAction.fieldMapping(this)'
            ];
            
            $buttons['remove']['attr'] = $attr;
        }
        /*POCOR-6634 ends*/
        return $buttons;
    }
    // End PHPOE-1897

    private function setupTabElements($entity)
    {
        $options['type'] = 'student';
        $tabElements = TableRegistry::get('Institution.StudentUser')->getAcademicTabElements($options);
        $tabElements = $this->controller->TabPermission->checkTabPermission($tabElements);
        $this->controller->set('tabElements', $tabElements);
        $this->controller->set('selectedAction', 'Programmes');
    }

    public function checkEnrolledInInstitution($studentId, $institutionId)
    {
        $statuses = TableRegistry::get('Student.StudentStatuses')->findCodeList();
        $status = $this
            ->find()
            ->where([$this->aliasField('student_id') => $studentId,
                $this->aliasField('institution_id') => $institutionId,
                $this->aliasField('student_status_id') => $statuses['CURRENT']
            ])
            ->count();
        return $status > 0;
    }

    public function checkIfCanTransfer($student, $institutionId)
    {
        $gradeId = ($student->has('education_grade_id'))? $student->education_grade_id: null;
        $studentId = ($student->has('student_id'))? $student->student_id: null;
        if (empty($gradeId) || empty($studentId)) {
            // missing critical parameter - grade, student_id - cant transfer
            return false;
        }

        // check if student exists in current year
        $academicPeriodId = ($student->has('academic_period_id'))? $student->academic_period_id: null;
        $currentAcademicPeriod = $this->AcademicPeriods->getCurrent();

        // POCOR-5003
        //if ($academicPeriodId != $currentAcademicPeriod) {
            //return false;
        //}

        $StudentStatuses = TableRegistry::get('Student.StudentStatuses');
        $studentStatusList = array_flip($StudentStatuses->findCodeList());

        $checkIfCanTransfer = (in_array($studentStatusList[$student->student_status_id], ['CURRENT', 'PROMOTED', 'GRADUATED']));

        // check ruleStudentNotEnrolledInAnyInstitutionAndSameEducationSystem && ruleStudentNotCompletedGrade
        $newSystemId = TableRegistry::get('Education.EducationGrades')->getEducationSystemId($gradeId);
        $validateEnrolledInAnyInstitutionResult = $this->validateEnrolledInAnyInstitution($studentId, $newSystemId, ['excludeInstitutions' => $institutionId]);

        if ($checkIfCanTransfer) {
            if (!empty($validateEnrolledInAnyInstitutionResult) ||
                $this->completedGrade($gradeId, $studentId)) {
                $checkIfCanTransfer = false;
            }
        }

        // additional logic for PROMOTED
        if ($checkIfCanTransfer && $studentStatusList[$student->student_status_id] == 'PROMOTED') {
            //'Promoted' status - this feature will be available if the student is at the last grade that the school offers
            // Education Grades
            $InstitutionEducationGrades = TableRegistry::get('Institution.InstitutionGrades');
            $EducationGrades = TableRegistry::get('Education.EducationGrades');

            $studentEducationGrade = $EducationGrades
                ->find()
                ->where([$EducationGrades->aliasField($EducationGrades->primaryKey()) => $gradeId])
                ->first();

            $currentProgrammeGrades = $EducationGrades
                ->find('list', [
                    'keyField' => 'id',

                    'valueField' => 'programme_grade_name'
                ])
                ->find('visible')
                ->where([
                    $this->EducationGrades->aliasField('order').' > ' => $studentEducationGrade->order,
                    $this->EducationGrades->aliasField('education_programme_id') => $studentEducationGrade->education_programme_id
                ])
                ->toArray();

            $EducationProgrammesNextProgrammesTable = TableRegistry::get('Education.EducationProgrammesNextProgrammes');
            $educationProgrammeId = $studentEducationGrade->education_programme_id;
            $nextEducationGradeList = $EducationProgrammesNextProgrammesTable->getNextGradeList($educationProgrammeId);
            $moreAdvancedEducationGrades = $currentProgrammeGrades + $nextEducationGradeList;

            $studentEducationGradeOrder = [];
            if (!empty($studentEducationGrade)) {
                $studentEducationGradeOrder = $studentEducationGrade->order;
            }

            $advancedGradeOptionsLeft = $InstitutionEducationGrades
                ->find('list', [
                        'keyField' => 'EducationGrades.id',
                        'valueField' => 'EducationGrades.name'
                    ])
                ->select([
                        'EducationGrades.id', 'EducationGrades.name', 'EducationGrades.order'
                    ])
                ->contain(['EducationGrades'])
                ->where(['EducationGrades.order > ' => $studentEducationGradeOrder])
                ->where(['institution_id' => $institutionId])
                ->group('education_grade_id')
                ->toArray()
                ;

            // if there are more advanced grades available to the student, the student cannot transfer
            if (count(array_intersect_key($moreAdvancedEducationGrades, $advancedGradeOptionsLeft))>0) {
                $checkIfCanTransfer = false;
            }
        }
        return $checkIfCanTransfer;
    }

    // Function use by the mini dashboard (For Institution Students)
    public function getNumberOfStudentsByGender($params = [])
    {
        $query = $params['query'];
        $InstitutionRecords = clone $query;
        $InstitutionStudentCount = $InstitutionRecords
            ->matching('Users.Genders')
            ->select([
                'count' => $InstitutionRecords->func()->count('DISTINCT ' . $this->aliasField('student_id')),
                'gender' => 'Genders.name',
                'gender_code' => 'Genders.code'
            ])
            ->group(['gender'], true);

        // Creating the data set
        $dataSet = [
            'M' => [],
            'F' => [],
        ];
        foreach ($InstitutionStudentCount->toArray() as $value) {
            //Compile the dataset
            $dataSet[$value['gender_code']] = [__($value['gender']), $value['count']];
        }
        $params['dataSet'] = array_values($dataSet);
        unset($InstitutionRecords);
        return $params;
    }

    // Function use by the mini dashboard (For Institution Students)
    public function getNumberOfStudentsByAge($params = [])
    {
        $query = $params['query'];
        $InstitutionRecords = $query->cleanCopy();
        $ageQuery = $InstitutionRecords
            ->select([
                'age' => $InstitutionRecords->func()->dateDiff([
                    $InstitutionRecords->func()->now(),
                    'Users.date_of_birth' => 'literal'
                ]),
                'student' => $this->aliasField('student_id')
            ])
            ->distinct(['student'])
            ->order('age');

        $InstitutionStudentCount = $ageQuery->toArray();

        $convertAge = [];

        // (Logic to be reviewed)
        // Calculate the age taking account to the average of leap years
        foreach ($InstitutionStudentCount as $val) {
            $convertAge[] = floor($val['age']/365.25);
        }
        // Count and sort the age
        $result = [];
        $prevValue = ['age' => -1, 'count' => null];
        foreach ($convertAge as $val) {
            if ($prevValue['age'] != $val) {
                unset($prevValue);
                $prevValue = ['age' => $val, 'count' => 0];
                $result[] =& $prevValue;
            }
            $prevValue['count']++;
        }

        // Creating the data set
        $dataSet = [];
        foreach ($result as $value) {
            //Compile the dataset
            $dataSet[] = [__('Age').' '.$value['age'], $value['count']];
        }
        $params['dataSet'] = $dataSet;
        unset($InstitutionRecords);
        return $params;
    }

    // Function use by the mini dashboard (For Institution Students)
    public function getNumberOfStudentsByGradeByInstitution($params = [])
    {
        $query = $params['query'];
        $InstitutionRecords = clone $query;
        $studentByGrades = $InstitutionRecords
            ->select([
                'grade' => 'EducationGrades.name',
                'count' => $query->func()->count('DISTINCT '.$this->aliasField('student_id'))
            ])
            ->contain([
                'EducationGrades'
            ])
            ->group([$this->aliasField('education_grade_id')], true)
            ->toArray();

        $dataSet = [];
        foreach ($studentByGrades as $value) {
            $dataSet[] = [__($value['grade']), $value['count']];
        }
        $params['dataSet'] = $dataSet;
        unset($InstitutionRecords);
        return $params;
    }

    // For Dashboard (Institution Dashboard and Home Page)
    public function getNumberOfStudentsByYear($params = [])
    {
        $conditions = isset($params['conditions']) ? $params['conditions'] : [];
        $_conditions = [];
        foreach ($conditions as $key => $value) {
            $_conditions[$this->alias().'.'.$key] = $value;
        }

        $AcademicPeriod = $this->AcademicPeriods;
        $currentPeriodId = $AcademicPeriod->getCurrent();

        $genderOptions = $this->Users->Genders->getList();
        $dataSet = new ArrayObject();
        foreach ($genderOptions as $key => $value) {
            $dataSet[$value] = ['name' => __($value), 'data' => []];
        }
        $dataSet['Total'] = ['name' => __('Total'), 'data' => []];

        $academicPeriodList = [];
        $found = false;
        foreach ($AcademicPeriod->getYearList() as $periodId => $periodName) {
            if ($found) {
                $academicPeriodList[$periodId] = $periodName;
                break;
            }
            if ($periodId == $currentPeriodId) {
                $academicPeriodList[$periodId] = $periodName;
                $found = true;
            } else {
                $academicPeriodList = [$periodId => $periodName];
            }
        }
        $academicPeriodList = array_reverse($academicPeriodList, true);

        foreach ($academicPeriodList as $periodId => $periodName) {
            if ($periodId == $currentPeriodId) {
            foreach ($dataSet as $dkey => $dvalue) {
                if (!array_key_exists($periodName, $dataSet[$dkey]['data'])) {
                    $dataSet[$dkey]['data'][$periodName] = 0;
                }
            }

            foreach ($genderOptions as $genderId => $genderName) {
                $queryCondition = array_merge(['Genders.id' => $genderId, 'AcademicPeriods.id' => $periodId], $_conditions);

                $studentsByYear = $this
                ->find('list', [
                    'groupField' => 'gender_name',
                    'keyField' => 'period_name',
                    'valueField' => 'total'
                ])
                ->matching('Users.Genders')
                ->matching('AcademicPeriods')
                ->select([
                    'gender_name' => 'Genders.name',
                    'period_name' => 'AcademicPeriods.name',
                    'total' => $this->find()->func()->count('DISTINCT '.$this->aliasField('student_id'))
                ])
                ->where($queryCondition)
                ->group(['gender_name', $this->aliasField('academic_period_id')])
                ->order('AcademicPeriods.order DESC')
                ->hydrate(false)
                ->toArray()
                ;

                if (!empty($studentsByYear)) {
                    $dataSet[$genderName]['data'][$periodName] = $studentsByYear[$genderName][$periodName];
                    $dataSet['Total']['data'][$periodName] += $studentsByYear[$genderName][$periodName];
                }
            }
            }
        }
        $params['dataSet'] = $dataSet->getArrayCopy();

        return $params;
    }

    // For Dashboard (Home Page and Institution Dashboard page)
    public function getNumberOfStudentsByStage($params = [])
    {
        $conditions = isset($params['conditions']) ? $params['conditions'] : [];
        $_conditions = [];
        foreach ($conditions as $key => $value) {
            $_conditions[$this->alias().'.'.$key] = $value;
        }

        $AcademicPeriod = $this->AcademicPeriods;
        $currentYearId = $AcademicPeriod->getCurrent();

        if (!empty($currentYearId)) {
            $currentYear = $AcademicPeriod->get($currentYearId, ['fields'=>'name'])->name;
        } else {
            $currentYear = __('Not Defined');
        }

        $studentsByGradeConditions = [
            $this->aliasField('academic_period_id') => $currentYearId,
            $this->aliasField('education_grade_id').' IS NOT NULL',
            'Genders.name IS NOT NULL'
        ];
        $studentsByGradeConditions = array_merge($studentsByGradeConditions, $_conditions);
        $query = $this->find();
        $studentByGrades = $query
            ->select([
                $this->aliasField('institution_id'),
                $this->aliasField('education_grade_id'),
                'EducationGrades.name',
                'EducationGrades.education_stage_id',
                'EducationStages.name',
                'EducationStages.order',
                'Users.id',
                'Genders.name',
                'total' => $query->func()->count($this->aliasField('id'))
            ])
            ->contain([
                'EducationGrades.EducationStages',
                'EducationGrades.EducationProgrammes.EducationCycles.EducationLevels',
                'Users.Genders'
            ])
            ->where($studentsByGradeConditions)
            ->group([
                'EducationGrades.education_stage_id',
                'Genders.name'
            ])
            ->order(
                ['EducationLevels.order', 'EducationCycles.order', 'EducationProgrammes.order', 'EducationStages.order']
            )
            ->toArray()
            ;


        $grades = [];

        $genderOptions = $this->Users->Genders->getList();
        $dataSet = array();
        foreach ($genderOptions as $key => $value) {
            $dataSet[$value] = array('name' => __($value), 'data' => array());
        }
        $dataSet['Total'] = ['name' => __('Total'), 'data' => []];

        foreach ($studentByGrades as $key => $studentByGrade) {
            $gradeId = $studentByGrade->education_grade->education_stage_id;
            $gradeName = $studentByGrade->education_grade->education_stage->name;
            $gradeGender = $studentByGrade->user->gender->name;
            $gradeTotal = $studentByGrade->total;

            $grades[$gradeId] = $gradeName;

            foreach ($dataSet as $dkey => $dvalue) {
                if (!array_key_exists($gradeId, $dataSet[$dkey]['data'])) {
                    $dataSet[$dkey]['data'][$gradeId] = 0;
                }
            }
            $dataSet[$gradeGender]['data'][$gradeId] = $gradeTotal;
            $dataSet['Total']['data'][$gradeId] += $gradeTotal;
        }

        // $params['options']['subtitle'] = array('text' => 'For Year '. $currentYear);
        $params['options']['subtitle'] = array('text' => sprintf(__('For Year %s'), $currentYear));
        $params['options']['xAxis']['categories'] = array_values($grades);
        $params['dataSet'] = $dataSet;

        return $params;
    }

	// For Dashboard (Home Page and Institution Dashboard page)
    public function getNumberOfStudentsByAttendanceType($params = [])
    {
        $conditions = isset($params['conditions']) ? $params['conditions'] : [];
        $_conditions = [];
        foreach ($conditions as $key => $value) {
            $_conditions[$this->alias().'.'.$key] = $value;
        }

        $AcademicPeriod = $this->AcademicPeriods;
        $currentYearId = $AcademicPeriod->getCurrent();

        if (!empty($currentYearId)) {
            $currentYear = $AcademicPeriod->get($currentYearId, ['fields'=>'name'])->name;
        } else {
            $currentYear = __('Not Defined');
        }

		$studentAttendanceMarkedRecords = TableRegistry::get('student_attendance_marked_records');

        $StudentAttendancesRecords = $studentAttendanceMarkedRecords->find('all')
            ->select([
				'education_grade' => 'educationGrades.name',
				'education_grade_id' => 'educationGrades.id',
				'period' => 'student_attendance_marked_records.period',
				'student_id' => 'InstitutionClassesStudents.student_id',
                'institution_class_id' => 'InstitutionClassesStudents.institution_class_id',
            ])
			->innerJoin(
			['InstitutionClasses' => 'institution_classes'],
			[
				'InstitutionClasses.id = student_attendance_marked_records.institution_class_id '
			]
			)
			->innerJoin(
			['InstitutionClassesStudents' => 'institution_class_students'],
			[
				'InstitutionClassesStudents.institution_class_id = InstitutionClasses.id '
			]
			)
			->innerJoin(
			['InstitutionStudents' => 'institution_students'],
			[
				'InstitutionStudents.student_id = InstitutionClassesStudents.student_id ',
				'InstitutionStudents.academic_period_id = InstitutionClassesStudents.academic_period_id ',
				'InstitutionClassesStudents.student_status_id = 1'
			]
			)
			->innerJoin(
			['educationGrades' => 'education_grades'],
			[
				'educationGrades.id = InstitutionStudents.education_grade_id '
			]
			)
            ->where([
                'student_attendance_marked_records.date' => date('Y-m-d'),
				'student_attendance_marked_records.academic_period_id' => $currentYearId,
				'student_attendance_marked_records.institution_id' => $conditions['institution_id'],
				'educationGrades.id IS NOT NULL',
            ])
            ->distinct(['InstitutionClassesStudents.student_id'])//POCOR-7019
			->order(['educationGrades.id' => 'ASC'])
			->toArray()
            ;
        $periodId = array(1,2);
		foreach ($StudentAttendancesRecords as $key => $record) {

			$InstitutionStudentAbsenceDetails = TableRegistry::get('institution_student_absence_details');
            //POCOR-7050 start
            $configVal = TableRegistry::get('config_items');
            $configData = $configVal->find()->select(['val'=>$configVal->aliasField('value')])->where([$configVal->aliasField('code')=>'calculate_daily_attendance'])->first();
            $configOption = $configData['val']; 
            if($configOption==2){ 
    			$StudentAttendancesData = $InstitutionStudentAbsenceDetails->find('all')
                ->select([
    				'student_id' => 'institution_student_absence_details.student_id',
                    'period' => 'institution_student_absence_details.period',
    				'class_id' => 'institution_student_absence_details.institution_class_id',
    				'present' => '(IF(institution_student_absence_details.absence_type_id IS NULL OR institution_student_absence_details.absence_type_id = 3,1,0))',
    				'absent' => '(IF(institution_student_absence_details.absence_type_id IN (1,2),1,0))',
    				'late' => '(IF(institution_student_absence_details.absence_type_id = 3, 1,0))',
                ])->innerJoin(["(SELECT value from config_items WHERE code = 'calculate_daily_attendance') attendance_config" ])
    			->where([
                    'institution_student_absence_details.date' => date('Y-m-d'),
    				//'institution_student_absence_details.period' => $record->period,
    				'institution_student_absence_details.student_id' => $record->student_id,
                    $InstitutionStudentAbsenceDetails->aliasField('period IN')=>$periodId,
                ])->group([$InstitutionStudentAbsenceDetails->aliasField('student_id'),$InstitutionStudentAbsenceDetails->aliasField('absence_type_id')])
    			->toArray();
            }else{
                $StudentAttendancesData = $InstitutionStudentAbsenceDetails->find('all')
                ->select([
                    'student_id' => 'institution_student_absence_details.student_id',
                    'class_id' => 'institution_student_absence_details.institution_class_id',
                    'present' => '(IF(institution_student_absence_details.absence_type_id IS NULL OR institution_student_absence_details.absence_type_id = 3,1,0))',
                    'absent' => '(IF(institution_student_absence_details.absence_type_id IN (1,2),1,0))',
                    'late' => '(IF(institution_student_absence_details.absence_type_id = 3, 1,0))',
                    
                ])->innerJoin(["(SELECT value from config_items WHERE code = 'calculate_daily_attendance') attendance_config" ])
                ->where([
                    'institution_student_absence_details.date' => date('Y-m-d'),
                   // 'institution_student_absence_details.period' => $record->period,
                    'institution_student_absence_details.student_id' => $record->student_id,
                ])->group([$InstitutionStudentAbsenceDetails->aliasField('student_id')])->toArray();

            } 
            //POCOR-7050 end
            
			$StudentAttendances[$record->education_grade_id][] = array('attendance'=>$StudentAttendancesData, 'education_grade_id'=> $record->education_grade_id, 'education_grade'=>$record->education_grade,'institution_class_id'=> $record->institution_class_id,'student_id'=> $record->student_id);
            
		}
        
        $attendanceData = [];

        $dataSet['Present'] = ['name' => __('Present'), 'data' => []];
        $dataSet['Absent'] = ['name' => __('Absent'), 'data' => []];
        $dataSet['Late'] = ['name' => __('Late'), 'data' => []];

        foreach ($StudentAttendances as $key => $attendances) {
           
            // START: POCOR-6382
			// $total_present = $total_absent = $total_late = 0;
            $total_absent = $total_late = 0;
            $total_present = 0; //POCOR-6900 changed value from -1 to 0 as due to -1 it was not displaying grades bases attendance
            // END: POCOR-6382

			foreach ($attendances as $key => $attendance) {
				$attendanceData[$attendance['education_grade_id']] = $attendance['education_grade'];
                $checkstudent = $InstitutionStudentAbsenceDetails->find()->select(['period'=>$InstitutionStudentAbsenceDetails->aliasField('period')])->where([$InstitutionStudentAbsenceDetails->aliasField('student_id')=>$attendance['student_id'],$InstitutionStudentAbsenceDetails->aliasField('education_grade_id')=>$attendance['education_grade_id'],$InstitutionStudentAbsenceDetails->aliasField('institution_class_id')=>$attendance['institution_class_id'],$InstitutionStudentAbsenceDetails->aliasField('date') => date('Y-m-d')])->toArray();
                $periodCount = count($checkstudent);
                $checkdata = $studentAttendanceMarkedRecords->find()->select(['institution_class_id'=>$studentAttendanceMarkedRecords->aliasField('institution_class_id')])->where([$studentAttendanceMarkedRecords->aliasField('period')=>1,$studentAttendanceMarkedRecords->aliasField('period')=>2,$studentAttendanceMarkedRecords->aliasField('institution_class_id')=>$attendance['institution_class_id']])->toArray();
				if(!empty($attendance['attendance'])) {
					foreach ($attendance['attendance'] as $key => $markAttendanceData) {
                        //add these if else condition for dashboard count data //POCOR-7050
                        if($configOption==2 && $periodCount==1 && !empty($checkdata)){
                            $total_present = $markAttendanceData->present + $total_present;
                            $absent = $markAttendanceData->absent;
                            $total_present = $total_present + $absent;
                            $total_late = $markAttendanceData->late + $total_late;
                        }elseif($configOption==2 && $periodCount==2 && !empty($checkdata)){
                            $total_present = $markAttendanceData->present + $total_present;
                            $total_absent = $markAttendanceData->absent + $total_absent;
                            $total_late = $markAttendanceData->late + $total_late;
                        }elseif($configOption==2 && $periodCount==0 && !empty($checkdata)){
                            $total_present = $markAttendanceData->present + $total_present;
                            $total_absent = $markAttendanceData->absent + $total_absent;
                            $total_late = $markAttendanceData->late + $total_late;
                        }elseif($configOption==2 && $periodCount==1 && empty($checkdata)){
                            $total_present = $markAttendanceData->present + $total_present;
                            $total_absent = $markAttendanceData->absent + $total_absent;
                            $total_late = $markAttendanceData->late + $total_late;
                        }else{
    						$total_present = $markAttendanceData->present + $total_present;
    						$total_absent = $markAttendanceData->absent + $total_absent;
    						$total_late = $markAttendanceData->late + $total_late;
                        }
					}
				} else {
					$total_present = $total_present + 1;
				}
				foreach ($dataSet as $dkey => $dvalue) {
					if (!array_key_exists($attendance['education_grade_id'], $dataSet[$dkey]['data'])) {
						$dataSet[$dkey]['data'][$attendance['education_grade_id']] = 0;
					}
				}

				$dataSet['Present']['data'][$attendance['education_grade_id']] = $total_present;
				$dataSet['Absent']['data'][$attendance['education_grade_id']] = $total_absent;
				$dataSet['Late']['data'][$attendance['education_grade_id']] = $total_late;
			}
		}
        // $params['options']['subtitle'] = array('text' => 'For Year '. $currentYear);
        $params['options']['subtitle'] = array('text' => __('For Today'));
        $params['options']['xAxis']['categories'] = array_values($attendanceData);
        $params['dataSet'] = $dataSet;
        return $params;
    }

    public function completedGrade($educationGradeId, $studentId, $academic_period_id = null)
    {
        $StudentStatuses = TableRegistry::get('Student.StudentStatuses');

        $statuses = $StudentStatuses->findCodeList();
        $completedGradeCount = $this->find()
            ->where([
                $this->aliasField('education_grade_id') => $educationGradeId,
                $this->aliasField('student_id') => $studentId,
                $this->aliasField('student_status_id').' IN ' => [$statuses['GRADUATED'], $statuses['PROMOTED']]
            ])
            ->count()
            ;

        return !($completedGradeCount == 0);
    }

    public function completedGradeNew($educationGradeId, $studentId, $academic_period_id = null, $education_grade_code = null)
    {
        $StudentStatuses = TableRegistry::get('Student.StudentStatuses');
        $statuses = $StudentStatuses->findCodeList();
        $EducationGrades = TableRegistry::get('Education.EducationGrades');
        $StudentEducationGradesData = $this->find('all')
        ->select([
            'education_grade' => 'EducationGrades.code'
        ])
        ->innerJoin(
        ['EducationGrades' => 'education_grades'],
        [
            ('EducationGrades.id = ') . $this->aliasField('education_grade_id')
        ]
        )
        ->where([
            $this->aliasField('student_id') =>  $studentId,
            $this->aliasField('student_status_id').' IN ' => [$statuses['GRADUATED'], $statuses['PROMOTED']]
        ])
        ->extract('education_grade')
        ->toArray()
        ;
        $flag = 1;
        if (in_array($education_grade_code, $StudentEducationGradesData))
        {
            $flag = 1;
        }
        else
        {
            $flag = 0;
        }
        return !($flag == 0);

        // $StudentStatuses = TableRegistry::get('Student.StudentStatuses');
        // $statuses = $StudentStatuses->findCodeList();
        // //Check Education grade order for request educationGradeId
        // $EducationGrades = TableRegistry::get('Education.EducationGrades');
        // $EducationGradesData = $EducationGrades->find()
        //     ->where([
        //         $EducationGrades->aliasField('id') => $educationGradeId
        //     ])
        //     ->extract('order')
        //     ->first()
        //     ;
        // //get Education grade ids currently asssigned to student
        // $StudentEducationGradesData = $this->find()
        // ->where([
        //     $this->aliasField('student_id') =>  $studentId,
        //     $this->aliasField('student_status_id').' IN ' => [$statuses['GRADUATED'], $statuses['PROMOTED']]
        // ])
        // // ->extract('education_grade_id')
        // ->order([$this->aliasField('created DESC')])
        // ->first()
        // ;
        
        // //Check Education grade order for currently educationGradeId
        // if(!empty($StudentEducationGradesData)){
        //     $checkNotApplicable = $EducationGrades->find()
        //     ->where([
        //         $EducationGrades->aliasField('id') => $StudentEducationGradesData->education_grade_id
        //     ])
        //     ->extract('order')
        //     ->first();
        //     $flag = 1;
        //     if($EducationGradesData > $checkNotApplicable){
        //         $flag = 0;
        //     }
        // }
        // return !($flag == 0);
    }

    public function institutionStudentRiskCalculateRiskValue(Event $event, ArrayObject $params)
    {
        $institutionId = $params['institution_id'];
        $studentId = $params['student_id'];
        $academicPeriodId = $params['academic_period_id'];
        $criteriaName = $params['criteria_name'];

        $valueIndex = $this->getValueIndex($institutionId, $studentId, $academicPeriodId, $criteriaName);

        return $valueIndex;
    }

    public function getValueIndex($institutionId, $studentId, $academicPeriodId, $criteriaName)
    {
        switch ($criteriaName) {
            case 'StatusRepeated':
                $statusRepeatedResults = $this->find()
                    ->where([
                        'student_id' => $studentId
                    ])
                    ->all();

                $getValueIndex = [];
                foreach ($statusRepeatedResults as $obj) {
                    $statusId = $obj->student_status_id;

                    // for '=' the value index will be in array (valueIndex[threshold] = value)
                    $getValueIndex[$statusId] = !empty($getValueIndex[$statusId]) ? $getValueIndex[$statusId] : 0;
                    $getValueIndex[$statusId] = $getValueIndex[$statusId] + 1;
                }

                return $getValueIndex;
                break;

            case 'Overage':
                $getValueIndex = 0;
                $results = $this->find()
                    ->contain(['Users', 'EducationGrades'])
                    ->where([
                        'student_id' => $studentId,
                        'student_status_id' => 1,  // student status current
                    ])
                    ->first();

                if (!empty($results)) {
                    $educationGradeId = $results->education_grade_id;
                    $educationProgrammeId = $this->EducationGrades->get($educationGradeId)->education_programme_id;
                    $admissionAge = $this->EducationGrades->getAdmissionAge($educationGradeId);
                    $schoolStartYear = $results->start_year;
                    $birthdayYear = $results->user->date_of_birth->format('Y');

                    $getValueIndex = ($schoolStartYear - $birthdayYear) - $admissionAge;
                }

                return $getValueIndex;
                break;

            case 'Genders':
                $getValueIndex = [];
                $results = $this->find()
                    ->contain(['Users', 'EducationGrades'])
                    ->where([
                        'student_id' => $studentId,
                        'student_status_id' => 1,  // student status current
                    ])
                    ->first();

                if (!empty($results)) {
                    // for '=' the value index will be in array (valueIndex[threshold] = value)
                    $getValueIndex[$results->user->gender_id] = 1;
                }

                return $getValueIndex;
                break;

            case 'Guardians':
                $getValueIndex = 0;
                $results = $this->find()
                    ->contain(['Users', 'EducationGrades'])
                    ->where([
                        'student_id' => $studentId,
                        'student_status_id' => 1,  // student status current
                    ])
                    ->first();

                if (!empty($results)) {
                    $Guardians = TableRegistry::get('Student.Guardians');

                    $guardiansData = $Guardians->find()
                        ->where(['student_id' => $results->student_id])
                        ->all()->toArray();

                    $getValueIndex = count($guardiansData);
                }

                return $getValueIndex;
                break;
        }
    }

    public function getReferenceDetails($institutionId, $studentId, $academicPeriodId, $threshold, $criteriaName)
    {
        $referenceDetails = [];

        switch ($criteriaName) {
            case 'StatusRepeated':
                $statusId = $threshold; // it will classified by the status Id
                $results = $this->find()
                    ->contain(['StudentStatuses', 'AcademicPeriods'])
                    ->where([
                        'student_id' => $studentId,
                        'student_status_id' => $statusId
                    ])
                    ->all();

                foreach ($results as $key => $obj) {
                    $title = $obj->student_status->name;
                    $date = $obj->academic_period->name;

                    $referenceDetails[$obj->id] = __($title) . ' (' . $date . ')';
                }

                break;

            case 'Overage':
                $results = $this->find()
                    ->contain(['Users', 'EducationGrades'])
                    ->where([
                        'student_id' => $studentId,
                        'student_status_id' => 1 // status enrolled
                    ])
                    ->all();

                foreach ($results as $key => $obj) {
                    $title = $obj->education_grade->name;
                    $date = $obj->user->date_of_birth->format('d/m/Y');

                    $referenceDetails[$obj->id] = __($title) . ' (' . __('Born on') . ': ' . $date . ')';
                }

                break;

            case 'Genders':
                $Genders = TableRegistry::get('User.Genders');

                $results = $this->find()
                    ->contain(['Users', 'EducationGrades'])
                    ->where([
                        'student_id' => $studentId,
                        'student_status_id' => 1 // status enrolled
                    ])
                    ->all();

                foreach ($results as $key => $obj) {
                    $referenceDetails[$obj->id] = __($Genders->get($obj->user->gender_id)->name);
                }

                break;

            case 'Guardians':
                $Guardians = TableRegistry::get('Student.Guardians');

                $results = $Guardians->find()
                    ->contain(['Users', 'GuardianRelations'])
                    ->where(['student_id' => $studentId])
                    ->all();

                if (!$results->isEmpty()) {
                    foreach ($results as $key => $obj) {
                        $guardianName = $obj->user->first_name . ' ' . $obj->user->last_name;
                        $guardianRelation = $obj->guardian_relation->name;

                        $referenceDetails[$obj->guardian_id] = $guardianName . ' (' . __($guardianRelation) . ')';
                    }
                } else {
                    $referenceDetails[] =  __('No Guardian');
                }

                break;
        }

        // tooltip only receieved string to be display
        $reference = '';
        foreach ($referenceDetails as $key => $referenceDetailsObj) {
            $reference = $reference . $referenceDetailsObj . '<br/>';
        }

        return $reference;
    }

    public function getInstitutionIdByUser($studentId, $academicPeriodId)
    {
        $institutionId = null;
        $record = $this->find()
            ->where([
                $this->aliasField('student_id') => $studentId,
                $this->aliasField('academic_period_id') => $academicPeriodId
            ])
            ->order([$this->aliasField('start_date') => 'DESC'])
            ->all();

        if (!$record->isEmpty()) {
            $institutionId = $record->first()->institution_id;
        }

        return $institutionId;
    }


    private function triggerAutomatedStudentWithdrawalShell() {

        $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
        $daysAbsent= $ConfigItems->value("automated_student_days_absent");
        $dateTimeFormat= $ConfigItems->value("date_time_format");
        $withdrawalEnable= $ConfigItems->value("automated_student_withdrawal");
        $AcademicPeriod = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $currentDate = date('d-m-Y');
        $currentYearId = $AcademicPeriod->getCurrent();

        if (strtotime($dateTimeFormat) < strtotime($currentDate)  && $withdrawalEnable == 1) {

            $InstitutionStudentAbsenceDays = TableRegistry::get('Institution.InstitutionStudentAbsenceDays');
            $data = $InstitutionStudentAbsenceDays
            ->find()
            ->where([
                $InstitutionStudentAbsenceDays->aliasField('absent_days') => $daysAbsent,
                $InstitutionStudentAbsenceDays->aliasField('absence_type_id') => 2
            ])->all();
            if (!$data->isEmpty()) {
                $InstitutionStudents = TableRegistry::get('Institution.InstitutionStudents');
                foreach ($data as $key => $value) {
                    $conditions = [
                        $InstitutionStudents->aliasField('academic_period_id = ') => $currentYearId,
                        $InstitutionStudents->aliasField('student_id = ') => $value['student_id'],
                        $InstitutionStudents->aliasField('institution_id = ') => $value['institution_id'],
                        $InstitutionStudents->aliasField('student_status_id = ') => 1,
                    ];
                    $StudentStatusUpdate = $InstitutionStudents
                    ->find()
                    ->where($conditions)
                    ->all();
                    if (!$StudentStatusUpdate->isEmpty()) {
                        $statusUpdate = $StudentStatusUpdate->first();
                    //update Institution Students table
                        $InstitutionStudents
                        ->updateAll(['student_status_id' => 4],['id' => $statusUpdate->id]);


                    //update institution_class_students table
                        $InstitutionClassStudents = TableRegistry::get('Institution.InstitutionClassStudents');
                        $conditionsClassStudents = [
                            $InstitutionClassStudents->aliasField('academic_period_id = ') => $currentYearId,
                            $InstitutionClassStudents->aliasField('student_id = ') => $value['student_id'],
                            $InstitutionClassStudents->aliasField('institution_id = ') => $value['institution_id'],
                            $InstitutionClassStudents->aliasField('student_status_id = ') => 1,
                        ];

                        $ClassStudentsStatusUpdate = $InstitutionClassStudents
                        ->find()
                        ->where($conditionsClassStudents)
                        ->all();
                        if (!$ClassStudentsStatusUpdate->isEmpty()) {
                            $ClassStudentsUpdate = $ClassStudentsStatusUpdate->first();
                            $InstitutionClassStudents
                            ->updateAll(['student_status_id' => 4],['id' => $ClassStudentsUpdate->id]);

                        }

                        $StudentWithdraw = TableRegistry::get('Institution.StudentWithdraw');
                        $conditions = [
                            $StudentWithdraw->aliasField('academic_period_id = ') => $currentYearId,
                            $StudentWithdraw->aliasField('student_id = ') => $value['student_id'],
                            $StudentWithdraw->aliasField('institution_id = ') => $value['institution_id'],
                            $StudentWithdraw->aliasField('education_grade_id = ') => $statusUpdate->education_grade_id,
                        ];

                        $StudentWithdrawAdd = $StudentWithdraw
                        ->find()
                        ->where($conditions)
                        ->all();

                        if ($StudentWithdrawAdd->isEmpty()) {
                            $date = date('Y-m-d H:i:s');
                            $newStudentWithdraw = [
                                'effective_date' => $date,
                                'status_id' => 76,
                                'student_id' => $value['student_id'],
                                'institution_id' => $value['institution_id'],
                                'education_grade_id' => $statusUpdate->education_grade_id,
                                'academic_period_id' => $currentYearId,
                                'student_withdraw_reason_id' => 669,
                                'comment' => "dropout",
                                'created' => $date,
                                'created_user_id' => 1

                            ];

                            $StudentWithdraw
                            ->query()
                            ->insert(['effective_date', 'status_id','student_id','institution_id','education_grade_id','academic_period_id','student_withdraw_reason_id','comment','created', 'created_user_id'])
                            ->values($newStudentWithdraw)
                            ->execute();
                        }

                    }
                }
            }

        }
    }
}
