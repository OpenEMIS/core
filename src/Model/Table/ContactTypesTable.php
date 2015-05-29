<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class ContactTypesTable extends Table {
	public function initialize(array $config) {
		$this->table('field_option_values');
		parent::initialize($config);

		$this->addBehavior('ControllerAction.FieldOption');
		$this->belongsTo('ContactOption', ['className' => 'ContactOptions']);
		$this->hasMany('UserContacts');
	}
}
