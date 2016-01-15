<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;

class InstitutionStudentsOutOfSchoolTable extends AppTable  {
	public function initialize(array $config) {
		$this->table('security_users');
		parent::initialize($config);

		$this->belongsTo('Genders', ['className' => 'User.Genders']);
		$this->belongsTo('AddressAreas', ['className' => 'Area.AreaAdministratives', 'foreignKey' => 'address_area_id']);
		$this->hasMany('Identities', 		['className' => 'User.Identities',		'foreignKey' => 'security_user_id', 'dependent' => true]);
		$this->addBehavior('Report.ReportList');
		$this->addBehavior('Excel', [
			'excludes' => ['super_admin', 'is_student', 'is_staff', 'is_guardian', 'photo_name', 'date_of_death', 'last_login', 'status', 'username'], 
			'pages' => false
		]);
	}

	public function onExcelBeforeQuery (Event $event, ArrayObject $settings, Query $query) {
		$query->join([
			'InstitutionStudent' => [
				'type' => 'left',
				'table' => 'institution_students',
				'conditions' => [
					$this->aliasField($this->primaryKey()) . ' = InstitutionStudent.student_id'
				],
			],
			'InstitutionStudentFilter' => [
				'type' => 'left',
				'table' => 'institution_students',
				'conditions' => [
					$this->aliasField($this->primaryKey()) . ' = InstitutionStudentFilter.student_id',
					'InstitutionStudentFilter.student_status_id = 1'
				],
			],
			'StudentStatus' => [
				'type' => 'left',
				'table' => 'student_statuses',
				'conditions' => [
					'StudentStatus.id = InstitutionStudent.student_status_id'
				]
			],
			'AcademicPeriod' => [
				'type' => 'left',
				'table' => 'academic_periods',
				'conditions' => [
					'AcademicPeriod.id = InstitutionStudent.academic_period_id'
				]
			],
			'EducationGrade' => [
				'type' => 'left',
				'table' => 'education_grades',
				'conditions' => [
					'EducationGrade.id = InstitutionStudent.education_grade_id'
				]
			]

		]);

		$query->leftJoin(
			['Identities' => 'user_identities'],
			[
				'Identities.security_user_id = '.$this->aliasField($this->primaryKey()),
				'Identities.identity_type_id' => $settings['identity']->id
			]
		);

		$query->select(['EndDate' => 'InstitutionStudent.end_date', 'StudentStatus' => 'StudentStatus.name', 'AcademicPeriod' => 'AcademicPeriod.name', 'EducationGrade' => 'EducationGrade.name', 'IdentitiesNumber' => 'Identities.number']);
		$query->autoFields('true');

		$query->where([$this->aliasField('is_student') => 1]);

		// omit all records who are 'enrolled'
		$query->where([
			'OR' => [
					'InstitutionStudent.student_status_id != ' => 1,
					'InstitutionStudent.student_status_id IS NULL'
				]
			]);

		// omit all students in current records who has 'enrolled'
		$query->where([
			'InstitutionStudentFilter.student_status_id IS NULL'
		]);

		$query->group([$this->aliasField($this->primaryKey())]);
		$query->order(['InstitutionStudent.end_date desc']);
	}

	public function onExcelRenderAge(Event $event, Entity $entity, $attr) {
		$age = '';
		if ($entity->has('date_of_birth')) {
			if (!empty($entity->date_of_birth)) {
				$yearOfBirth = $entity->date_of_birth->format('Y');
				$age = date("Y")-$yearOfBirth;
			}
		}
	
		return $age;
	}

	public function onExcelGetStudentStatus(Event $event, Entity $entity) {
		return (!$entity->has('StudentStatus') || empty($entity->StudentStatus))? '<Not In School>': $entity->StudentStatus;
	}


	public function onExcelBeforeStart (Event $event, ArrayObject $settings, ArrayObject $sheets) {
		$sheets[] = [
			'name' => __('Students Out of School'),
			'table' => $this,
			'query' => $this->find(),
			'orientation' => 'landscape'
		];
	}

	public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields) {
		$IdentityType = TableRegistry::get('FieldOption.IdentityTypes');
		$identity = $IdentityType
		   ->find()
		   ->contain(['FieldOptions'])
		   ->where([
		   		'FieldOptions.code' => 'IdentityTypes'
		   ])
		   ->order(['IdentityTypes.default DESC'])
		   ->first();

		$settings['identity'] = $identity;

		$extraField[] = [
			'key' => 'AcademicPeriod.name',
			'field' => 'AcademicPeriod',
			'type' => 'string',
			'label' => 'Academic Period',
		];

		$extraField[] = [
			'key' => 'StudentStatus.name',
			'field' => 'StudentStatus',
			'type' => 'string',
			'label' => 'Student Status',
		];

	
		$extraField[] = [
			'key' => 'EducationGrade.name',
			'field' => 'EducationGrade',
			'type' => 'string',
			'label' => 'Education Grade',
		];

		$extraField[] = [
			'key' => 'InstitutionStudent.end_date',
			'field' => 'EndDate',
			'type' => 'string',
			'label' => 'End Date',
		];

		$extraField[] = [
			'key' => 'Age',
			'field' => 'Age',
			'type' => 'Age',
			'label' => 'Age',
		];

		$extraField[] = [
			'key' => 'Identities.number',
			'field' => 'IdentitiesNumber',
			'type' => 'string',
			'label' => __($identity->name)
		];

		$newFields = array_merge($extraField, $fields->getArrayCopy());
		$fields->exchangeArray($newFields);
	}
}