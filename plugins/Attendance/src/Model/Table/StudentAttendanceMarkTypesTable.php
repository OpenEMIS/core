<?php
namespace Attendance\Model\Table;

use App\Model\Table\AppTable;
use Cake\Datasource\ResultSetInterface;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Attendance\Model\Table\StudentAttendanceTypesTable as AttendanceTypes;
use Cake\Log\Log;
use Cake\Event\Event;
use DateTime;//POCOR-7183
use Cake\I18n\Time;//POCOR-7183

class StudentAttendanceMarkTypesTable extends AppTable
{
    const MAX_MARK_DAYS = 5;
    const DEFAULT_ATTENDANCE_PER_DAY = 1;

    public function initialize(array $config)
    {
        $this->table('student_attendance_mark_types');
        parent::initialize($config);

        //$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades', 'foreignKey' => 'education_grade_id']);
        //$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' => 'academic_period_id']);
        $this->belongsTo('StudentAttendanceTypes', ['className' => 'Attendance.StudentAttendanceTypes', 'foreignKey' => 'attendance_type_id']);
        $this->addBehavior('Restful.RestfulAccessControl', [
            'StudentAttendances' => ['index', 'view']
        ]);
    }

    public function implementedEvents()
    {
        $events = parent::implementedEvents();
        $events['Restful.Model.isAuthorized'] = ['callable' => 'isAuthorized', 'priority' => 1];
        return $events;
    }

    public function isAuthorized(Event $event, $scope, $action, $extra)
    {
        if ($action == 'index' || $action == 'view') {
            // check for the user permission to view here
            $event->stopPropagation();
            return true;
        }
    }

    public function getDefaultMarkType()
    {
        $defaultTypes = [
            'student_attendance_type_id' => AttendanceTypes::DAY,
            'attendance_per_day' => self::DEFAULT_ATTENDANCE_PER_DAY
        ];

        return $defaultTypes;
    }

    public function getAttendancePerDayOptions()
    {
        $options = [];

        for ($i = 1; $i <= self::MAX_MARK_DAYS; ++$i) {
            $options[$i] = $i;
        }

        return $options;
    }

    public function isDefaultType($attendancePerDay, $attendanceTypeId)
    {
        return ($attendancePerDay == self::DEFAULT_ATTENDANCE_PER_DAY && $attendanceTypeId == AttendanceTypes::DAY);
    }

    public function getAttendancePerDayByClass($classId, $academicPeriodId)
    {
        $InstitutionClassGrades = TableRegistry::get('Institution.InstitutionClassGrades');
        $StudentMarkTypeStatuses = TableRegistry::get('Attendance.StudentMarkTypeStatuses');
        $StudentMarkTypeStatusGrades = TableRegistry::get('Attendance.StudentMarkTypeStatusGrades');
        $gradeId = $InstitutionClassGrades
            ->find()
            ->where([$InstitutionClassGrades->aliasField('institution_class_id') => $classId])
            ->extract('education_grade_id')
            ->first();

        if (!is_null($gradeId)) {
            $attendancePerDay = $this
                ->find()
                ->leftJoin(
                [$StudentMarkTypeStatuses->alias() => $StudentMarkTypeStatuses->table()],
                [
                 $StudentMarkTypeStatuses->aliasField('student_attendance_mark_type_id = ') . $this->aliasField('id')
                ]
                )
                ->leftJoin(
                [$StudentMarkTypeStatusGrades->alias() => $StudentMarkTypeStatusGrades->table()],
                [
                 $StudentMarkTypeStatusGrades->aliasField('student_mark_type_status_id = ') . $StudentMarkTypeStatuses->aliasField('id')
                ]
                )
                ->where([
                    $StudentMarkTypeStatusGrades->aliasField('education_grade_id') => $gradeId,
                    $StudentMarkTypeStatuses->aliasField('academic_period_id') => $academicPeriodId
                ])
                //->extract('attendance_per_day')
                ->first();
            if (!is_null($attendancePerDay)) {
                $attendancePerDayId = $attendancePerDay->id;
                $StudentAttendancePerDayPeriods = TableRegistry::get('Attendance.StudentAttendancePerDayPeriods');
                $modelData = $StudentAttendancePerDayPeriods
                             ->find()
                             ->select([
                                'id' => $StudentAttendancePerDayPeriods->aliasField('period'),//POCOR-7941
                                'name'
                            ])
                             ->where([$StudentAttendancePerDayPeriods->aliasField('student_attendance_mark_type_id') => $attendancePerDayId,
                    
                                ])
                             ->toArray();
                if (empty($modelData)) {
                    $data[] = [
                    'id' => 1,
                    'name' => 'Period 1'
                    ];
             
                    return $data;
                }                
                return $modelData;

            } else {
                $data[] = [
                    'id' => 1,
                    'name' => 'Period 1'
                ];
             
                return $data;
            }
        } else {
            Log::write('error', 'Error extracting education_grade_id for class_id ' . $classId);
        }
    }

