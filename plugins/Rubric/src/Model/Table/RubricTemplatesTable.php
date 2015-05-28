<?php
namespace Rubric\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class RubricTemplatesTable extends AppTable {
	private $weightingType = [
		1 => ['id' => 1, 'name' => 'Points'],
		2 => ['id' => 2, 'name' => 'Percentage']
	];

	public function initialize(array $config) {
		parent::initialize($config);
		$this->hasMany('RubricSections', ['className' => 'Rubric.RubricSections']);
		$this->hasMany('RubricTemplateOptions', ['className' => 'Rubric.RubricTemplateOptions']);
	}

	public function validationDefault(Validator $validator) {
		$validator
		->requirePresence('name')
		->notEmpty('name', 'Please enter a name.')
		->requirePresence('pass_mark')
		->notEmpty('pass_mark', 'Please enter a pass mark.');

		return $validator;
	}

	public function getList() {
		$result = $this->find('list', [
			'order' => [
				$this->alias().'.name'
			]
		]);
		$list = $result->toArray();

		return $list;
	}

	public function beforeAction() {
		$weightingTypeOptions = [];
		foreach ($this->weightingType as $key => $weightingType) {
			$weightingTypeOptions[$weightingType['id']] = __($weightingType['name']);
		}

		if($this->action == 'add' || $this->action == 'edit') {
			$this->fields['weighting_type']['type'] = 'select';
			$this->fields['weighting_type']['options'] = $weightingTypeOptions;
		}
	}
}
