<?php
namespace Institution\Model\Table;

use ArrayObject;

use Cake\Event\Event;
use Cake\I18n\Time;
use Cake\Network\Request;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\ResultSet;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;
use Cake\Validation\Validator;
use Cake\Chronos\Date;
use Cake\Datasource\ResultSetInterface;
use Cake\Core\Configure;

use App\Model\Table\ControllerActionTable;

class StudentMealsTable extends ControllerActionTable
{
    private $allDayOptions = [];
    private $selectedDate;
    private $_absenceData = [];

    public function initialize(array $config)
    {
        $this->table('institution_class_students');
        parent::initialize($config);

        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' =>'student_id']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('NextInstitutionClasses', ['className' => 'Institution.InstitutionClasses', 'foreignKey' =>'next_institution_class_id']);
        $this->hasMany('InstitutionClassGrades', ['className' => 'Institution.InstitutionClassGrades']);
        //$this->hasOne('StudentAbsencesPeriodDetails', ['className' => 'Institution.StudentAbsencesPeriodDetails']);
        $this->addBehavior('Excel', [
            'excludes' => [
                'start_date',
                'end_date',
                'start_year',
                'end_year',
                'FTE',
                'staff_type_id',
                'staff_status_id',
                'institution_id',
                'institution_position_id',
                'security_group_user_id'
            ],
            'pages' => ['index']
        ]);
       
        $AbsenceTypesTable = TableRegistry::get('Institution.AbsenceTypes');
        $this->absenceList = $AbsenceTypesTable->getAbsenceTypeList();
        $this->absenceCodeList = $AbsenceTypesTable->getCodeList();

        $this->addBehavior('Restful.RestfulAccessControl', [
            'StudentMeals' => ['index', 'view']
        ]);
    }

    public function findClassStudentsWithMeal(Query $query, array $options)
    {
        $institutionId = $options['institution_id'];
        $institutionClassId = $options['institution_class_id'];
        $academicPeriodId = $options['academic_period_id'];
        $weekId = $options['week_id'];
        $weekStartDay = $options['week_start_day'];
        $weekEndDay = $options['week_end_day'];
        $day = $options['day_id'];
        $subjectId = $options['subject_id'];      

        $InstitutionSubjectStudents = TableRegistry::get('Institution.InstitutionSubjectStudents');
        $this->Users = TableRegistry::get('Security.Users');

        if ($day == -1) {
            $findDay[] = $weekStartDay;
            $findDay[] = $weekEndDay;
        } else {
            $findDay = $day;
        }

        if ($subjectId != 0) {
            $query
            ->select([
                $this->aliasField('academic_period_id'),
                $this->aliasField('institution_class_id'),
                $this->aliasField('institution_id'),
                $this->aliasField('student_id'),
                $this->Users->aliasField('id'),
                $this->Users->aliasField('openemis_no'),
                $this->Users->aliasField('first_name'),
                $this->Users->aliasField('middle_name'),
                $this->Users->aliasField('third_name'),
                $this->Users->aliasField('last_name'),
                $this->Users->aliasField('preferred_name')
            ])
            ->contain([$this->Users->alias()])
            ->matching($this->StudentStatuses->alias(), function($q) {
                return $q->where([
                    $this->StudentStatuses->aliasField('code') => 'CURRENT'
                ]);
            })
            ->leftJoin(
                    [$InstitutionSubjectStudents->alias() => $InstitutionSubjectStudents->table()],
                    [
                        $InstitutionSubjectStudents->aliasField('institution_class_id = ') . $this->aliasField('institution_class_id'),
                        $InstitutionSubjectStudents->aliasField('student_id = ') . $this->aliasField('student_id'),
                    ]
                )
            ->where([
                $this->aliasField('academic_period_id') => $academicPeriodId,
                $this->aliasField('institution_class_id') => $institutionClassId,
                $InstitutionSubjectStudents->aliasField('institution_subject_id') => $subjectId
            ])
            ->group([
                $InstitutionSubjectStudents->aliasField('student_id')
            ])
            ->order([
                $this->Users->aliasField('id')
            ]);
        } else {
         
        $query
            ->select([
                $this->aliasField('academic_period_id'),
                $this->aliasField('institution_class_id'),
                $this->aliasField('institution_id'),
                $this->aliasField('student_id'),
                $this->Users->aliasField('id'),
                $this->Users->aliasField('openemis_no'),
                $this->Users->aliasField('first_name'),
                $this->Users->aliasField('middle_name'),
                $this->Users->aliasField('third_name'),
                $this->Users->aliasField('last_name'),
                $this->Users->aliasField('preferred_name')
            ])
            ->contain([$this->Users->alias()])
            ->matching($this->StudentStatuses->alias(), function($q) {
                return $q->where([
                    $this->StudentStatuses->aliasField('code') => 'CURRENT'
                ]);
            })
            ->where([
                $this->aliasField('academic_period_id') => $academicPeriodId,
                $this->aliasField('institution_class_id') => $institutionClassId,
            ])
            ->order([
                $this->Users->aliasField('first_name')
            ]);
        }

        return $query;
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $Users = TableRegistry::get('Security.Users');
        $StudentAbsencesPeriodDetails = TableRegistry::get('Institution.StudentAbsencesPeriodDetails');
        $institution_id = !empty($this->request->query['institution_id']) ? $this->request->query['institution_id'] : 0 ;

        $query
        ->leftJoin(
                    [$Users->alias() => $Users->table()],
                    [
                        $Users->aliasField('id = ') . $this->aliasField('student_id')
                    ]
                )
        ->where([$this->aliasField('institution_id') => $institution_id]);
    }

