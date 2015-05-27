<?php
namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class IdentityTypesTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('FieldOptionValues');
		$this->table('field_option_values');
		// $this->hasMany('StudentContact');
		// $this->hasMany('StaffContact');
	}

	
}
