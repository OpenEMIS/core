<?php
namespace Education\Model\Table;

use App\Model\Table\AppTable;

class EducationSystemsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->hasMany('EducationLevels', ['className' => 'Education.EducationLevels', 'dependent' => true, 'cascadeCallbacks' => true]);
	}
}
