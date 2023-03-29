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
		  $query
		  ->contain(['Institutions'])
		  ->group([$this->aliasField('institution_id'), $this->aliasField('Institutions.name')])->first();
		  $query
        ->where([
            $this->aliasField('academic_period_id') => 32
        ]);
        $query->formatResults(function (\Cake\Collection\CollectionInterface $results) use($i) {
            return $results->map(function ($row) use($i){
                $academicPeriodId = 32;
				
				$institutionStudents = TableRegistry::get('institution_students');
				$academicPeriod = TableRegistry::get('academic_periods');
				$institutions = TableRegistry::get('institutions');
				$connection = ConnectionManager::get('default');
				$statement = $connection->prepare("SELECT main_query.academic_period_name 
			,main_query.institution_code 
			,main_query.institution_name 
			,main_query.count_students 
								FROM
								(
									SELECT academic_periods.name academic_period_name 	
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
									GROUP BY institutions.id ,institution_students.academic_period_id
								) main_query
								CROSS JOIN
								(
									SELECT @min_age := MAX(CASE WHEN config_items.code = 'report_outlier_min_age' THEN config_items.value ELSE 0 END) min_age
										,@max_age := MAX(CASE WHEN config_items.code = 'report_outlier_max_age' THEN config_items.value ELSE 0 END) max_age
										,@min_enrolment := MAX(CASE WHEN config_items.code = 'report_outlier_min_student' THEN config_items.value ELSE 0 END) min_enrolment
										,@max_enrolment := MAX(CASE WHEN config_items.code = 'report_outlier_max_student' THEN config_items.value ELSE 0 END) max_enrolment
									FROM config_items
									WHERE config_items.code IN ('report_outlier_min_age', 'report_outlier_max_age', 
										'report_outlier_min_student', 'report_outlier_max_student')
								) subq
								WHERE main_query.count_students NOT BETWEEN subq.min_enrolment AND subq.max_enrolment");
			$statement->execute();

			$list =  $statement->fetchAll(\PDO::FETCH_ASSOC);
			foreach ($list as  $value) {
				$row['academic_period_name'] = $value['academic_period_name'];
				$row['institution_code'] = $value['institution_code'];
				$row['institution_name'] = $value['institution_name'];
				$row['count_students'] = $value['count_students'];
			}
			return $row;
            });
            
        }); 
        
	}

	/*public function onExcelGetStatus(Event $event, Entity $entity)
    {
    }*/


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
