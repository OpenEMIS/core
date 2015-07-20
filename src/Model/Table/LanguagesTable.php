<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class LanguagesTable extends AppTable {
	public function initialize(array $config) {
		$this->table('field_option_values');
		$this->addBehavior('ControllerAction.FieldOption');
		$this->hasMany('UserLanguages', ['className' => 'UserLanguages', 'dependent' => true, 'cascadeCallbacks' => true]);
	}
}
