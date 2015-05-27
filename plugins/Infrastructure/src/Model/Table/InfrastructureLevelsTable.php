<?php
namespace Infrastructure\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class InfrastructureLevelsTable extends AppTable {
	public function initialize(array $config) {

		$this->hasMany('InfrastructureTypes', ['className' => 'Infrastructure.InfrastructureTypes']);
		
	}

	public function validationDefault(Validator $validator) {
		
		return $validator;
	}

	public function beforeAction() {
		
	}

	public function getList() {
		$result = $this->find('list', [
			'conditions' => [
				$this->alias().'.visible' => 1
			],
			'order' => [
				$this->alias().'.order',
				$this->alias().'.name'
			]
		]);
		$list = $result->toArray();

		return $list;
	}

}
