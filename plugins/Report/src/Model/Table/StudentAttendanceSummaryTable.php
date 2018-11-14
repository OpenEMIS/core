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

    public function initialize(array $config)
    {
        $this->table('institution_classes');
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
        $requestData = json_decode($settings['process']['params']);
        $sheetData = $settings['sheet']['sheetData'];
        $gradeId = $sheetData['education_grade_id'];

        $academicPeriodId = $requestData->academic_period_id;
        $educationGradeId = $requestData->education_grade_id;
        $institutionId = $requestData->institution_id;
        $enrolledStatus = TableRegistry::get('Student.StudentStatuses')->getIdByCode('CURRENT');

        $reportStartDate = new DateTime($requestData->report_start_date);
        $reportEndDate = new DateTime($requestData->report_end_date);

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
            ->select([
                $this->aliasField('id'),
                $this->aliasField('name'),
                $this->aliasField('institution_id'),
                $this->aliasField('academic_period_id')
            ]);

            $results = $query->toArray();

            // To get a list of dates based on user's input start and end dates
            $begin = $reportStartDate;
            $end = $reportEndDate;
            $end = $end->modify('+1 day');

            $interval = new DateInterval('P1D');
            $daterange = new DatePeriod($begin, $interval, $end);
            $formattedDates = [];

            // To get all the dates of the working days only
            foreach ($daterange as $date) {
                $dayText = $date->format('l');
                if (in_array($dayText, $this->workingDays)) {
                    $formattedDates[] = $date;
                }
            }

            //Insert each date from $formattedDates into each and every entity
            $formattedDateResults = [];
            foreach ($formattedDates as $key => $formattedDate) {
                foreach ($results as $result) {
                    $cloneResult =  clone $result;
                    $cloneResult['date'] = $formattedDate;
                    $formattedDateResults[] = $cloneResult;
                }
            }

            // To get the student absent count for each date
            $InstitutionStudentAbsences = TableRegistry::get('Institution.InstitutionStudentAbsences');
            $institutionStudentAbsencesRecords = $InstitutionStudentAbsences
                ->find()
                ->where([
                    $InstitutionStudentAbsences->aliasField('date >=') => $reportStartDate->format("Y-m-d"),
                    $InstitutionStudentAbsences->aliasField('date <=') => $reportEndDate->format("Y-m-d"),
                    $InstitutionStudentAbsences->aliasField('institution_id') => $institutionId
                ])
                ->toArray();

            $rowData = [];
            foreach ($formattedDateResults as $k => $formattedDateResult) {
                $absenceCount = 0;
                $lateCount = 0;
                $currentDate = $this->formatDate($formattedDateResult->date);

                if (count($institutionStudentAbsencesRecords) > 0) {
                    foreach ($institutionStudentAbsencesRecords as $key => $value) {
                        $absenceDate = $this->formatDate($value->date);

                        if (($absenceDate == $currentDate) && $value->institution_id == $formattedDateResult->institution_id) {
                            $institutionClassStudents = $formattedDateResult->institution_class_students;
                            foreach ($institutionClassStudents as $key => $institutionClassStudent) {
                                if ($institutionClassStudent->student_id == $value->student_id) {
                                    if ($value->absence_type_id == 3) {
                                        $lateCount++;
                                    } else {
                                        $absenceCount++;
                                    }
                                }
                            }
                        }
                    }
                }
                $formattedDateResult['absence_count'] = $absenceCount;
                $formattedDateResult['late_count'] = $lateCount;
                $rowData[] = $formattedDateResult;
            }

            //To get the attendance mark status for each date
            $ClassAttendanceRecords = TableRegistry::get('Institution.ClassAttendanceRecords');
            $classAttendanceRecords = $ClassAttendanceRecords
                ->find()
                ->where([
                    $ClassAttendanceRecords->aliasField('academic_period_id') => $academicPeriodId
                ])
                ->toArray();

            $rowResults = [];
            foreach ($rowData as $key => $value) {
                $month = $value->date->format('n');
                $day_text = 'day_'.$value->date->format('j');

                foreach ($classAttendanceRecords as $k => $classAttendanceRecord) {
                    if ($classAttendanceRecord->institution_class_id == $value->id && $classAttendanceRecord->month == $month) {
                        $value['class_attendance_records'] = $classAttendanceRecord->{$day_text};
                    }
                }
                $rowResults[] = $value;
            }

        $query
            ->formatResults(function (ResultSetInterface $results) use ($rowResults) {
                return $rowResults;
            });
    }

    public function onExcelRenderTotalStudents(Event $event, Entity $entity, $attr)
    {
        $totalStudents = 0;
        if ($entity->has('institution_class_students')) {
            $totalStudents = count($entity->institution_class_students);
        }

        if ($totalStudents == 0) {
            $totalStudents = '-';
        }
        return $totalStudents;
    }


    public function onExcelRenderTotalStudentsAbsent(Event $event, Entity $entity, $attr)
    {
        $totalStudentsAbsent = 0;

        if ($entity->has('absence_count')) {
            $totalStudentsAbsent = $entity->absence_count;
        }

        if ($totalStudentsAbsent == 0) {
            $totalStudentsAbsent = '-';
        }
        return $totalStudentsAbsent;
    }

    public function onExcelRenderTotalStudentsPresent(Event $event, Entity $entity, $attr)
    {
        $totalStudentsPresent = 0;
        $totalStudentsAbsent = 0;
        $totalStudents = 0;

        if ($entity->has('absence_count')) {
            $totalStudentsAbsent = $entity->absence_count;
        }
        if ($entity->has('institution_class_students')) {
            $totalStudents = count($entity->institution_class_students);
        }

        $totalStudentsPresent = $totalStudents - $totalStudentsAbsent;

        if ($totalStudentsPresent == 0) {
            $totalStudentsPresent = '-';
        }
        return $totalStudentsPresent;
    }

    public function onExcelRenderTotalStudentsLate(Event $event, Entity $entity, $attr)
    {
        $totalStudentsLate = 0;

        if ($entity->has('late_count')) {
            $totalStudentsLate = $entity->late_count;
        }

        if ($totalStudentsLate == 0) {
            $totalStudentsLate = '-';
        }

        return $totalStudentsLate;
    }

    public function onExcelRenderMarkStatus(Event $event, Entity $entity, $attr)
    {
        $markStatus = '';
        $institutionId = $entity->institution_id;
        $dateFormatted = $entity->date->format('Y-m-d');

        if (array_key_exists($dateFormatted, $this->schoolClosedDays[$institutionId])) {
            $markStatus = __('School Closed');
        } elseif ($entity->has('class_attendance_records')) {
            if ($entity->class_attendance_records == RecordMarkedType::MARKED) {
                $markStatus = __('Marked');
            } elseif ($entity->class_attendance_records == RecordMarkedType::NOT_MARKED) {
                $markStatus = __('Not Marked');
            } elseif ($entity->class_attendance_records == RecordMarkedType::PARTIAL_MARKED) {
                $markStatus = __('Partial Marked');
            }
        } else {
            // if the whole month for the class is not marked, it will not have any records, thus is defaulted to NOT MARKED
            $markStatus = __('Not Marked');
        }

        return $markStatus;
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
            'key' => 'TotalStudents',
            'field' => 'TotalStudents',
            'type' => 'TotalStudents',
            'label' => 'Total No. Students',
        ];
        $extraField[] = [
            'key' => 'TotalStudentsPresent',
            'field' => 'TotalStudentsPresent',
            'type' => 'TotalStudentsPresent',
            'label' => 'Total No. Students Present',
        ];
        $extraField[] = [
            'key' => 'TotalStudentsAbsent',
            'field' => 'TotalStudentsAbsent',
            'type' => 'TotalStudentsAbsent',
            'label' => 'Total No. Students Absent',
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

        $InstitutionGradesTable = TableRegistry::get('Institution.InstitutionGrades');
        $institutionGradeResults = $InstitutionGradesTable->getGradeOptions($institutionId, $academicPeriodId, true);

        $gradeOptions = [];
        if ($educationGradeId != -1) {
            $gradeOptions[$educationGradeId] = $institutionGradeResults[$educationGradeId];
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
                'name' => $gradeName,
                'table' => $this,
                'query' => $query,
                'orientation' => 'landscape'
            ];
        }
        return $sheets;
    }
}
