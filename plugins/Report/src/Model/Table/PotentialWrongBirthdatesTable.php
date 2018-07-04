<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;

class PotentialWrongBirthdatesTable extends AppTable  
{
	public function initialize(array $config) 
	{
		$this->table('institution_students');
		parent::initialize($config);
        
        // Associations
		$this->belongsTo('SecurityUsers', ['className' => 'User.Users', 'foreignKey' => 'student_id']);
		$this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
		$this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
		$this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        
		// Behaviors
		$this->addBehavior('Excel', [
			'excludes' => ['is_student', 'is_staff', 'is_guardian', 'external_reference', 'super_admin', 'status', 'last_login', 'photo_name', 'photo_content', 'preferred_language'],
			'pages' => false
		]);
		$this->addBehavior('Report.ReportList');
	}

	public function beforeAction(Event $event) 
	{
		$this->fields = [];
		$this->ControllerAction->field('feature');
		$this->ControllerAction->field('format');
	}

	public function onUpdateFieldFeature(Event $event, array $attr, $action, Request $request) 
	{
		$attr['options'] = $this->controller->getFeatureOptions($this->alias());
		return $attr;
	}

	public function onExcelGetAge(Event $event, Entity $entity)
	{
		// Calculate the age
		if (!is_null($entity->start_year) && !is_null($entity->date_of_birth)) {
			$startYear = $entity->start_year;
			$dob = $entity->date_of_birth->format('Y');
    		return $startYear - $dob;
		}
	}

   	public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
		$query
			->select([
				'openemis_no' => 'SecurityUsers.openemis_no',
				'first_name' => 'SecurityUsers.first_name',
				'last_name' => 'SecurityUsers.last_name',
				'date_of_birth' => 'SecurityUsers.date_of_birth',
				'grades_name' => 'EducationGrades.name',
				'grades_admission_age' => 'EducationGrades.admission_age',
				'institutions_name' => 'Institutions.name',
				'start_year' => 'AcademicPeriods.start_year'
            ])
			->innerJoinWith('SecurityUsers')	
			->innerJoinWith('AcademicPeriods')
			->innerJoinWith('EducationGrades')
            ->innerJoinWith('Institutions')
            ->innerJoinWith('StudentStatuses')
            ->where([
            	'StudentStatuses.code' => 'CURRENT',
            	"`AcademicPeriods`.`start_year` - YEAR(`SecurityUsers`.`date_of_birth`) <> `EducationGrades`.`admission_age`"
            ]);
    }

   	public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {

        $Fields = [];
        $Fields[] = [
            'key' => 'SecurityUsers.openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => __('Openemis No')
        ];

        $Fields[] = [
            'key' => 'SecurityUsers.first_name',
            'field' => 'first_name',
            'type' => 'string',
            'label' => __('First Name')
        ];

        $Fields[] = [
            'key' => 'SecurityUsers.last_name',
            'field' => 'last_name',
            'type' => 'string',
            'label' => __('Last Name')
        ];
        $Fields[] = [
            'key' => 'SecurityUsers.date_of_birth',
            'field' => 'date_of_birth',
            'type' => 'date',
            'label' => __('Date Of Birth')
        ];

        $Fields[] = [
            'key' => 'SecurityUsers.age',
            'field' => 'age',
            'type' => 'string',            
            'label' => __('Age')
        ];

        $Fields[] = [
            'key' => 'EducationGrades.name',
            'field' => 'grades_name',
            'type' => 'string',
            'label' => __('Grades Name')
        ];   
   
        $Fields[] = [
            'key' => 'EducationGrades.admission_age',
            'field' => 'grades_admission_age',
            'type' => 'string',
            'label' => __('Grades Admission Age')
        ];      

        $Fields[] = [
            'key' => 'Institutions.name',
            'field' => 'institutions_name',
            'type' => 'string',
            'label' => __('Institutions Name')
        ];                 
         $fields->exchangeArray($Fields);
    }    
}
