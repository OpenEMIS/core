<?php
namespace Report\Model\Table;

use ArrayObject;
use DateInterval;
use DatePeriod;
use DateTime;
use App\Model\Table\AppTable;
use Cake\Datasource\ResultSetInterface;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use Institution\Model\Table\ClassAttendanceRecordsTable as RecordMarkedType;
use Cake\Log\Log;

class StudentAttendanceSummaryTable extends AppTable
{
    private $workingDays = [];
    private $schoolClosedDays = [];

    const MALE = 'M';
    const FEMALE = 'F';

    public function initialize(array $config)
    {
        //$this->table('institution_classes');
        $this->table('report_student_attendance_summary');
        parent::initialize($config);

        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions',
            'foreignKey' => 'institution_id'
        ]);
        $this->hasMany('InstitutionClassStudents', ['className' => 'Institution.InstitutionClassStudents']);
        $this->belongsToMany('EducationGrades', [
            'className' => 'Education.EducationGrades',
            'joinTable' => 'institution_class_grades',
            'through' => 'Institution.InstitutionClassGrades',
            'foreignKey' => 'institution_class_id',
            'targetForeignKey' => 'education_grade_id',
            'dependent' => true
        ]);

        $this->addBehavior('Excel', [
            'excludes' => [
                'class_number',
                'capacity',
                'total_male_students',
                'total_female_students',
                'staff_id',
                'secondary_staff_id',
                'institution_shift_id'
            ],
            'autoFields' => false
        ]);
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('Report.InstitutionSecurity');
        $this->addBehavior('Institution.Calendar');

        $AcademicPeriodTable = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $this->workingDays = $AcademicPeriodTable->getWorkingDaysOfWeek();
    }

    public function onExcelBeforeStart(Event $event, ArrayObject $settings, ArrayObject $sheets)
    {
        $requestData = json_decode($settings['process']['params']);
        $sheetsData = $this->generateSheetsData($requestData);
        $sheets->exchangeArray($sheetsData);
        $this->schoolClosedDays = $this->getSchoolClosedDate($requestData);
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        //
        $requestData = json_decode($settings['process']['params']);
        //echo "<pre>"; print_r($requestData); die;
        //$sheetData = $settings['sheet']['sheetData'];
        //$gradeId = $sheetData['education_grade_id'];
        $academicPeriodId = $requestData->academic_period_id;
        $educationGradeId = $requestData->education_grade_id;
        $institutionId = $requestData->institution_id;
        $institutionTypeId = $requestData->institution_type_id;
        $areaId = $requestData->area_education_id;
        $enrolledStatus = TableRegistry::get('Student.StudentStatuses')->getIdByCode('CURRENT');

        $reportStartDate = new DateTime($requestData->report_start_date);
        $reportEndDate = new DateTime($requestData->report_end_date);
        
        $startDate = $reportStartDate->format('Y-m-d');
        $endDate = $reportEndDate->format('Y-m-d');

        $conditions = [];

        $institutions = TableRegistry::get('Institution.Institutions');
        $StudentAbsencesPeriodDetails = TableRegistry::get('Institution.StudentAbsencesPeriodDetails');
        $institutionSubjects = TableRegistry::get('Institution.InstitutionSubjects');
        $studentAttendanceMarkedRecords = TableRegistry::get('Attendance.StudentAttendanceMarkedRecords');
        $institutionIds = $institutions->find('list', [
                                                    'keyField' => 'id',
                                                    'valueField' => 'id'
                                                ])
                        ->where(['institution_type_id' => $institutionTypeId])
                        ->toArray();

        if (!empty($institutionTypeId)) {
            $conditions['StudentAttendanceSummary.institution_id IN'] = $institutionIds;
        }

        if (!empty($academicPeriodId)) {
            $conditions['StudentAttendanceSummary.academic_period_id'] = $academicPeriodId;
        }
        if (!empty($areaId) && $areaId  != -1) {
            $conditions['Institutions.area_id'] = $areaId;
        }

        $query

            ->contain([
                'Institutions' => [
                    'fields' => [
                        'Institutions.id',
                        'Institutions.name'
                    ]
                ],
                'AcademicPeriods' => [
                    'fields' => [
                        'AcademicPeriods.name'
                    ]
                ],
        
                'InstitutionClassStudents' => function ($q) use ($enrolledStatus) {
                    return $q
                        ->select([
                            'id',
                            'student_id',
                            'institution_class_id',
                            'academic_period_id',
                            'institution_id',
                            'student_status_id'
                        ])
                        
                        ->where(['InstitutionClassStudents.student_status_id' => $enrolledStatus]);
                }
            ])

            ->leftJoin(['InstitutionClassGrades' => 'institution_class_grades'], [
                        'InstitutionClassGrades.institution_class_id = '. $this->aliasField('id'),
                    ])
            ->leftJoin(['StudentAttendanceMarkedRecords' => 'institution_student_absence_details'], [
                        'StudentAttendanceMarkedRecords.institution_class_id = '. $this->aliasField('id'),
                        'StudentAttendanceMarkedRecords.academic_period_id = '.$this->aliasField('academic_period_id'),
                        'StudentAttendanceMarkedRecords.institution_id = '.$this->aliasField('institution_id'),
                        'StudentAttendanceMarkedRecords.date >= "'.$startDate.'"',
                        'StudentAttendanceMarkedRecords.date <= "'.$endDate.'"'
                    ])
            ->select([
                $this->aliasField('id'),
                'name'=>$this->aliasField('class_name'),
                $this->aliasField('institution_id'),
                $this->aliasField('academic_period_id'),
                'InstitutionClassGrades.education_grade_id',
                'StudentAttendanceMarkedRecords.period',
                'StudentAttendanceMarkedRecords.subject_id', 
                $this->aliasField('mark_status'),
                $this->aliasField('female_count'),
                $this->aliasField('male_count'),
                $this->aliasField('total_count'),
                $this->aliasField('present_female_count'),
                $this->aliasField('present_male_count'),
                $this->aliasField('present_total_count'),
                $this->aliasField('absent_female_count'),
                $this->aliasField('absent_male_count'),
                $this->aliasField('absent_total_count'),
                $this->aliasField('late_female_count'),
                $this->aliasField('late_male_count'),
                $this->aliasField('late_total_count'),
                'date'=>$this->aliasField('attendance_date')
            ])
            ->group([$this->aliasField('id'), 
                'StudentAttendanceMarkedRecords.period', 
                'StudentAttendanceMarkedRecords.subject_id'
                ])
            ->where([$conditions]);
            
        //$results = $query->toArray();
        echo "<pre>"; print_r($query); die;
            // To get a list of dates based on user's input start and end dates
            
        
    }

    public function onExcelRenderDate(Event $event, Entity $entity, $attr)
    {
        $date = '';
        if ($entity->has('date')) {
            $date = $this->formatDate($entity->date);
        }
        return $date;
    }
    
    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $extraField[] = [
            'key' => 'Subject',
            'field' => 'Subject',
            'type' => 'Subject',
            'label' => 'Subject',
        ];

        $extraField[] = [
            'key' => 'Period',
            'field' => 'Period',
            'type' => 'Period',
            'label' => 'Period',
        ];

        $extraField[] = [
            'key' => 'Date',
            'field' => 'Date',
            'type' => 'Date',
            'label' => 'Date',
        ];
        $extraField[] = [
            'key' => 'MarkStatus',
            'field' => 'MarkStatus',
            'type' => 'MarkStatus',
            'label' => 'Mark Status',
        ];
        $extraField[] = [
            'key' => 'TotalFemaleStudents',
            'field' => 'TotalFemaleStudents',
            'type' => 'TotalFemaleStudents',
            'label' => 'No. of Female Students',
        ];
        $extraField[] = [
            'key' => 'TotalMaleStudents',
            'field' => 'TotalMaleStudents',
            'type' => 'TotalMaleStudents',
            'label' => __('No. of Male Students'),
        ];
        $extraField[] = [
            'key' => 'TotalStudents',
            'field' => 'TotalStudents',
            'type' => 'TotalStudents',
            'label' => 'Total No. Students',
        ];
        $extraField[] = [
            'key' => 'TotalFemaleStudentsPresent',
            'field' => 'TotalFemaleStudentsPresent',
            'type' => 'TotalFemaleStudentsPresent',
            'label' => 'No. of Female Students Present',
        ];
        $extraField[] = [
            'key' => 'TotalMaleStudentsPresent',
            'field' => 'TotalMaleStudentsPresent',
            'type' => 'TotalMaleStudentsPresent',
            'label' => 'No. of Male Students Present',
        ];
        $extraField[] = [
            'key' => 'TotalStudentsPresent',
            'field' => 'TotalStudentsPresent',
            'type' => 'TotalStudentsPresent',
            'label' => 'Total No. Students Present',
        ];
        $extraField[] = [
            'key' => '',
            'field' => 'TotalFemaleStudentsAbsent',
            'type' => 'TotalFemaleStudentsAbsent',
            'label' => 'No. of Female Students Absent',
        ];
        $extraField[] = [
            'key' => '',
            'field' => 'TotalMaleStudentsAbsent',
            'type' => 'TotalMaleStudentsAbsent',
            'label' => 'No. of Male Students Absent',
        ];
        $extraField[] = [
            'key' => 'TotalStudentsAbsent',
            'field' => 'TotalStudentsAbsent',
            'type' => 'TotalStudentsAbsent',
            'label' => 'Total No. Students Absent',
        ];
        $extraField[] = [
            'key' => '',
            'field' => 'TotalFemaleStudentsLate',
            'type' => 'TotalFemaleStudentsLate',
            'label' => 'No. of Female Students Late',
        ];
        $extraField[] = [
            'key' => '',
            'field' => 'TotalMaleStudentsLate',
            'type' => 'TotalMaleStudentsLate',
            'label' => 'No. of Male Students Late',
        ];
        $extraField[] = [
            'key' => 'TotalStudentsLate',
            'field' => 'TotalStudentsLate',
            'type' => 'TotalStudentsLate',
            'label' => 'Total No. Students Late',
        ];
        $newFields = array_merge($fields->getArrayCopy(), $extraField);
        $fields->exchangeArray($newFields);
    }

    private function getSchoolClosedDate($requestData)
    {
        $institutionId = [$requestData->institution_id];
        $startDate = new DateTime($requestData->report_start_date);
        $endDate = new DateTime($requestData->report_end_date);
        $closedDates = $this->getInstitutionClosedDates($startDate, $endDate, $institutionId);
        return $closedDates;
    }

    private function generateSheetsData($requestData)
    {
        $institutionId = $requestData->institution_id;
        $academicPeriodId = $requestData->academic_period_id;
        $educationGradeId = $requestData->education_grade_id;
        $institutionTypeId = $requestData->institution_type_id;

        $ids ='';
        $institutions = TableRegistry::get('Institution.Institutions');
        $institutionIds = $institutions->find('list', [
                                                    'keyField' => 'id',
                                                    'valueField' => 'id'
                                                ])
                        ->where(['institution_type_id' => $institutionTypeId])
                        ->toArray();

        $InstitutionGradesTable = TableRegistry::get('Institution.InstitutionGrades');
        $institutionGradeResults = $InstitutionGradesTable->getGradeOptions($institutionId, $academicPeriodId, true);

        $gradeOptions = [];
        if ($educationGradeId != -1) {
            if(in_array($educationGradeId, $institutionGradeResults)){
                $gradeOptions[$educationGradeId] = $institutionGradeResults[$educationGradeId];
            }else{
                $EducationGrades = TableRegistry::get('Education.EducationGrades');
                $educationGradesOptions = $EducationGrades
                    ->find('list', [
                        'keyField' => 'id',
                        'valueField' => 'name'
                    ])
                    ->select([
                        'id' => $EducationGrades->aliasField('id'),
                        'name' => $EducationGrades->aliasField('name'),
                        'education_programme_name' => 'EducationProgrammes.name'
                    ])
                    ->contain(['EducationProgrammes'])
                    ->order([
                        'EducationProgrammes.order' => 'ASC',
                        $EducationGrades->aliasField('name') => 'ASC'
                    ])
                    ->toArray();

                $gradeOptions[$educationGradeId] = $educationGradesOptions[$educationGradeId];
            }
        } else {
            $gradeOptions = $institutionGradeResults;
        }

        $sheets = [];
        foreach ($gradeOptions as $gradeId => $gradeName) {
            $query = $this
                ->find()
                ->where([
                    $this->aliasField('institution_id') => $institutionId,
                    $this->aliasField('academic_period_id') => $academicPeriodId
                ])
                ->matching('EducationGrades', function ($q) use ($gradeId) {
                    return $q->where([
                        'EducationGrades.id' => $gradeId
                    ]);
                });

            $sheets[] = [
                'sheetData' => [
                    'education_grade_id' => $gradeId
                ],
                'name' => preg_replace("/\([^)]+\)/","",$gradeName),
                'table' => $this,
                'query' => $query,
                'orientation' => 'landscape'
            ];
        }
        
        return $sheets;
    }
}
