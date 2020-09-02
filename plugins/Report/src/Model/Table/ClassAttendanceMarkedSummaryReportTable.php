<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\I18n\Time;
use Cake\I18n\Date;

class ClassAttendanceMarkedSummaryReportTable extends AppTable
{
    public const CLASS_TEACHER = 'Home Room Teacher';
    public const ASSISTANT_TEACHER = 'Secondary Teacher';
    public $reportStartDate;
    public $reportEndDate;

    public function initialize(array $config)
    {
        $this->table('institution_classes');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Staff', ['className' => 'User.Users',                       'foreignKey' => 'staff_id']);
        $this->belongsTo('InstitutionShifts', ['className' => 'Institution.InstitutionShifts',    'foreignKey' => 'institution_shift_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions',         'foreignKey' => 'institution_id']);
        $this->hasMany('ClassesSecondaryStaff', ['className' => 'Institution.InstitutionClassesSecondaryStaff', 'saveStrategy' => 'replace', 'foreignKey' => 'institution_class_id']);

        $this->belongsToMany('EducationGrades', [
            'className' => 'Education.EducationGrades',
            'through' => 'Institution.InstitutionClassGrades',
            'foreignKey' => 'institution_class_id',
            'targetForeignKey' => 'education_grade_id',
            'dependent' => true
        ]);

        $this->addBehavior('Excel', [
            'excludes' => [
                'class_number',
                'total_male_students',
                'total_female_students'
            ],
            'autoFields' => false
        ]);
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Report.InstitutionSecurity');
    }

    public function beforeAction(Event $event)
    {
        $this->fields = [];
        $this->ControllerAction->field('feature');
        $this->ControllerAction->field('format');
    }

