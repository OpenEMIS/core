<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;

class FieldOptionsTable extends AppTable {
	public function initialize(array $config) {
		$config['Modified'] = false;
		$config['Created'] = false;
		parent::initialize($config);

		$this->hasMany('FieldOptionValues', ['className' => 'FieldOption.FieldOptionValues', 'dependent' => true, 'cascadeCallbacks' => true]);
	}
}