    public function onExcelBeforeStart(Event $event, ArrayObject $settings, ArrayObject $sheets)
    {
        ini_set("memory_limit", "-1");

        $institutionId = $this->Session->read('Institution.Institutions.id');
        $classId = !empty($this->request->query['institution_class_id']) ? $this->request->query['institution_class_id'] : 0 ;
        $attendancePeriodId = $this->request->query['attendance_period_id'];
        $weekId = $this->request->query['week_id'];
        $weekStartDay = $this->request->query['week_start_day'];
        $weekEndDay = $this->request->query['week_end_day'];
        $dayId = $this->request->query['day_id'];
        

        $sheetName = 'StudentAttendances';
        $sheets[] = [
            'name' => $sheetName,
            'table' => $this,
            'query' => $this
                ->find()
                ->select(['openemis_no' => 'Users.openemis_no'
                ]),
            'institutionId' => $institutionId,
            'classId' => $classId,
            'academicPeriodId' => $this->request->query['academic_period_id'],
            'attendancePeriodId' => $attendancePeriodId,
            'weekId' => $weekId,
            'weekStartDay' => $weekStartDay,
            'weekEndDay' => $weekEndDay,
            'dayId' => $dayId,
            'orientation' => 'landscape'
        ];
    }

    // To select another one more field from the containable data
    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $day_id = $this->request->query('day_id');
        $newArray[] = [
            'key' => 'StudentAttendances.name',
            'field' => 'name',
            'type' => 'string',
            'label' => 'Name'
        ];

        if ($day_id == -1) {

            $ConfigItems = TableRegistry::get('Configuration.ConfigItems');
            $firstDayOfWeek = $ConfigItems->value('first_day_of_week');
            $daysPerWeek = $ConfigItems->value('days_per_week');

            $optionTable = TableRegistry::get('Configuration.ConfigItemOptions');
            $options = $optionTable->find('list', ['keyField' => 'value', 'valueField' => 'option'])
                ->where([
                    'ConfigItemOptions.option_type' =>'first_day_of_week',
                    'ConfigItemOptions.visible' => 1
                ])
                ->toArray();

                $schooldays = [];
                for ($i = 0; $i < $daysPerWeek; ++$i) {
                    $schooldays[] = ($firstDayOfWeek + 7 + $i) % 7;
                }

                if (!empty($schooldays)) {
               
                   foreach ($schooldays as $key => $value) {
                        $newArray[] = [
                            'key' => 'StudentAttendances.week_attendance_status_'.$options[$value],
                            'field' => 'week_attendance_status_'.$options[$value],
                            'type' => 'string',
                            'label' => $options[$value]
                        ];
                   }
                }
        } else {
            $newArray[] = [
                'key' => 'StudentAttendances.attendance',
                'field' => 'attendance',
                'type' => 'string',
                'label' => ''
            ];
            $newArray[] = [
                'key' => 'StudentAttendances.student_absence_reasons',
                'field' => 'student_absence_reasons',
                'type' => 'string',
                'label' => 'Reason/Comment'
            ];
        }

