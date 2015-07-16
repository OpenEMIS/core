<?php
namespace Institution\Model\Table;

use CustomField\Model\Table\CustomTableCellsTable;

class InstitutionCustomTableCellsTable extends CustomTableCellsTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'CustomField.CustomFields']);
		$this->belongsTo('CustomRecords', ['className' => 'Institution.Institutions', 'foreignKey' => 'institution_site_id']);
	}
}
