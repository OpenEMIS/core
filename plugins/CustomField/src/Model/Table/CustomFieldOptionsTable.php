<?php
namespace CustomField\Model\Table;

use App\Model\Table\AppTable;

class CustomFieldOptionsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'CustomField.CustomFields']);

		if ($this->behaviors()->has('Reorder')) {
			$this->behaviors()->get('Reorder')->config([
				'filter' => 'custom_field_id',
			]);
		}
	}
}
