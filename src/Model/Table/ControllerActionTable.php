<?php
namespace App\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class ControllerActionTable extends AppTable {
	public $CAVersion = '4.0';

	public function initialize(array $config) {
		parent::initialize($config);

		$this->addBehavior('OpenEmis.OpenEmis');
		$this->addBehavior('ControllerAction.ControllerAction', [
			'fields' => ['excludes' => ['modified_user_id', 'created_user_id']]
		]);
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);
		return $validator;
	}
}
