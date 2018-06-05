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
                'name',
                'class_number',
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
        $className = $settings['sheet']['name'];

        $academicPeriodId = $requestData->academic_period_id;
        $educationGradesId = $requestData->education_grade_id;

        $startDate = new DateTime($requestData->report_start_date);
        $endDate = new DateTime($requestData->report_end_date);

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
                'InstitutionClassStudents' => function ($q) {
                    return $q
                        ->select([
                            'id',
                            'student_id',
                            'institution_class_id',
                            'academic_period_id',
                            'institution_id',
                            'student_status_id'
                        ])
                        ->where(['InstitutionClassStudents.student_status_id' => 1]);
                }
            ])
            ->matching('EducationGrades')
            ->select([
                $this->aliasField('id'),
                $this->aliasField('name'),
                $this->aliasField('institution_id'),
                $this->aliasField('academic_period_id')
            ])
            ->where([
                $this->aliasField('name') => $className,
                $this->aliasField('academic_period_id') => $academicPeriodId,
                'EducationGrades.id' => $educationGradesId,
            ])
            ->distinct();
        $query
            ->formatResults(function (ResultSetInterface $results) use ($startDate, $endDate, $academicPeriodId) {

                $results = $results->toArray();

                //To get a list of dates based on user's input start and end dates
                $begin = $startDate;
                $end = $endDate;
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
                        $cloneResult = clone $result;
                        $cloneResult['date'] = $formattedDate;
                        $formattedDateResults[] = $cloneResult;
                    }
                }

                // To get the student absent count for each date
                $InstitutionStudentAbsences = TableRegistry::get('Institution.InstitutionStudentAbsences');
                $institutionStudentAbsences = $InstitutionStudentAbsences
                    ->find()
                    ->where([
                        $InstitutionStudentAbsences->aliasField('start_date >=') => $startDate->format("Y-m-d"),
                        $InstitutionStudentAbsences->aliasField('end_date <=') => $endDate->format("Y-m-d"),
                    ])
                    ->toArray();

                $rowData = [];
                foreach ($formattedDateResults as $k => $formattedDateResult) {
                    $absenceCount = 0;
                    $lateCount = 0;
                    $currentDate = $this->formatDate($formattedDateResult->date);
                    if (count($institutionStudentAbsences) > 0) {
                        foreach ($institutionStudentAbsences as $key => $value) {
                            $absentStartDate = $this->formatDate($value->start_date);
                            $absentEndDate = $this->formatDate($value->end_date);

                            if (($absentStartDate <= $currentDate && $absentEndDate >= $currentDate) && $value->institution_id == $formattedDateResult->institution_id) {
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
                        if ($classAttendanceRecord->institution_class_id == $value->id
                            && $classAttendanceRecord->month == $month) {
                            $value['class_attendance_records'] = $classAttendanceRecord->{$day_text};
                        }
                    }
                    $rowResults[] = $value;
                }
                return $rowResults;
            });
    }

    public function onExcelRenderTotalStudents(Event $event, Entity $entity, $attr)
    {
        
        $totalStudents = 0;
        if ($entity->has('institution_class_students')) {
            $totalStudents = count($entity->institution_class_students);
        }
        return $totalStudents;
    }


    public function onExcelRenderTotalStudentsAbsent(Event $event, Entity $entity, $attr)
    {
        $totalStudentsAbsent = 0;

        if ($entity->has('absence_count')) {
            $totalStudentsAbsent = $entity->absence_count;
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
        return $totalStudentsPresent;
    }

    public function onExcelRenderTotalStudentsLate(Event $event, Entity $entity, $attr)
    {
        $totalStudentsLate = 0;

        if ($entity->has('late_count')) {
            $totalStudentsLate = $entity->late_count;
        }
        return $totalStudentsLate;
    }

    public function onExcelRenderMarkStatus(Event $event, Entity $entity, $attr)
    {
        $markStatus = 'Not Marked';

        $schoolClosedDays = $this->schoolClosedDays;

        if ($entity->has('class_attendance_records') && $entity->class_attendance_records == 1) {
            $markStatus = 'Marked';
        } else {
            foreach ($schoolClosedDays as $institution_id => $value) {
                if ($entity->institution_id == $institution_id) {
                    foreach ($value as $schoolClosedDate => $isRequired) {
                        if ($schoolClosedDate == $entity->date->format("Y-m-d")) {
                            $markStatus = 'School Closed';
                        }
                    }
                }
            }
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

        return $this->getInstitutionClosedDates($startDate, $endDate, $institutionList);
    }

    private function generateSheetsData($requestData)
    {

        $academicPeriodId = $requestData->academic_period_id;
        $educationGradeId = $requestData->education_grade_id;

        $query = $this
            ->find()
            ->select([
                $this->aliasField('name'),
            ])
            ->matching('EducationGrades')
            ->where([
                $this->aliasField('academic_period_id') => $academicPeriodId,
                'EducationGrades.id' => $educationGradeId,
            ])
            ->order([
                $this->aliasField('name') => 'ASC'
            ])
            ->distinct()
            ->toArray();
            
        $sheets = [];

        foreach ($query as $value) {
            $sheets[] = [
                'sheetData' => '',
                'name' => $value->name,
                'table' => $this,
                'query' => $this->find(),
                'orientation' => 'landscape'
            ];
        }
        return $sheets;
    }
}
