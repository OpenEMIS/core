<?php
namespace Competency\Model\Table;

use App\Model\Table\AppTable;
use App\Model\Traits\OptionsTrait;
use Cake\Validation\Validator;

class CompetenciesAppTable extends AppTable {
	use OptionsTrait;

	private $_defaultConfig = [
		'actions' => [
			'index' => true, 
			'add' => true, 
			'view' => true, 
			'edit' => true, 
			'remove' => 'cascade',
			'search' => ['orderField' => 'order'],
			'reorder' => ['orderField' => 'order']
		],
		'fields' => [
			'excludes' => ['modified', 'created']
		]
	];

	public function initialize(array $config) {
		parent::initialize($config);
		$this->_initializeFields();
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);
		return $validator;
	}

	private function _initializeFields() {
		$alias = $this->alias();
		$className = $this->registryAlias();
		$schema = $this->schema();

		$columns = $schema->columns();
		$fields = [];
		$visibility = ['view' => true, 'edit' => true, 'index' => true];
		$order = 10;

		foreach ($columns as $i => $col) {
			$attr = $schema->column($col);
			$attr['model'] = $alias;
			$attr['className'] = $className;
			$attr['visible'] = $col != 'password' ? $visibility : false;
			$attr['order'] = $order * ($i+1);
			$attr['field'] = $col;

			$fields[$col] = $attr;
		}
		$primaryKey = $this->primaryKey();

		if (!is_array($primaryKey) && array_key_exists($primaryKey, $fields)) { // not composite primary keys
			$fields[$primaryKey]['type'] = 'hidden';
		}

		$excludedFields = $this->_defaultConfig['fields']['excludes'];
		foreach ($excludedFields as $field) {
			if (array_key_exists($field, $fields)) {
				$fields[$field]['visible']['index'] = false;
				$fields[$field]['visible']['view'] = true;
				$fields[$field]['visible']['edit'] = false;
				$fields[$field]['labelKey'] = 'general';
			}
		}
		
		$this->fields = $fields;
	}

}
