<?php
namespace CustomField\Model\Table;

use App\Model\Table\AppTable;

class CustomFormTypesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('CustomForms', ['className' => 'CustomField.CustomForms']);
		$this->belongsTo('FieldOptionValues', ['className' => 'FieldOptionValues']);
	}
}
