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

		$query
			->select([
				'institution_name' => 'Institution.name',
				'institution_code' => 'Institution.code',
				'staff_position_name' => 'staffPositionTitle.name',
				'staff_type_name' => 'fieldOptionValue.name'
			])
			->innerJoin(
				['institutionStaff' => 'institution_staff'],
					['institutionStaff.staff_id = '.$this->aliasField('staff_id')]
			)
			->innerJoin(
				['Institution' => 'institutions'],
					['Institution.id = institutionStaff.institution_id']
			)
			->innerJoin(
				['institutionPosition' => 'institution_positions'],
					['institutionPosition.id = institutionStaff.institution_position_id']
			)
			->innerJoin(
				['staffPositionTitle' => 'staff_position_titles'],
					['staffPositionTitle.id = institutionPosition.staff_position_title_id']
			)
			->innerJoin(
				['fieldOptionValue' => 'field_option_values'],
					['institutionStaff.staff_type_id = fieldOptionValue.id']
			);

		// if (!$superAdmin) {
		// 	$query->find('ByAccess', ['user_id' => $userId, 'institution_field_alias' => $this->aliasField('institution_id')]);
		// }
	}

	public function onExcelUpdateFields(Event $event, ArrayObject $settings, $fields) {
		$newArray = [];
		$newArray[] = [
			'key' => 'Institution.name',
			'field' => 'institution_name',
			'type' => 'string',
			'label' => __('Institution Name')
		];
		$newArray[] = [
			'key' => 'Institution.code',
			'field' => 'institution_code',
			'type' => 'string',
			'label' => __('Institution Code')
		];
		$newArray[] = [
			'key' => 'staffPositionTitle.name',
			'field' => 'staff_position_name',
			'type' => 'string',
			'label' => __('Position')
		];
		$newArray[] = [
			'key' => 'fieldOptionValue.name',
			'field' => 'staff_type_name',
			'type' => 'string',
			'label' => __('Staff Type')
		];
		
		$newFields = array_merge($newArray, $fields->getArrayCopy());
		$fields->exchangeArray($newFields);
	}
}
