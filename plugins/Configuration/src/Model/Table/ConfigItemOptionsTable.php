<?php
namespace Configuration\Model\Table;

use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;

class ConfigItemOptionsTable extends AppTable {
	use OptionsTrait;

	public function initialize(array $config) {
		parent::initialize($config);
		if ($this->behaviors()->has('Reorder')) {
			$this->behaviors()->get('Reorder')->config([
				'filter' => 'option_type',
			]);
		}
		// $this->hasMany('ConfigItems');
	}

}
