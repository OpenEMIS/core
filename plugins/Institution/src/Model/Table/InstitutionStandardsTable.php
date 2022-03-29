<?php

namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\Log\Log;

/**
 * 
 * This class is used to generate the Student Standard report
 * Where Basic details of Student will be added in report
 * @author Anand Malvi <anand.malvi@mail.valuecoders.com>
 * 
 */
class InstitutionStandardsTable extends AppTable
{
    // Used to get the dynamic fields from database
    private $_dynamicFieldName = 'custom_field_data';

    /**
     * Initializing the dependencies
     * @param array $config
     */
    public function initialize(array $config)
    {
        $this->table('security_users');
        parent::initialize($config);

        // Relationship
        $this->belongsTo('Genders', ['className' => 'User.Genders']);
        $this->belongsTo('MainNationalities', ['className' => 'FieldOption.Nationalities', 'foreignKey' => 'nationality_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        // Behaviours
        $this->addBehavior('Excel', [
            'excludes' => ['is_student', 'photo_name', 'is_staff', 'is_guardian',  'super_admin', 'status'],
            'pages' => false,
            'autoFields' => false
        ]);
        $this->addBehavior('Report.ReportList');
    }

    public function beforeAction(Event $event)
    {
        $this->fields = [];
        $this->ControllerAction->field('feature', ['select' => false]);
        $this->ControllerAction->field('format');
        $this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);
        
        $controllerName = $this->controller->name;
        $institutions_crumb = __('Institutions');
        $parent_crumb       = __('Statistics');
		$reportName         = __('Standard');
        
        //# START: Crumb
        $this->Navigation->removeCrumb($this->getHeader($this->alias));
        $this->Navigation->addCrumb($institutions_crumb . ' ' . $parent_crumb);
        //# END: Crumb
        $this->controller->set('contentHeader', __($institutions_crumb) . ' ' . $parent_crumb . ' - ' . $reportName);
    }

    public function addBeforeAction(Event $event)
    {
        $this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);
        $this->ControllerAction->field('education_grade_id', ['type' => 'hidden']);
        $this->ControllerAction->field('institution_class_id', ['type' => 'hidden']);
        $this->ControllerAction->field('month', ['type' => 'hidden']);

        $session = $this->request->session();
        $institution_id = $session->read('Institution.Institutions.id');
        $this->ControllerAction->field('institution_id', ['type' => 'hidden', 'value' => $institution_id]);
    }

    public function onUpdateFieldFormat(Event $event, array $attr, $action, Request $request)
    {
        $session = $this->request->session();
        $institution_id = $session->read('Institution.Institutions.id');
        $request->data[$this->alias()]['current_institution_id'] = $institution_id;
        $request->data[$this->alias()]['institution_id'] = $institution_id;
        if ($action == 'add') {
            $attr['value'] = 'xlsx';
            $attr['attr']['value'] = 'Excel';
            $attr['type'] = 'readonly';
            return $attr;
        }
    }

