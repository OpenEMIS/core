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
use DateTime;

class ClassAttendanceMarkedSummaryReportTable extends AppTable
{
    public const CLASS_TEACHER = 'Home Room Teacher';
    public const ASSISTANT_TEACHER = 'Secondary Teacher';
    public $reportStartDate;
    public $reportEndDate;
    public $schoolClosedDays;

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

        $this->addBehavior('Institution.Calendar');
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

    public function onExcelBeforeStart(Event $event, ArrayObject $settings, ArrayObject $sheets)
    {
        $requestData = json_decode($settings['process']['params']);
        $this->schoolClosedDays = $this->getSchoolClosedDate($requestData);
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

    public function onExcelRenderPeriodName(Event $event, Entity $entity)
    {
        $period_name = '';
        if ($entity->has('period')) {
        $education_grade_id = $entity->education_grades[0]->id;
        $StudentMarkTypeStatusGrades = TableRegistry::get('Attendance.StudentMarkTypeStatusGrades');
        $data = $StudentMarkTypeStatusGrades
                ->find()
                ->select([
                    'StudentAttendanceMarkTypes.id',
                    'StudentAttendanceMarkTypes.student_attendance_type_id',
                    'StudentAttendanceMarkTypes.attendance_per_day',
                ])
                ->leftJoin(
                ['StudentMarkTypeStatuses' => 'student_mark_type_statuses'],
                [
                    'StudentMarkTypeStatuses.id = '. $StudentMarkTypeStatusGrades->aliasField('student_mark_type_status_id')
                ]
                )
                ->leftJoin(
                ['StudentAttendanceMarkTypes' => 'student_attendance_mark_types'],
                [
                    'StudentAttendanceMarkTypes.id = StudentMarkTypeStatuses.student_attendance_mark_type_id'
                ]
                )
                ->where([
                    $StudentMarkTypeStatusGrades->aliasField('education_grade_id') => $education_grade_id
                ])
                ->toArray();

        if (!empty($data)) {
            $period = $entity->period;
            $student_attendance_mark_type_id = $data[0]->StudentAttendanceMarkTypes['id'];
            $student_attendance_type_id = $data[0]->StudentAttendanceMarkTypes['student_attendance_type_id'];
            $StudentAttendanceTypes = TableRegistry::get('Attendance.StudentAttendanceTypes');
            $attendancetype = $StudentAttendanceTypes
                                ->find()
                                ->select([
                                    $StudentAttendanceTypes->aliasField('code')
                                ])
                                ->where([
                                    $StudentAttendanceTypes->aliasField('id') => $student_attendance_type_id
                                ])
                                ->toArray();
            $attendancecode = $attendancetype[0]->code;

            if ($attendancecode == 'DAY') {
                $StudentAttendancePerDayPeriods = TableRegistry::get('Attendance.StudentAttendancePerDayPeriods');
                $periodData = $StudentAttendancePerDayPeriods
                                ->find()
                                ->select([
                                    $StudentAttendancePerDayPeriods->aliasField('name')])
                                ->where([
                                    $StudentAttendancePerDayPeriods->aliasField('student_attendance_mark_type_id') => $student_attendance_mark_type_id,
                                     $StudentAttendancePerDayPeriods->aliasField('period') => $period,
                                ])
                                ->toArray();
                if (!empty($periodData)) {
                    $period_name = $periodData[0]->name;
                }
            } 
        } else {
            $period_name = 'Period 1';
        }

    } else {
        $period_name = '-';
    }
        return $period_name;
    }

    public function onExcelGetSubjectName(Event $event, Entity $entity)
    { 
        if (!$entity->has('subject_name')) {
            return '-';
        }
    }

    public function onExcelRenderSubjectStaff(Event $event, Entity $entity)
    { 
        $subject_id = $entity->subject_id;
        $InstitutionSubjectStaff = TableRegistry::get('Institution.InstitutionSubjectStaff');
        $staffSubjectData = $InstitutionSubjectStaff
                            ->find()
                            ->select([
                                'staff_subject_name' => $InstitutionSubjectStaff->find()->func()->concat([
                                'SecurityUsers.openemis_no' => 'literal',
                                " - ",
                                'SecurityUsers.first_name' => 'literal',
                                " ",
                                'SecurityUsers.last_name' => 'literal'
                                ])
                            ])
                            ->leftJoin(
                                ['SecurityUsers' => 'security_users'],
                                [
                                    'SecurityUsers.id = '. $InstitutionSubjectStaff->aliasField('staff_id')
                                ]
                                )
                            ->where([
                                $InstitutionSubjectStaff->aliasField('institution_subject_id') => $subject_id
                            ])
                            ->toArray();
        if (!empty($staffSubjectData)) {
        $staff_subject_name = $staffSubjectData[0]->staff_subject_name;
        } else {
            $staff_subject_name = '-';
        }
        return $staff_subject_name;
    }

    public function onExcelGetTotalUnmarked(Event $event, Entity $entity)
    {  
        $reportStartDate = (new DateTime($this->reportStartDate));
        $reportEndDate = (new DateTime($this->reportEndDate));
        $diff=date_diff($reportStartDate,$reportEndDate);
        $days = $diff->format("%a");
        $notworkingdays = $this->getNotWorkingDays($this->reportStartDate, $this->reportEndDate);
        $schoolClosedDays = count($this->schoolClosedDays[-1]);
        $notworkingdaysschool = $notworkingdays + $schoolClosedDays;
        $schoolDays = $days - $notworkingdaysschool;
        $totalunmarked =($schoolDays-$entity->total_marked);
        return $totalunmarked;
    }

    public function onExcelGetTotalDaysToBeMarked(Event $event, Entity $entity)
    {  
        $reportStartDate = (new DateTime($this->reportStartDate));
        $reportEndDate = (new DateTime($this->reportEndDate));
        $diff=date_diff($reportStartDate,$reportEndDate);
        $days = $diff->format("%a");
        $notworkingdays = $this->getNotWorkingDays($this->reportStartDate, $this->reportEndDate);
        $schoolClosedDays = count($this->schoolClosedDays[-1]);
        $notworkingdaysschool = $notworkingdays + $schoolClosedDays;
        $schoolDays = $days - $notworkingdaysschool;
        $totalDaysToBeMarked =$schoolDays;
        return $totalDaysToBeMarked;
    }

    public function onExcelGetSecondaryStaffName(Event $event, Entity $entity)
    {
        $institution_class_id = $entity->id;
        $InstitutionClassesSecondaryStaff = TableRegistry::get('Institution.InstitutionClassesSecondaryStaff');
        $data = $InstitutionClassesSecondaryStaff
                ->find()
                ->select([
                'secondary_staff_name' => $InstitutionClassesSecondaryStaff->find()->func()->concat([
                    'SecurityUsers.openemis_no' => 'literal',
                    " - ",
                    'SecurityUsers.first_name' => 'literal',
                    " ",
                    'SecurityUsers.last_name' => 'literal'
                ])
            ])
                ->leftJoin(
                    ['SecurityUsers' => 'security_users'],
                    [
                        'SecurityUsers.id = '. $InstitutionClassesSecondaryStaff->aliasField('secondary_staff_id')
                    ]
                    )
                ->where([
                    $InstitutionClassesSecondaryStaff->aliasField('institution_class_id') => $institution_class_id
                ])
                ->toArray();
                $a = array();
        foreach($data as $key => $value) {
            $a[$key] = $value->secondary_staff_name;
        }
        $secondary_staff_name = implode(",", $a);
        return $secondary_staff_name;
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $institution_id = $requestData->institution_id;
        $subjects = $requestData->subjects;
        $education_grade_id = $requestData->education_grade_id;
        $attendance_type = $requestData->attendance_type; 
        $periods = $requestData->periods; 
        $StudentAttendanceTypes = TableRegistry::get('Attendance.StudentAttendanceTypes');
        if (!empty($attendance_type)) {
                $attendanceTypeData = $StudentAttendanceTypes
                                        ->find()
                                        ->where([
                                            $StudentAttendanceTypes->aliasField('id') => $attendance_type
                                        ])
                                        ->toArray();
                $attendanceTypeCode = $attendanceTypeData[0]->code;
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
        if ($attendanceTypeCode == 'DAY') {
            $where['InstitutionClassGrades.education_grade_id'] = $education_grade_id;            
        } else {
            $where['InstitutionSubjects.education_grade_id'] = $education_grade_id;
        }
        }

        if ($periods != 0) {
            $where['StudentAttendanceMarkedRecords.period'] = $periods;
        }
        $academic_period_id = $requestData->academic_period_id;

        $EducationGrades = TableRegistry::get('Education.EducationGrades');
        $InstitutionClasses = TableRegistry::get('Institution.InstitutionClasses');
        $StaffPositionTitles = TableRegistry::get('Institution.StaffPositionTitles');
        $Institutions = TableRegistry::get('Institution.Institutions');
        $InstitutionClassesSecondaryStaff = TableRegistry::get('Institution.InstitutionClassesSecondaryStaff');
        $StudentAttendanceMarkedRecords = TableRegistry::get('Attendance.StudentAttendanceMarkedRecords');       

        if ($attendanceTypeCode == 'DAY') {
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
                'total_male_students' => 'ClassAttendanceMarkedSummaryReport.total_male_students',
                'total_female_students' => 'ClassAttendanceMarkedSummaryReport.total_female_students',
                'total_students' => $query->newExpr('ClassAttendanceMarkedSummaryReport.total_male_students + ClassAttendanceMarkedSummaryReport.total_female_students'),
                'period' => 'StudentAttendanceMarkedRecords.period',
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
                'ClassAttendanceMarkedSummaryReport.id',
                'StudentAttendanceMarkedRecords.period'
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
                'total_male_students' => 'ClassAttendanceMarkedSummaryReport.total_male_students',
                'total_female_students' => 'ClassAttendanceMarkedSummaryReport.total_female_students',
                'total_students' => $query->newExpr('ClassAttendanceMarkedSummaryReport.total_male_students + ClassAttendanceMarkedSummaryReport.total_female_students'),
                'subject_id' => 'InstitutionSubjects.id',
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
                        'Staff.id',
                        'Staff.openemis_no',
                        'Staff.first_name',
                        'Staff.middle_name',
                        'Staff.third_name',
                        'Staff.last_name'
                    ]
                ]
            ])
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
        $newFields[] = [
            'key' => 'Institutions.institution_code',
            'field' => 'institution_code',
            'type' => 'string',
            'label' => __('Institution Code')
        ];

        $newFields[] = [
            'key' => 'Areas.name',
            'field' => 'area_name',
            'type' => 'string',
            'label' => __('Area')
        ];

        $newFields[] = [
            'key' => 'Institutions.institution_name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Institution Name')
        ];
        $newFields[] = [
            'key' => 'ClassAttendanceMarkedSummaryReport.institution_shift_id',
            'field' => 'institution_shift_id',
            'type' => 'integer',
            'label' => __('Shift')
        ];
        $newFields[] = [
            'key' => 'Education.education_grades',
            'field' => 'education_grades',
            'type' => 'string',
            'label' => __('Education Grade')
        ];
        $newFields[] = [
            'key' => 'ClassAttendanceMarkedSummaryReport.name',
            'field' => 'name',
            'type' => 'string',
            'label' => __('Class Name')
        ];
        $newFields[] = [
            'key' => '',
            'field' => 'staff_name',
            'type' => 'string',
            'label' => __('HomeRoom Teacher')
        ];

        $newFields[] = [
            'key' => 'secondary_staff_name',
            'field' => 'secondary_staff_name',
            'type' => 'string',
            'label' => __('Secondary Teacher')
        ];

        $newFields[] = [
            'key' => 'subject_staff',
            'field' => 'subject_staff',
            'type' => 'subject_staff',
            'label' => __('Subject Teacher')
        ];

        $newFields[] = [
            'key' => 'InstitutionSubjects.name',
            'field' => 'subject_name',
            'type' => 'string',
            'label' => __('Subject')
        ];

        $newFields[] = [
            'key' => 'period_name',
            'field' => 'period_name',
            'type' => 'period_name',
            'label' => __('Period')
        ];

        $newFields[] = [
            'key' => '',
            'field' => 'total_marked',
            'type' => 'integer',
            'label' => __('Total No. of days Marked')
        ];

        $newFields[] = [
            'key' => 'total_unmarked',
            'field' => 'total_unmarked',
            'type' => 'integer',
            'label' => __('Total No. of days Unmarked')
        ];

        $newFields[] = [
            'key' => 'total_days_to_be_marked',
            'field' => 'total_days_to_be_marked',
            'type' => 'integer',
            'label' => __('Total No. of days to be marked')
        ];
        $fields->exchangeArray($newFields);
    }

    private function getSchoolClosedDate($requestData)
    {
        $superAdmin = $requestData->super_admin;
        $userId = $requestData->user_id;
        $startDate = new DateTime($requestData->report_start_date);
        $endDate = new DateTime($requestData->report_end_date);

        $query = $this->find();

        if (!$superAdmin) {
            $query->find('byAccess', [
                'user_id' => $userId,
                'institution_field_alias' => $this->aliasField($this->association('Institutions')->foreignKey())
            ]);
        }
            
        $institutionList = $query
            ->group('institution_id')
            ->extract('institution_id')
            ->toArray();

        return $this->getInstitutionClosedDates($startDate, $endDate, -1);
    }

    public function getNotWorkingDays($startDate, $endDate)
        {
            $begin = strtotime($startDate);
            $end   = strtotime($endDate);
            if ($begin > $end) {

                return 0;
            } else {
                $no_days  = 0;
                while ($begin <= $end) {
                    $what_day = date("N", $begin);
                    if (in_array($what_day, [6,7]) ) // 6 and 7 are weekend
                        $no_days++;
                    $begin += 86400; // +1 day
                };

                return $no_days;
            }
        }
}
