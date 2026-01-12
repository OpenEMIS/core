<?php
namespace Configuration\Model\Table;

use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;

class ConfigItemOptionsTable extends AppTable {
	use OptionsTrait;

	public function initialize(array $config): void {
		parent::initialize($config);
		if ($this->behaviors()->has('Reorder')) {
			$reorderBehavior = $this->behaviors()->get('Reorder');
        	$reorderBehavior->setConfig('filter', 'option_type');
		}
		// $this->hasMany('ConfigItems');
	}

}
