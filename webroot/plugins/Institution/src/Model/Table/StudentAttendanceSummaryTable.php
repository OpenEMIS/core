<?php

namespace Institution\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\Log\Log;
use Cake\ORM\Entity;
use DateTime;
/**
 * Get the StudentAttendanceSummaryTable Report details in excel file 
 * @ticket POCOR-6872
 */
class StudentAttendanceSummaryTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('institution_student_absences');
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' =>'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' =>'institution_id']);
        $this->belongsTo('InstitutionClasses', ['className' => 'Institution.InstitutionClasses', 'foreignKey' =>'institution_class_id']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades', 'foreignKey' =>'education_grade_id']);
        $this->belongsTo('AbsenceTypes', ['className' => 'Institution.AbsenceTypes', 'foreignKey' =>'absence_type_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' =>'academic_period_id']);
        $this->belongsTo('InstitutionStudentAbsenceDays', ['className' => 'Institution.InstitutionStudentAbsenceDays', 'foreignKey' =>'institution_student_absence_day_id']);

        // Behaviours
        $this->addBehavior('Excel', [
            'excludes' => [],
            'pages' => false,
            'autoFields' => false
        ]);
        $this->addBehavior('Report.ReportList');
    }

    public function beforeAction(Event $event)
    {
        $this->fields = [];
        $this->ControllerAction->field('feature', ['select' => false]);
        $this->ControllerAction->field('format');
        $this->ControllerAction->field('academic_period_id', ['type' => 'hidden']);

        $controllerName = $this->controller->name;
        $institutions_crumb = __('Institutions');
        $parent_crumb       = __('Statistics');
		$reportName         = __('Standard');
        
        //# START: Crumb
        $this->Navigation->removeCrumb($this->getHeader($this->alias));
        $this->Navigation->addCrumb($institutions_crumb . ' ' . $parent_crumb);
        //# END: Crumb
        $this->controller->set('contentHeader', __($institutions_crumb) . ' ' . $parent_crumb . ' - ' . $reportName);
    }

    public function onUpdateFieldFormat(Event $event, array $attr, $action, Request $request)
    {
        $session = $this->request->session();
        $institution_id = $session->read('Institution.Institutions.id');
        $request->data[$this->alias()]['current_institution_id'] = $institution_id;
        $request->data[$this->alias()]['institution_id'] = $institution_id;
        if ($action == 'add') {
            $attr['value'] = 'xlsx';
            $attr['attr']['value'] = 'Excel';
            $attr['type'] = 'readonly';
            return $attr;
        }
    }

    public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request)
    {
        $options = $options = $this->controller->getInstitutionStatisticStandardReportFeature();
        $attr['options'] = $options;
        $attr['onChangeReload'] = true;
        if (!(isset($this->request->data[$this->alias()]['feature']))) {
            $option = $attr['options'];
            reset($option);
            $this->request->data[$this->alias()]['feature'] = key($option);
        }
        return $attr;
    }

    public function onExcelBeforeStart(Event $event, ArrayObject $settings, ArrayObject $sheets)
    {
        $sheets[] = [
            'name' => $this->alias(),
            'table' => $this,
            'query' => $this->find(),
            'orientation' => 'landscape'
        ];
    }

    public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;
        $institutionId = $requestData->institution_id;
        $gradeId = $requestData->education_grade_id;
        $classId = $requestData->institution_class_id;
        $month = $requestData->month;
        $absentDays = TableRegistry::get('Institution. InstitutionStudentAbsences');
        $monthoption = ['01'=>"January",'02'=>"February",'03'=>"March",'04'=>"April",'05'=>"May",'06'=>"June",'07'=>"July",'08'=>"August",'09'=>"September",10=>"October",11=>"November",12=>"December"];
       
        //POCOR-7265::Start
        $subQuery = "(SELECT institution_class_students.academic_period_id 
        ,institution_class_students.institution_id
        ,institution_class_students.education_grade_id
        ,institution_class_students.institution_class_id
        ,institution_class_students.student_id
	    ,security_users.openemis_no
	    ,CONCAT_WS(' ',security_users.first_name,security_users.middle_name,security_users.third_name,security_users.last_name) student_name
	    ,institution_classes.name institution_classes_name
	FROM institution_class_students
	INNER JOIN security_users
	ON security_users.id = institution_class_students.student_id
	INNER JOIN institution_classes
	ON institution_classes.id = institution_class_students.institution_class_id 
	INNER JOIN academic_periods
	ON academic_periods.id = institution_class_students.academic_period_id
	WHERE academic_periods.id = $academicPeriodId
	AND IF((CURRENT_DATE >= academic_periods.start_date AND CURRENT_DATE <= academic_periods.end_date), institution_class_students.student_status_id = 1, institution_class_students.student_status_id IN (1, 7, 6, 8))
	GROUP BY  institution_class_students.student_id)";
    
    $join=[];
    $conditions=[];


    $join['attendance_info'] = [
    'type' => 'inner',
    'table' => "(SELECT institutions.id institution_id
    ,institutions.code institution_code
    ,institutions.name institution_name
    ,month_generator.academic_period_id
    ,month_generator.year_name
    ,month_generator.month_id
    ,month_generator.month_name
    ,COUNT(month_generator.date_info) - IFNULL(private_holidays.private_days_out, 0) days_attended
FROM institutions
INNER JOIN 
(
        SELECT academic_period_id
        ,YEAR(m1) AS year_name
        ,MONTH(m1) AS month_id
        ,MONTHNAME(m1) AS month_name
        ,generated_date AS date_info
    FROM
    (
        SELECT 
            (academic_periods.start_date - INTERVAL DAYOFMONTH(academic_periods.start_date)-1 DAY) +INTERVAL m MONTH AS m1,
            academic_periods.end_date,
            academic_periods.id AS academic_period_id,
            DATE((academic_periods.start_date - INTERVAL DAYOFMONTH(academic_periods.start_date)-1 DAY) +INTERVAL m MONTH + INTERVAL days.day DAY) AS generated_date
        FROM academic_periods
        CROSS JOIN
        (
            SELECT  @rownum:= @rownum+1 AS m
            FROM
            (
                SELECT  1 UNION SELECT  2 UNION SELECT  3 UNION SELECT  4
            ) t1, 
            (
                SELECT  1 UNION SELECT  2 UNION SELECT  3 UNION SELECT  4
            ) t2,
            (
                SELECT  1 UNION SELECT  2 UNION SELECT  3 UNION SELECT  4
            ) t3,
            (
                SELECT  1 UNION SELECT  2 UNION SELECT  3 UNION SELECT  4
            ) t4,
            (
                SELECT  @rownum:= -1
            ) t0
        ) d1
        CROSS JOIN 
        (
            SELECT 0 AS day
            UNION ALL SELECT 1 UNION ALL SELECT 2 UNION ALL SELECT 3 UNION ALL SELECT 4
            UNION ALL SELECT 5 UNION ALL SELECT 6 UNION ALL SELECT 7 UNION ALL SELECT 8
            UNION ALL SELECT 9 UNION ALL SELECT 10 UNION ALL SELECT 11 UNION ALL SELECT 12
            UNION ALL SELECT 13 UNION ALL SELECT 14 UNION ALL SELECT 15 UNION ALL SELECT 16
            UNION ALL SELECT 17 UNION ALL SELECT 18 UNION ALL SELECT 19 UNION ALL SELECT 20
            UNION ALL SELECT 21 UNION ALL SELECT 22 UNION ALL SELECT 23 UNION ALL SELECT 24
            UNION ALL SELECT 25 UNION ALL SELECT 26 UNION ALL SELECT 27 UNION ALL SELECT 28
            UNION ALL SELECT 29 UNION ALL SELECT 30
        ) AS days
        CROSS JOIN 
        (
            SELECT MAX(CASE WHEN config_items.code = 'first_day_of_week' THEN IF(LENGTH(config_items.value) = 0, config_items.default_value, config_items.value) END) first_day_of_week
                ,MAX(CASE WHEN config_items.code = 'days_per_week' THEN IF(LENGTH(config_items.value) = 0, config_items.default_value, config_items.value) END) days_per_week
            FROM config_items
        ) working_days
        LEFT JOIN 
        (
            SELECT calendar_event_dates.date public_hol
            FROM calendar_event_dates
            INNER JOIN calendar_events
            ON calendar_events.id = calendar_event_dates.calendar_event_id
            WHERE calendar_events.academic_period_id = $academicPeriodId
            AND calendar_events.institution_id = -1
            GROUP BY calendar_event_dates.date
        ) public_hol_info
        ON public_hol_info.public_hol = DATE((academic_periods.start_date - INTERVAL DAYOFMONTH(academic_periods.start_date)-1 DAY) +INTERVAL m MONTH + INTERVAL days.day DAY)
        WHERE academic_periods.id = $academicPeriodId
        AND DATE((academic_periods.start_date - INTERVAL DAYOFMONTH(academic_periods.start_date)-1 DAY) +INTERVAL m MONTH + INTERVAL days.day DAY) <= academic_periods.end_date
        AND MONTH(DATE((academic_periods.start_date - INTERVAL DAYOFMONTH(academic_periods.start_date)-1 DAY) +INTERVAL m MONTH + INTERVAL days.day DAY)) = MONTH((academic_periods.start_date - INTERVAL DAYOFMONTH(academic_periods.start_date)-1 DAY) +INTERVAL m MONTH)
        AND YEAR(DATE((academic_periods.start_date - INTERVAL DAYOFMONTH(academic_periods.start_date)-1 DAY) +INTERVAL m MONTH + INTERVAL days.day DAY)) = YEAR((academic_periods.start_date - INTERVAL DAYOFMONTH(academic_periods.start_date)-1 DAY) +INTERVAL m MONTH)
        AND DAYOFWEEK(DATE((academic_periods.start_date - INTERVAL DAYOFMONTH(academic_periods.start_date)-1 DAY) +INTERVAL m MONTH + INTERVAL days.day DAY)) BETWEEN working_days.first_day_of_week + 1 AND working_days.first_day_of_week + days_per_week
        AND public_hol_info.public_hol IS NULL
    ) d2
    WHERE d2.m1 <= d2.end_date
) month_generator
LEFT JOIN 
(
    SELECT private_hol.institution_id
        ,MONTH(private_hol.date_info) month_info
        ,COUNT(*) private_days_out
    FROM 
    ( 
        SELECT calendar_events.id calendar_event_id
            ,calendar_events.institution_id
            ,calendar_event_dates.date date_info
        FROM calendar_event_dates
        INNER JOIN calendar_events
        ON calendar_events.id = calendar_event_dates.calendar_event_id
        INNER JOIN calendar_types
        ON calendar_types.id = calendar_events.calendar_type_id
        WHERE calendar_events.academic_period_id = $academicPeriodId
        AND calendar_events.institution_id != -1
        AND calendar_types.is_attendance_required = 0
        GROUP BY calendar_events.institution_id
            ,calendar_event_dates.date
    ) private_hol
    GROUP BY private_hol.institution_id
        ,MONTH(private_hol.date_info)
) private_holidays
ON private_holidays.institution_id = institutions.id
AND private_holidays.month_info = month_generator.month_id
GROUP BY month_generator.year_name
    ,institutions.id
    ,month_generator.month_id)",
    'conditions'=>[
        'attendance_info.institution_id = students_data.institution_id'
    ]
    ]; 




    $join['absence_info'] = [
    'type' => 'left',
    'table' => "(SELECT attend_info.academic_period_id
    ,attend_info.institution_id
    ,attend_info.education_grade_id
    ,attend_info.institution_class_id
    ,attend_info.student_id
    ,YEAR(attend_info.absence_date) absent_year
    ,MONTH(attend_info.absence_date) absent_month
    ,COUNT(*) absence_counter
FROM 
(
    SELECT institution_student_absence_details.academic_period_id
        ,institution_student_absence_details.institution_id
        ,institution_student_absence_details.education_grade_id
        ,institution_student_absence_details.institution_class_id
        ,institution_student_absence_details.student_id
        ,institution_student_absence_details.date absence_date
        ,institution_student_absence_details.subject_id
        ,period_counter.attendance_per_day period_attendance_per_day
        ,subject_counter.subjects_taken
        ,attendance_type.value 
    FROM institution_student_absence_details
    INNER JOIN 
    (
        SELECT student_mark_type_status_grades.education_grade_id
            ,student_mark_type_statuses.academic_period_id
            ,student_attendance_mark_types.attendance_per_day
        FROM student_mark_type_status_grades
        INNER JOIN student_mark_type_statuses
        ON student_mark_type_statuses.id = student_mark_type_status_grades.student_mark_type_status_id
        INNER JOIN student_attendance_mark_types
        ON student_attendance_mark_types.id = student_mark_type_statuses.student_attendance_mark_type_id
        GROUP BY student_mark_type_status_grades.education_grade_id
            ,student_mark_type_statuses.academic_period_id
    ) period_counter
    ON period_counter.education_grade_id = institution_student_absence_details.education_grade_id
    AND period_counter.academic_period_id = institution_student_absence_details.academic_period_id
    LEFT JOIN 
    (
        SELECT institution_subject_students.academic_period_id
            ,institution_subject_students.institution_id
            ,institution_subject_students.education_grade_id
            ,institution_subject_students.institution_class_id
            ,institution_subject_students.student_id
            ,COUNT(DISTINCT(institution_subject_students.education_subject_id)) subjects_taken
        FROM institution_subject_students
        INNER JOIN academic_periods
        ON academic_periods.id = institution_subject_students.academic_period_id
        WHERE institution_subject_students.academic_period_id = $academicPeriodId
        AND IF((CURRENT_DATE >= academic_periods.start_date AND CURRENT_DATE <= academic_periods.end_date), institution_subject_students.student_status_id = 1, institution_subject_students.student_status_id IN (1, 7, 6, 8))
        GROUP BY institution_subject_students.academic_period_id
            ,institution_subject_students.institution_id
            ,institution_subject_students.education_grade_id
            ,institution_subject_students.institution_class_id
            ,institution_subject_students.student_id
    ) subject_counter
    ON subject_counter.academic_period_id = institution_student_absence_details.academic_period_id
    AND subject_counter.institution_id = institution_student_absence_details.institution_id
    AND subject_counter.education_grade_id = institution_student_absence_details.education_grade_id
    AND subject_counter.institution_class_id = institution_student_absence_details.institution_class_id
    AND subject_counter.student_id = institution_student_absence_details.student_id
    CROSS JOIN
    (
        SELECT config_items.value
        FROM config_items
        WHERE config_items.code LIKE 'calculate_daily_attendance'
    ) attendance_type
    WHERE institution_student_absence_details.academic_period_id = $academicPeriodId
    AND institution_student_absence_details.absence_type_id != 3
    GROUP BY institution_student_absence_details.academic_period_id
        ,institution_student_absence_details.institution_id
        ,institution_student_absence_details.education_grade_id
        ,institution_student_absence_details.institution_class_id
        ,institution_student_absence_details.student_id
        ,institution_student_absence_details.date
    HAVING 
        CASE 
            WHEN attendance_type.value = 1 
            THEN COUNT(*) >= 1 
            ELSE 
                CASE WHEN institution_student_absence_details.subject_id = 0 
                THEN COUNT(*) >= period_counter.attendance_per_day
                ELSE COUNT(*) >= IFNULL(subject_counter.subjects_taken, 0)
                END
        END
) attend_info
GROUP BY attend_info.academic_period_id
    ,attend_info.institution_id
    ,attend_info.education_grade_id
    ,attend_info.institution_class_id
    ,attend_info.student_id
    ,YEAR(attend_info.absence_date)
    ,MONTH(attend_info.absence_date))",
    'conditions'=>[ 
        'absence_info.academic_period_id = students_data.academic_period_id',
        'absence_info.institution_id = students_data.institution_id',
        'absence_info.education_grade_id = students_data.education_grade_id',
        'absence_info.institution_class_id = students_data.institution_class_id',
        'absence_info.student_id = students_data.student_id',
        'absence_info.absent_year = attendance_info.year_name',
        'absence_info.absent_month = attendance_info.month_id',
    ]
    ]; 
 
    $query
        ->select([
            'openemis_no' => 'students_data.openemis_no',
            'student_name' => 'students_data.student_name',
            'institutions_code' => 'attendance_info.institution_code',
            'institutions_name'=>'attendance_info.institution_name',
            'institution_classes_name' => 'students_data.institution_classes_name',
            'month' => 'attendance_info.month_name',
            'year' => 'attendance_info.year_name',
            'total_attended_days' => "(attendance_info.days_attended - IFNULL(absence_info.absence_counter, 0))",
            'total_absent_days' => "(IFNULL(absence_info.absence_counter, 0))",
            'attendance_rate' => "(CONCAT(ROUND((attendance_info.days_attended - IFNULL(absence_info.absence_counter, 0)) / attendance_info.days_attended * 100, 2), '%'))",
            ])
        ->from(['students_data' => $subQuery])
        ->where([
            'attendance_info.month_id' => $month
        ]);
        $query->join($join);
        //POCOR-7265::End
   
    }

    function getBetweenDates($startDate, $endDate)
    {
        $rangArray = [];
            
        $startDate = strtotime($startDate);
        $endDate = strtotime($endDate);
             
        for ($currentDate = $startDate; $currentDate <= $endDate; 
                                        $currentDate += (86400)) {
                                                
            $date = date('Y-m-d', $currentDate);
            $rangArray[] = $date;
        }
  
        return $rangArray;
    }

    /**
     * Generate the all Header for sheet
     */
    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $newFields = [];
       
        $newFields[] = [
            'key'   => 'openemis_no',
            'field' => 'openemis_no',
            'type'  => 'string',
            'label' => __('OpenEMIS ID'),
        ];
        $newFields[] = [
            'key'   => 'student_name',
            'field' => 'student_name',
            'type'  => 'string',
            'label' => __('Student Name'),
        ];
        $newFields[] = [
            'key'   => 'institutions_code',
            'field' => 'institutions_code',
            'type'  => 'string',
            'label' => __('Institution Code'),
        ];
        $newFields[] = [
            'key'   => 'institutions_name',
            'field' => 'institutions_name',
            'type'  => 'string',
            'label' => __('Institution Name'),
        ];
        
        $newFields[] = [
            'key'   => 'institution_classes_name',
            'field' => 'institution_classes_name',
            'type'  => 'string',
            'label' => __('Class'),
        ];
        
        $newFields[] = [
            'key'   => 'month',
            'field' => 'month',
            'type'  => 'string',
            'label' => __('Month'),
        ];
        //POCOR-7265
        $newFields[] = [
            'key'   => 'year',
            'field' => 'year',
            'type'  => 'string',
            'label' => __('Year'),
        ];
        //POCOR-7265
        $newFields[] = [
            'key' => 'total_attended_days',
            'field' => 'total_attended_days',
            'type' => 'string',
            'label' => __('Total Attended Days')
        ];
        $newFields[] = [
            'key'   => 'total_absent_days',
            'field' => 'total_absent_days',
            'type'  => 'integer',
            'label' => __('Total Absent Days'),
        ];
        $newFields[] = [
            'key'   => 'attendance_rate',
            'field' => 'attendance_rate',
            'type'  => 'string',
            'label' => __('Attendance Rate'),
        ];
       
        

        $fields->exchangeArray($newFields);
    }

    

   
}