    public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request)
    {
        $attr['options'] = $this->controller->getFeatureOptions('Institutions');
        return $attr;
    }

    public function onExcelGetInstitutionShiftId(Event $event, Entity $entity)
    {
        return $entity->shift_name;
    }

    public function onExcelGetEducationGrades(Event $event, Entity $entity)
    {
        $classGrades = [];
        if ($entity->education_grades) {
            foreach ($entity->education_grades as $key => $value) {
                $classGrades[] = $value->name;
            }
        }

        return implode(', ', $classGrades); //display as comma seperated
    }

    public function onExcelGetTotalUnmarked(Event $event, Entity $entity)
    {  
        $reportStartDate = (new Date($this->reportStartDate))->format('Y-m-d');
        $reportEndDate = (new Date($this->reportEndDate))->format('Y-m-d');
        return 10;
        //echo $reportStartDate;die;
    }

    public function onExcelGetTotalDaysToBeMarked(Event $event, Entity $entity)
    {  
        $reportStartDate = (new Date($this->reportStartDate))->format('Y-m-d');
        $reportEndDate = (new Date($this->reportEndDate))->format('Y-m-d');
        return 10;
        //echo $reportStartDate;die;
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $institution_id = $requestData->institution_id;
        $subjects = $requestData->subjects;
        $education_grade_id = $requestData->education_grade_id;
        $attendance_type = $requestData->attendance_type;  
        $StudentAttendanceTypes = TableRegistry::get('Attendance.StudentAttendanceTypes');
        if (!empty($attendance_type)) {

                $attendanceTypeCode = $StudentAttendanceTypes
                                        ->find()
                                        ->where([
                                            $StudentAttendanceTypes->aliasField('id') => $attendance_type
                                        ])
                                        ->toArray();
                $attendanceTypeCodeName = $attendanceTypeCode[0]->code;
            }
        $this->reportStartDate = (new Date($requestData->report_start_date))->format('Y-m-d');
        $this->reportEndDate = (new Date($requestData->report_end_date))->format('Y-m-d');

        $where = [];
        if ($institution_id != 0) {
            $where['Institutions.id'] = $institution_id;
        }
        if (!empty($subjects)) {
            $where['StudentAttendanceMarkedRecords.subject_id'] = $subjects;
            $where['InstitutionSubjects.id'] = $subjects;
        }
        if ($education_grade_id != -1) {
        if ($attendanceTypeCodeName == 'DAY') {
            $where['InstitutionClassGrades.education_grade_id'] = $education_grade_id;            
        } else {
            $where['InstitutionSubjects.education_grade_id'] = $education_grade_id;
        }
        }
        $academic_period_id = $requestData->academic_period_id;

        $EducationGrades = TableRegistry::get('Education.EducationGrades');
        $InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
        $StaffPositionTitles = TableRegistry::get('Institution.StaffPositionTitles');
        $Institutions = TableRegistry::get('Institution.Institutions');
        $InstitutionClassesSecondaryStaff = TableRegistry::get('Institution.InstitutionClassesSecondaryStaff');
        $StudentAttendanceMarkedRecords = TableRegistry::get('Attendance.StudentAttendanceMarkedRecords');       

        if ($attendanceTypeCodeName == 'DAY') {
            $query
            ->select([
                $this->aliasField('id'),
                'academic_period_id' => 'ClassAttendanceMarkedSummaryReport.academic_period_id',
                'institution_code' => 'Institutions.code',
                'institution_name' => 'Institutions.name',
                'institution_type' => 'Types.name',
                'area_name' => 'Areas.name',
                'area_code' => 'Areas.code',
                'area_administrative_code' => 'AreaAdministratives.code',
                'area_administrative_name' => 'AreaAdministratives.name',
                'shift_name' => 'ShiftOptions.name',
                'name' => 'ClassAttendanceMarkedSummaryReport.name',
                'staff_name' => $query->func()->concat([
                    'Staff.openemis_no' => 'literal',
                    " - ",
                    'Staff.first_name' => 'literal',
                    " ",
                    'Staff.last_name' => 'literal'
                ]),
                'secondary_staff_name' => $query->func()->concat([
                    'SecurityUsers.openemis_no' => 'literal',
                    " - ",
                    'SecurityUsers.first_name' => 'literal',
                    " ",
                    'SecurityUsers.last_name' => 'literal'
                ]),
                'total_male_students' => 'ClassAttendanceMarkedSummaryReport.total_male_students',
                'total_female_students' => 'ClassAttendanceMarkedSummaryReport.total_female_students',
                'total_students' => $query->newExpr('ClassAttendanceMarkedSummaryReport.total_male_students + ClassAttendanceMarkedSummaryReport.total_female_students'),
                'period_name' => 'StudentAttendanceMarkedRecords.period',
                'total_marked' => $query->func()->count('StudentAttendanceMarkedRecords.period')
            ])
            ->contain([
                'AcademicPeriods' => [
                    'fields' => [
                        'AcademicPeriods.name'
                    ]
                ],
                'Institutions.Types',
                'Institutions.Areas',
                'Institutions.AreaAdministratives',
                'InstitutionShifts.ShiftOptions',
                'EducationGrades' => [
                    'fields' => [
                        'InstitutionClassGrades.institution_class_id',
                        'EducationGrades.id',        
                        'EducationGrades.code',
                        'EducationGrades.name'
                    ]
                ],
                'Staff' => [
                    'fields' => [
                        'Staff.openemis_no',
                        'Staff.first_name',
                        'Staff.middle_name',
                        'Staff.third_name',
                        'Staff.last_name'
                    ]
                ]
            ])
            ->leftJoin(
            ['InstitutionClassesSecondaryStaff' => 'institution_classes_secondary_staff'],
            [
                'InstitutionClassesSecondaryStaff.institution_class_id = '. $this->aliasField('id')
            ]
            )
            ->leftJoin(
            ['SecurityUsers' => 'security_users'],
            [
                'SecurityUsers.id = '. $InstitutionClassesSecondaryStaff->aliasField('secondary_staff_id')
            ]
            )
            ->innerJoin(
            ['StudentAttendanceMarkedRecords' => 'student_attendance_marked_records'],
            [
                'StudentAttendanceMarkedRecords.institution_class_id = '. $this->aliasField('id'),
                'StudentAttendanceMarkedRecords.subject_id' => 0
            ]
            )
            ->leftJoin(
            ['InstitutionClassGrades' => 'institution_class_grades'],
            [
                'InstitutionClassGrades.institution_class_id = '. $this->aliasField('id')
            ]
            )
            ->where([
                'ClassAttendanceMarkedSummaryReport.academic_period_id' => $academic_period_id,
                $StudentAttendanceMarkedRecords->aliasField('date >= "') . $this->reportStartDate . '"',
                $StudentAttendanceMarkedRecords->aliasField('date <= "') . $this->reportEndDate . '"',
                $where
            ])
            ->group([
                'ClassAttendanceMarkedSummaryReport.id',
                'StudentAttendanceMarkedRecords.period'
            ])
            ->order([
                'AcademicPeriods.order',
                'Institutions.code',
                'ClassAttendanceMarkedSummaryReport.id'
            ]);
        } else {

        $query
            ->select([
                $this->aliasField('id'),
                'academic_period_id' => 'ClassAttendanceMarkedSummaryReport.academic_period_id',
                'institution_code' => 'Institutions.code',
                'institution_name' => 'Institutions.name',
                'institution_type' => 'Types.name',
                'area_name' => 'Areas.name',
                'area_code' => 'Areas.code',
                'area_administrative_code' => 'AreaAdministratives.code',
                'area_administrative_name' => 'AreaAdministratives.name',
                'shift_name' => 'ShiftOptions.name',
                'name' => 'ClassAttendanceMarkedSummaryReport.name',
                'staff_name' => $query->func()->concat([
                    'Staff.openemis_no' => 'literal',
                    " - ",
                    'Staff.first_name' => 'literal',
                    " ",
                    'Staff.last_name' => 'literal'
                ]),
                'secondary_staff_name' => $query->func()->concat([
                    'SecurityUsers.openemis_no' => 'literal',
                    " - ",
                    'SecurityUsers.first_name' => 'literal',
                    " ",
                    'SecurityUsers.last_name' => 'literal'
                ]),
                'total_male_students' => 'ClassAttendanceMarkedSummaryReport.total_male_students',
                'total_female_students' => 'ClassAttendanceMarkedSummaryReport.total_female_students',
                'total_students' => $query->newExpr('ClassAttendanceMarkedSummaryReport.total_male_students + ClassAttendanceMarkedSummaryReport.total_female_students'),
                'subject_name' => 'InstitutionSubjects.name',
                'total_marked' => $query->func()->count('StudentAttendanceMarkedRecords.subject_id')
            ])
            ->contain([
                'AcademicPeriods' => [
                    'fields' => [
                        'AcademicPeriods.name'
                    ]
                ],
                'Institutions.Types',
                'Institutions.Areas',
                'Institutions.AreaAdministratives',
                'InstitutionShifts.ShiftOptions',
                'EducationGrades' => [
                    'fields' => [
                        'InstitutionClassGrades.institution_class_id',
                        'EducationGrades.id',
                        'EducationGrades.code',
                        'EducationGrades.name'
                    ]
                ],
                'Staff' => [
                    'fields' => [
                        'Staff.openemis_no',
                        'Staff.first_name',
                        'Staff.middle_name',
                        'Staff.third_name',
                        'Staff.last_name'
                    ]
                ]
            ])
            ->leftJoin(
            ['InstitutionClassesSecondaryStaff' => 'institution_classes_secondary_staff'],
            [
                'InstitutionClassesSecondaryStaff.institution_class_id = '. $this->aliasField('id')
            ]
            )
            ->leftJoin(
            ['SecurityUsers' => 'security_users'],
            [
                'SecurityUsers.id = '. $InstitutionClassesSecondaryStaff->aliasField('secondary_staff_id')
            ]
            )
            ->leftJoin(
            ['InstitutionClassSubjects' => 'institution_class_subjects'],
            [
                'InstitutionClassSubjects.institution_class_id = '. $this->aliasField('id')
            ]
            )
            ->leftJoin(
            ['InstitutionSubjects' => 'institution_subjects'],
            [
                'InstitutionSubjects.id = InstitutionClassSubjects.institution_subject_id'
            ]
            )
            ->innerJoin(
            ['StudentAttendanceMarkedRecords' => 'student_attendance_marked_records'],
            [
                'StudentAttendanceMarkedRecords.subject_id = InstitutionClassSubjects.institution_subject_id'
            ]
            )
            ->where([
                'ClassAttendanceMarkedSummaryReport.academic_period_id' => $academic_period_id,
                $StudentAttendanceMarkedRecords->aliasField('date >= "') . $this->reportStartDate . '"',
                $StudentAttendanceMarkedRecords->aliasField('date <= "') . $this->reportEndDate . '"',
                $where
            ])
            ->group([
                'InstitutionSubjects.id'
            ])
            ->order([
                'AcademicPeriods.order',
                'Institutions.code',
                'ClassAttendanceMarkedSummaryReport.id'
            ]);
        }            
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        //redeclare all for sorting purpose.
        /*$newFields[] = [
            'key' => 'ClassAttendanceMarkedSummaryReport.academic_period_id',
            'field' => 'academic_period_id',
            'type' => 'integer',
            'label' => ''
        ];*/

        $newFields[] = [
            'key' => 'Institutions.institution_code',
            'field' => 'institution_code',
            'type' => 'string',
            'label' => 'GS code'
        ];

        $newFields[] = [
            'key' => 'Areas.name',
            'field' => 'area_name',
            'type' => 'string',
            'label' => __('Atoll')
        ];

        $newFields[] = [
            'key' => 'Institutions.institution_name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => 'School'
        ];
        $newFields[] = [
            'key' => 'ClassAttendanceMarkedSummaryReport.institution_shift_id',
            'field' => 'institution_shift_id',
            'type' => 'integer',
            'label' => 'Shift'
        ];
        $newFields[] = [
            'key' => 'Education.education_grades',
            'field' => 'education_grades',
            'type' => 'string',
            'label' => 'Grade'
        ];
        $newFields[] = [
            'key' => 'ClassAttendanceMarkedSummaryReport.name',
            'field' => 'name',
            'type' => 'string',
            'label' => 'Class'
        ];
        $newFields[] = [
            'key' => '',
            'field' => 'staff_name',
            'type' => 'string',
            'label' => self::CLASS_TEACHER
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'secondary_staff_name',
            'type' => 'string',
            'label' => self::ASSISTANT_TEACHER
        ];

        /*$newFields[] = [
            'key' => 'InstitutionSubjects.name',
            'field' => 'subject_name',
            'type' => 'string',
            'label' => 'Subject'
        ];*/

        $newFields[] = [
            'key' => 'StudentAttendanceMarkedRecords.period',
            'field' => 'period_name',
            'type' => 'string',
            'label' => 'Period'
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'total_marked',
            'type' => 'integer',
            'label' => 'Total Marked'
        ];

        $newFields[] = [
            'key' => 'total_unmarked',
            'field' => 'total_unmarked',
            'type' => 'integer',
            'label' => 'Total Unmarked'
        ];

        $newFields[] = [
            'key' => 'total_days_to_be_marked',
            'field' => 'total_days_to_be_marked',
            'type' => 'integer',
            'label' => 'Total No days to be marked'
        ];
        /*$newFields[] = [
            'key' => 'Types.institution_type',
            'field' => 'institution_type',
            'type' => 'string',
            'label' => ''
        ];*/

        /*$newFields[] = [
            'key' => 'Areas.code',
            'field' => 'area_code',
            'type' => 'string',
            'label' => __('Area Code')
        ];*/       

        /*$newFields[] = [
            'key' => 'AreaAdministratives.code',
            'field' => 'area_administrative_code',
            'type' => 'string',
            'label' => __('Area Administrative Code')
        ];

        $newFields[] = [
            'key' => 'AreaAdministratives.name',
            'field' => 'area_administrative_name',
            'type' => 'string',
            'label' => __('Area Administrative')
        ];*/        
        /*$newFields[] = [
            'key' => 'ClassAttendanceMarkedSummaryReport.total_male_students',
            'field' => 'total_male_students',
            'type' => 'integer',
            'label' => ''
        ];

        $newFields[] = [
            'key' => 'ClassAttendanceMarkedSummaryReport.total_female_students',
            'field' => 'total_female_students',
            'type' => 'integer',
            'label' => ''
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'total_students',
            'type' => 'integer',
            'label' => 'Total Students'
        ];*/

        $fields->exchangeArray($newFields);
    }
}
