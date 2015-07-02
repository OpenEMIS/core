<?php
namespace Area\Model\Table;

use App\Model\Table\AppTable;

class AreaAdministrativesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('Parents', ['className' => 'Area.AreaAdministratives']);
		$this->belongsTo('AreaAdministrativeLevels', ['className' => 'Area.AreaAdministrativeLevels']);
	}
}
