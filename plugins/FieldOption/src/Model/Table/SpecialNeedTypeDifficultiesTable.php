<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

class SpecialNeedTypeDifficultiesTable extends ControllerActionTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		$this->table('special_need_type_difficulties');
		parent::initialize($config);
		$this->hasMany('SpecialNeeds', ['className' => 'User.SpecialNeeds', 'foreignKey' => 'special_need_type_difficulty_id']);

		$this->behaviors()->get('ControllerAction')->config('actions.remove', 'transfer');
	}
}
