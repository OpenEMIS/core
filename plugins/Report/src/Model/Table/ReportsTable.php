<?php
namespace Report\Model\Table;

use ArrayObject;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\Event\Event;
use Cake\Network\Request;
use App\Model\Table\AppTable;
use Cake\Utility\Inflector;

class ReportsTable extends AppTable  {

	public function initialize(array $config) {
		$this->table('reports');
		parent::initialize($config);
		$this->CAVersion = '4.0';
		$this->addBehavior('OpenEmis.OpenEmis');
		$this->addBehavior('ControllerAction.ControllerAction', [
			'actions' => ['remove' => false],
			'fields' => ['excludes' => ['modified_user_id', 'created_user_id']]
		]);
		$this->joinTypes = $this->getSelectOptions('Reports.join_types');
		$this->joinOperators = $this->getSelectOptions('Reports.join_operators');
		$this->conditionOperators = $this->getSelectOptions('Reports.condition_operators');
	}

	protected function setupValues(Entity $entity) {
		$data[$this->alias()] = [];

		$mainTable = '';
		$mainTableAlias = '';

		$queryValue = $entity->query;
		if (!empty($queryValue)) {
			$arrQuery = json_decode($queryValue, true);
			// from (main_table and table_alias)
			if (array_key_exists('from', $arrQuery)) {
				$records = $arrQuery['from'];
				$tables = [];
				// For now we only take the first found table as the main table
				foreach ($records as $obj) {
					$str = str_ireplace(" AS ", " as ", $obj);
					$str = str_replace("`", "", $str);
					list($tableName, $tableAlias) = explode(" as ", $str, 2);
					if (strpos($tableAlias, "__")) {
						$alias = '';
					} else {
						$alias = $tableAlias;
					}
					
					$tables[] = [
						'table' => $tableName,
						'alias' => $alias
					];
					break;
				}
				$mainTable = $tables[0]['table'];
				$mainTableAlias = $tables[0]['alias'];
				$data[$this->alias()]['tables'] = $tables;
			}

			// joins (will have to improve on this part) (To support one main table for now)
			if (array_key_exists('join', $arrQuery)) {
				$records = $arrQuery['join'];

				foreach ($records as $obj) {
					$joins = $obj;

					$thisTable = $obj['table'];
					$thisTableAlias = !empty($obj['alias']) ? $obj['alias'] : $mainTableAlias;

					$conditions = [];
					foreach ($obj['conditions'] as $condition) {
						$joinCondition = [];

						$condition = str_replace("`", "",$condition);
						list($thisTableColumn, $operator, $otherTableColumn) = explode(" ", $condition, 3);
						$joinCondition['this_table'] = $thisTableColumn;
						$joinCondition['operator'] = array_search($operator, $this->joinOperators);
						$joinCondition['other_table'] = $otherTableColumn;

						$conditions[] = $joinCondition;
					}

					$joins['conditions'] = $conditions;
					$data[$this->alias()]['joins'][] = $joins;
				}
			}
			// End

			// fields
			if (array_key_exists('select', $arrQuery)) {
				$records = $arrQuery['select'];

				$fields = [];
				foreach ($records as $obj) {
					$str = str_ireplace(" AS ", " as ", $obj);
					$str = str_replace("`", "", $str);
					list($columnName, $aliasName) = explode(" as ", $str, 2);
					list($tableAlias, $column) = explode(".", $columnName, 2);
					if (strpos($aliasName, "__")) {
						$alias = '';
					} else {
						$alias = $aliasName;
					}
					$fields[] = [
						'visible' => 1,
						'table' => $tableAlias,
						'column' => $column,
						'alias' => $alias
					];
				}

				$data[$this->alias()]['fields'] = $fields;
			}
			// End

			// conditions
			if (array_key_exists('where', $arrQuery)) {
				$records = $arrQuery['where'];

				$conditions = [];
				foreach ($records as $obj) {
					if (strpos(strtolower($obj), " is not null")) {
						$str = str_ireplace(" IS NOT NULL", "", $obj);
						$column = str_replace("`", "", $str);
						$operator = 'is_not_null';

						$condition = [
							'column' => $column,
							'operator' => $operator,
							'value' => ''
						];
					} else if (strpos(strtolower($obj), " is null")) {
						$str = str_ireplace(" IS NULL", "", $obj);
						$column = str_replace("`", "", $str);
						$operator = 'is_null';

						$condition = [
							'column' => $column,
							'operator' => $operator,
							'value' => ''
						];
					} else if (strpos(strtolower($obj), " not in ")) {
						$str = str_ireplace(" NOT IN ", " not in ", $obj);
						$str = str_replace("`", "", $str);
						list($column, $value) = explode(" not in ", $str, 2);
						$value = str_replace("(", "",$value);
						$value = str_replace(")", "",$value);
						$operator = 'not_in';

						$condition = [
							'column' => $column,
							'operator' => $operator,
							'value' => $value
						];
					} else if (strpos(strtolower($obj), " in ")) {
						$str = str_ireplace(" IN ", " in ", $obj);
						$str = str_replace("`", "", $str);
						list($column, $value) = explode(" in ", $str, 2);
						$value = str_replace("(", "",$value);
						$value = str_replace(")", "",$value);
						$operator = 'in';

						$condition = [
							'column' => $column,
							'operator' => $operator,
							'value' => $value
						];
					} else {
						$str = str_replace("`", "", $obj);
						list($column, $operator, $value) = explode(" ", $str, 3);
						$operator = array_search($operator, $this->conditionOperators);
						$value = str_replace("'", "",$value);

						$condition = [
							'column' => $column,
							'operator' => $operator,
							'value' => $value
						];
					}
					$conditions[] = $condition;
				}

				$data[$this->alias()]['conditions'] = $conditions;
			}
			// End

			// groups
			if (array_key_exists('group', $arrQuery)) {
				$records = $arrQuery['group'];

				$groups = [];
				foreach ($records as $obj) {
					$str = str_replace("`", "", $obj);
					list($tableAlias, $column) = explode(".", $str, 2);
					$groups[] = [
						'visible' => 1,
						'table' => $tableAlias,
						'column' => $column
					];
				}

				$data[$this->alias()]['groups'] = $groups;
			}
			// End

			// having
			if (array_key_exists('having', $arrQuery)) {
				$records = $arrQuery['having'];

				$havingCondition = [];

				foreach ($records as $obj) {
					$str = str_replace("`", "", $obj);
					list($column, $operator, $value) = explode(" ", $str, 3);
					$operator = array_search($operator, $this->conditionOperators);
					$value = str_replace("'", "",$value);

					$havingCondition [] = [
						'column' => $column,
						'operator' => $operator,
						'value' => $value
					];
				}
				$data[$this->alias()]['having'] = $havingCondition;
			}
			// End

			$entity = $this->patchEntity($entity, $data, ['validate' => false]);
		}

		return $entity;
	}

	private function getSelectOptions($code) {
		$options = [
			'general' => [
				'active' => [1 => __('Active'), 0 => __('Inactive')],
				'yesno' => [1 => __('Yes'), 0 => __('No')],
			],
			'Reports' => [
				'join_types' => ['INNER' => 'Inner', 'LEFT' => 'Left'],
				'join_operators' => ['eq' => '=', 'neq' => '!='],
				// 'join_operators' => ['eq' => '=', 'neq' => '!=', 'gt' => '>', 'gte' => '>=', 'lt' => '<', 'lte' => '<='],
				'condition_operators' => [
					'eq' => '=',
					'neq' => '!=',
					'gt' => '>',
					'gte' => '>=',
					'lt' => '<',
					'lte' => '<=',
					'in' => 'IN (...)',
					'not_in' => 'NOT IN (...)',
					'is_null' => 'IS NULL',
					'is_not_null' => 'IS NOT NULL'
				]
			]
		];

		$index = explode('.', $code);
		foreach ($index as $i) {
			if (isset($options[$i])) {
				$options = $options[$i];
			} else {
				$options = false;
				break;
			}
		}
		return $options;
	}
}