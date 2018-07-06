<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\TableRegistry;
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
        $this->belongsTo('Users', ['className' => 'Security.Users', 'foreignKey' => 'student_id']);
        $this->belongsTo('StudentStatuses', ['className' => 'Student.StudentStatuses']);
        $this->belongsTo('EducationGrades', ['className' => 'Education.EducationGrades']);
        $this->belongsTo('Institutions', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
        $this->belongsTo('AcademicPeriods', ['className' => 'AcademicPeriod.AcademicPeriods']);
        $this->belongsTo('PreviousInstitutionStudents', ['className' => 'Institution.Students', 'foreignKey' => 'previous_institution_student_id']);
        
		// Behaviors
		$this->addBehavior('Excel', [
			'excludes' => [
                'start_date', 'start_year', 'end_date', 'end_year', 'previous_institution_student_id'
            ],
			'pages' => false,
            'autoFields' => false
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
		if (!is_null($entity->academic_period_start_year) && !is_null($entity->date_of_birth)) {
			$startYear = $entity->academic_period_start_year;
			$dob = $entity->date_of_birth->format('Y');
    		return $startYear - $dob;
		}
	}

   	public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query)
    {
        $enrolledStatus = $this->StudentStatuses->getIdByCode('CURRENT');

		$query
            ->select([
                $this->aliasField('student_id'),                
                $this->aliasField('student_status_id'),
                $this->aliasField('education_grade_id'),
                $this->aliasField('academic_period_id'),
                $this->aliasField('institution_id'),                
            ])
            ->contain([
                'Users' => [
                    'fields' => [
                        'openemis_no' => 'Users.openemis_no',
                        'Users.first_name',
                        'Users.middle_name',
                        'Users.third_name',
                        'Users.last_name',
                        'Users.preferred_name',
                        'date_of_birth' => 'Users.date_of_birth'
                    ]
                ],
                'StudentStatuses' => [
                    'fields' => [
                        'name'
                    ]
                ],
                'EducationGrades' => [
                    'fields' => [
                        'education_name' => 'EducationGrades.name',
                        'education_grade_age' => 'EducationGrades.admission_age'
                    ]
                ],
                'Institutions' => [
                    'fields' => [
                        'institutions_name' => 'Institutions.name'
                    ]
                ],
                'AcademicPeriods' => [
                    'fields' => [
                        'name',
                        'academic_period_start_year' => 'AcademicPeriods.start_year'
                    ]
                ]
            ])   
            ->where([
                $this->aliasField('student_status_id') => $enrolledStatus,
                "`AcademicPeriods`.`start_year` - YEAR(`Users`.`date_of_birth`) <> `EducationGrades`.`admission_age`"
            ]);
    }

    public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields)
    {
        $newFields = [];

        $newFields[] = [
            'key' => 'openemis_no',
            'field' => 'openemis_no',
            'type' => 'string',
            'label' => __('Openemis No')
        ];

        $newFields[] = [
            'key' => 'Users.full_name',
            'field' => 'student_id',
            'type' => 'string',
            'label' => ''
        ];           

        $newFields[] = [
            'key' => 'date_of_birth',
            'field' => 'date_of_birth',
            'type' => 'date',
            'label' => __('Date Of Birth')
        ];   
        
        $newFields[] = [
            'key' => 'SecurityUsers.age',
            'field' => 'age',
            'type' => 'string',            
            'label' => __('Age')
        ];

        $newFields[] = [
            'key' => 'education_name',
            'field' => 'education_name',
            'type' => 'string',
            'label' => __('Education Name')
        ];              

        $newFields[] = [
            'key' => 'institutions_name',
            'field' => 'institutions_name',
            'type' => 'string',
            'label' => __('institutions_name')
        ]; 

        $newFields[] = [
            'key' => 'education_grade_age',
            'field' => 'education_grade_age',
            'type' => 'string',
            'label' => __('Education Grade Age')
        ];         

        $newFields[] = [
            'key' => 'AcademicPeriods.start_year',
            'field' => 'academic_period_start_year',
            'type' => 'string',
            'label' => __('Academic Period Start Year')
        ];  

        $fields->exchangeArray($newFields);    
    }

}
