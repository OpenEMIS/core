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
       
        $subQuery = "(SELECT institution_class_students.*
        ,security_users.openemis_no
        ,CONCAT_WS(' ', security_users.first_name, security_users.middle_name, security_users.third_name, security_users.last_name) student_name
        ,institutions.code institutions_code
        ,institutions.name institutions_name
        ,institution_classes.name institution_classes_name
    FROM institution_class_students
    INNER JOIN security_users
    ON security_users.id = institution_class_students.student_id
    INNER JOIN institutions
    ON institutions.id = institution_class_students.institution_id
    INNER JOIN institution_classes
    ON institution_classes.id = institution_class_students.institution_class_id
    AND institution_classes.institution_id = institution_class_students.institution_id
    AND institution_classes.academic_period_id = institution_class_students.academic_period_id
    INNER JOIN academic_periods
    ON academic_periods.id = institution_class_students.academic_period_id
    WHERE academic_periods.id = $academicPeriodId
    AND institutions.id = $institutionId
    AND institution_class_students.student_status_id = 1
    GROUP BY institution_class_students.student_id)";
    
    $join=[];
    $conditions=[];

    $join['month_data'] = [
    'type' => 'cross',
    'table' => "(SELECT MONTH(institution_student_absences.date) month_id
    ,IF(MONTH(institution_student_absences.date) = 1, 'January', 
    IF(MONTH(institution_student_absences.date) = 2, 'February', 
        IF(MONTH(institution_student_absences.date) = 3, 'March',
            IF(MONTH(institution_student_absences.date) = 4, 'April',
                IF(MONTH(institution_student_absences.date) = 5, 'May',
                IF(MONTH(institution_student_absences.date) = 6, 'June',
                    IF(MONTH(institution_student_absences.date) = 7, 'July',
                        IF(MONTH(institution_student_absences.date) = 8, 'August',
                            IF(MONTH(institution_student_absences.date) = 9, 'September',
                            IF(MONTH(institution_student_absences.date) = 10, 'October',
                                IF(MONTH(institution_student_absences.date) = 11, 'November',
                                    IF(MONTH(institution_student_absences.date) = 12, 'December', '')))))))))))) month_name
    FROM institution_student_absences
    INNER JOIN academic_periods
    ON academic_periods.id = institution_student_absences.academic_period_id
    WHERE academic_periods.id = $academicPeriodId
    GROUP BY MONTH(institution_student_absences.date))",
    'conditions'=>[ ]
    ]; 


    $join['absence_data'] = [
    'type' => 'left',
    'table' => "(SELECT institution_student_absences.*
    ,COUNT(*) number_of_absences
    ,IF(MONTH(institution_student_absences.date) = 1, 'January', 
    IF(MONTH(institution_student_absences.date) = 2, 'February', 
        IF(MONTH(institution_student_absences.date) = 3, 'March',
            IF(MONTH(institution_student_absences.date) = 4, 'April',
                IF(MONTH(institution_student_absences.date) = 5, 'May',
                IF(MONTH(institution_student_absences.date) = 6, 'June',
                    IF(MONTH(institution_student_absences.date) = 7, 'July',
                        IF(MONTH(institution_student_absences.date) = 8, 'August',
                            IF(MONTH(institution_student_absences.date) = 9, 'September',
                            IF(MONTH(institution_student_absences.date) = 10, 'October',
                                IF(MONTH(institution_student_absences.date) = 11, 'November',
                                    IF(MONTH(institution_student_absences.date) = 12, 'December', '')))))))))))) month_name
    FROM institution_student_absences
    INNER JOIN academic_periods
    ON academic_periods.id = institution_student_absences.academic_period_id
    WHERE academic_periods.id = $academicPeriodId
    AND institution_student_absences.institution_id = $institutionId
    GROUP BY institution_student_absences.student_id,
    month(institution_student_absences.date))",
    'conditions'=>[
        'absence_data.student_id = students_data.student_id',
        'absence_data.month_name = month_data.month_name'
    ]
    ]; 
 
    $query
        ->select([
            'openemis_no' => 'students_data.openemis_no',
            'student_name' => 'students_data.student_name',
            'institutions_code' => 'students_data.institutions_code',
            'institutions_name'=>'students_data.institutions_name',
            'institution_classes_name' => 'students_data.institution_classes_name',
            'month' => 'month_data.month_name',
            'total_attended_days' => "(20 - IFNULL(absence_data.number_of_absences, 0))",
            'total_absent_days' => "(IFNULL(absence_data.number_of_absences, '0 '))",
            'attendance_rate' => "(CONCAT(ROUND((20 - IFNULL(absence_data.number_of_absences, 0)) / 20, 2) * 100, '%'))",
            ])
        ->from(['students_data' => $subQuery])
        ->where([
            'month_data.month_name' => $monthoption[$month],
        ])
        ->order([
            'students_data.institutions_name',
            'students_data.institution_classes_name',
            'students_data.openemis_no',
            'month_data.month_id'
        ]);
        $query->join($join);
   
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
