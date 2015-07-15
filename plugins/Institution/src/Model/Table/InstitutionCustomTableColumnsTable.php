<?php
namespace Institution\Model\Table;

use CustomField\Model\Table\CustomTableColumnsTable;

class InstitutionCustomTableColumnsTable extends CustomTableColumnsTable {
	public function initialize(array $config) {
		$this->table('institution_site_custom_table_columns');

		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'Institution.InstitutionCustomFields', 'foreignKey' => 'institution_site_custom_field_id']);
	}
}
