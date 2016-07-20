<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\ORM\TableRegistry;

class InstitutionStudentsTable extends AppTable  {
	public function initialize(array $config) {
		$this->table('institution_students');
		parent::initialize($config);

		$this->belongsTo('Users',			['className' => 'Security.Users', 'foreignKey' => 'student_id']);
		$this->belongsTo('StudentStatuses',	['className' => 'Student.StudentStatuses']);
		$this->belongsTo('EducationGrades',	['className' => 'Education.EducationGrades']);
		$this->belongsTo('Institutions',	['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
		$this->belongsTo('AcademicPeriods',	['className' => 'AcademicPeriod.AcademicPeriods']);
		$this->addBehavior('Report.ReportList');
		$this->addBehavior('Excel', [
			'excludes' => ['start_year', 'end_year'], 
			'pages' => false
		]);
	}

	public function onExcelBeforeStart (Event $event, ArrayObject $settings, ArrayObject $sheets) {
		$sheets[] = [
			'name' => $this->alias(),
			'table' => $this,
			'query' => $this->find(),
			'orientation' => 'landscape'
		];
	}

	public function onExcelBeforeWrite(Event $event, ArrayObject $settings, $rowProcessed, $percentCount) {
		if (empty($settings['entity']->user)) {
			$entity = $settings['entity'];
			return $this->delete($entity);
		}
	}

	public function onExcelBeforeQuery (Event $event, ArrayObject $settings, Query $query) {
		// Setting request data and modifying fetch condition
		$requestData = json_decode($settings['process']['params']);
		$academicPeriodId = $requestData->academic_period_id;
		$statusId = $requestData->status;

		if ($academicPeriodId!=0) {
			$query->where([
				$this->aliasField('academic_period_id') => $academicPeriodId
			]);
		}

		if ($statusId!=0) {
			$query->where([
				$this->aliasField('student_status_id') => $statusId
			]);
		}



		$query->leftJoin(
			['Identities' => 'user_identities'],
			[
				'Identities.security_user_id = '.$this->aliasField('student_id'),
				'Identities.identity_type_id' => $settings['identity']->id
			]
		);

		$query
			->contain(['Users.Genders', 'Institutions.Areas', 'Institutions.Types'])
			->select(['openemis_no' => 'Users.openemis_no', 'number' => 'Identities.number', 'code' => 'Institutions.code', 'gender_name' => 'Genders.name', 'area_name' => 'Areas.name', 'area_code' => 'Areas.code', 'institution_type' => 'Types.name']);
	}

	public function onExcelRenderAge(Event $event, Entity $entity, $attr) {
		$age = '';
		if ($entity->has('user')) {
			if ($entity->user->has('date_of_birth')) {
				if (!empty($entity->user->date_of_birth)) {
					$yearOfBirth = $entity->user->date_of_birth->format('Y');
					$age = date("Y")-$yearOfBirth;
				}
			}
		}
		return $age;
	}

	public function onExcelUpdateFields(Event $event, ArrayObject $settings, ArrayObject $fields) {
		$IdentityType = TableRegistry::get('FieldOption.IdentityTypes');
		$identity = $IdentityType->getDefaultEntity();

		$settings['identity'] = $identity;

		// To update to this code when upgrade server to PHP 5.5 and above
		// unset($fields[array_search('institution_id', array_column($fields, 'field'))]);

		foreach ($fields as $key => $field) {
			if ($field['field'] == 'institution_id') {
				unset($fields[$key]);
				break;
			}
		}
		
		$extraField[] = [
			'key' => 'Institutions.code',
			'field' => 'code',
			'type' => 'string',
			'label' => '',
		];

		$extraField[] = [
			'key' => 'Students.institution_id',
			'field' => 'institution_id',
			'type' => 'integer',
			'label' => '',
		];

		$extraField[] = [
			'key' => 'Institutions.institution_type_id',
			'field' => 'institution_type',
			'type' => 'integer',
			'label' => '',
		];

		$extraField[] = [
			'key' => 'Users.openemis_no',
			'field' => 'openemis_no',
			'type' => 'string',
			'label' => ''
		];

		$extraField[] = [
			'key' => 'Identities.number',
			'field' => 'number',
			'type' => 'string',
			'label' => __($identity->name)
		];

		$extraField[] = [
			'key' => 'Users.gender_id',
			'field' => 'gender_name',
			'type' => 'string',
			'label' => ''
		];

		$extraField[] = [
			'key' => 'Institutions.area_name',
			'field' => 'area_name',
			'type' => 'string',
			'label' => ''
		];

		$extraField[] = [
			'key' => 'Institutions.area_code',
			'field' => 'area_code',
			'type' => 'string',
			'label' => ''
		];

		$extraField[] = [
			'key' => 'Age',
			'field' => 'Age',
			'type' => 'Age',
			'label' => 'Age',
		];

		$newFields = array_merge($extraField, $fields->getArrayCopy());
		$fields->exchangeArray($newFields);
	}
}