<?php
namespace FieldOption\Model\Table;

use ArrayObject;
use App\Model\Table\ControllerActionTable;
use Cake\Event\Event;
use Cake\Validation\Validator;

class BanksTable extends ControllerActionTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		$this->table('banks');
		parent::initialize($config);
		$this->hasMany('BankBranches', ['className' => 'FieldOption.BankBranches', 'foreignKey' => 'bank_id']);
	}

	public function indexBeforeAction(Event $event, ArrayObject $extra)
	{
		$this->field('code', ['after' => 'name']);
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);

		$validator
			->notEmpty('name', 'Please enter a name.')
			->notEmpty('code', 'Please enter a code.');

		return $validator;
	}
}