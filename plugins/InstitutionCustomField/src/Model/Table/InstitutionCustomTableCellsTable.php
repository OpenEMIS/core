<?php
namespace InstitutionCustomField\Model\Table;

use CustomField\Model\Table\CustomTableCellsTable;

class InstitutionCustomTableCellsTable extends CustomTableCellsTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'InstitutionCustomField.InstitutionCustomFields', 'foreignKey' => 'institution_custom_field_id']);
		$this->belongsTo('CustomRecords', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_id']);
		$this->belongsTo('CustomTableRows', ['className' => 'Institution.InstitutionCustomTableRows', 'foreignKey' => 'institution_custom_table_row_id']);
		$this->belongsTo('CustomTableColumns', ['className' => 'Institution.InstitutionCustomTableColumns', 'foreignKey' => 'institution_custom_table_column_id']);
	}
}
