<?php

namespace Institution\Model\Table;

use App\Model\Table\ControllerActionTable;
use ArrayObject;
use Cake\Core\Configure;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Utility\Inflector;

class StudentUserExportTable extends ControllerActionTable
{
    private $studentsTabsData = [
        0 => "General",
        1 => "Academic",
        2 => "Assessment",
        3 => "Absence"
    ];
    // POCOR-6130 custome fields code
    private $_dynamicFieldName = 'custom_field_data';

    // POCOR-6130 custome fields code

    private static function debug($something)
    {
        if (is_null($something)) {
            $message = 'NULL';
        } elseif (is_bool($something)) {
            $message = $something ? 'TRUE' : 'FALSE';
        } elseif (is_array($something) || is_object($something)) {
            $message = json_encode($something, JSON_PRETTY_PRINT);
        } else {
            $message = (string)$something;
        }

        \Cake\Log\Log::debug($message);
    }

    /**
     * @param string $tableName
     * @return Table
     *
     */
    private static function getDynamicTableInstance(string $tableName): Table
    {
        $locator = TableRegistry::getTableLocator();;
        try {
            return $locator->get($tableName);
        } catch (\Exception $exception) {

        }
        // Parse plugin and table names if dot notation is used
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
            self::debug([$tableFullAlias, $tableAlias]);
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

    public function initialize(array $config): void
    {
        $this->setTable('security_users');
        $this->setEntityClass('User.User');
        parent::initialize($config);

        // Associations
        self::handleAssociations($this);

        // Behaviors
        $this->addBehavior('User.User');

        $this->addBehavior('Excel', [
            'excludes' => ['photo_name', 'is_student', 'is_staff', 'is_guardian', 'super_admin', 'date_of_death'],
            'filename' => 'Students',
            'pages' => ['view']
        ]);

        $this->addBehavior('TrackActivity', ['target' => 'User.UserActivities', 'key' => 'security_user_id', 'session' => 'Student.Students.id']);
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Students' => ['index', 'add', 'edit']
        ]);
        if (!in_array('Risks', (array)Configure::read('School.excludedPlugins'))) {
            $this->addBehavior('Risk.Risks');
        }

