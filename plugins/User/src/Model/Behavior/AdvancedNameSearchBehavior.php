<?php
namespace User\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\Query;

class AdvancedNameSearchBehavior extends Behavior {
	protected $_userRole;
	protected $_info;
	protected $_roleFields;

	public function initialize(array $config) {
	}

	public function implementedEvents() {
		$events = parent::implementedEvents();
		$newEvent = [
		];
		$events = array_merge($events,$newEvent);
		return $events;
	}

	// findByNames
	// advancedNameSearch Behavior
	// public function findAcademicPeriod(Query $query, array $options) {
	public function addSearchConditions(Query $query, $options = []) {
		if (array_key_exists('searchTerm', $options)) {
			$search = $options['searchTerm'];
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
			switch (count($searchParams)) {
				case 1:
					// 1 word - search by openemis id or 1st or middle or third or last
					$query->where(['Users.openemis_no'.' LIKE' => '%' . trim($search) . '%']);
					foreach ($searchParams as $key => $value) {
						$searchString = '%' . $value . '%';
						$query->orWhere(['Users.first_name'.' LIKE' => $searchString]);
						$query->orWhere(['Users.middle_name'.' LIKE' => $searchString]);
						$query->orWhere(['Users.third_name'.' LIKE' => $searchString]);
						$query->orWhere(['Users.last_name'.' LIKE' => $searchString]);
					}
					break;

				case 2:
					// 2 words - search by 1st and last name
					$query->where(['CONCAT_WS(" ",'.'trim(Users.first_name)'.' ,'.'trim(Users.last_name)'.' ) LIKE "%' . trim($search) . '%"']);
					break;

				case 3:
					// 3 words - search by 1st middle last
					$query->where(['CONCAT_WS(" ",'.'trim(Users.first_name)'.' ,'.'trim(Users.middle_name)'.' ,'.'trim(Users.last_name)'.' ) LIKE "%' . trim($search) . '%"']);
					break;

				case 4:
					// 4 words - search by 1st middle third last
					$query->where(['CONCAT_WS(" ",'.'trim(Users.first_name)'.' ,'.'trim(Users.middle_name)'.' ,'.'trim(Users.third_name)'.' ,'.'trim(Users.last_name)'.' ) LIKE "%' . trim($search) . '%"']);
					break;
				
				default:
					$query->where(['Users.openemis_no'.' LIKE' => '%' . trim($search) . '%']);
					foreach ($searchParams as $key => $value) {
						$searchString = '%' . $value . '%';
						$query->orWhere(['Users.first_name'.' LIKE' => $searchString]);
						$query->orWhere(['Users.middle_name'.' LIKE' => $searchString]);
						$query->orWhere(['Users.third_name'.' LIKE' => $searchString]);
						$query->orWhere(['Users.last_name'.' LIKE' => $searchString]);
					}
					break;
			}
		} else {
			$query->where(['Users.openemis_no'.' LIKE' => '%' . trim($search) . '%']);
			foreach ($searchParams as $key => $value) {
				$searchString = '%' . $value . '%';
				$query->orWhere(['Users.first_name'.' LIKE' => $searchString]);
				$query->orWhere(['Users.middle_name'.' LIKE' => $searchString]);
				$query->orWhere(['Users.third_name'.' LIKE' => $searchString]);
				$query->orWhere(['Users.last_name'.' LIKE' => $searchString]);
			}
		}

		return $query;
	}

	
}
