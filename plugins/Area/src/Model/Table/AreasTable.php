<?php
namespace Area\Model\Table;

use App\Model\Table\AppTable;

class AreasTable extends AppTable {
	public function initialize(array $config) {
		$this->belongsTo('AreaLevels', ['className' => 'Area.AreaLevels']);
	}
}
