<?php
namespace InstitutionCustomField\Model\Table;

use CustomField\Model\Table\CustomTableRowsTable;

class InstitutionCustomTableRowsTable extends CustomTableRowsTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'InstitutionCustomField.InstitutionCustomFields', 'foreignKey' => 'institution_custom_field_id']);
		$this->hasMany('CustomTableCells', ['className' => 'InstitutionCustomField.InstitutionCustomTableCells', 'foreignKey' => 'institution_custom_table_row_id', 'dependent' => true]);
		if ($this->behaviors()->has('Reorder')) {
			$this->behaviors()->get('Reorder')->config([
				'filter' => 'institution_custom_field_id',
			]);
		}
	}
}
