<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Query;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;
use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;

class StudentAbsencesTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('institution_student_absences');
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' =>'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' =>'institution_id']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses', 'foreignKey' =>'institution_class_id']);
        $this->belongsTo('AbsenceTypes', ['className' => 'Institution.AbsenceTypes', 'foreignKey' =>'absence_type_id']);
        $this->belongsTo('InstitutionStudentAbsenceDays', ['className' => 'Institution.InstitutionStudentAbsenceDays', 'foreignKey' =>'institution_student_absence_day_id']);
        $this->addBehavior('Report.ReportList');
        $this->addBehavior('StudentAbsencesExcel', [
            'excludes' => [
                'start_year',
                'end_year',
                'full_day',
                'start_date',
                'start_time',
                'end_time',
                'end_date'
            ],
            'pages' => false,
            'autoFields' => false
        ]);
        $this->addBehavior('Report.InstitutionSecurity');
    }
    public function onExcelGetDate(Event $event, Entity $entity)
    {
        return $this->formatDate($entity->date);
    }    

    public function onExcelGetAbsenceTypeId(Event $event, Entity $entity)
    {
        return $entity->absence_type;
    }

    public function onExcelGetInstitutionName(Event $event, Entity $entity)
    {

        return $entity->institution_id;
    }

    public function onExcelGetAttendancePerDay(Event $event, Entity $entity)
    { 
        $EducationGrades = TableRegistry::get('Education.EducationGrades');
        $StudentMarkTypeStatusGrades = TableRegistry::get('Attendance.StudentMarkTypeStatusGrades');
        $StudentMarkTypeStatuses = TableRegistry::get('Attendance.StudentMarkTypeStatuses');
        $StudentAttendanceMarkTypes = TableRegistry::get('Attendance.StudentAttendanceMarkTypes');
        $StudentAttendanceTypes = TableRegistry::get('Attendance.StudentAttendanceTypes');
        $StudentAttendancePerDayPeriods = TableRegistry::get('Attendance.StudentAttendancePerDayPeriods');
        $EducationGrades = TableRegistry::get('Education.EducationGrades');
        $InstitutionClassGrades = TableRegistry::get('Institution.InstitutionClassGrades');
        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $StudentAttendanceMarkedRecords = TableRegistry::get('Attendance.StudentAttendanceMarkedRecords');
        $academicPeriodsId = $entity->academicPeriodId;
        if (!is_null($academicPeriodsId) && $academicPeriodsId != 0) {
            $periodEntity = $AcademicPeriods->get($academicPeriodsId);

            $startDate = $periodEntity->start_date->format('Y-m-d');
            $endDate = $periodEntity->end_date->format('Y-m-d');
        }
        //echo "<pre>";
        //print_r($periodEntity);
        //echo "<br>";
        //print_r($endDate);
        die();
        $openemisNo = $entity->openemis_no;
        $Users = TableRegistry::get('User.Users');
        $userId = $Users->find()->where([$Users->aliasField('openemis_no') => $openemisNo])->first()->id;
        $findUser = $this->find()->where([$this->aliasField('student_id')=> $userId])->first();
        $institutionId = $findUser->institution_id;
        $yearId = $findUser->academic_period_id;
        $classId = $findUser->institution_class_id;
        $gradeId = $findUser->education_grade_id;
        $date = $findUser->date->format('Y-m-d');

        $check = $StudentAttendanceMarkedRecords->find()
                ->where([
                   $StudentAttendanceMarkedRecords->aliasField('institution_id') => $institutionId,
                   $StudentAttendanceMarkedRecords->aliasField('academic_period_id') => $yearId ,
                   $StudentAttendanceMarkedRecords->aliasField('institution_class_id') => $classId,
                   $StudentAttendanceMarkedRecords->aliasField('education_grade_id') => $gradeId,
                   $StudentAttendanceMarkedRecords->aliasField('date') => $date
                ])->first();

        $row = [];
        $overlapDateCondition['OR'] = [];
        $overlapDateCondition['OR'][] = [$StudentMarkTypeStatuses->aliasField('date_enabled') . ' >= ' => $startDate, $StudentMarkTypeStatuses->aliasField('date_enabled') . ' <= ' => $endDate];
        $overlapDateCondition['OR'][] = [$StudentMarkTypeStatuses->aliasField('date_disabled'). ' >= ' => $startDate, $StudentMarkTypeStatuses->aliasField('date_disabled') . ' <= ' => $endDate];
        $overlapDateCondition['OR'][] = [$StudentMarkTypeStatuses->aliasField('date_enabled') . ' <= ' => $startDate, $StudentMarkTypeStatuses->aliasField('date_disabled') . ' >= ' => $endDate];

        if ($check['period'] !=0 && $check['subject_id'] == 0) {
            $data = $this->find()
                ->select([
                    $StudentAttendancePerDayPeriods->aliasField('name'),
                    $StudentAttendanceTypes->aliasField('code'),
                ])
                ->leftJoin(
                    [$InstitutionClassGrades->alias() => $InstitutionClassGrades->table()],
                    [
                        $InstitutionClassGrades->aliasField('institution_class_id = ') . $this->aliasField('institution_class_id')
                    ]
                )
                ->leftJoin(
                    [$EducationGrades->alias() => $EducationGrades->table()],
                    [
                        $EducationGrades->aliasField('id = ') . $InstitutionClassGrades->aliasField('education_grade_id')
                    ]
                )
                ->leftJoin([$StudentMarkTypeStatusGrades->alias() => $StudentMarkTypeStatusGrades->table()], [
                    $StudentMarkTypeStatusGrades->aliasField('education_grade_id = ') . $EducationGrades->aliasField('id')
                ])
                ->innerJoin([$StudentMarkTypeStatuses->alias() => $StudentMarkTypeStatuses->table()], [
                    $StudentMarkTypeStatuses->aliasField('id = ') . $StudentMarkTypeStatusGrades->aliasField('student_mark_type_status_id')
                ])
                ->innerJoin([$StudentAttendanceMarkTypes->alias() => $StudentAttendanceMarkTypes->table()], [
                    $StudentAttendanceMarkTypes->aliasField('id = ') . $StudentMarkTypeStatuses->aliasField('student_attendance_mark_type_id')
                ])
                ->innerJoin([$StudentAttendanceTypes->alias() => $StudentAttendanceTypes->table()], [
                    $StudentAttendanceTypes->aliasField('id = ') . $StudentAttendanceMarkTypes->aliasField('student_attendance_type_id')
                ])
                ->leftJoin([$StudentAttendancePerDayPeriods->alias() => $StudentAttendancePerDayPeriods->table()], [
                        $StudentAttendancePerDayPeriods->aliasField('student_attendance_mark_type_id = ') . $StudentAttendanceMarkTypes->aliasField('id')
                ])
                ->group([$StudentAttendancePerDayPeriods->aliasField('name')])
                ->where([
                    $this->aliasField('academic_period_id') => $academicPeriodsId, 
                    $this->aliasField('student_id') => $userId,
                    $this->aliasField('date >= ') => $startDate,
                    $this->aliasField('date <= ') => $endDate,
                     $overlapDateCondition
                ])
                ->toArray();
        
            if (!empty($data)) {
                foreach ($data as $key => $value) {
                    $row[] =  $value->StudentAttendancePerDayPeriods['name'];
                }
            }
        }
        return implode(',', $row);  
    }

    public function onExcelGetSubjects(Event $event, Entity $entity)
    {
        $EducationGrades = TableRegistry::get('Education.EducationGrades');
        $StudentMarkTypeStatusGrades = TableRegistry::get('Attendance.StudentMarkTypeStatusGrades');
        $StudentMarkTypeStatuses = TableRegistry::get('Attendance.StudentMarkTypeStatuses');
        $StudentAttendanceMarkTypes = TableRegistry::get('Attendance.StudentAttendanceMarkTypes');
        $StudentAttendanceTypes = TableRegistry::get('Attendance.StudentAttendanceTypes'); 
        $EducationGrades = TableRegistry::get('Education.EducationGrades');
        $InstitutionClassGrades = TableRegistry::get('Institution.InstitutionClassGrades');
        $AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $InstitutionSubjectStudents = TableRegistry::get('Institution.InstitutionSubjectStudents');
        $InstitutionSubjects = TableRegistry::get('Institution.InstitutionSubjects');
        $StudentAttendanceMarkedRecords = TableRegistry::get('Attendance.StudentAttendanceMarkedRecords');
        $academicPeriodsId = $entity->academicPeriodId;
        if (!is_null($academicPeriodsId) && $academicPeriodsId != 0) {
            $periodEntity = $AcademicPeriods->get($academicPeriodsId);

            $startDate = $periodEntity->start_date->format('Y-m-d');
            $endDate = $periodEntity->end_date->format('Y-m-d');
        }
        $openemisNo = $entity->openemis_no;
        $Users = TableRegistry::get('User.Users');
        $userId = $Users->find()->where([$Users->aliasField('openemis_no') => $openemisNo])->first()->id;
        $findUser = $this->find()->where([$this->aliasField('student_id')=> $userId])->first();
        $institutionId = $findUser->institution_id;
        $yearId = $findUser->academic_period_id;
        $classId = $findUser->institution_class_id;
        $gradeId = $findUser->education_grade_id;
        $date = $findUser->date->format('Y-m-d');

        $check = $StudentAttendanceMarkedRecords->find()
                ->where([
                   $StudentAttendanceMarkedRecords->aliasField('institution_id') => $institutionId,
                   $StudentAttendanceMarkedRecords->aliasField('academic_period_id') => $yearId ,
                   $StudentAttendanceMarkedRecords->aliasField('institution_class_id') => $classId,
                   $StudentAttendanceMarkedRecords->aliasField('education_grade_id') => $gradeId,
                   $StudentAttendanceMarkedRecords->aliasField('date') => $date
                ])->first();
        
        $row = [];
        $overlapDateCondition['OR'] = [];
        $overlapDateCondition['OR'][] = [$StudentMarkTypeStatuses->aliasField('date_enabled') . ' >= ' => $startDate, $StudentMarkTypeStatuses->aliasField('date_enabled') . ' <= ' => $endDate];
        $overlapDateCondition['OR'][] = [$StudentMarkTypeStatuses->aliasField('date_disabled'). ' >= ' => $startDate, $StudentMarkTypeStatuses->aliasField('date_disabled') . ' <= ' => $endDate];
        $overlapDateCondition['OR'][] = [$StudentMarkTypeStatuses->aliasField('date_enabled') . ' <= ' => $startDate, $StudentMarkTypeStatuses->aliasField('date_disabled') . ' >= ' => $endDate];

        if ($check['subject_id'] != 0) {
             $data = $this->find()
                ->select([$StudentAttendanceTypes->aliasField('code'), 
                    $this->aliasField('education_grade_id'), 
                    $InstitutionSubjects->aliasField('name')
                ])
                ->leftJoin(
                    [$InstitutionClassGrades->alias() => $InstitutionClassGrades->table()],
                    [
                        $InstitutionClassGrades->aliasField('institution_class_id = ') . $this->aliasField('institution_class_id')
                    ]
                )
                ->leftJoin(
                    [$InstitutionSubjectStudents->alias() => $InstitutionSubjectStudents->table()],
                    [
                        $InstitutionSubjectStudents->aliasField('institution_class_id = ') . $InstitutionClassGrades->aliasField('institution_class_id')
                    ]
                )
                ->leftJoin(
                    [$EducationGrades->alias() => $EducationGrades->table()],
                    [
                        $EducationGrades->aliasField('id = ') . $InstitutionClassGrades->aliasField('education_grade_id')
                    ]
                )
                ->leftJoin(
                    [$InstitutionSubjects->alias() => $InstitutionSubjects->table()],
                    [
                        $InstitutionSubjects->aliasField('id = ') . $InstitutionSubjectStudents->aliasField('institution_subject_id')
                    ]
                )
                ->leftJoin([$StudentMarkTypeStatusGrades->alias() => $StudentMarkTypeStatusGrades->table()], [
                   $EducationGrades->aliasField('id = ')  . $StudentMarkTypeStatusGrades->aliasField('education_grade_id')
                ])
                ->innerJoin([$StudentMarkTypeStatuses->alias() => $StudentMarkTypeStatuses->table()], [
                    $StudentMarkTypeStatuses->aliasField('id = ') . $StudentMarkTypeStatusGrades->aliasField('student_mark_type_status_id')
                ])
                ->innerJoin([$StudentAttendanceMarkTypes->alias() => $StudentAttendanceMarkTypes->table()], [
                    $StudentAttendanceMarkTypes->aliasField('id = ') . $StudentMarkTypeStatuses->aliasField('student_attendance_mark_type_id')
                ])
                ->innerJoin([$StudentAttendanceTypes->alias() => $StudentAttendanceTypes->table()], [
                    $StudentAttendanceTypes->aliasField('id = ') . $StudentAttendanceMarkTypes->aliasField('student_attendance_type_id')
                ])
                ->group([$InstitutionSubjects->aliasField('id')])
                ->where([
                    $this->aliasField('academic_period_id') => $academicPeriodsId, 
                    $this->aliasField('student_id') => $userId,
                    $this->aliasField('date >= ') => $startDate,
                    $this->aliasField('date <= ') => $endDate,
                     $overlapDateCondition,
                ])
                ->toArray();

            if (!empty($data)) {
                foreach ($data as $key => $value) {
                    $row[] = $value->InstitutionSubjects['name'];
                }
            } 
        }
       
        return implode(',', $row);       
    }   
}
