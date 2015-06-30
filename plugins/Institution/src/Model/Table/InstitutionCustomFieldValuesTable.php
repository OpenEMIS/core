<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;

class InstitutionCustomFieldValuesTable extends AppTable {
	public function initialize(array $config) {
		$this->table('institution_site_custom_field_values');

		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'CustomField.CustomFields']);
		$this->belongsTo('CustomRecords', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);
	}
}
