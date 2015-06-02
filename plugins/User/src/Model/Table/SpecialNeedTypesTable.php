<?php
namespace User\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class SpecialNeedTypesTable extends AppTable {
	public function initialize(array $config) {
		$this->table('field_option_values');
		$this->addBehavior('FieldOptionValues');
		$this->hasMany('UserSpecialNeedsTable', ['className' => 'User.UserSpecialNeedsTable']);
	}
}
