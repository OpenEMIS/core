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
		$this->belongsTo('QualificationLevels', ['className' => 'FieldOption.QualificationLevels']);
		$this->belongsTo('QualificationInstitutions', ['className' => 'Staff.QualificationInstitutions']);
		$this->belongsTo('QualificationSpecialisations', ['className' => 'FieldOption.QualificationSpecialisations']);

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
			->select([
				'institution_name' => 'Institutions.name',
				'institution_code' => 'Institutions.code',
				'staff_position_name' => 'StaffPositionTitles.name',
				'staff_type_name' => 'StaffTypes.name'
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
	}
}
