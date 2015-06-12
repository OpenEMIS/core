<?php
namespace Area\Model\Table;

use App\Model\Table\AppTable;

class AreasTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('AreaLevels', ['className' => 'Area.AreaLevels']);
		$this->belongsTo('Parent', ['className' => 'Area.Areas', 'foreignKey' => 'parent_id']);
	}
}
