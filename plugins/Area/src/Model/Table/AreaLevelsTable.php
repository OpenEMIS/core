<?php
namespace Area\Model\Table;

use App\Model\Table\AppTable;

class AreaLevelsTable extends AppTable {
	public function initialize(array $config) {
		$this->hasMany('Areas', ['className' => 'Area.Areas']);
	}
}
