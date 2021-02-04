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
        //$sheetData = $settings['sheet']['sheetData'];
        //$gradeId = $sheetData['education_grade_id'];
        $academicPeriodId = $requestData->academic_period_id;
        $educationGradeId = $requestData->education_grade_id;
        $institutionId = $requestData->institution_id;
        $institutionTypeId = $requestData->institution_type_id;
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
                $this->aliasField('name'),
                $this->aliasField('institution_id'),
                $this->aliasField('academic_period_id'),
                'InstitutionClassGrades.education_grade_id',
                'StudentAttendanceMarkedRecords.period',
                'StudentAttendanceMarkedRecords.subject_id' 
            ])
            ->group([$this->aliasField('id'), 
                'StudentAttendanceMarkedRecords.period', 
                'StudentAttendanceMarkedRecords.subject_id'
                ])
            ->where([$conditions]);
            
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
           
            if (!empty($institutionId)) {
                $conditions[$InstitutionStudentAbsences->aliasField('institution_id')] = $institutionId;
            }

            $institutionStudentAbsencesRecords = $InstitutionStudentAbsences
                ->find()
                ->where([
                    $InstitutionStudentAbsences->aliasField('date >=') => $reportStartDate->format("Y-m-d"),
                    $InstitutionStudentAbsences->aliasField('date <=') => $reportEndDate->format("Y-m-d"),
                   
                ]);
              
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


             // To get the female student absent count for each date
             $InstitutionFemaleAbsences = TableRegistry::get('Institution.InstitutionStudentAbsences');

             if (!empty($institutionId)) {
                $conditions[$InstitutionFemaleAbsences->aliasField('institution_id')] = $institutionId;
             }

            $institutionFemaleAbsencesRecords = $InstitutionFemaleAbsences
                                                ->find();
                                                    $institutionFemaleAbsencesRecords->innerJoin(['Users' => 'security_users'], [
                                                    'Users.id = ' . $InstitutionFemaleAbsences->aliasfield('student_id')
                                                ])
                                                ->where([
                                                    'Users.gender_id IS NOT NULL','Users.gender_id = 2',
                                                    $InstitutionFemaleAbsences->aliasField('date >=') => $reportStartDate->format("Y-m-d"),
                                                    $InstitutionFemaleAbsences->aliasField('date <=') => $reportEndDate->format("Y-m-d"),
                                                   
                                                ]);
           
             $rowData = [];
             foreach ($formattedDateResults as $k => $formattedDateResult) {
                 $femaleAbsenceCount = 0;
                 $femaleLateCount = 0;
                 $currentDate = $this->formatDate($formattedDateResult->date);
 
                 if (count($institutionFemaleAbsencesRecords) > 0) {
                     foreach ($institutionFemaleAbsencesRecords as $key => $value) {
                         $absenceDate = $this->formatDate($value->date);
 
                         if (($absenceDate == $currentDate) && $value->institution_id == $formattedDateResult->institution_id) {
                             $institutionClassStudents = $formattedDateResult->institution_class_students;
                             foreach ($institutionClassStudents as $key => $institutionClassStudent) {
                                 if ($institutionClassStudent->student_id == $value->student_id) {
                                     if ($value->absence_type_id == 3) {
                                         $femaleLateCount++;
                                     } else {
                                         $femaleAbsenceCount++;
                                     }
                                 }
                             }
                         }
                     }
                 }
                 $formattedDateResult['female_absence_count'] = $femaleAbsenceCount;
                 $formattedDateResult['female_late_count'] = $femaleLateCount;
                 $rowData[] = $formattedDateResult;
             }

               // To get the male student absent count for each date
               $InstitutionMaleAbsences = TableRegistry::get('Institution.InstitutionStudentAbsences');
               
               if (!empty($institutionId)) {
                $conditions[$InstitutionMaleAbsences->aliasField('institution_id')] = $institutionId;
               }

                $institutionMaleAbsencesRecords = $InstitutionMaleAbsences
                                                ->find();
                                                        $institutionMaleAbsencesRecords->innerJoin(['Users' => 'security_users'], [
                                                        'Users.id = ' . $InstitutionMaleAbsences->aliasfield('student_id')
                                                ])
                                                ->where([
                                                        'Users.gender_id IS NOT NULL','Users.gender_id = 1',
                                                        $InstitutionMaleAbsences->aliasField('date >=') => $reportStartDate->format("Y-m-d"),
                                                        $InstitutionMaleAbsences->aliasField('date <=') => $reportEndDate->format("Y-m-d"),
                                                       
                                                ]);
           
               $rowData = [];
               foreach ($formattedDateResults as $k => $formattedDateResult) {
                   $maleAbsenceCount = 0;
                   $maleLateCount = 0;
                   $currentDate = $this->formatDate($formattedDateResult->date);
   
                   if (count($institutionMaleAbsencesRecords) > 0) {
                       foreach ($institutionMaleAbsencesRecords as $key => $value) {
                           $absenceDate = $this->formatDate($value->date);
   
                           if (($absenceDate == $currentDate) && $value->institution_id == $formattedDateResult->institution_id) {
                               $institutionClassStudents = $formattedDateResult->institution_class_students;
                               foreach ($institutionClassStudents as $key => $institutionClassStudent) {
                                   if ($institutionClassStudent->student_id == $value->student_id) {
                                       if ($value->absence_type_id == 3) {
                                           $maleLateCount++;
                                       } else {
                                           $maleAbsenceCount++;
                                       }
                                   }
                               }
                           }
                       }
                   }
                   $formattedDateResult['male_absence_count'] = $maleAbsenceCount;
                   $formattedDateResult['male_late_count'] = $maleLateCount;
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

    public function onExcelRenderTotalFemaleStudents(Event $event, Entity $entity, $attr)
    {
        $totalFemaleStudents = 0;
        $InstitutionClasses = $this->find('all')
                            ->where(['id' => $entity->id])
                            ->first();
      
        if ($entity->has('id')) {
            $totalFemaleStudents = $InstitutionClasses->total_female_students;
        }

        if ($totalFemaleStudents == 0) {
            $totalFemaleStudents = '-';
        }

        return $totalFemaleStudents;
    }

    public function onExcelRenderTotalMaleStudents(Event $event, Entity $entity, $attr)
    {
        $totalMaleStudents = 0;
        $InstitutionClasses = $this->find('all')
                            ->where(['id' => $entity->id])
                            ->first();

        if ($entity->has('id')) {
            $totalMaleStudents = $InstitutionClasses->total_male_students;
        }

        if ($totalMaleStudents == 0) {
            $totalMaleStudents = '-';
        }

        return $totalMaleStudents;
    }
    
    public function onExcelRenderTotalStudentsAbsent(Event $event, Entity $entity, $attr)
    {   
       
        $totalStudentsAbsent = 0;
        $periodId = $entity->StudentAttendanceMarkedRecords['period'];
        $subjectId = $entity->StudentAttendanceMarkedRecords['subject_id'];

        $dateFormatted = $entity->date->format('Y-m-d');
        $StudentAbsencesPeriodDetails = TableRegistry::get('Institution.StudentAbsencesPeriodDetails');
        
        if ($entity->class_attendance_records == RecordMarkedType::MARKED) {
            
        if(!empty($subjectId)){
        $markConditions = [
            $StudentAbsencesPeriodDetails->aliasField('academic_period_id') => $entity->academic_period_id, 
            $StudentAbsencesPeriodDetails->aliasField('institution_id') => $entity->institution_id,
            $StudentAbsencesPeriodDetails->aliasField('institution_class_id') => $entity->id, 
            $StudentAbsencesPeriodDetails->aliasField('date') =>  $dateFormatted,
            $StudentAbsencesPeriodDetails->aliasField('subject_id') =>  $subjectId,
            'absence_type_id IN (1,2)',
            $StudentAbsencesPeriodDetails->aliasField('period') => 1
            ];
        }else{
            $markConditions = [
            $StudentAbsencesPeriodDetails->aliasField('academic_period_id') => $entity->academic_period_id, 
            $StudentAbsencesPeriodDetails->aliasField('institution_id') => $entity->institution_id,
            $StudentAbsencesPeriodDetails->aliasField('institution_class_id') => $entity->id, 
            $StudentAbsencesPeriodDetails->aliasField('date') =>  $dateFormatted,
            $StudentAbsencesPeriodDetails->aliasField('period') =>  $periodId,
            'absence_type_id IN (1,2)',
            $StudentAbsencesPeriodDetails->aliasField('subject_id') => 0
            ];
        }
        
        $totalStudentsAbsent = $StudentAbsencesPeriodDetails->find()            
            ->where($markConditions)
            ->count() ;
        } 
    
        if ($entity->class_attendance_records == RecordMarkedType::PARTIAL_MARKED) {
            if(!empty($subjectId)){
        $conditions = [
            $StudentAbsencesPeriodDetails->aliasField('academic_period_id') => $entity->academic_period_id, 
            $StudentAbsencesPeriodDetails->aliasField('institution_id') => $entity->institution_id,
            $StudentAbsencesPeriodDetails->aliasField('institution_class_id') => $entity->id, 
            $StudentAbsencesPeriodDetails->aliasField('date') =>  $dateFormatted,
            $StudentAbsencesPeriodDetails->aliasField('subject_id') =>  $subjectId,
            'absence_type_id!=3',
            $StudentAbsencesPeriodDetails->aliasField('period') => 1
            ];
        }else{
            $conditions = [
            $StudentAbsencesPeriodDetails->aliasField('academic_period_id') => $entity->academic_period_id, 
            $StudentAbsencesPeriodDetails->aliasField('institution_id') => $entity->institution_id,
            $StudentAbsencesPeriodDetails->aliasField('institution_class_id') => $entity->id, 
            $StudentAbsencesPeriodDetails->aliasField('date') =>  $dateFormatted,
            $StudentAbsencesPeriodDetails->aliasField('period') =>  $periodId,
            'absence_type_id!=3',
            $StudentAbsencesPeriodDetails->aliasField('subject_id') => 0
            ];
        }
            $totalStudentsAbsent = $StudentAbsencesPeriodDetails->find()            
            ->where($conditions)
            ->count();

        }
        if ($totalStudentsAbsent <= 0) {     
            $totalStudentsAbsent = '-';
        }

        return $totalStudentsAbsent;
    }
    
    public function onExcelRenderTotalFemaleStudentsAbsent(Event $event, Entity $entity, $attr)
    {   
        $totalFemaleStudentsAbsent = 0;
        $periodId = $entity->StudentAttendanceMarkedRecords['period'];
        $subjectId = $entity->StudentAttendanceMarkedRecords['subject_id'];
        $dateFormatted = $entity->date->format('Y-m-d');
        $StudentAbsencesPeriodDetails = TableRegistry::get('Institution.StudentAbsencesPeriodDetails'); 
        
        if ($entity->class_attendance_records == RecordMarkedType::PARTIAL_MARKED) {
            if(!empty($subjectId)){
        $conditions = [
            $StudentAbsencesPeriodDetails->aliasField('academic_period_id') => $entity->academic_period_id, 
            $StudentAbsencesPeriodDetails->aliasField('institution_id') => $entity->institution_id,
            $StudentAbsencesPeriodDetails->aliasField('institution_class_id') => $entity->id, 
            $StudentAbsencesPeriodDetails->aliasField('date') =>  $dateFormatted,
            $StudentAbsencesPeriodDetails->aliasField('subject_id') =>  $subjectId,
            'absence_type_id!=3',
            'SecurityUsers.gender_id' => 2,
            $StudentAbsencesPeriodDetails->aliasField('period') => 1
            ];
        }else{
            $conditions = [
            $StudentAbsencesPeriodDetails->aliasField('academic_period_id') => $entity->academic_period_id, 
            $StudentAbsencesPeriodDetails->aliasField('institution_id') => $entity->institution_id,
            $StudentAbsencesPeriodDetails->aliasField('institution_class_id') => $entity->id, 
            $StudentAbsencesPeriodDetails->aliasField('date') =>  $dateFormatted,
            $StudentAbsencesPeriodDetails->aliasField('period') =>  $periodId,
            'absence_type_id!=3',
            'SecurityUsers.gender_id' => 2,
            $StudentAbsencesPeriodDetails->aliasField('subject_id') => 0
            ];
        }
            $totalStudentsAbsent = $StudentAbsencesPeriodDetails->find() 
                ->leftJoin(['SecurityUsers' => 'security_users'], [
                        'SecurityUsers.id = '. $StudentAbsencesPeriodDetails->aliasField('student_id')
                    ])
                ->where($conditions)
                ->count();

                $totalFemaleStudent = $entity->female_absence_count;
                $totalFemaleStudentsAbsents = $totalStudentsAbsent-$totalFemaleStudent;
         }

           if ($entity->class_attendance_records == RecordMarkedType::MARKED){

                if ($entity->has('female_absence_count')) {
                    $totalFemaleStudentsAbsent = $entity->female_absence_count;
                }
              
            }  
              
            if ($totalFemaleStudentsAbsent <= 0) {
                $totalFemaleStudentsAbsent = '-';
            }
            return $totalFemaleStudentsAbsent;
         }

   

    public function onExcelRenderTotalMaleStudentsAbsent(Event $event, Entity $entity, $attr)
    {   
        $periodId = $entity->StudentAttendanceMarkedRecords['period'];
        $subjectId = $entity->StudentAttendanceMarkedRecords['subject_id'];
        
        $totalMaleStudentsAbsent = 0;
        $dateFormatted = $entity->date->format('Y-m-d');
        $StudentAbsencesPeriodDetails = TableRegistry::get('Institution.StudentAbsencesPeriodDetails'); 
        
        if(!empty($subjectId)){
        $conditions = [
            $StudentAbsencesPeriodDetails->aliasField('academic_period_id') => $entity->academic_period_id, 
            $StudentAbsencesPeriodDetails->aliasField('institution_id') => $entity->institution_id,
            $StudentAbsencesPeriodDetails->aliasField('institution_class_id') => $entity->id, 
            $StudentAbsencesPeriodDetails->aliasField('date') =>  $dateFormatted,
            $StudentAbsencesPeriodDetails->aliasField('subject_id') =>  $subjectId,
            'absence_type_id!=3',
            'SecurityUsers.gender_id' => 1,
            $StudentAbsencesPeriodDetails->aliasField('period') => 1
            ];
        }else{
            $conditions = [
            $StudentAbsencesPeriodDetails->aliasField('academic_period_id') => $entity->academic_period_id, 
            $StudentAbsencesPeriodDetails->aliasField('institution_id') => $entity->institution_id,
            $StudentAbsencesPeriodDetails->aliasField('institution_class_id') => $entity->id, 
            $StudentAbsencesPeriodDetails->aliasField('date') =>  $dateFormatted,
            $StudentAbsencesPeriodDetails->aliasField('period') =>  $periodId,
            'absence_type_id!=3',
            'SecurityUsers.gender_id' => 1,
            $StudentAbsencesPeriodDetails->aliasField('subject_id') => 0
            ];
        }
        
        if ($entity->class_attendance_records == RecordMarkedType::PARTIAL_MARKED) {
            $totalStudentsAbsent = $StudentAbsencesPeriodDetails->find() 
                ->leftJoin(['SecurityUsers' => 'security_users'], [
                        'SecurityUsers.id = '. $StudentAbsencesPeriodDetails->aliasField('student_id')
                    ])
                ->where($conditions)
                ->count() ;

                $totalMaleStudent = $entity->male_absence_count;
                $totalMaleStudentsAbsent = $totalStudentsAbsent-$totalMaleStudent; 
         }
        
        if ($entity->class_attendance_records == RecordMarkedType::MARKED) {
            if ($entity->has('male_absence_count')) {
                $totalMaleStudentsAbsent = $entity->male_absence_count;
            }
        }

        if ($totalMaleStudentsAbsent <= 0) {
            $totalMaleStudentsAbsent = '-';
        }

        return $totalMaleStudentsAbsent;
        }
    
    public function onExcelRenderTotalStudentsPresent(Event $event, Entity $entity, $attr)
    {
        $periodId = $entity->StudentAttendanceMarkedRecords['period'];
        $subjectId = $entity->StudentAttendanceMarkedRecords['subject_id'];
        $dateFormatted = $entity->date->format('Y-m-d');
        $totalStudentsPresent = 0;
        $totalStudentsAbsent = 0;
        $totalStudents = 0;

        $StudentAbsencesPeriodDetails = TableRegistry::get('Institution.StudentAbsencesPeriodDetails');
        
        if(!empty($subjectId)){
        $markConditions = [
            $StudentAbsencesPeriodDetails->aliasField('academic_period_id') => $entity->academic_period_id, 
            $StudentAbsencesPeriodDetails->aliasField('institution_id') => $entity->institution_id,
            $StudentAbsencesPeriodDetails->aliasField('institution_class_id') => $entity->id, 
            $StudentAbsencesPeriodDetails->aliasField('date') =>  $dateFormatted,
            $StudentAbsencesPeriodDetails->aliasField('subject_id') =>  $subjectId,
            'absence_type_id IN (1,2)',
            $StudentAbsencesPeriodDetails->aliasField('period') => 1
            ];
        }else{
            $markConditions = [
            $StudentAbsencesPeriodDetails->aliasField('academic_period_id') => $entity->academic_period_id, 
            $StudentAbsencesPeriodDetails->aliasField('institution_id') => $entity->institution_id,
            $StudentAbsencesPeriodDetails->aliasField('institution_class_id') => $entity->id, 
            $StudentAbsencesPeriodDetails->aliasField('date') =>  $dateFormatted,
            $StudentAbsencesPeriodDetails->aliasField('period') =>  $periodId,
            'absence_type_id IN (1,2)', 
            $StudentAbsencesPeriodDetails->aliasField('subject_id') => 0
            ];
        }
        
        $totalStudentsAbsent = $StudentAbsencesPeriodDetails->find()
                
            ->where($markConditions)
            ->count() ;

            if ($entity->class_attendance_records == RecordMarkedType::MARKED) {

                if ($entity->has('institution_class_students')) {
                     $totalStudents = count($entity->institution_class_students);

                 }
                 $totalStudentsPresent = $totalStudents - $totalStudentsAbsent;

             } 

             if ($entity->class_attendance_records == RecordMarkedType::PARTIAL_MARKED)
             {
                if(!empty($subjectId)){
                $conditions = [
                    $StudentAbsencesPeriodDetails->aliasField('academic_period_id') => $entity->academic_period_id, 
                    $StudentAbsencesPeriodDetails->aliasField('institution_id') => $entity->institution_id,
                    $StudentAbsencesPeriodDetails->aliasField('institution_class_id') => $entity->id, 
                    $StudentAbsencesPeriodDetails->aliasField('date') =>  $dateFormatted,
                    $StudentAbsencesPeriodDetails->aliasField('subject_id') =>  $subjectId,
                    'absence_type_id!=3',
                    $StudentAbsencesPeriodDetails->aliasField('period') => 1
                    ];
                }else{
                    $conditions = [
                    $StudentAbsencesPeriodDetails->aliasField('academic_period_id') => $entity->academic_period_id, 
                    $StudentAbsencesPeriodDetails->aliasField('institution_id') => $entity->institution_id,
                    $StudentAbsencesPeriodDetails->aliasField('institution_class_id') => $entity->id, 
                    $StudentAbsencesPeriodDetails->aliasField('date') =>  $dateFormatted,
                    $StudentAbsencesPeriodDetails->aliasField('period') =>  $periodId,
                    'absence_type_id!=3',
                    $StudentAbsencesPeriodDetails->aliasField('subject_id') => 0
                    ];
                }

                $totalStudentsAbsent = $StudentAbsencesPeriodDetails->find()
                ->where($conditions)
                ->count();

                if ($entity->has('institution_class_students')) {
                    $totalStudents = count($entity->institution_class_students);

                }
                $totalStudentsPresent = $totalStudents - $totalStudentsAbsent;
             }
                      
       
       if ($totalStudentsPresent <= 0) {
            $totalStudentsPresent = '-';
        }
       
        return $totalStudentsPresent;
    }   

    public function onExcelRenderTotalFemaleStudentsPresent(Event $event, Entity $entity, $attr)
    {
        $periodId = $entity->StudentAttendanceMarkedRecords['period'];
        $subjectId = $entity->StudentAttendanceMarkedRecords['subject_id'];
        $totalFemaleStudentsPresent = 0;
        $totalFemaleStudentsAbsent = 0;
        $totalFemaleStudents = 0;
        $dateFormatted = $entity->date->format('Y-m-d');
        $institutionClasses = $this->find('all')
                            ->where(['id' => $entity->id])
                            ->first();
        
        $StudentAbsencesPeriodDetails = TableRegistry::get('Institution.StudentAbsencesPeriodDetails');                    
        
        if(!empty($subjectId)){
        $markConditions = [
            $StudentAbsencesPeriodDetails->aliasField('academic_period_id') => $entity->academic_period_id, 
            $StudentAbsencesPeriodDetails->aliasField('institution_id') => $entity->institution_id,
            $StudentAbsencesPeriodDetails->aliasField('institution_class_id') => $entity->id, 
            $StudentAbsencesPeriodDetails->aliasField('date') =>  $dateFormatted,
            $StudentAbsencesPeriodDetails->aliasField('subject_id') =>  $subjectId,
            'absence_type_id IN (1,2)',
            'SecurityUsers.gender_id' => 2,
            $StudentAbsencesPeriodDetails->aliasField('period') => 1
            ];
        }else{
            $markConditions = [
            $StudentAbsencesPeriodDetails->aliasField('academic_period_id') => $entity->academic_period_id, 
            $StudentAbsencesPeriodDetails->aliasField('institution_id') => $entity->institution_id,
            $StudentAbsencesPeriodDetails->aliasField('institution_class_id') => $entity->id, 
            $StudentAbsencesPeriodDetails->aliasField('date') =>  $dateFormatted,
            $StudentAbsencesPeriodDetails->aliasField('period') =>  $periodId,
            'absence_type_id IN (1,2)',
            'SecurityUsers.gender_id' => 2,
            $StudentAbsencesPeriodDetails->aliasField('subject_id') => 0
            ];
        }
        
        $totalStudentsAbsent = $StudentAbsencesPeriodDetails->find()
                    ->leftJoin(['SecurityUsers' => 'security_users'], [
                        'SecurityUsers.id = '. $StudentAbsencesPeriodDetails->aliasField('student_id')
                    ])
                    ->where($markConditions)
                    ->count()
                    ;
                          
        if ($entity->class_attendance_records == RecordMarkedType::MARKED) {
            $totalFemaleStudentsAbsent = $entity->female_absence_count;
           
            $totalFemaleStudents = $institutionClasses->total_female_students;
            $totalFemaleStudentsPresent = $totalFemaleStudents - $totalFemaleStudentsAbsent;
          
        } 

        else if ($entity->class_attendance_records == RecordMarkedType::PARTIAL_MARKED) {
            
        if(!empty($subjectId)){
        $conditions = [
            $StudentAbsencesPeriodDetails->aliasField('academic_period_id') => $entity->academic_period_id, 
            $StudentAbsencesPeriodDetails->aliasField('institution_id') => $entity->institution_id,
            $StudentAbsencesPeriodDetails->aliasField('institution_class_id') => $entity->id, 
            $StudentAbsencesPeriodDetails->aliasField('date') =>  $dateFormatted,
            $StudentAbsencesPeriodDetails->aliasField('subject_id') =>  $subjectId,
            'absence_type_id!=3',
            $StudentAbsencesPeriodDetails->aliasField('period') => 1,
            'SecurityUsers.gender_id' => 2
            ];
        }else{
            $conditions = [
            $StudentAbsencesPeriodDetails->aliasField('academic_period_id') => $entity->academic_period_id, 
            $StudentAbsencesPeriodDetails->aliasField('institution_id') => $entity->institution_id,
            $StudentAbsencesPeriodDetails->aliasField('institution_class_id') => $entity->id, 
            $StudentAbsencesPeriodDetails->aliasField('date') =>  $dateFormatted,
            $StudentAbsencesPeriodDetails->aliasField('period') =>  $periodId,
            'absence_type_id!=3',
            $StudentAbsencesPeriodDetails->aliasField('subject_id') => 0,
            'SecurityUsers.gender_id' => 2
            ];
        }
            $totalStudentsAbsent = $StudentAbsencesPeriodDetails->find()
                    ->leftJoin(['SecurityUsers' => 'security_users'], [
                        'SecurityUsers.id = '. $StudentAbsencesPeriodDetails->aliasField('student_id')
                    ])
                    ->where($conditions)
                    ->count()
                    ;
            $totalFemaleStudentsAbsent =  $totalStudentsAbsent;
          
            $totalFemaleStudents = $institutionClasses->total_female_students;
            $totalFemaleStudentsPresent = $totalFemaleStudents - $totalFemaleStudentsAbsent;
          
        } else {
            $totalFemaleStudentsPresent = '-';
        }

        return $totalFemaleStudentsPresent;
    }
        
    public function onExcelRenderTotalMaleStudentsPresent(Event $event, Entity $entity, $attr)
    {
        $periodId = $entity->StudentAttendanceMarkedRecords['period'];
        $subjectId = $entity->StudentAttendanceMarkedRecords['subject_id'];
        $totalMaleStudentsPresent = 0;
        $totalMaleStudentsAbsent = 0;
        $totalMaleStudents = 0;
        $dateFormatted = $entity->date->format('Y-m-d');
        $institutionClasses = $this->find('all')
                            ->where(['id' => $entity->id])
                            ->first();

        $StudentAbsencesPeriodDetails = TableRegistry::get('Institution.StudentAbsencesPeriodDetails');                    
                
        if(!empty($subjectId)){
        $markConditions = [
            $StudentAbsencesPeriodDetails->aliasField('academic_period_id') => $entity->academic_period_id, 
            $StudentAbsencesPeriodDetails->aliasField('institution_id') => $entity->institution_id,
            $StudentAbsencesPeriodDetails->aliasField('institution_class_id') => $entity->id, 
            $StudentAbsencesPeriodDetails->aliasField('date') =>  $dateFormatted,
            $StudentAbsencesPeriodDetails->aliasField('subject_id') =>  $subjectId,
            'absence_type_id IN (1,2)',
            'SecurityUsers.gender_id' => 1,
            $StudentAbsencesPeriodDetails->aliasField('period') => 1
            ];
        }else{
            $markConditions = [
            $StudentAbsencesPeriodDetails->aliasField('academic_period_id') => $entity->academic_period_id, 
            $StudentAbsencesPeriodDetails->aliasField('institution_id') => $entity->institution_id,
            $StudentAbsencesPeriodDetails->aliasField('institution_class_id') => $entity->id, 
            $StudentAbsencesPeriodDetails->aliasField('date') =>  $dateFormatted,
            $StudentAbsencesPeriodDetails->aliasField('period') =>  $periodId,
            'absence_type_id IN (1,2)',
            'SecurityUsers.gender_id' => 1,
            $StudentAbsencesPeriodDetails->aliasField('subject_id') => 0
            ];
        }
        
        $totalStudentsAbsent = $StudentAbsencesPeriodDetails->find()    
            ->leftJoin(['SecurityUsers' => 'security_users'], [
                        'SecurityUsers.id = '. $StudentAbsencesPeriodDetails->aliasField('student_id')
                    ])
            ->where($markConditions)
            ->count()
            ;
        
        if ($entity->class_attendance_records == RecordMarkedType::MARKED) {
           
            $totalMaleStudentsAbsent = $entity->male_absence_count;
            $totalMaleStudents = $institutionClasses->total_male_students;
            $totalMaleStudentsPresent = $totalMaleStudents - $totalMaleStudentsAbsent;
           
        }
        
        else if ($entity->class_attendance_records == RecordMarkedType::PARTIAL_MARKED) {
           
           
            $totalMaleStudentsAbsent = $totalStudentsAbsent;
            $totalMaleStudents = $institutionClasses->total_male_students;
            $totalMaleStudentsPresent = $totalMaleStudents - $totalMaleStudentsAbsent;
        
        }
        else{
            $totalMaleStudentsPresent = '-';
        }
      
        return $totalMaleStudentsPresent;
    }

   
    public function onExcelRenderTotalFemaleStudentsLate(Event $event, Entity $entity, $attr)
    {
        $periodId = $entity->StudentAttendanceMarkedRecords['period'];
        $subjectId = $entity->StudentAttendanceMarkedRecords['subject_id'];
        $totalFemaleStudentsLate = 0;
        $dateFormatted = $entity->date->format('Y-m-d');
        $StudentAbsencesPeriodDetails = TableRegistry::get('Institution.StudentAbsencesPeriodDetails');   
                
        if(!empty($subjectId)){
        $conditions = [
            $StudentAbsencesPeriodDetails->aliasField('academic_period_id') => $entity->academic_period_id, 
            $StudentAbsencesPeriodDetails->aliasField('institution_id') => $entity->institution_id,
            $StudentAbsencesPeriodDetails->aliasField('institution_class_id') => $entity->id, 
            $StudentAbsencesPeriodDetails->aliasField('date') =>  $dateFormatted,
            $StudentAbsencesPeriodDetails->aliasField('subject_id') =>  $subjectId,
            'absence_type_id'=>3,
            'SecurityUsers.gender_id' => 2,
            $StudentAbsencesPeriodDetails->aliasField('period') => 1
            ];
        }else{
            $conditions = [
            $StudentAbsencesPeriodDetails->aliasField('academic_period_id') => $entity->academic_period_id, 
            $StudentAbsencesPeriodDetails->aliasField('institution_id') => $entity->institution_id,
            $StudentAbsencesPeriodDetails->aliasField('institution_class_id') => $entity->id, 
            $StudentAbsencesPeriodDetails->aliasField('date') =>  $dateFormatted,
            $StudentAbsencesPeriodDetails->aliasField('period') =>  $periodId,
            'absence_type_id'=>3,
            'SecurityUsers.gender_id' => 2,
            $StudentAbsencesPeriodDetails->aliasField('subject_id') => 0
            ];
        }
        
        $totalStudentsLate = $StudentAbsencesPeriodDetails->find()
            ->leftJoin(['SecurityUsers' => 'security_users'], [
                        'SecurityUsers.id = '. $StudentAbsencesPeriodDetails->aliasField('student_id')
                    ])
            ->where($conditions)
            ->count()
            ;
     if ($entity->class_attendance_records == RecordMarkedType::MARKED) {
        if ($entity->has('female_late_count')) {
            $totalFemaleStudentsLate = $entity->female_late_count;
        }

        if ($totalFemaleStudentsLate <= 0) {
            $totalFemaleStudentsLate = '-';
        }

        if ($entity->class_attendance_records == RecordMarkedType::PARTIAL_MARKED) {
           
            $totalFemaleStudent = $entity->female_late_count;
            $totalFemaleStudentsLate = $totalStudentsLate-$totalFemaleStudent;
          
        }
    }
        return $totalFemaleStudentsLate;
    
    }
    public function onExcelRenderTotalMaleStudentsLate(Event $event, Entity $entity, $attr)
    {
        $totalMaleStudentsLate = 0;
        $periodId = $entity->StudentAttendanceMarkedRecords['period'];
        $subjectId = $entity->StudentAttendanceMarkedRecords['subject_id'];
        $dateFormatted = $entity->date->format('Y-m-d');
        $StudentAbsencesPeriodDetails = TableRegistry::get('Institution.StudentAbsencesPeriodDetails');   
               
        if(!empty($subjectId)){
        $conditions = [
            $StudentAbsencesPeriodDetails->aliasField('academic_period_id') => $entity->academic_period_id, 
            $StudentAbsencesPeriodDetails->aliasField('institution_id') => $entity->institution_id,
            $StudentAbsencesPeriodDetails->aliasField('institution_class_id') => $entity->id, 
            $StudentAbsencesPeriodDetails->aliasField('date') =>  $dateFormatted,
            $StudentAbsencesPeriodDetails->aliasField('subject_id') =>  $subjectId,
            'absence_type_id'=>3,
            'SecurityUsers.gender_id' => 1,
            $StudentAbsencesPeriodDetails->aliasField('period') => 1
            ];
        }else{
            $conditions = [
            $StudentAbsencesPeriodDetails->aliasField('academic_period_id') => $entity->academic_period_id, 
            $StudentAbsencesPeriodDetails->aliasField('institution_id') => $entity->institution_id,
            $StudentAbsencesPeriodDetails->aliasField('institution_class_id') => $entity->id, 
            $StudentAbsencesPeriodDetails->aliasField('date') =>  $dateFormatted,
            $StudentAbsencesPeriodDetails->aliasField('period') =>  $periodId,
            'absence_type_id'=>3,
            'SecurityUsers.gender_id' => 1,
            $StudentAbsencesPeriodDetails->aliasField('subject_id') => 0
            ];
        }
        $totalStudentsLate = $StudentAbsencesPeriodDetails->find()
            ->leftJoin(['SecurityUsers' => 'security_users'], [
                        'SecurityUsers.id = '. $StudentAbsencesPeriodDetails->aliasField('student_id')
                    ])
            ->where($conditions)
            ->count()
            ;

        if ($entity->class_attendance_records == RecordMarkedType::MARKED) {
            if ($entity->has('male_late_count')) {
                $totalMaleStudentsLate = $entity->male_late_count;
            }

            if ($totalMaleStudentsLate == 0) {
                $totalMaleStudentsLate = '-';
            }
        }
        if ($entity->class_attendance_records == RecordMarkedType::PARTIAL_MARKED) {
            $totalMaleStudent = $entity->male_late_count;
            $totalMaleStudentsLate = $totalStudentsLate;
          
        }
        
        return $totalMaleStudentsLate;
    }


    public function onExcelRenderTotalStudentsLate(Event $event, Entity $entity, $attr)
    {
      
        $totalStudentsLate = 0;
        $periodId = $entity->StudentAttendanceMarkedRecords['period'];
        $subjectId = $entity->StudentAttendanceMarkedRecords['subject_id'];
        $dateFormatted = $entity->date->format('Y-m-d');

        $StudentAbsencesPeriodDetails = TableRegistry::get('Institution.StudentAbsencesPeriodDetails');
                
        if(!empty($subjectId)){
        $conditions = [
            $StudentAbsencesPeriodDetails->aliasField('academic_period_id') => $entity->academic_period_id, 
            $StudentAbsencesPeriodDetails->aliasField('institution_id') => $entity->institution_id,
            $StudentAbsencesPeriodDetails->aliasField('institution_class_id') => $entity->id, 
            $StudentAbsencesPeriodDetails->aliasField('date') =>  $dateFormatted,
            $StudentAbsencesPeriodDetails->aliasField('subject_id') =>  $subjectId,
            'absence_type_id' => 3,
            $StudentAbsencesPeriodDetails->aliasField('period') => 1
            ];
        }else{
            $conditions = [
            $StudentAbsencesPeriodDetails->aliasField('academic_period_id') => $entity->academic_period_id, 
            $StudentAbsencesPeriodDetails->aliasField('institution_id') => $entity->institution_id,
            $StudentAbsencesPeriodDetails->aliasField('institution_class_id') => $entity->id, 
            $StudentAbsencesPeriodDetails->aliasField('date') =>  $dateFormatted,
            $StudentAbsencesPeriodDetails->aliasField('period') =>  $periodId,
            'absence_type_id' => 3,
            $StudentAbsencesPeriodDetails->aliasField('subject_id') => 0
            ];
        }
        
        $totalStudentsLate = $StudentAbsencesPeriodDetails->find()
            ->where($conditions)
            ->count();
        
        if ($totalStudentsLate <= 0) {
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
public function onExcelRenderSubject(Event $event, Entity $entity, $attr)
    {
        $subjectId = $entity->StudentAttendanceMarkedRecords['subject_id'];
        $periodId = $entity->StudentAttendanceMarkedRecords['period'];
        $subjectName = '';
        
        if(!empty($subjectId)){
            $institutionSubject = TableRegistry::get('Institution.InstitutionSubjects');             
            $periodDetails = $institutionSubject->find('all')
            ->select(['name'])
            ->where(['id' => $subjectId])
            ->first();
            $subjectName = $periodDetails->name;
        }
        
        return $subjectName;
    }
    
    public function onExcelRenderPeriod(Event $event, Entity $entity, $attr)
    {
        $periodId = $entity->StudentAttendanceMarkedRecords['period'];
        $subjectId = $entity->StudentAttendanceMarkedRecords['subject_id'];
        $educationGradeId = $entity->InstitutionClassGrades['education_grade_id'];
        
        $periodName = '';
        
        if(!empty($periodId)){
            $institionClassId = $entity->id;
            $academicPeriodId = $entity->academic_period_id;
            $dayId = date('Y-m-d');
            $studentAttendanceMarkTypeTmpArr = [];

            $studentAttendanceMarkTypesTable = TableRegistry::get('Attendance.StudentAttendanceMarkTypes');
            $studentAttendanceMarkTypes = $studentAttendanceMarkTypesTable->getAttendancePerDayOptionsByClass(
                    $institionClassId, $academicPeriodId, $dayId , $educationGradeId
                    );

            foreach ($studentAttendanceMarkTypes as $studentAttendanceMarkTypes){
                $studentAttendanceMarkTypeTmpArr[$studentAttendanceMarkTypes['id']] = $studentAttendanceMarkTypes['name'];
            }
        
            $periodName = $studentAttendanceMarkTypeTmpArr[$periodId];
        }
        
        return (empty($subjectId))?$periodName:'';
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