    public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request)
    {
        $options = $this->controller->getInstitutionStatisticStandardReportFeature();
        /*
        $options = [
            'Institution.InstitutionStandards' => __('Students') . ' ' . __('Overview'),
            //'Institution.StudentSpecialNeeds'  => __('Student Special Needs'),
            //'Institution.StudentHealths'  => __('Student Health'),
        ];
        */
        $attr['options'] = $options;
        $attr['onChangeReload'] = true;
        if (!(isset($this->request->data[$this->alias()]['feature']))) {
            $option = $attr['options'];
            reset($option);
            $this->request->data[$this->alias()]['feature'] = key($option);
        }
        return $attr;
    }
    /**
    * POCOR-6631 
    */
    public function onUpdateFieldMonth(Event $event, array $attr, $action, $request)
    {
        if (($request->data[$this->alias()]['feature'])=='Institution.InstitutionStandardStudentAbsences')
        {
            $monthoption = ['01'=>"January",'02'=>"February",'03'=>"March",'04'=>"April",'05'=>"May",'06'=>"June",'07'=>"July",'08'=>"August",'09'=>"September",10=>"October",11=>"November",12=>"December"];
            $attr['options']        = $monthoption;
            $attr['type']           = 'select';
            $attr['select']         = false; 
            $attr['onChangeReload'] = true;
            return $attr;
        }   
        
    }

    public function onUpdateFieldAcademicPeriodId(Event $event, array $attr, $action, Request $request)
    {
        if (isset($request->data[$this->alias()]['feature'])) {
            $feature                = $this->request->data[$this->alias()]['feature'];
            $AcademicPeriodTable    = TableRegistry::get('AcademicPeriod.AcademicPeriods');
            $academicPeriodOptions  = $AcademicPeriodTable->getYearList();
            $currentPeriod          = $AcademicPeriodTable->getCurrent();
            $attr['options']        = $academicPeriodOptions;
            $attr['type']           = 'select';
            $attr['select']         = false;
            $attr['onChangeReload'] = true;
            if (empty($request->data[$this->alias()]['academic_period_id'])) {
                $request->data[$this->alias()]['academic_period_id'] = $currentPeriod;
            }
            return $attr;
        }
    }

    /**
    * POCOR-6631
    * Fetch Education Grade  based on institute, acadmic period
    */
    public function onUpdateFieldEducationGradeId(Event $event, array $attr, $action, Request $request)
    {
        if (($request->data[$this->alias()]['feature'])=='Institution.InstitutionStandardStudentAbsences') {
            $feature = $this->request->data[$this->alias()]['feature'];
            $academicPeriodId = $this->request->data[$this->alias()]['academic_period_id'];
            $institutionId = $this->request->data[$this->alias()]['institution_id'];
            $InstitutionGrades = TableRegistry::get('Institution.InstitutionGrades');
            $gradeOptions = $InstitutionGrades
                    ->find('list', [
                        'keyField' => 'id',
                        'valueField' => 'name'
                    ])
                    ->select([
                        'id' => 'EducationGrades.id',
                        'name' => 'EducationGrades.name',
                    ])
                    ->contain(['EducationGrades.EducationProgrammes.EducationCycles.EducationLevels.EducationSystems'])
                    ->where([
                        $InstitutionGrades->aliasField('institution_id') => $institutionId,
                        'EducationSystems.academic_period_id' => $academicPeriodId,
                    ])
                    ->order([
                        'EducationProgrammes.order' => 'ASC',
                        'EducationGrades.name' => 'ASC'
                    ])
                    ->toArray();
                $attr['type'] = 'select';
                $attr['select'] = false;
                $attr['options'] = ['-1' => __('All Grades')] + $gradeOptions;
                $attr['onChangeReload'] = true;
            
            return $attr;
        }
    }
    /**
    * POCOR-6631
    * fetch class name based on institute, acadmic period, education grade id 
    */ 
    public function onUpdateFieldInstitutionClassId(Event $event, array $attr, $action, Request $request)
    {   if (($request->data[$this->alias()]['feature'])=='Institution.InstitutionStandardStudentAbsences') 
        {
            $academicPeriodId = $this->request->data[$this->alias()]['academic_period_id'];
            $educationgradeid = $this->request->data[$this->alias()]['education_grade_id'];
            $institutionId = $this->request->data[$this->alias()]['institution_id'];
            $InstitutionClass = TableRegistry::get('Institution.InstitutionClasses');
            $InstitutionClassGrades = TableRegistry::get('Institution.InstitutionClassGrades');
            $classes = $InstitutionClass
                ->find('list')
                ->select([
                            'id' => 'id',
                            'name' => 'name',
                        ])
                ->where([$InstitutionClass->aliasField('institution_id') => $institutionId,
                    $InstitutionClass->aliasField('academic_period_id') => $academicPeriodId,
                   // 'InstitutionClassGrades.education_grade_id' => $educationgradeid,
                    ])
                ->order($InstitutionClass->aliasField('name'))
                ->toArray();
            $attr['type'] = 'select';
            $attr['select'] = false;
            $attr['options'] = ['0' => __('All Classes')] + $classes;
            $attr['onChangeReload'] = true;
            return $attr;
        }
    }

    public function onExcelBeforeStart(Event $event, ArrayObject $settings, ArrayObject $sheets)
    {
        $sheet_tabs = [
            'Student',
            'Academic',
            'Assessment',
            'Absence',
        ];
        foreach($sheet_tabs as $val) {  
            $tabsName = $val.'s';
            $sheets[] = [
                'sheetData'   => ['student_tabs_type' => $val],
                'name'        => $tabsName,
                'table'       => $this,
                'query'       => $this->find(),
                'orientation' => 'landscape'
            ];
        }
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $requestData           = json_decode($settings['process']['params']);
        $sheetData             = $settings['sheet']['sheetData'];
        $sheet_tab_name        = $sheetData['student_tabs_type'];
        $academicPeriodId      = $requestData->academic_period_id;
        $institutionId         = $requestData->institution_id;
        $ClassStudents         = TableRegistry::get('Institution.InstitutionClassStudents');
        $Classes               = TableRegistry::get('Institution.InstitutionClasses');
        $UserIdentitiesTable   = TableRegistry::get('User.Identities');
        $IdentityTypesTable    = TableRegistry::get('FieldOption.IdentityTypes');
        $AssessmentItemResults = TableRegistry::get('Institution.AssessmentItemResults');
        $conditions            = [];
        $selectable            = [];
        $group_by              = [];
        $group_by[]            = $this->aliasField('openemis_no');
        $group_by[]            = 'InstitutionStudent.student_status_id';

        $birth_certificate_code_id = $IdentityTypesTable->getIdByName('Birth Certificate');

        // START: JOINs
        $join = [
            'InstitutionStudent' => [
                'type' => 'inner',
                'table' => 'institution_students',
                'conditions' => [
                    'InstitutionStudent.student_id = ' . $this->aliasField('id')
                ],
            ],
            'StudentStatuses' => [
                'type' => 'inner',
                'table' => 'student_statuses',
                'conditions' => [
                    'StudentStatuses.id = InstitutionStudent.student_status_id'
                ],
            ],
            'Institution' => [
                'type' => 'inner',
                'table' => 'institutions',
                'conditions' => [
                    'Institution.id = InstitutionStudent.institution_id'
                ]
            ],
            'AcademicPeriod' => [
                'type' => 'inner',
                'table' => 'academic_periods',
                'conditions' => [
                    'AcademicPeriod.id = InstitutionStudent.academic_period_id'
                ]
            ],
        ];
        $join['EducationGrades'] = [
            'type' => 'inner',
            'table' => 'education_grades',
            'conditions' => [
                'EducationGrades.id = InstitutionStudent.education_grade_id'
            ],
        ];

        if ( $sheet_tab_name == 'Student' ) {
            $selectable['gender'] = 'Genders.name';
            $selectable['birth_certificate'] = 'Identities.number';
            $selectable['date_of_birth'] = $this->aliasField('date_of_birth');
            $selectable['nationality_name'] = 'MainNationalities.name';
            $query->leftJoin([$UserIdentitiesTable->alias() => $UserIdentitiesTable->table()], [
                $UserIdentitiesTable->aliasField('security_user_id') . ' = ' . $this->aliasField('id'),
                $UserIdentitiesTable->aliasField('identity_type_id') . " = $birth_certificate_code_id",
            ]);
        
        } else if ( $sheet_tab_name == 'Academic' ) {
            $selectable['education_programme'] = 'EducationProgrammes.name';
            $join['EducationProgrammes'] = [
                'type' => 'inner',
                'table' => 'education_programmes',
                'conditions' => [
                    'EducationProgrammes.id = EducationGrades.education_programme_id'
                ],
            ];
        
        } else if ( $sheet_tab_name == 'Assessment' ) {    
            $selectable['assessment_mark'] = 'AssessmentItemResults.marks';
            $selectable['assessment_education_subject_id'] = 'EducationSubjects.name';
            $selectable['education_programme'] = 'EducationProgrammes.name';
            $selectable['assesment_code'] = 'AssessmentPeriods.code';
            $selectable['assesment_name'] = 'AssessmentPeriods.name';
            $group_by = [];

            $join['EducationProgrammes'] = [
                'type' => 'inner',
                'table' => 'education_programmes',
                'conditions' => [
                    'EducationProgrammes.id = EducationGrades.education_programme_id'
                ],
            ];
            $join['AssessmentItemResults'] = [
                'type' => 'inner',
                'table' => 'assessment_item_results',
                'conditions' => [
                    'AssessmentItemResults.student_id = InstitutionStudent.student_id',
                    'AssessmentItemResults.academic_period_id = InstitutionStudent.academic_period_id',
                    'AssessmentItemResults.institution_id = InstitutionStudent.institution_id',
                ],
            ];
            $join['EducationSubjects'] = [
                'type' => 'inner',
                'table' => 'education_subjects',
                'conditions' => [
                    'EducationSubjects.id = AssessmentItemResults.education_subject_id',
                ],
            ];
            $join['AssessmentPeriods'] = [
                'type' => 'inner',
                'table' => 'assessment_periods',
                'conditions' => [
                    'AssessmentPeriods.id = AssessmentItemResults.assessment_period_id',
                ],
            ];
            
        } else if ( $sheet_tab_name == 'Absence' ) {
            $selectable['absence_date'] = 'InstitutionStudentAbsences.date';
            $selectable['absence_type'] = 'AbsenceTypes.name';

            $join['InstitutionStudentAbsences'] = [
                'type' => 'inner',
                'table' => 'institution_student_absences',
                'conditions' => [
                    'InstitutionStudentAbsences.student_id = InstitutionStudent.student_id',
                    'InstitutionStudentAbsences.institution_id = Institution.id',
                    'InstitutionStudentAbsences.academic_period_id = InstitutionStudent.academic_period_id',
                    'InstitutionStudentAbsences.education_grade_id = InstitutionStudent.education_grade_id',
                ],
            ];
            $join['AbsenceTypes'] = [
                'type' => 'inner',
                'table' => 'absence_types',
                'conditions' => [
                    'AbsenceTypes.id = InstitutionStudentAbsences.absence_type_id',  
                ],
            ];
        }

        $query->join($join);

        $query->leftJoin([$ClassStudents->alias() => $ClassStudents->table()], [
            $ClassStudents->aliasField('student_id = ') . 'InstitutionStudent.student_id',
            $ClassStudents->aliasField('institution_id = ') . 'InstitutionStudent.institution_id',
            $ClassStudents->aliasField('education_grade_id = ') . 'InstitutionStudent.education_grade_id',
            $ClassStudents->aliasField('student_status_id = ') . 'InstitutionStudent.student_status_id',
            $ClassStudents->aliasField('academic_period_id = ') . 'InstitutionStudent.academic_period_id'
        ]);

        $query->leftJoin([$Classes->alias() => $Classes->table()], [
            $Classes->aliasField('id = ') . $ClassStudents->aliasField('institution_class_id')
        ]);
        // END: JOINs

        
        // START : Selectable fields
        $selectable['institution_code']    = 'Institution.code';
        $selectable['institution_name']    = 'Institution.name';
        $selectable['openemis_no']         = $this->aliasField('openemis_no');
        $selectable['student_status_name'] = 'StudentStatuses.name';
        $selectable['education_grade']     = 'EducationGrades.name';
        $selectable['class_name']          = 'InstitutionClasses.name';
        $selectable['academic_year_name']  = 'AcademicPeriod.name';
        $selectable['student_start_date']  = 'InstitutionStudent.start_date';
        $selectable['student_end_date']    = 'InstitutionStudent.end_date';
        $selectable['student_id']          = $this->aliasField('id');
        $selectable['first_name']          = $this->aliasField('first_name');
        $selectable['last_name']           = $this->aliasField('last_name');
        $query->select($selectable);
        // END : Selectable fields


        $query->contain(['Genders', 'MainNationalities'])
        ->where([
            'InstitutionStudent.academic_period_id' => $academicPeriodId,
            'InstitutionStudent.institution_id'     => $institutionId,
            $this->aliasField('is_student')         => 1,
        ]);
        
        $query->group($group_by)->order([$this->aliasField('first_name'), $this->aliasField('last_name')]);
        
        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) use ($sheet_tab_name)
        {
            return $results->map(function ($row) use ($sheet_tab_name)
            {
                // START : Student tab formating
                if ( $sheet_tab_name == 'Student' ) {
                    $Guardians = TableRegistry::get('student_custom_field_values');
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
                            'checkbox_value_text'            => 'studentCustomFieldOptions.name',
                            'question_name'                  => 'studentCustomField.name',
                            'field_type'                     => 'studentCustomField.field_type',
                            'field_description'              => 'studentCustomField.description',
                            'question_field_type'            => 'studentCustomField.field_type',
                        ])
                        ->leftJoin(['studentCustomField' => 'student_custom_fields'], ['studentCustomField.id = ' . $Guardians->aliasField('student_custom_field_id')])
                        ->leftJoin(['studentCustomFieldOptions' => 'student_custom_field_options'], ['studentCustomFieldOptions.id = ' . $Guardians->aliasField('number_value')])
                        ->where([$Guardians->aliasField('student_id') => $row['student_id']])->toArray();
    
                    $existingCheckboxValue = '';
                    foreach ($guardianData as $guadionRow) {
                        $fieldType = $guadionRow->field_type;
                        if ($fieldType == 'TEXT') {
                            $row[$this->_dynamicFieldName . '_' . $guadionRow->student_custom_field_id] = $guadionRow->text_value;
                        } else if ($fieldType == 'CHECKBOX') {
                            $existingCheckboxValue = trim($row[$this->_dynamicFieldName . '_' . $guadionRow->student_custom_field_id], ',') . ',' . $guadionRow->checkbox_value_text;
                            $row[$this->_dynamicFieldName . '_' . $guadionRow->student_custom_field_id] = trim($existingCheckboxValue, ',');
                        } else if ($fieldType == 'NUMBER') {
                            $row[$this->_dynamicFieldName . '_' . $guadionRow->student_custom_field_id] = $guadionRow->number_value;
                        } else if ($fieldType == 'DECIMAL') {
                            $row[$this->_dynamicFieldName . '_' . $guadionRow->student_custom_field_id] = $guadionRow->decimal_value;
                        } else if ($fieldType == 'TEXTAREA') {
                            $row[$this->_dynamicFieldName . '_' . $guadionRow->student_custom_field_id] = $guadionRow->textarea_value;
                        } else if ($fieldType == 'DROPDOWN') {
                            $row[$this->_dynamicFieldName . '_' . $guadionRow->student_custom_field_id] = $guadionRow->checkbox_value_text;
                        } else if ($fieldType == 'DATE') {
                            $row[$this->_dynamicFieldName . '_' . $guadionRow->student_custom_field_id] = date('Y-m-d', strtotime($guadionRow->date_value));
                        } else if ($fieldType == 'TIME') {
                            $row[$this->_dynamicFieldName . '_' . $guadionRow->student_custom_field_id] = date('h:i A', strtotime($guadionRow->time_value));
                        } else if ($fieldType == 'COORDINATES') {
                            $row[$this->_dynamicFieldName . '_' . $guadionRow->student_custom_field_id] = $guadionRow->text_value;
                        } else if ($fieldType == 'NOTE') {
                            $row[$this->_dynamicFieldName . '_' . $guadionRow->student_custom_field_id] = $guadionRow->field_description;
                        }
                    }
                } // END : Student tab formating

                else if ( $sheet_tab_name == 'Assessment' ) {
                    $row['assessment_full_name'] = $row['assesment_code'] . ' ' .  $row['assesment_name'];
                }

                $row['student_full_name'] = $row['first_name'] . ' ' .  $row['last_name'];
                $row['institution_name_code'] = $row['institution_code'] . ' ' .  $row['institution_name'];
                return $row;
            });
        });
    }

    /**
     * Generate the all Header for sheet tab wise
     */
    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $IdentityType         = TableRegistry::get('FieldOption.IdentityTypes');
        $identity             = $IdentityType->getDefaultEntity();
        $settings['identity'] = $identity;
        $sheetData            = $settings['sheet']['sheetData'];
        $sheet_tab_name       = $sheetData['student_tabs_type'];

        $extraField = [];

        if ( $sheet_tab_name == 'Student' ) {
            $extraField = $this->getGeneralTabFields($extraField);
        
        } else if ( $sheet_tab_name == 'Academic' ) {
            $extraField = $this->getAcademicTabFields($extraField);

        } else if ( $sheet_tab_name == 'Assessment' ) {
            $extraField = $this->getAssessmentTabFields($extraField);
            
        } else if ( $sheet_tab_name == 'Absence' ) {
            $extraField = $this->getAbsenceTabFields($extraField);

        }

        $fields->exchangeArray($extraField);
    }

    private function getAbsenceTabFields($extraField)
    {
        $extraField[] = [
            'key'   => '',
            'field' => 'absence_date',
            'type'  => 'date',
            'label' => __('Date'),
        ];
        $extraField[] = [
            'key'   => 'AbsenceTypes.name',
            'field' => 'absence_type',
            'type'  => 'string',
            'label' => __('Absence'),
        ];
        $extraField[] = [
            'key'   => 'InstitutionStandards.openemis_no',
            'field' => 'openemis_no',
            'type'  => 'string',
            'label' => __('OpenEMIS ID'),
        ];
        $extraField[] = [
            'key'   => '',
            'field' => 'student_full_name',
            'type'  => 'string',
            'label' => __('Student'),
        ];
        return $extraField;
    }

    private function getAssessmentTabFields($extraField)
    {
        $extraField[] = [
            'key'   => 'AcademicPeriod.name',
            'field' => 'academic_year_name',
            'type'  => 'string',
            'label' => __('Academic Period'),
        ];
        $extraField[] = [
            'key'   => '',
            'field' => 'institution_name_code',
            'type'  => 'string',
            'label' => __('Institution'),
        ];
        $extraField[] = [
            'key'   => 'InstitutionStandards.openemis_no',
            'field' => 'openemis_no',
            'type'  => 'string',
            'label' => __('OpenEMIS ID'),
        ];
        $extraField[] = [
            'key'   => '',
            'field' => 'student_full_name',
            'type'  => 'string',
            'label' => __('Student'),
        ];
        $extraField[] = [
            'key'   => 'EducationProgrammes.name',
            'field' => 'education_programme',
            'type'  => 'string',
            'label' => __('Education Programme'),
        ];
        $extraField[] = [
            'key'   => 'EducationGrades.name',
            'field' => 'education_grade',
            'type'  => 'string',
            'label' => __('Education Grades'),
        ];
        $extraField[] = [
            'key'   => '',
            'field' => 'assessment_full_name',
            'type'  => 'string',
            'label' => __('Assessment Periods'),
        ];
        $extraField[] = [
            'key'   => 'EducationSubjects.name',
            'field' => 'assessment_education_subject_id',
            'type'  => 'string',
            'label' => __('Subject'),
        ];
        $extraField[] = [
            'key'   => '',
            'field' => 'assessment_mark',
            'type'  => 'string',
            'label' => __('Marks'),
        ];        
        return $extraField;
    }

    private function getGeneralTabFields($extraField)
    {
        $extraField[] = [
            'key'   => 'Institution.code',
            'field' => 'institution_code',
            'type'  => 'string',
            'label' => __('Code'),
        ];
        $extraField[] = [
            'key'   => 'Institution.name',
            'field' => 'institution_name',
            'type'  => 'string',
            'label' => __('Institution') . ' ' . __('Name'),
        ];
        $extraField[] = [
            'key'   => 'InstitutionStandards.openemis_no',
            'field' => 'openemis_no',
            'type'  => 'string',
            'label' => __('OpenEMIS ID'),
        ];
        $extraField[] = [
            'key'   => 'Genders.name',
            'field' => 'gender',
            'type'  => 'string',
            'label' => __('Gender'),
        ];
        $extraField[] = [
            'key'   => 'InstitutionStandards.date_of_birth',
            'field' => 'date_of_birth',
            'type'  => 'date',
            'label' => __('Date Of Birth'),
        ];
        $extraField[] = [
            'key'   => 'Identities.number',
            'field' => 'birth_certificate',
            'type'  => 'string',
            'label' => __('Birth Certificate'),
        ];
        $extraField[] = [
            'key'   => 'MainNationalities.name',
            'field' => 'nationality_name',
            'type'  => 'string',
            'label' => __('Nationalities'),
        ];
        $extraField[] = [
            'key'   => 'StudentStatuses.name',
            'field' => 'student_status_name',
            'type'  => 'string',
            'label' => __('Student Status'),
        ];
        $extraField[] = [
            'key'   => '',
            'field' => 'student_full_name',
            'type'  => 'string',
            'label' => __('Student'),
        ];
        $extraField[] = [
            'key'   => 'EducationGrades.name',
            'field' => 'education_grade',
            'type'  => 'string',
            'label' => __('Education Grades'),
        ];
        $extraField[] = [
            'key'   => 'InstitutionClasses.name',
            'field' => 'class_name',
            'type'  => 'string',
            'label' => __('Class'),
        ];
        $extraField[] = [
            'key'   => 'AcademicPeriod.name',
            'field' => 'academic_year_name',
            'type'  => 'string',
            'label' => __('Academic Period'),
        ];
        $extraField[] = [
            'key'   => 'InstitutionStudent.start_date',
            'field' => 'student_start_date',
            'type'  => 'date',
            'label' => __('Start Date'),
        ];
        $extraField[] = [
            'key'   => 'InstitutionStudent.end_date',
            'field' => 'student_end_date',
            'type'  => 'date',
            'label' => __('End Date'),
        ];

        $student_custom_fields_table = TableRegistry::get('student_custom_fields');
        $customFieldData = $student_custom_fields_table->find()->select([
            'custom_field_id' => $student_custom_fields_table->aliasfield('id'),
            'custom_field' => $student_custom_fields_table->aliasfield('name')
        ])->innerJoin(
            ['StudentCustomFormsFields' => 'student_custom_forms_fields'], // Class Object => table_name
            [
                'StudentCustomFormsFields.student_custom_field_id = ' . $student_custom_fields_table->aliasField('id'), // Where
            ]
        )->group($student_custom_fields_table->aliasfield('id'))->toArray();

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

        return $extraField;
    }

    private function getAcademicTabFields($extraField)
    {
        $extraField[] = [
            'key'   => 'AcademicPeriod.name',
            'field' => 'academic_year_name',
            'type'  => 'string',
            'label' => __('Academic Period'),
        ];
        $extraField[] = [
            'key'   => '',
            'field' => 'institution_name_code',
            'type'  => 'string',
            'label' => __('Institution'),
        ];
        $extraField[] = [
            'key'   => 'InstitutionStandards.openemis_no',
            'field' => 'openemis_no',
            'type'  => 'string',
            'label' => __('OpenEMIS ID'),
        ];
        $extraField[] = [
            'key'   => '',
            'field' => 'student_full_name',
            'type'  => 'string',
            'label' => __('Student'),
        ];
        $extraField[] = [
            'key'   => 'EducationProgrammes.name',
            'field' => 'education_programme',
            'type'  => 'string',
            'label' => __('Education Programme'),
        ];
        $extraField[] = [
            'key'   => 'EducationGrades.name',
            'field' => 'education_grade',
            'type'  => 'string',
            'label' => __('Education Grades'),
        ];
        $extraField[] = [
            'key'   => 'InstitutionStudent.start_date',
            'field' => 'student_start_date',
            'type'  => 'date',
            'label' => __('Start Date'),
        ];
        $extraField[] = [
            'key'   => 'InstitutionStudent.end_date',
            'field' => 'student_end_date',
            'type'  => 'date',
            'label' => __('End Date'),
        ];
        $extraField[] = [
            'key'   => 'InstitutionClasses.name',
            'field' => 'class_name',
            'type'  => 'string',
            'label' => __('Current Class'),
        ];
        $extraField[] = [
            'key'   => 'StudentStatuses.name',
            'field' => 'student_status_name',
            'type'  => 'string',
            'label' => __('Student Status'),
        ];
        return $extraField;
    }
}
