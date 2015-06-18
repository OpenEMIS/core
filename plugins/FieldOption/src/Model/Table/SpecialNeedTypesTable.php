<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class SpecialNeedTypesTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		$this->hasMany('SpecialNeeds', ['className' => 'User.SpecialNeeds']);
	}
}