        $this->toggle('index', false);
        $this->toggle('remove', false);
    }

    public static function handleAssociations($model)
    {
        $model->belongsTo('Genders', ['className' => 'User.Genders']);
        $model->belongsTo('AddressAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'address_area_id']);
        $model->belongsTo('BirthplaceAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'birthplace_area_id']);
        $model->belongsTo('MainNationalities', ['className' => 'FieldOption.Nationalities', 'foreignKey' => 'nationality_id']);
        $model->belongsTo('MainIdentityTypes', ['className' => 'FieldOption.IdentityTypes', 'foreignKey' => 'identity_type_id']);

        $model->hasMany('Identities', ['className' => 'User.Identities', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $model->hasMany('Nationalities', ['className' => 'User.UserNationalities', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $model->hasMany('Contacts', ['className' => 'User.Contacts', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $model->hasMany('Attachments', ['className' => 'User.Attachments', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $model->hasMany('BankAccounts', ['className' => 'User.BankAccounts', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $model->hasMany('Comments', ['className' => 'User.Comments', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $model->hasMany('Languages', ['className' => 'User.UserLanguages', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $model->hasMany('Awards', ['className' => 'User.Awards', 'foreignKey' => 'security_user_id', 'dependent' => true]);

        $model->hasMany('SpecialNeeds', ['className' => 'SpecialNeeds.SpecialNeedsAssessments', 'foreignKey' => 'security_user_id', 'dependent' => true]);

        $model->belongsToMany('SecurityRoles', [
            'className' => 'Security.SecurityRoles',
            'foreignKey' => 'security_role_id',
            'targetForeignKey' => 'security_user_id',
            'through' => 'Security.SecurityGroupUsers',
            'dependent' => true
        ]);

        $model->hasMany('ClassStudents', [
            'className' => 'Institution.InstitutionClassStudents',
            'foreignKey' => 'student_id'
        ]);

        // remove all student records from institution_students, institution_site_student_absences, student_behaviours, assessment_item_results, student_guardians, institution_student_admission, student_custom_field_values, student_custom_table_cells, student_fees, student_extracurriculars


        $model->belongsToMany('Institutions', [
            'className' => 'Institution.Institutions',
            'joinTable' => 'institution_students',
            'foreignKey' => 'student_id',
            'targetForeignKey' => 'institution_id',
            'through' => 'Institution.Students',
            'dependent' => true
        ]);

        $model->hasMany('InstitutionStudents', ['className' => 'Institution.Students', 'foreignKey' => 'student_id', 'dependent' => true]);
        $model->hasMany('InstitutionStaff', ['className' => 'Institution.Staff', 'foreignKey' => 'staff_id', 'dependent' => true]);
        $model->hasMany('StudentAbsences', ['className' => 'Institution.InstitutionSiteStudentAbsences', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $model->hasMany('StudentBehaviours', ['className' => 'Institution.StudentBehaviours', 'foreignKey' => 'student_id', 'dependent' => true]);
        $model->hasMany('AssessmentItemResults', ['className' => 'Assessment.AssessmentItemResults', 'foreignKey' => 'student_id', 'dependent' => true]);
        $model->belongsToMany('Guardians', [
            'className' => 'Student.Guardians',
            'foreignKey' => 'student_id',
            'targetForeignKey' => 'guardian_id',
            'through' => 'Student.StudentGuardians',
            'dependent' => true
        ]);
        $model->hasMany('StudentAdmission', ['className' => 'Institution.StudentAdmission', 'foreignKey' => 'student_id', 'dependent' => true]);
        $model->hasMany('StudentCustomFieldValues', ['className' => 'CustomField.StudentCustomFieldValues', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $model->hasMany('StudentCustomTableCells', ['className' => 'CustomField.StudentCustomTableCells', 'foreignKey' => 'security_user_id', 'dependent' => true]);
        $model->hasMany('StudentFees', ['className' => 'Institution.StudentFeesAbstract', 'foreignKey' => 'student_id', 'dependent' => true]);
        $model->hasMany('Extracurriculars', ['className' => 'Student.Extracurriculars', 'foreignKey' => 'security_user_id', 'dependent' => true]);
    }

    // POCOR-5684
    // POCOR-6130 adding tabs in sheet

    public function beforeMarshal(EventInterface $event, ArrayObject $data, ArrayObject $options)
    {
        $options['associated']['Nationalities'] = [
            'validate' => 'AddByAssociation'
        ];
        $options['associated']['Identities'] = [
            'validate' => 'AddByAssociation'
        ];
    }

    // POCOR-6130

    public function beforeAction(EventInterface $event, ArrayObject $extra)
    {
        // this value comes from the list page from StudentsTable->onUpdateActionButtons
        $institutionStudentId = $this->getQueryString('institution_student_id');
        $institutionId = $this->getQueryString('institution_id');
        $studentId = $this->getQueryString('student_id');
        $extra['institutionStudentId'] = $institutionStudentId;
        $extra['studentId'] = $studentId;
        $extra['institutionId'] = $institutionId;
    }

    public function onExcelBeforeStart(EventInterface $event, ArrayObject $settings, ArrayObject $sheets)
    {
        unset($sheets[0]);
        $studentsTabsData = $this->studentsTabsData;
        $InstitutionStudents = self::getDynamicTableInstance('institution_students');
        $institutionStudentId = $settings['id'];

        foreach ($studentsTabsData as $key => $val) {
            $tabsName = $val . 's';
            $sheets[] = [
                'sheetData' => [
                    'student_tabs_type' => $val
                ],
                'name' => $tabsName,
                'table' => $this,
                'query' => $this
                    ->find()
                /* ->leftJoin([$InstitutionStudents->alias() => $InstitutionStudents->table()],[
                    $this->aliasField('id = ').$InstitutionStudents->aliasField('student_id')
                ])
                ->where([
                    $InstitutionStudents->aliasField('student_id = ').$institutionStudentId,
                ]) */,
                'orientation' => 'landscape'
            ];
        }
    }

    public function onExcelUpdateFields(EventInterface $event, ArrayObject $settings, ArrayObject $fields)
    {
        $sheetData = $settings['sheet']['sheetData'];
        $StudentType = $sheetData['student_tabs_type'];

        $newFields = [];
        if ($StudentType == 'General') {
            $IdentityType = self::getDynamicTableInstance('FieldOption.IdentityTypes');
            $identity = $IdentityType->getDefaultEntity();

            $extraField[] = [
                "key" => "StudentUser.username",
                "field" => "username",
                "type" => "string",
                "label" => "Username"
            ];

            $extraField[] = [
                "key" => "StudentUser.openemis_no",
                "field" => "openemis_no",
                "type" => "string",
                "label" => "OpenEMIS ID"
            ];

            $extraField[] = [
                'key' => 'StudentUser.first_name',
                'field' => 'first_name',
                'type' => 'string',
                'label' => 'First Name'
            ];

            $extraField[] = [
                'key' => 'StudentUser.middle_name',
                'field' => 'middle_name',
                'type' => 'string',
                'label' => 'Middle Name'
            ];

            $extraField[] = [
                'key' => 'StudentUser.third_name',
                'field' => 'third_name',
                'type' => 'string',
                'label' => 'Third Name'
            ];

            $extraField[] = [
                'key' => 'StudentUser.last_name',
                'field' => 'last_name',
                'type' => 'string',
                'label' => 'Last Name'
            ];

            $extraField[] = [
                'key' => 'StudentUser.preferred_name',
                'field' => 'preferred_name',
                'type' => 'string',
                'label' => __('Preferred Name')
            ];

            $extraField[] = [
                'key' => 'StudentUser.email',
                'field' => 'email',
                'type' => 'string',
                'label' => __('Email')
            ];

            $extraField[] = [
                'key' => 'StudentUser.address',
                'field' => 'address',
                'type' => 'string',
                'label' => __('Address')
            ];

            $extraField[] = [
                'key' => 'StudentUser.postal_code',
                'field' => 'postal_code',
                'type' => 'string',
                'label' => __('Postal Code')
            ];

            $extraField[] = [
                'key' => 'StudentUser.address_area_id',
                'field' => 'address_area_id',
                'type' => 'string',
                'label' => __('Address Area')
            ];

            $extraField[] = [
                'key' => 'StudentUser.birthplace_area_id',
                'field' => 'birthplace_area_id',
                'type' => 'string',
                'label' => __('Birthplace Area')
            ];

            $extraField[] = [
                'key' => 'StudentUser.gender_id',
                'field' => 'gender_id',
                'type' => 'integer',
                'label' => 'Gender'
            ];

            $extraField[] = [
                'key' => 'StudentUser.date_of_birth',
                'field' => 'date_of_birth',
                'type' => 'string',
                'label' => 'Date Of Birth'
            ];

            $extraField[] = [
                'key' => 'StudentUser.nationality_id',
                'field' => 'nationality_id',
                'type' => 'integer',
                'label' => __('Nationality')
            ];

            $extraField[] = [
                'key' => 'StudentUser.identity_number',
                'field' => 'identity_number',
                'type' => 'string',
                'label' => __($identity->name)
            ];

            /* $extraField[] = [
                 'key' => 'StudentUser.external_reference',
                 'field' => 'external_reference',
                 'type' => 'string',
                 'label' => __('External Reference')
             ];*/
            $extraField[] = [
                'key' => 'StudentUser.status',
                'field' => 'status',
                'type' => 'integer',
                'label' => __('Status')
            ];

            $extraField[] = [
                'key' => 'StudentUser.last_login',
                'field' => 'last_login',
                'type' => 'datetime',
                'label' => __('Last Login')
            ];
            $extraField[] = [
                'key' => 'StudentUser.preferred_language',
                'field' => 'preferred_language',
                'type' => 'string',
                'label' => __('Preferred Language')
            ];

            // POCOR-6129 custome fields code
            $InfrastructureCustomFields = self::getDynamicTableInstance('student_custom_fields');
            $customFieldData = $InfrastructureCustomFields->find()->select([
                'custom_field_id' => $InfrastructureCustomFields->aliasfield('id'),
                'custom_field' => $InfrastructureCustomFields->aliasfield('name')
            ])->group($InfrastructureCustomFields->aliasfield('id'))->toArray();

            if (!empty($customFieldData)) {
                foreach ($customFieldData as $data) {
                    $custom_field_id = $data->custom_field_id;
                    $custom_field = $data->custom_field;
                    $extraField[] = [
                        'key' => '',
                        'field' => $this->_dynamicFieldName . '_' . $custom_field_id,
                        'type' => 'string',
                        'label' => __($custom_field)
                    ];
                }
            }
            // POCOR-6129 custome fields code

            $fields->exchangeArray($extraField);
        }

        if ($StudentType == 'Academic') {
            $newFields[] = [
                'key' => '',
                'field' => 'academic_period_name',
                'type' => 'string',
                'alias' => 'academic_period_name',
                'label' => __('Academic Period')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'institution_name',
                'type' => 'string',
                'label' => __('Institution')
            ];
            $newFields[] = [
                'key' => '',
                'field' => 'education_programme',
                'type' => 'string',
                'label' => __('Education Programme')
            ];
            $newFields[] = [
                'key' => '',
                'field' => 'education_grade_name',
                'type' => 'string',
                'label' => __('Education Grade')
            ];
            $newFields[] = [
                'key' => '',
                'field' => 'start_date_name',
                'type' => 'date',
                'label' => __('Start Date')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'end_date_name',
                'type' => 'date',
                'label' => __('End Date')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'current_class_name',
                'type' => 'string',
                'label' => __('Current Class')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'student_status_name',
                'type' => 'string',
                'label' => __('Student Status')
            ];

            $fields->exchangeArray($newFields);
        }

        if ($StudentType == 'Assessment') {
            $newFields[] = [
                'key' => '',
                'field' => 'asses_academic_period',
                'type' => 'string',
                'label' => __('Academic Period')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'asses_institution_name',
                'type' => 'string',
                'label' => __('Institution')
            ];
            $newFields[] = [
                'key' => '',
                'field' => 'assessment_period',
                'type' => 'string',
                'label' => __('Assessment Periods')
            ];
            $newFields[] = [
                'key' => '',
                'field' => 'education_subject',
                'type' => 'string',
                'label' => __('Subject')
            ];
            $newFields[] = [
                'key' => '',
                'field' => 'marks',
                'type' => 'string',
                'label' => __('Mark')
            ];

            $fields->exchangeArray($newFields);
        }

        if ($StudentType == 'Absence') {
            $newFields[] = [
                'key' => '',
                'field' => 'absense_date',
                'type' => 'date',
                'label' => __('Date')
            ];

            $newFields[] = [
                'key' => '',
                'field' => 'absense',
                'type' => 'string',
                'label' => __('Absense')
            ];

            $fields->exchangeArray($newFields);
        }

    }

    public function onExcelGetIdentityNumber(EventInterface $event, Entity $entity)
    {

        $users = self::getDynamicTableInstance('user_identities');
        $result = $users->find()->select(['number'])->where(['identity_type_id' => 160, 'security_user_id' => $entity->id])->first();
        return $result->number;

    }

    public function onExcelGetDateOfBirth(EventInterface $event, Entity $entity)
    {
        return $this->formatDate($entity->date_of_birth);
    }

    // needs to migrate

    public function onExcelBeforeQuery(EventInterface $event, ArrayObject $settings, Query $query)
    {
//        self::debug($settings);
        $params = $this->getQueryString();
        $this->institution_id = $params['institution_id'];
        $this->student_id = $params['student_id'];
        $query->where([$this->aliasField('id') => $this->student_id]);
        $this->institution_student_id = $params['institution_student_id'];
        $InstitutionStudents = self::getDynamicTableInstance('institution_students');
        $ClassStudents = self::getDynamicTableInstance('Institution.InstitutionClassStudents');
        $Classes = self::getDynamicTableInstance('Institution.InstitutionClasses');
        $institutionStudentId = $this->student_id;

        // for Academic Tab
        $AcademicPeriods = self::getDynamicTableInstance('academic_periods');
        $institutions = self::getDynamicTableInstance('institutions');
        $EducationGrades = self::getDynamicTableInstance('education_grades');
        $EducationProgrammes = self::getDynamicTableInstance('education_programmes');
        $StudentStatuses = self::getDynamicTableInstance('student_statuses');
        // for Academic Tab

        // for abesense
        $institutionStudentAbsenses = self::getDynamicTableInstance('institution_student_absences');
        $absensesTypes = self::getDynamicTableInstance('absence_types');
        // for abesense

        // for assessments
        $Assessments = self::getDynamicTableInstance('assessments');
        $AssessmentPeriods = self::getDynamicTableInstance('assessment_periods');
        $AssessmentItemResults = self::getDynamicTableInstance('assessment_item_results');
        $EducationSubjects = self::getDynamicTableInstance('education_subjects');
        // for assessments

        $sheetData = $settings['sheet']['sheetData'];
        $StudentType = $sheetData['student_tabs_type'];

        // for Generals Tab
        if ($StudentType == 'General') {
            $query->formatResults(function (\Cake\Collection\CollectionInterface $results) {

                return $results->map(function ($row) {

                    // POCOR-6130 custome fields code
                    $customFieldsTable = self::getDynamicTableInstance('student_custom_field_values');
                    $studentCustomFieldOptions = self::getDynamicTableInstance('student_custom_field_options');
                    $studentCustomFields = self::getDynamicTableInstance('student_custom_fields');

                    $customFieldsData = $customFieldsTable->find()
                        ->select([
                            'id' => $customFieldsTable->aliasField('id'),
                            'student_id' => $customFieldsTable->aliasField('student_id'),
                            'student_custom_field_id' => $customFieldsTable->aliasField('student_custom_field_id'),
                            'text_value' => $customFieldsTable->aliasField('text_value'),
                            'number_value' => $customFieldsTable->aliasField('number_value'),
                            'decimal_value' => $customFieldsTable->aliasField('decimal_value'),
                            'textarea_value' => $customFieldsTable->aliasField('textarea_value'),
                            'date_value' => $customFieldsTable->aliasField('date_value'),
                            'time_value' => $customFieldsTable->aliasField('time_value'),
                            'checkbox_value_text' => 'studentCustomFieldOptions.name',
                            'question_name' => 'studentCustomField.name',
                            'field_type' => 'studentCustomField.field_type',
                            'field_description' => 'studentCustomField.description',
                            'question_field_type' => 'studentCustomField.field_type',
                        ])->leftJoin(
                            ['studentCustomField' => 'student_custom_fields'],
                            [
                                'studentCustomField.id = ' . $customFieldsTable->aliasField('student_custom_field_id')
                            ]
                        )->leftJoin(
                            ['studentCustomFieldOptions' => 'student_custom_field_options'],
                            [
                                'studentCustomFieldOptions.id = ' . $customFieldsTable->aliasField('number_value')
                            ]
                        )
                        ->where([
                            $customFieldsTable->aliasField('student_id') => $row['id'],
                        ])->toArray();

                    $existingCheckboxValue = '';
                    foreach ($customFieldsData as $customFieldsRow) {
                        $fieldType = $customFieldsRow->field_type;
                        if ($fieldType == 'TEXT') {
                            $row[$this->_dynamicFieldName . '_' . $customFieldsRow->student_custom_field_id] = $customFieldsRow->text_value;
                        } else if ($fieldType == 'CHECKBOX') {
                            $existingCheckboxValue = trim($row[$this->_dynamicFieldName . '_' . $customFieldsRow->student_custom_field_id], ',') . ',' . $customFieldsRow->checkbox_value_text;
                            $row[$this->_dynamicFieldName . '_' . $customFieldsRow->student_custom_field_id] = trim($existingCheckboxValue, ',');
                        } else if ($fieldType == 'NUMBER') {
                            $row[$this->_dynamicFieldName . '_' . $customFieldsRow->student_custom_field_id] = $customFieldsRow->number_value;
                        } else if ($fieldType == 'DECIMAL') {
                            $row[$this->_dynamicFieldName . '_' . $customFieldsRow->student_custom_field_id] = $customFieldsRow->decimal_value;
                        } else if ($fieldType == 'TEXTAREA') {
                            $row[$this->_dynamicFieldName . '_' . $customFieldsRow->student_custom_field_id] = $customFieldsRow->textarea_value;
                        } else if ($fieldType == 'DROPDOWN') {
                            $row[$this->_dynamicFieldName . '_' . $customFieldsRow->student_custom_field_id] = $customFieldsRow->checkbox_value_text;
                        } else if ($fieldType == 'DATE') {
                            $row[$this->_dynamicFieldName . '_' . $customFieldsRow->student_custom_field_id] = date('Y-m-d', strtotime($customFieldsRow->date_value));
                        } else if ($fieldType == 'TIME') {
                            $row[$this->_dynamicFieldName . '_' . $customFieldsRow->student_custom_field_id] = date('h:i A', strtotime($customFieldsRow->time_value));
                        } else if ($fieldType == 'COORDINATES') {
                            $row[$this->_dynamicFieldName . '_' . $customFieldsRow->student_custom_field_id] = $customFieldsRow->text_value;
                        } else if ($fieldType == 'NOTE') {
                            $row[$this->_dynamicFieldName . '_' . $customFieldsRow->student_custom_field_id] = $customFieldsRow->field_description;
                        }
                    }
                    // POCOR-6130 custome fields code

                    return $row;
                });
            });
        }
        // for Generals Tab
        // for Academics Tab
        if ($StudentType == 'Academic') {
            $res = $query
                ->select([
                    'id' => $InstitutionStudents->aliasField('id'),
                    'academic_period_name' => $AcademicPeriods->aliasField('name'),
                    'institution_name' => $institutions->find()->func()->concat([
                        $institutions->aliasField('code') => 'literal',
                        " - ",
                        $institutions->aliasField('name') => 'literal'
                    ]),
                    'education_programme' => $EducationProgrammes->aliasField('name'),
                    'education_grade_name' => $EducationGrades->aliasField('name'),
                    'start_date_name' => $InstitutionStudents->aliasField('start_date'),
                    'end_date_name' => $InstitutionStudents->aliasField('end_date'),
                    'student_status_name' => $StudentStatuses->aliasField('name'),
                    'current_class_name' => $Classes->aliasField('name'),
                ])
                ->leftJoin([$InstitutionStudents->getAlias() => $InstitutionStudents->getTable()], [
                    $this->aliasField('id = ') . $InstitutionStudents->aliasField('student_id')
                ])
                ->innerJoin([$AcademicPeriods->getAlias() => $AcademicPeriods->getTable()], [
                    $InstitutionStudents->aliasField('academic_period_id = ') . $AcademicPeriods->aliasField('id')
                ])
                ->innerJoin([$institutions->getAlias() => $institutions->getTable()], [
                    $InstitutionStudents->aliasField('institution_id = ') . $institutions->aliasField('id')
                ])
                ->innerJoin([$EducationGrades->getAlias() => $EducationGrades->getTable()], [
                    $InstitutionStudents->aliasField('education_grade_id = ') . $EducationGrades->aliasField('id')
                ])
                ->innerJoin([$EducationProgrammes->getAlias() => $EducationProgrammes->getTable()], [
                    $EducationGrades->aliasField('education_programme_id = ') . $EducationProgrammes->aliasField('id')
                ])
                ->innerJoin([$StudentStatuses->getAlias() => $StudentStatuses->getTable()], [
                    $InstitutionStudents->aliasField('student_status_id = ') . $StudentStatuses->aliasField('id')
                ])
                ->leftJoin([$ClassStudents->getAlias() => $ClassStudents->getTable()], [
                    $this->InstitutionStudents->aliasField('student_id = ') . $ClassStudents->aliasField('student_id'), $this->InstitutionStudents->aliasField('student_status_id = ') . $ClassStudents->aliasField('student_status_id')
                ])
                ->leftJoin([$Classes->getAlias() => $Classes->getTable()], [
                    $Classes->aliasField('id = ') . $ClassStudents->aliasField('institution_class_id')
                ])
                ->where([
                    $InstitutionStudents->aliasField('student_id =') . $institutionStudentId,
                ])->group('current_class_name')->sql();


        }
        // for Academic Tab
        // for Assessments Tab
        if ($StudentType == 'Assessment') {
            $query
                ->select([
                    'asses_academic_period' => $AcademicPeriods->aliasField('name'),
                    'asses_institution_name' => $institutions->aliasField('name'),
                    //POCOR-7474-HINDOL TYPO FIX
                    'assessment_period' => $AssessmentPeriods->find()->func()->concat([
                        $AssessmentPeriods->aliasField('code') => 'literal',
                        " - ",
                        $AssessmentPeriods->aliasField('name') => 'literal'
                    ]),
                    'education_subject' => $EducationSubjects->aliasField('name'),
                    'marks' => $AssessmentItemResults->aliasField('marks'),
                ])
                ->leftJoin([$AssessmentItemResults->getAlias() => $AssessmentItemResults->getTable()], [
                    $this->aliasField('id = ') . $AssessmentItemResults->aliasField('student_id')
                ])
                ->leftJoin([$Assessments->getAlias() => $Assessments->getTable()], [
                    $AssessmentItemResults->aliasField('assessment_id = ') . $Assessments->aliasField('id')
                ])
                ->leftJoin([$EducationSubjects->getAlias() => $EducationSubjects->getTable()], [
                    $AssessmentItemResults->aliasField('education_subject_id = ') . $EducationSubjects->aliasField('id')
                ])
                ->innerJoin([$AcademicPeriods->getAlias() => $AcademicPeriods->getTable()], [
                    $AssessmentItemResults->aliasField('academic_period_id = ') . $AcademicPeriods->aliasField('id')
                ])
                ->innerJoin([$AssessmentPeriods->getAlias() => $AssessmentPeriods->getTable()], [
                    $AssessmentItemResults->aliasField('assessment_period_id = ') . $AssessmentPeriods->aliasField('id')
                ])
                ->innerJoin([$institutions->getAlias() => $institutions->getTable()], [
                    $AssessmentItemResults->aliasField('institution_id = ') . $institutions->aliasField('id')
                ])
                ->where([
                    $AssessmentItemResults->aliasField('student_id =') . $institutionStudentId,
                ]);
        }
        // for Assessments Tab
        // for Absenses Tab
        if ($StudentType == 'Absence') {
            $query
                ->select([
                    'absense_date' => $institutionStudentAbsenses->aliasField('date'),
                    'absense' => $absensesTypes->aliasField('name'),
                ])
                ->leftJoin([$institutionStudentAbsenses->getAlias() => $institutionStudentAbsenses->getTable()], [
                    $this->aliasField('id = ') . $institutionStudentAbsenses->aliasField('student_id')
                ])
                ->leftJoin([$absensesTypes->getAlias() => $absensesTypes->getTable()], [
                    $institutionStudentAbsenses->aliasField('absence_type_id = ') . $absensesTypes->aliasField('id')
                ])
                ->where([
                    $institutionStudentAbsenses->aliasField('student_id =') . $institutionStudentId,
                ]);
        }
        // for Absenses Tab

        // dump($query);die;

    }

}
