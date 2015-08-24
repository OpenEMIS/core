<?php 
namespace AcademicPeriod\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\I18n\Time;

class PeriodBehavior extends Behavior {
	public function findAcademicPeriod(Query $query, array $options) {
		$table = $this->_table;
		if (array_key_exists('table', $options)) {
			$table = $options['table'];
		}

		if (array_key_exists('academic_period_id', $options)) {
			$AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
			$periodObj = $AcademicPeriods
				->findById($options['academic_period_id'])
				->first();

			if ($periodObj->start_date instanceof Time) {
				$startDate = $periodObj->start_date->format('Y-m-d');
			} else {
				$startDate = date('Y-m-d', strtotime($periodObj->start_date));
			}

			if ($periodObj->end_date instanceof Time) {
				$endDate = $periodObj->end_date->format('Y-m-d');
			} else {
				$endDate = date('Y-m-d', strtotime($periodObj->end_date));
			}

			$conditions = [];
			$conditions['OR'] = [
				'OR' => [
					[
						$table->aliasField('end_date') . ' IS NOT NULL',
						$table->aliasField('start_date') . ' <=' => $startDate,
						$table->aliasField('end_date') . ' >=' => $startDate
					],
					[
						$table->aliasField('end_date') . ' IS NOT NULL',
						$table->aliasField('start_date') . ' <=' => $endDate,
						$table->aliasField('end_date') . ' >=' => $endDate
					],
					[
						$table->aliasField('end_date') . ' IS NOT NULL',
						$table->aliasField('start_date') . ' >=' => $startDate,
						$table->aliasField('end_date') . ' <=' => $endDate
					]
				],
				[
					$table->aliasField('end_date') . ' IS NULL',
					$table->aliasField('start_date') . ' <=' => $endDate
				]
			];

			return $query->where($conditions);
		} else {
			return $query;
		}
	}
}
