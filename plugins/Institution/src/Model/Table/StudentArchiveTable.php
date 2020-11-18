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

        $allData = $connection->query("SELECT * FROM academic_periods");
        $user = $allData->fetchAll();
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