        $fields_arr = $fields->getArrayCopy();
        $field_show = array();
        $filter_key = array('StudentAttendances.id','StudentAttendances.student_id','StudentAttendances.institution_class_id','StudentAttendances.education_grade_id','StudentAttendances.academic_period_id','StudentAttendances.next_institution_class_id','StudentAttendances.student_status_id');

        foreach ($fields_arr as $field){
            if (in_array($field['key'], $filter_key)) {
                unset($field);
            }
            else {
                array_push($field_show,$field);
            }
        }

        $newFields = array_merge($newArray, $field_show);
        $fields->exchangeArray($newFields);
        $sheet = $settings['sheet'];
        $AcademicPeriodTable = TableRegistry::get('AcademicPeriod.AcademicPeriods');

        // Set data into a temporary variable
        $options['institution_id'] = $sheet['institutionId'];
        $options['institution_class_id'] = $sheet['classId'];
        $options['academic_period_id'] = $sheet['academicPeriodId'];
        $options['attendance_period_id'] = $sheet['attendancePeriodId'];
        $options['week_id'] = $sheet['weekId'];
        $options['week_start_day'] = $sheet['weekStartDay'];
        $options['week_end_day'] = $sheet['weekEndDay'];
        $options['day_id'] = $sheet['dayId'];

        $this->_absenceData = $this->findClassStudentsWithAbsence($sheet['query'], $options);
    }

    public function onExcelRenderAttendance(Event $event, Entity $entity, array $attr)
    {
        // Get the data from the temporary variable
        $absenceData = $this->_absenceData;
        $absenceCodeList = $this->absenceCodeList;
        if (isset($absenceData[$entity->student_id][$attr['date']])) {
            $absenceObj = $absenceData[$entity->student_id][$attr['date']];
            if (! $absenceObj['full_day']) {
                $startTimeAbsent = $absenceObj['start_time'];
                $endTimeAbsent = $absenceObj['end_time'];
                $startTime = new Time($startTimeAbsent);
                $startTimeAbsent = $startTime->format('h:i A');
                $endTime = new Time($endTimeAbsent);
                $endTimeAbsent = $endTime->format('h:i A');
                if ($absenceCodeList[$absenceObj['absence_type_id']] == 'LATE') {
                    $secondsLate = intval($endTime->toUnixString()) - intval($startTime->toUnixString());
                    $minutesLate = $secondsLate / 60;
                    $hoursLate = floor($minutesLate / 60);
                    if ($hoursLate > 0) {
                        $minutesLate = $minutesLate - ($hoursLate * 60);
                        $lateString = $hoursLate.' '.__('Hour').' '.$minutesLate.' '.__('Minute');
                    } else {
                        $lateString = $minutesLate.' '.__('Minute');
                    }
                    $timeStr = sprintf(__($absenceObj['absence_type_name']) . ' - (%s)', $lateString);
                } else {
                    $timeStr = sprintf(__('Absent') . ' - ' . $absenceObj['absence_reason']. ' (%s - %s)', $startTimeAbsent, $endTimeAbsent);
                }
                return $timeStr;
            } else {
                return sprintf('%s %s %s', __('Absent'), __('Full'), __('Day'));
            }
        } else {
            return '';
        }
    }

    public function indexAfterAction(Event $event, Query $query, ResultSet $data, ArrayObject $extra)
    {
        echo "<pre>";print_r($data);die;
    }

    
}
