<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

class SpecialNeedTypesTable extends ControllerActionTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		$this->table('special_need_types');
		parent::initialize($config);
		$this->hasMany('SpecialNeeds', ['className' => 'User.SpecialNeeds', 'foreignKey' => 'special_need_type_id']);

		$this->behaviors()->get('ControllerAction')->config('actions.remove', 'transfer');
	}
}
