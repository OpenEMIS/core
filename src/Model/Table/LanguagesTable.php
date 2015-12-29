<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class LanguagesTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		$this->table('field_option_values');
		parent::initialize($config);
		$this->hasMany('UserLanguages', ['className' => 'UserLanguages', 'foreignKey' => 'language_id']);
	}
}
