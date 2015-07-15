<?php
namespace Institution\Model\Table;

use CustomField\Model\Table\CustomTableRowsTable;

class InstitutionCustomTableRowsTable extends CustomTableRowsTable {
	public function initialize(array $config) {
		$this->table('institution_site_custom_table_rows');

		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'Institution.InstitutionCustomFields', 'foreignKey' => 'institution_site_custom_field_id']);
	}
}
