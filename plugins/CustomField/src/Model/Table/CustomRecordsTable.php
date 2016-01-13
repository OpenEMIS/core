<?php
namespace CustomField\Model\Table;

use App\Model\Table\AppTable;

class CustomRecordsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomForms', ['className' => 'CustomField.CustomForms']);
		$this->hasMany('CustomFieldValues', ['className' => 'CustomField.CustomFieldValues', 'dependent' => true]);
		$this->hasMany('CustomTableCells', ['className' => 'CustomField.CustomTableCells', 'dependent' => true]);
	}
}
