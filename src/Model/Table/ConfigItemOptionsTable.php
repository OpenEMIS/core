<?php
namespace App\Model\Table;

use ArrayObject;
use Cake\I18n\Time;
use Cake\ORM\Table;
use Cake\ORM\Entity;
use Cake\ORM\TableRegistry;
use Cake\Event\Event;
use Cake\Network\Request;
use Cake\Utility\Inflector;
use Cake\Validation\Validator;
use App\Model\Traits\OptionsTrait;

class ConfigItemOptionsTable extends AppTable {
	use OptionsTrait;

	public function initialize(array $config) {
		parent::initialize($config);
		
		// $this->hasMany('ConfigItems');
	}

}
