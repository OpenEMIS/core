<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class CountriesTable extends AppTable {
	public function initialize(array $config) {
		$this->hasMany('UserNationalities', ['className' => 'User.UserNationalities']);
	}
}
