<?php 
namespace AcademicPeriod\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;

class PeriodBehavior extends Behavior {
	public function initialize(array $config) {
	}

	public function findAcademicPeriod(Query $query, array $options) {
		if (array_key_exists('academic_period_id', $options)) {
			$AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
			$periodObj = $AcademicPeriods
				->findById($options['academic_period_id'])
				->first();
			$startDate = date('Y-m-d', strtotime($periodObj->start_date));
			$endDate = date('Y-m-d', strtotime($periodObj->end_date));

			$conditions = [];
			$conditions['OR'] = [
				'OR' => [
					[
						$this->_table->aliasField('end_date') . ' IS NOT NULL',
						$this->_table->aliasField('start_date') . ' <=' => $startDate,
						$this->_table->aliasField('end_date') . ' >=' => $startDate
					],
					[
						$this->_table->aliasField('end_date') . ' IS NOT NULL',
						$this->_table->aliasField('start_date') . ' <=' => $endDate,
						$this->_table->aliasField('end_date') . ' >=' => $endDate
					],
					[
						$this->_table->aliasField('end_date') . ' IS NOT NULL',
						$this->_table->aliasField('start_date') . ' >=' => $startDate,
						$this->_table->aliasField('end_date') . ' <=' => $endDate
					]
				],
				[
					$this->_table->aliasField('end_date') . ' IS NULL',
					$this->_table->aliasField('start_date') . ' <=' => $endDate
				]
			];

			return $query->where($conditions);
		} else {
			return $query;
		}
	}
}
