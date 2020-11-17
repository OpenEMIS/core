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
use Cake\Datasource\ConnectionManager;

use App\Model\Table\ControllerActionTable;

class StudentArchiveTable extends ControllerActionTable
{
    private $allDayOptions = [];
    private $selectedDate;
    private $_absenceData = [];

    public function initialize(array $config)
    {
        $connectionone = ConnectionManager::get('default');
        $connectiontwo = ConnectionManager::get('prd_cor_arc');

        $AcademicPeriods = TableRegistry::config('default', ['table' => 'academic_periods']);
        $Institution = TableRegistry::config('default', ['table' => 'institutions']);
        $institution_classes = TableRegistry::config('default', ['table' => 'institution_classes']);
        $institution_class_grades = TableRegistry::config('default', ['table' => 'institution_class_grades']);
        $education_grades = TableRegistry::config('default', ['table' => 'education_grades']);
        $institution_class_students = TableRegistry::config('default', ['table' => 'institution_class_students']);
        $security_users = TableRegistry::config('default', ['table' => 'security_users']);
        $institution_subjects = TableRegistry::config('default', ['table' => 'institution_subjects']);
        $student_mark_type_statuses = TableRegistry::config('default', ['table' => 'student_mark_type_statuses']);
        $student_attendance_types = TableRegistry::config('default', ['table' => 'student_attendance_types']);
        $student_attendance_per_day_periods = TableRegistry::config('default', ['table' => 'student_attendance_per_day_periods']);
        $student_mark_type_status_grades = TableRegistry::config('default', ['table' => 'student_mark_type_status_grades']);
        $student_absence_reasons = TableRegistry::config('default', ['table' => 'student_absence_reasons']);

        $student_attendance_marked_records = TableRegistry::config('prd_cor_arc', ['table' => 'student_attendance_marked_records']);
        $student_attendance_mark_types = TableRegistry::config('prd_cor_arc', ['table' => 'student_attendance_mark_types']);
        $institution_student_absence_details = TableRegistry::config('prd_cor_arc', ['table' => 'institution_student_absence_details']);

        $allData = $connection->query("SELECT
        all_class_students.marked_date AS 'date',
        all_class_students.academic_period_name AS 'academic_period',
        all_class_students.institutions_code AS 'institution_id',
        all_class_students.institutions_name AS 'institution_name',
        all_class_students.institution_classes_name AS 'institution_class_name',
        IF(all_class_students.subject_name IS NULL,all_class_students.period_name, all_class_students.subject_name) AS 'Attendance Per Day',
        all_class_students.security_users_oe AS 'student_id',
        IF(student_absences.attendance IS NULL,'Present',student_absences.attendance) AS 'student_attendance',
        IF(student_absences.absence_type IS NULL ,'',student_absences.absence_type) AS 'student_absence_type',
        IF(student_absences.absence_reasons IS NULL,'',student_absences.absence_reasons) AS 'student_absence_reasons'
        FROM
        (
            SELECT 
            student_attendance_marked_records.date AS marked_date,
            academic_periods.id AS academic_period_id,
            academic_periods.name AS academic_period_name,
            academic_periods.current AS academic_periods_current,
            institutions.id AS institutions_id,
            institutions.code AS institutions_code,
            institutions.name AS institutions_name,
            institution_classes.id AS institution_classes_id,
            institution_classes.name AS institution_classes_name,
            student_attendance_marked_records.subject_id,
            institution_subjects.name AS subject_name,
            IF(attendance_config.period IS NULL, student_attendance_marked_records.period,attendance_config.period) AS period_id,
            IF(attendance_config.period_name IS NULL,CONCAT('Period ',student_attendance_marked_records.period),attendance_config.period_name) AS period_name,
            security_users.id AS security_users_id,
            security_users.openemis_no AS security_users_oe
            FROM $connectiontwo.$student_attendance_marked_records
            INNER JOIN $connectionone.$AcademicPeriods
            ON student_attendance_marked_records.academic_period_id = academic_periods.id
            INNER JOIN $connectionone.$Institutions
            ON student_attendance_marked_records.institution_id = institutions.id
            INNER JOIN $connectionone.$institution_classes
            ON student_attendance_marked_records.institution_class_id = institution_classes.id
            INNER JOIN $connectionone.$institution_class_grades
            ON institution_classes.id = institution_class_grades.institution_class_id
            INNER JOIN $connectionone.$education_grades
            ON institution_class_grades.education_grade_id = education_grades.id 
            INNER JOIN $connectionone.$institution_class_students
            ON institution_class_students.institution_class_id = institution_classes.id
            INNER JOIN $connectionone.$security_users
            ON institution_class_students.student_id = security_users.id
            LEFT JOIN $connectionone.$institution_subjects
            ON institution_subjects.id = student_attendance_marked_records.subject_id
            LEFT JOIN 
                (SELECT 
                    student_attendance_types.name,
                    student_mark_type_status_grades.education_grade_id,
                    student_mark_type_statuses.academic_period_id,
                    student_mark_type_statuses.date_enabled,
                    student_mark_type_statuses.date_disabled,
                    student_attendance_per_day_periods.name AS period_name,
                    student_attendance_per_day_periods.period,
                        student_attendance_per_day_periods.order,
                    student_attendance_mark_types.attendance_per_day
                    FROM $connectionone.$student_mark_type_statuses
                        INNER JOIN $connectiontwo.$student_attendance_mark_types
                        ON student_mark_type_statuses.student_attendance_mark_type_id = student_attendance_mark_types.id
                        INNER JOIN $connectionone.$student_attendance_types
                        ON student_attendance_types.id = student_attendance_mark_types.student_attendance_type_id
                        LEFT JOIN $connectionone.$student_attendance_per_day_periods
                        ON student_attendance_per_day_periods.student_attendance_mark_type_id = student_attendance_mark_types.id 
                        INNER JOIN $connectionone.$student_mark_type_status_grades
                        ON student_mark_type_statuses.id = student_mark_type_status_grades.student_mark_type_status_id
                    ORDER BY student_mark_type_statuses.academic_period_id DESC,student_mark_type_status_grades.education_grade_id ASC, student_mark_type_statuses.date_enabled ASC,
                    student_attendance_per_day_periods.period ASC) AS attendance_config
                ON institution_class_grades.education_grade_id = attendance_config.education_grade_id
                AND institution_classes.academic_period_id = attendance_config.academic_period_id
                AND student_attendance_marked_records.period = attendance_config.period
                AND student_attendance_marked_records.date BETWEEN attendance_config.date_enabled AND attendance_config.date_disabled
        ) AS all_class_students
        LEFT JOIN 
        (SELECT
            IF(institution_student_absence_details.absence_type_id ='3', 'Late', 'Absent') AS attendance,
            IF(institution_student_absence_details.absence_type_id ='2','Unexcused Absence',IF(institution_student_absence_details.absence_type_id !='3','Excused Absence','')) absence_type,
            IF(student_absence_reasons.name IS NULL,'',student_absence_reasons.name) AS absence_reasons,
            institution_student_absence_details.student_id,
            institution_student_absence_details.institution_id,
            institution_student_absence_details.institution_class_id,
            institution_student_absence_details.date,
            institution_student_absence_details.period,
            institution_student_absence_details.subject_id,
            institution_student_absence_details.academic_period_id
                FROM $connectiontwo.$institution_student_absence_details 
                LEFT JOIN $connectionone.$student_absence_reasons
                ON institution_student_absence_details.student_absence_reason_id = student_absence_reasons.id
        ) AS student_absences
        ON all_class_students.marked_date = student_absences.date
        AND all_class_students.institution_classes_id = student_absences.institution_class_id
        AND all_class_students.institutions_id = student_absences.institution_id
        AND all_class_students.period_id = student_absences.period
        AND all_class_students.security_users_id = student_absences.student_id
        ORDER BY all_class_students.marked_date ASC,all_class_students.institutions_code ASC,all_class_students.institutions_name ASC, all_class_students.period_name ASC,all_class_students.security_users_oe;
    
    
    ");
        $user = $allData->fetch();
        if(empty($user)){
            echo "<pre>";print_r("Empty");exit;
        }else{
            echo "<pre>";print_r($user);exit;
        }
        

        $this->addBehavior('Restful.RestfulAccessControl', [
            'StudentArchive' => ['index', 'view']
        ]);
    }

    public function findClassStudentsWithAbsence()
    {
        $queryData = "Hello Ehteram";
        return $queryData;
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
