<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\Log\Log;
use Cake\ORM\Entity;
use DateTime;


//POCOR-7211
class EnrollmentOutliersTable extends AppTable  {
	public function initialize(array $config) {
		$this->table('institution_students');
		parent::initialize($config);
		
		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' => 'academic_period_id']);

		$this->addBehavior('Excel', [
            'autoFields' => false
        ]);
		$this->addBehavior('Report.ReportList');
	}

	public function beforeAction(Event $event) {
		$this->fields = [];
		$this->ControllerAction->field('feature');
		$this->ControllerAction->field('format');
	}

	public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request) {
		$attr['options'] = $this->controller->getFeatureOptions($this->alias());
		return $attr;
	}

	public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
	{
		//$requestData = json_decode($settings['process']['params']);
        //$academicPeriodId = $requestData->academic_period_id;
        $academicPeriodId = 32;
		//$conditions = [];
		//$conditionQuery = [];
		$join = [];
        $institutionStudents = TableRegistry::get('institution_students');
        $academicPeriod = TableRegistry::get('academic_periods');
        $institutions = TableRegistry::get('institutions');
       // $conditions[$institutionStudents->aliasField('academic_period_id')] = $academicPeriodId;
        /*$conditions["IF((CURRENT_DATE >= academic_periods.start_date AND CURRENT_DATE <= academic_periods.end_date), 
        institution_students.student_status_id = 1, institution_students.student_status_id IN (1, 7, 6, 8))"];*/
        
        //$conditionQuery['institution_students.count_students NOT BETWEEN subq.min_enrolment AND subq.max_enrolment'];
        $main_query  = "(SELECT academic_periods.name academic_period_name 	
				        ,institutions.code institution_code			
				        ,institutions.name institution_name			
				        ,COUNT(DISTINCT(institution_students.student_id)) count_students
				    FROM institution_students
				    INNER JOIN academic_periods
				    ON academic_periods.id = institution_students.academic_period_id
				    INNER JOIN institutions
				    ON institutions.id = institution_students.institution_id
				    WHERE academic_periods.id = $academicPeriodId
				    AND IF((CURRENT_DATE >= academic_periods.start_date AND CURRENT_DATE <= academic_periods.end_date), institution_students.student_status_id = 1, institution_students.student_status_id IN (1, 7, 6, 8))
				    GROUP BY institutions.id)";
        $join['subq'] = [
             'type' => 'cross',
             'table' => "(SELECT @min_age := MAX(CASE WHEN config_items.code = 'report_outlier_min_age' THEN 					config_items.value ELSE 0 END) min_age
       						 ,@max_age := MAX(CASE WHEN config_items.code = 'report_outlier_max_age' THEN config_items.value ELSE 0 END) max_age
        					,@min_enrolment := MAX(CASE WHEN config_items.code = 'report_outlier_min_student' THEN config_items.value ELSE 0 END) min_enrolment
        					,@max_enrolment := MAX(CASE WHEN config_items.code = 'report_outlier_max_student' THEN config_items.value ELSE 0 END) max_enrolment
   							 FROM config_items
   							 WHERE config_items.code IN ('report_outlier_min_age', 'report_outlier_max_age', 'report_outlier_min_student', 'report_outlier_max_student')",
   						];
		$query->from(['students_data' => $main_query]);
                      
        $query->join($join)->where(['institution_students.count_students NOT BETWEEN subq.min_enrolment AND subq.max_enrolment']);
        print_r($query->Sql());die('kjkj');
	}

	public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
    	$extraFields = [];
        $extraFields[] = [
            'key' => 'HealthReports.code_name',
            'field' => 'code_name',
            'type' => 'string',
            'label' => __('Code')
        ];

        $fields->exchangeArray($extraFields);
    }


}
