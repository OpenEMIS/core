<?php
namespace Area\Model\Table;

use App\Model\Table\AppTable;

class AreaAdministrativeLevelsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('AreaAdministratives', ['className' => 'Area.AreaAdministratives']);
	}
}
