<?php
namespace InstitutionCustomField\Model\Table;

use CustomField\Model\Table\CustomFieldValuesTable;

class InstitutionCustomFieldValuesTable extends CustomFieldValuesTable {
	public function initialize(array $config) {
		$config['extra'] = ['scope' => 'institution_custom_field_id'];
		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'InstitutionCustomField.InstitutionCustomFields', 'foreignKey' => 'institution_custom_field_id']);
		$this->belongsTo('CustomRecords', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
	}
}
