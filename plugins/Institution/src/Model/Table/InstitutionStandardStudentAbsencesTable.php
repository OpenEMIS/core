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
use Cake\Datasource\ConnectionManager;
/**
 * Get the Student Absences details in excel file 
 * @ticket POCOR-6631
 */
class InstitutionStandardStudentAbsencesTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('institution_student_absence_days');
        parent::initialize($config);
        $this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' =>'student_id']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' =>'institution_id']);
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
        $academic_period = TableRegistry::get('AcademicPeriod.AcademicPeriods');
        $getyear = $academic_period->find('all')
                   ->select(['end_year','name'=>$academic_period->aliasField('start_year')]) //POCOR-6854
                   ->where(['id'=>$academicPeriodId])
                   ->limit(1);
        foreach($getyear->toArray() as $val) {
            $year  = $val['name'];
            $yearSecond  = $val['end_year']; //POCOR-6854
        }
        $institutionId = $requestData->institution_id;
        $gradeId = $requestData->education_grade_id;
        $classId = $requestData->institution_class_id;
        $month = $requestData->month;
        $where = [];
        if ($gradeId != -1) {
            $where['education_grades.id'] = $gradeId;
        }
        if ($classId != 0) {
            $where['institution_classes.id'] = $classId;
        }
       // $where[$this->aliasField('academic_period_id')] = $academicPeriodId;
        //$where[$this->aliasField('institution_id')] = $institutionId;
        $date =  '"'.$year.'-'.$month.'%"';
        $datelike =  '"'.$year.'-'.$month.'"';
        $dateSecond =  '"'.$yearSecond.'-'.$month.'%"';  //POCOR-6854
        $yearSecond =  $yearSecond;  //POCOR-6854
        $join = [];
        $subQuery = "(SELECT institution_student_absence_days.institution_id
                          ,institution_student_absence_days.student_id
                          ,institution_student_absence_days.start_date
                          ,institution_student_absence_days.end_date
                          ,institution_student_absence_days.absent_days existing_number_of_days
                          ,DATEDIFF(institution_student_absence_days.end_date, institution_student_absence_days.start_date) + 1 AS new_number_of_days
                          ,LEAST(LAST_DAY(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-01')), institution_student_absence_days.end_date) - GREATEST(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-01'), institution_student_absence_days.start_date) + 1 AS number_of_days_based_on_selected_month
                          ,IF(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-01') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_1
                          ,IF(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-02') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_2
                          ,IF(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-03') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_3
                          ,IF(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-04') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_4
                          ,IF(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-05') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_5
                          ,IF(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-06') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_6
                          ,IF(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-07') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_7
                          ,IF(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-08') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_8
                          ,IF(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-09') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_9
                          ,IF(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-10') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_10
                          ,IF(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-11') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_11
                          ,IF(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-12') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_12
                          ,IF(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-13') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_13
                          ,IF(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-14') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_14
                          ,IF(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-15') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_15
                          ,IF(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-16') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_16
                          ,IF(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-17') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_17
                          ,IF(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-18') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_18
                          ,IF(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-19') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_19
                          ,IF(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-20') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_20
                          ,IF(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-21') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_21
                          ,IF(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-22') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_22
                          ,IF(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-23') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_23
                          ,IF(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-24') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_24
                          ,IF(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-25') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_25
                          ,IF(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-26') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_26
                          ,IF(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-27') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_27
                          ,IF(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-28') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_28
                          ,IF(DAY(LAST_DAY(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-01'))) >= 29 AND CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-29') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_29
                          ,IF(DAY(LAST_DAY(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-01'))) >= 30 AND CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-30') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_30
                          ,IF(DAY(LAST_DAY(CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-01'))) = 31 AND CONCAT(year_id, '-', LPAD(month_id, 2, '0'), '-31') BETWEEN institution_student_absence_days.start_date AND institution_student_absence_days.end_date, 1, '') AS day_31
                        FROM institution_student_absence_days, (SELECT @month_id := $month as month_id, @year_id := $yearSecond AS year_id) AS variables
                        WHERE MONTH(institution_student_absence_days.start_date) <= $month
                        AND YEAR(institution_student_absence_days.start_date) <= $yearSecond
                        AND MONTH(institution_student_absence_days.end_date) >= $month
                        AND YEAR(institution_student_absence_days.end_date) >= $yearSecond
                        AND institution_student_absence_days.absence_type_id != 3
                        AND institution_student_absence_days.institution_id = $institutionId)";
    

    $query
    ->select(['name' => 'academic_periods.name',
            'institution_code' =>'institutions.code',
            'institution_name' =>'institutions.name',
            'education_grade_name' => 'MAX(education_grades.name)',
            'class_name' => 'IFNULL(MAX(institution_classes.name), \'\')',
            'security_users.openemis_no',
            'identity_number' => 'IFNULL(StudentIdentities.identity_number, \'\')',
            'student_name' => "CONCAT_WS(' ', security_users.first_name, security_users.middle_name, security_users.third_name, security_users.last_name)",
            'total_absent_days' => "MAX(subq.day_1) + MAX(subq.day_2) + MAX(subq.day_3) + MAX(subq.day_4) + MAX(subq.day_5) + MAX(subq.day_6) + MAX(subq.day_7) + MAX(subq.day_8) + MAX(subq.day_9) + MAX(subq.day_10) + MAX(subq.day_11) + MAX(subq.day_12) + MAX(subq.day_13) + MAX(subq.day_14) + MAX(subq.day_15) + MAX(subq.day_16) + MAX(subq.day_17) + MAX(subq.day_18) + MAX(subq.day_19) + MAX(subq.day_20) + MAX(subq.day_21) + MAX(subq.day_22) + MAX(subq.day_23) + MAX(subq.day_24) + MAX(subq.day_25) + MAX(subq.day_26) + MAX(subq.day_27) + MAX(subq.day_28) + MAX(subq.day_29) + MAX(subq.day_30) + MAX(subq.day_31)",
            'day_1' => 'MAX(subq.day_1)',
            'day_2' => 'MAX(subq.day_2)',
            'day_3' => 'MAX(subq.day_3)',
            'day_4' => 'MAX(subq.day_4)',
            'day_5' => 'MAX(subq.day_5)',
            'day_6' => 'MAX(subq.day_6)',
            'day_7' => 'MAX(subq.day_7)',
            'day_8' => 'MAX(subq.day_8)',
            'day_9' => 'MAX(subq.day_9)',
            'day_10' => 'MAX(subq.day_10)',
            'day_11' => 'MAX(subq.day_11)',
            'day_12' => 'MAX(subq.day_12)',
            'day_13' => 'MAX(subq.day_13)',
            'day_14' => 'MAX(subq.day_14)',
            'day_15' => 'MAX(subq.day_15)',
            'day_16' => 'MAX(subq.day_16)',
            'day_17' => 'MAX(subq.day_17)',
            'day_18' => 'MAX(subq.day_18)',
            'day_19' => 'MAX(subq.day_19)',
            'day_20' => 'MAX(subq.day_20)',
            'day_21' => 'MAX(subq.day_21)',
            'day_22' => 'MAX(subq.day_22)',
            'day_23' => 'MAX(subq.day_23)',
            'day_24' => 'MAX(subq.day_24)',
            'day_25' => 'MAX(subq.day_25)',
            'day_26' => 'MAX(subq.day_26)',
            'day_27' => 'MAX(subq.day_27)',
            'day_28' => 'MAX(subq.day_28)',
            'day_29' => 'MAX(subq.day_29)',
            'day_30' => 'MAX(subq.day_30)',
            'day_31' => 'MAX(subq.day_31)',
        ])
        ->from(['subq'=>$subQuery]);


        $query->innerJoin(
                ['academic_periods' => 'academic_periods'],
                ['academic_periods.name' .'='. $yearSecond]
            )
        ->innerJoin(
                ['institutions' => 'institutions'],
                ['institutions.id = subq.institution_id']
            )
        ->innerJoin(
                ['security_users' => 'security_users'],
                ['security_users.id = subq.student_id']
            )
        
        ->innerJoin(
            ['institution_students' => 'institution_students'],
            [
                'institution_students.student_id = security_users.id',
                'institution_students.institution_id = institutions.id',
                'institution_students.academic_period_id = academic_periods.id',
                'IF((CURRENT_DATE >= academic_periods.start_date AND CURRENT_DATE <= academic_periods.end_date), institution_students.student_status_id = 1, institution_students.student_status_id IN (1, 7, 6, 8))'
            ]
        )
        ->join([
        'table' => 'education_grades',
        'conditions' => [
            'education_grades.id = institution_students.education_grade_id'
        ]
        ])
        ->leftJoin(
            ['institution_class_students' => 'institution_class_students'],
            [
                'institution_class_students.student_status_id = institution_students.student_status_id',
                'institution_class_students.student_id = institution_students.student_id',
                'institution_class_students.education_grade_id = institution_students.education_grade_id',
                'institution_class_students.institution_id = institution_students.institution_id',
                'institution_class_students.academic_period_id = institution_students.academic_period_id'
            ]
        )
        ->leftJoin(
            ['institution_classes' => 'institution_classes'],
            [
                'institution_classes.id = institution_class_students.institution_class_id'
            ]
        )
        ->leftJoin(
            ['StudentIdentities' => '(SELECT user_identities.security_user_id, GROUP_CONCAT(identity_types.name) as identity_type, GROUP_CONCAT(user_identities.number) as identity_number FROM user_identities INNER JOIN identity_types ON identity_types.id = user_identities.identity_type_id WHERE identity_types.default = 1 GROUP BY user_identities.security_user_id)'],
            ['StudentIdentities.security_user_id = security_users.id'],
        )->where($where)
        ->group(['subq.student_id']);

    }

    /**
     * Generate the all Header for sheet
     */
    public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {
        $i_max = 31;
        $newFields = [];
        $newFields[] = [
            'key'   => 'name',
            'field' => 'name',
            'type'  => 'integer',
            'label' => __('Academic Period'),
        ];
        $newFields[] = [
            'key'   => 'institution_code',
            'field' => 'institution_code',
            'type'  => 'string',
            'label' => __('School Code'),
        ];
        $newFields[] = [
            'key'   => 'institution_name',
            'field' => 'institution_name',
            'type'  => 'string',
            'label' => __('School Name'),
        ];
        $newFields[] = [
            'key'   => 'education_grade_name',
            'field' => 'education_grade_name',
            'type'  => 'string',
            'label' => __('Grade'),
        ];
        $newFields[] = [
            'key'   => 'class_name',
            'field' => 'class_name',
            'type'  => 'string',
            'label' => __('Class'),
        ];
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
            'key' => 'identity_number',
            'field' => 'identity_number',
            'type' => 'string',
            'label' => __('Identity Number')
        ];
        $newFields[] = [
            'key'   => 'total_absent_days',
            'field' => 'total_absent_days',
            'type'  => 'integer',
            'label' => __('Total absences'),
        ];

        for( $i=1; $i<=$i_max; $i++ ) //POCOR-5181 
        { 
            $newFields[]=[
            'key'   => 'day_'.$i,
            'field' => 'day_'.$i,
            'type'  => 'string',
            'label' => __('day_'.$i),
            ];
        }
        

        $fields->exchangeArray($newFields);
    }

}
