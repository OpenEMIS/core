<?php
namespace Report\Model\Table;

use ArrayObject;
use DateTime;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;
use Cake\ORM\Table;
use Cake\Utility\Inflector;
use Cake\I18n\Time;
use Cake\Validation\Validator;
use Cake\Datasource\ConnectionManager;
use App\Model\Traits\OptionsTrait;


//POCOR-7211
class EnrollmentOutliersTable extends AppTable  {
	use OptionsTrait;

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
		$requestData = json_decode($settings['process']['params']);
        $academicPeriodId = $requestData->academic_period_id;
		$this->InstitutionStudents = TableRegistry::get('Institutions.InstitutionStudents');
		$academicPeriod = TableRegistry::get('academic_periods');
		$institutions = TableRegistry::get('institutions');
		$this->ConfigItems = TableRegistry::get('Configuration.ConfigItems');
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
		$subquery = $this->ConfigItems
		->find()
		->select(function (Query $query) {
			$minAgeExpr = $query->newExpr()
				->addCase(
					[$query->newExpr()->eq('code', 'report_outlier_min_age')],
					['value'],
					['integer'],
					'integer'
				);
			$maxAgeExpr = $query->newExpr()
				->addCase(
					[$query->newExpr()->eq('code', 'report_outlier_max_age')],
					['value'],
					['integer'],
					'integer'
				);
			$minEnrolmentExpr = $query->newExpr()
				->addCase(
					[$query->newExpr()->eq('code', 'report_outlier_min_student')],
					['value'],
					['integer'],
					'integer'
				);
			$maxEnrolmentExpr = $query->newExpr()
				->addCase(
					[$query->newExpr()->eq('code', 'report_outlier_max_student')],
					['value'],
					['integer'],
					'integer'
				);
	
			return [
				'min_age' => $query->func()->max($minAgeExpr),
				'max_age' => $query->func()->max($maxAgeExpr),
				'min_enrolment' => $query->func()->max($minEnrolmentExpr),
				'max_enrolment' => $query->func()->max($maxEnrolmentExpr),
			];
		})
		->where([
			'code IN' => ['report_outlier_min_age', 'report_outlier_max_age', 'report_outlier_min_student', 'report_outlier_max_student']
		]);
		//POCOR-8247
   		$query->select(['academic_period_name' => 'main_query.academic_period_name',
   			'institution_code' => 'main_query.institution_code',
   			'institution_name' => 'main_query.institution_name',
   			'count_students' => 'main_query.count_students'
   		])->from(['main_query' => $main_query]);
   		$query->join(['subq' => $subquery])->where('main_query.count_students NOT BETWEEN subq.min_enrolment AND subq.max_enrolment');
	}


	public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields)
    {

    	$extraFields = [];
        $extraFields[] = [
            'key' => 'academic_period_name',
            'field' => 'academic_period_name',
            'type' => 'integer',
            'label' => __('Academic Period')
        ];
        $extraFields[] = [
            'key' => 'institution_code',
            'field' => 'institution_code',
            'type' => 'string',
            'label' => __('Institution code')
        ];
        $extraFields[] = [
            'key' => 'institution_name',
            'field' => 'institution_name',
            'type' => 'string',
            'label' => __('Institution Name')
        ];
        $extraFields[] = [
            'key' => 'count_students',
            'field' => 'count_students',
            'type' => 'string',
            'label' => __('count students')
        ];
        

        $fields->exchangeArray($extraFields);
    }


}
