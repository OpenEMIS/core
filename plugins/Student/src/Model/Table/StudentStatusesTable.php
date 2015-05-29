<?php
namespace Student\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class StudentStatusesTable extends AppTable {
	public function initialize(array $config) {
		$this->table('field_option_values');
		$this->addBehavior('FieldOptionValues');
		$this->hasMany('UserIdentities', ['className' => 'UserIdentities']);
	}
}