    public function getAttendancePerDayOptionsByClass($classId, $academicPeriodId, $dayId, $educationGradeId, $weekStartDay = '', $weekEndDay = '')
    {// POCOR-7183 add parmas $weekStartDay, $weekEndDay
        $prefix = 'Period ';
        $InstitutionClassGrades = TableRegistry::get('Institution.InstitutionClassGrades');
        $StudentAttendanceTypes = TableRegistry::get('Attendance.StudentAttendanceTypes');
        $StudentMarkTypeStatuses = TableRegistry::get('Attendance.StudentMarkTypeStatuses');
        $StudentMarkTypeStatusGrades = TableRegistry::get('Attendance.StudentMarkTypeStatusGrades');
        $gradesResultSet = $InstitutionClassGrades
            ->find('list', [
                'keyField' => 'education_grade_id',
                'valueField' => 'education_grade_id'
            ])
            ->where([
                $InstitutionClassGrades->aliasField('institution_class_id') => $classId,
                $InstitutionClassGrades->aliasField('education_grade_id') => $educationGradeId
            ])->all();
        // if($dayId == -1){
        //     $gradesResultSet = $gradesResultSet->toArray();
        // }else{
            if (!$gradesResultSet->isEmpty()) {
                $gradeList = $gradesResultSet->toArray();
                $attendencePerDay = 1;
                if ($dayId == -1) {
                    $conditions = [
                        $StudentMarkTypeStatusGrades->aliasField('education_grade_id IN ') => $gradeList,
                        $StudentMarkTypeStatuses->aliasField('academic_period_id') => $academicPeriodId,
                        //$StudentMarkTypeStatuses->aliasField('date_enabled <= ') => $dayId,
                        //$StudentMarkTypeStatuses->aliasField('date_disabled >= ') => $dayId
                    ];
                }else{
                    $dayId = date('Y-m-d',strtotime($dayId));
                    $conditions = [
                        $StudentMarkTypeStatusGrades->aliasField('education_grade_id IN ') => $gradeList,
                        $StudentMarkTypeStatuses->aliasField('academic_period_id') => $academicPeriodId,
                        $StudentMarkTypeStatuses->aliasField('date_enabled <= ') => $dayId,
                        $StudentMarkTypeStatuses->aliasField('date_disabled >= ') => $dayId
                    ];
                }

                $markResultSet = $this
                    ->find()
                    ->select([
                        $this->aliasField('attendance_per_day'),
                        $StudentAttendanceTypes->aliasField('code')
                    ])
                    ->leftJoin(
                        [$StudentAttendanceTypes->alias() => $StudentAttendanceTypes->table()],
                        [
                         $StudentAttendanceTypes->aliasField('id = ') . $this->aliasField('student_attendance_type_id')
                        ]
                    )
                    ->leftJoin(
                        [$StudentMarkTypeStatuses->alias() => $StudentMarkTypeStatuses->table()],
                        [
                         $StudentMarkTypeStatuses->aliasField('student_attendance_mark_type_id = ') . $this->aliasField('id')
                        ]
                    )
                    ->leftJoin(
                        [$StudentMarkTypeStatusGrades->alias() => $StudentMarkTypeStatusGrades->table()],
                        [
                         $StudentMarkTypeStatusGrades->aliasField('student_mark_type_status_id = ') . $StudentMarkTypeStatuses->aliasField('id')
                        ]
                    )->where($conditions)->all()->first();

                $attendanceType = $markResultSet->StudentAttendanceTypes['code'];
                if ($attendanceType != 'SUBJECT') {
                    if (!empty($markResultSet->attendance_per_day)) {
                        $attendencePerDay = $markResultSet->attendance_per_day;
                    }
                }

                $StudentAttendancePerDayPeriods = TableRegistry::get('Attendance.StudentAttendancePerDayPeriods');
                //POCOR-7183 starts
                if($dayId == -1){
                    $DayConditions = [
                        $StudentMarkTypeStatusGrades->aliasField('education_grade_id IN ') => $gradeList,
                        $StudentMarkTypeStatuses->aliasField('academic_period_id') => $academicPeriodId,
                        $StudentMarkTypeStatuses->aliasField('date_enabled <= ') => $weekStartDay,
                        $StudentMarkTypeStatuses->aliasField('date_disabled >= ') => $weekEndDay
                    ];
                }else{
                    $dayId = date('Y-m-d',strtotime($dayId));
                    $DayConditions = [
                        $StudentMarkTypeStatusGrades->aliasField('education_grade_id IN ') => $gradeList,
                        $StudentMarkTypeStatuses->aliasField('academic_period_id') => $academicPeriodId,
                        $StudentMarkTypeStatuses->aliasField('date_enabled <= ') => $dayId,
                        $StudentMarkTypeStatuses->aliasField('date_disabled >= ') => $dayId
                    ];    
                }//POCOR-7183 ends
                $periodsData = $StudentAttendancePerDayPeriods
                                ->find('all')
                                ->leftJoin(
                                    [$StudentMarkTypeStatuses->alias() => $StudentMarkTypeStatuses->table()],
                                    [
                                     $StudentMarkTypeStatuses->aliasField('student_attendance_mark_type_id = ') . $StudentAttendancePerDayPeriods->aliasField('student_attendance_mark_type_id')
                                    ]
                                )
                                ->leftJoin(
                                    [$StudentMarkTypeStatusGrades->alias() => $StudentMarkTypeStatusGrades->table()],
                                    [
                                     $StudentMarkTypeStatusGrades->aliasField('student_mark_type_status_id = ') . $StudentMarkTypeStatuses->aliasField('id')
                                    ]
                                )
                                ->where([ $DayConditions ])//POCOR-7183
                                //->order(['order'=>'asc']) //POCOR-6059
                                //->all()//POCOR-6059
                                ->toArray();

                $options = [];
                $periodsDataId = [];
                $j = 0;  
                for ($k = 0; $k <= $attendencePerDay; ++$k) {
                  $periodsDataId[] =  $periodsData[$k]['id'];
                }
                
                $periodsDataId = array_filter($periodsDataId);
                asort($periodsDataId);
                $periodsDataId = array_combine(range(1, count($periodsDataId)), array_values($periodsDataId));
                $periodsDataId = array_flip($periodsDataId);  
                for ($i = 1; $i <= $attendencePerDay; ++$i) {
                    $options[] = [
                        'id' => (!empty($periodsDataId[$periodsData[$j]['id']])) ? $periodsDataId[$periodsData[$j]['id']] : $i,
                        'name' => __((!empty($periodsData[$j]['name'])) ? $periodsData[$j]['name'] : "Period ".$i)
                    ];
                    $j++;
                }
                return $options;
            } 
        //}
    }

    public function findPeriodByClass(Query $query, array $options)
    {
        $institionClassId = $options['institution_class_id'];
        $academicPeriodId = $options['academic_period_id'];
        $dayId = $options['day_id'];
        $educationGradeId = $options['education_grade_id'];
        $weekStartDay = $options['week_start_day'];//POCOR-7183
        $weekEndDay = $options['week_end_day'];//POCOR-7183

        $attendanceOptions = $this->getAttendancePerDayOptionsByClass($institionClassId, $academicPeriodId, $dayId, $educationGradeId, $weekStartDay, $weekEndDay);// POCOR-7183 add params $weekStartDay, $weekEndDay
        return $query
            ->formatResults(function (ResultSetInterface $results) use ($attendanceOptions) {
                return $attendanceOptions;
            });
    }
}
