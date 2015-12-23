<?php
namespace User\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\Query;

class AdvancedNameSearchBehavior extends Behavior {
	// findByNames
	// advancedNameSearch Behavior
	public function addSearchConditions(Query $query, $options = []) {
		$searchByUserName = false;
		if (array_key_exists('searchByUserName', $options)) {
			$searchByUserName = $options['searchByUserName'];
		}

		if (array_key_exists('searchTerm', $options)) {
			$search = $options['searchTerm'];
		}

		$alias = $this->_table->alias();
		if (array_key_exists('alias', $options)) {
			$alias = $options['alias'];
		}
		
		$searchParams = explode(' ', $search);
		foreach ($searchParams as $key => $value) {
			if (empty($searchParams[$key])) {
				unset($searchParams[$key]);
			}
		}

		// note that CONCAT_WS is not supported by cakephp and also not supported by some dbs like sqlite and mysqlserver, thus this condition
		if ($this->_table->connection()->config()['driver'] == 'Cake\Database\Driver\Mysql') {
			// this search is catered to jordon's request
			$searchString = '%' . $search . '%';
			switch (count($searchParams)) {
				case 1:
					// 1 word - search by openemis id or 1st or middle or third or last
					
					$conditions = [
						'OR' => [
							$alias . '.openemis_no LIKE' => $searchString,
							$alias . '.first_name LIKE' => $searchString,
							$alias . '.middle_name LIKE' => $searchString,
							$alias . '.third_name LIKE' => $searchString,
							$alias . '.last_name LIKE' => $searchString
						]
					];
					if ($searchByUserName) {
						$conditions['OR'][$alias . '.username LIKE'] = $searchString;
					}
					$query->where($conditions);
					break;

				case 2:
					// 2 words - search by 1st and last name
					$names = ["$alias.first_name", "$alias.last_name"];
					$concatCondition = ['CONCAT_WS(" ", trim(' . $names[0] . '), trim(' . $names[1] . ') ) LIKE "%' . trim($search) . '%"'];
					if (!$searchByUserName) {
						$conditions = $concatCondition;
						
					} else {
						$conditions = [];
						$conditions['OR'] = [];
						$conditions['OR'][] = $concatCondition;
						$conditions['OR'][$alias . '.username LIKE'] = $searchString;
					}
					$query->where($conditions);
					break;

				case 3:
					// 3 words - search by 1st middle last
					$names = ["$alias.first_name", "$alias.middle_name", "$alias.last_name"];
					$concatCondition = ['CONCAT_WS(" ", trim(' . $names[0] . '), trim(' . $names[1] . '), trim(' . $names[2] . ') ) LIKE "%' . trim($search) . '%"'];
					if (!$searchByUserName) {
						$conditions = $concatCondition;
						
					} else {
						$conditions = [];
						$conditions['OR'] = [];
						$conditions['OR'][] = $concatCondition;
						$conditions['OR'][$alias . '.username LIKE'] = $searchString;
					}
					$query->where($conditions);
					break;

				case 4:
					// 4 words - search by 1st middle third last
					$names = ["$alias.first_name", "$alias.middle_name", "$alias.third_name", "$alias.last_name"];
					$concatCondition = ['CONCAT_WS(" ", trim(' . $names[0] . '), trim(' . $names[1] . '), trim(' . $names[2] . '), trim(' . $names[3] . ') ) LIKE "%' . trim($search) . '%"'];
					if (!$searchByUserName) {
						$conditions = $concatCondition;
						
					} else {
						$conditions = [];
						$conditions['OR'] = [];
						$conditions['OR'][] = $concatCondition;
						$conditions['OR'][$alias . '.username LIKE'] = $searchString;
					}
					$query->where($conditions);
					break;
				
				default:
					foreach ($searchParams as $key => $value) {
						$searchString = '%' . $value . '%';
						$conditions = [
							'OR' => [
								$alias . '.openemis_no LIKE' => $searchString,
								$alias . '.first_name LIKE' => $searchString,
								$alias . '.middle_name LIKE' => $searchString,
								$alias . '.third_name LIKE' => $searchString,
								$alias . '.last_name LIKE' => $searchString
							]
						];
						if ($searchByUserName) {
							$conditions['OR'][$alias . '.username LIKE'] = $searchString;
						}
					}
					break;
			}
		} else {
			foreach ($searchParams as $key => $value) {
				$searchString = '%' . $value . '%';

				$conditions = [
					'OR' => [
						$alias . '.openemis_no LIKE' => $searchString,
						$alias . '.first_name LIKE' => $searchString,
						$alias . '.middle_name LIKE' => $searchString,
						$alias . '.third_name LIKE' => $searchString,
						$alias . '.last_name LIKE' => $searchString
					]
				];
				if ($searchByUserName) {
					$conditions['OR'][$alias . '.username LIKE'] = $searchString;
				}
			}
		}

		return $query;
	}	
}
