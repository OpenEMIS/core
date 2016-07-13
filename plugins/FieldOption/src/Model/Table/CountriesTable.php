<?php
namespace FieldOption\Model\Table;

use App\Model\Table\ControllerActionTable;
use Cake\Validation\Validator;

class CountriesTable extends ControllerActionTable {
	public function initialize(array $config)
	{
		$this->addBehavior('ControllerAction.FieldOption');
		$this->table('countries');
		parent::initialize($config);
	}

	public function validationDefault(Validator $validator)
	{
		$validator = parent::validationDefault($validator);

		$validator
			->notEmpty('name', 'Please enter a name.');

		return $validator;
	}
}
