<?php
namespace User\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class SpecialNeedTypesTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		$this->hasMany('UserSpecialNeedsTable', ['className' => 'User.UserSpecialNeedsTable']);
	}
}
