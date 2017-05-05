<?php
namespace Infrastructure\Model\Table;

use CustomField\Model\Table\CustomFieldValuesTable;

class RoomCustomFieldValuesTable extends CustomFieldValuesTable {
	protected $extra = ['scope' => 'infrastructure_custom_field_id'];

	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'Infrastructure.RoomCustomFields', 'foreignKey' => 'infrastructure_custom_field_id']);
		$this->belongsTo('CustomRecords', ['className' => 'Institution.InstitutionRooms', 'foreignKey' => 'institution_room_id']);
	}
}
