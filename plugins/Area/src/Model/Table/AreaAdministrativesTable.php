<?php
namespace Area\Model\Table;

use App\Model\Table\AppTable;

class AreaAdministrativesTable extends AppTable {
	public function initialize(array $config) {
		$this->belongsTo('AreaAdministrativeLevels', ['className' => 'Area.AreaAdministrativeLevels']);
	}
}
