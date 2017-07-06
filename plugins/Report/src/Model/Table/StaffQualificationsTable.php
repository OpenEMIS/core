<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\Event\Event;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\Network\Request;
use App\Model\Table\AppTable;

class StaffQualificationsTable extends AppTable  {
	public function initialize(array $config) {
		$this->table('staff_qualifications');
		parent::initialize($config);

		$this->belongsTo('Users', ['className' => 'User.Users', 'foreignKey' => 'staff_id']);
		$this->belongsTo('QualificationTitles', 	['className' => 'FieldOption.QualificationTitles']);
		$this->belongsTo('QualificationCountries', 	['className' => 'FieldOption.Countries', 'foreignKey' => 'qualification_country_id']);
		$this->belongsTo('FieldOfStudies', ['className' => 'Education.EducationFieldOfStudies', 'foreignKey' => 'education_field_of_study_id']);

		$this->belongsToMany('EducationSubjects', [
            'className' => 'Education.EducationSubjects',
            'joinTable' => 'staff_qualifications_subjects',
            'foreignKey' => 'staff_qualification_id',
            'targetForeignKey' => 'education_subject_id',
            'through' => 'Staff.QualificationsSubjects',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);

		$this->addBehavior('Excel', [
			'excludes' => [
				'file_name'
			]
		]);
		$this->addBehavior('Report.InstitutionSecurity');
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

	public function onExcelBeforeQuery(Event $event, ArrayObject $settings, Query $query) {

		$requestData = json_decode($settings['process']['params']);

		$userId = $requestData->user_id;
		$superAdmin = $requestData->super_admin;

		$query
			->contain(['QualificationTitles.QualificationLevels', 'FieldOfStudies'])
			->select([
				'institution_name' => 'Institutions.name',
				'institution_code' => 'Institutions.code',
				'staff_position_name' => 'StaffPositionTitles.name',
				'staff_type_name' => 'StaffTypes.name',
				'qualification_level' => 'QualificationLevels.name',
				'field_of_study_name' => 'FieldOfStudies.name'
			])
			->innerJoin(
				['InstitutionStaff' => 'institution_staff'],
					['InstitutionStaff.staff_id = '.$this->aliasField('staff_id')]
			)
			->innerJoin(
				['Institutions' => 'institutions'],
					['Institutions.id = InstitutionStaff.institution_id']
			)
			->innerJoin(
				['InstitutionPositions' => 'institution_positions'],
					['InstitutionPositions.id = InstitutionStaff.institution_position_id']
			)
			->innerJoin(
				['StaffPositionTitles' => 'staff_position_titles'],
					['StaffPositionTitles.id = InstitutionPositions.staff_position_title_id']
			)
			->innerJoin(
				['StaffTypes' => 'staff_types'],
					['InstitutionStaff.staff_type_id = StaffTypes.id']
			);

		if (!$superAdmin) {
			$query->find('ByAccess', ['user_id' => $userId, 'institution_field_alias' => 'Institutions.id']);
		}
	}

	public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields) {
		$newArray = [];
		$newArray[] = [
			'key' => 'Institutions.name',
			'field' => 'institution_name',
			'type' => 'string',
			'label' => __('Institution Name')
		];
		$newArray[] = [
			'key' => 'Institutions.code',
			'field' => 'institution_code',
			'type' => 'string',
			'label' => __('Institution Code')
		];
		$newArray[] = [
			'key' => 'StaffPositionTitles.name',
			'field' => 'staff_position_name',
			'type' => 'string',
			'label' => __('Position')
		];
		$newArray[] = [
			'key' => 'StaffTypes.name',
			'field' => 'staff_type_name',
			'type' => 'string',
			'label' => __('Staff Type')
		];

		$newFields = array_merge($newArray, $fields->getArrayCopy());
		$fields->exchangeArray($newFields);

		$newFields = [];
		foreach ($fields as $key => $value) {
			$newFields[] = $value;
			if ($value['field'] == 'qualification_title_id') {
				$newFields[] = [
					'key' => 'QualificationLevels.name',
					'field' => 'qualification_level',
					'type' => 'string',
					'label' => __('Qualification Level')
				];
			} else if ($value['field'] == 'education_field_of_study_id') {
				$newFields[] = [
					'key' => 'FieldOfStudies.name',
					'field' => 'field_of_study_name',
					'type' => 'string',
					'label' => __('Education Field Of Study')
				];
			}
		}

		$fields->exchangeArray($newFields);
	}
}
