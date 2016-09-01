<?php 
namespace AcademicPeriod\Model\Behavior;

use Cake\ORM\Behavior;
use Cake\ORM\Query;
use Cake\ORM\TableRegistry;
use Cake\I18n\Time;
use Cake\I18n\Date;

class PeriodBehavior extends Behavior {
	public function findAcademicPeriod(Query $query, array $options) {
		$table = $this->_table;

		if (array_key_exists('academic_period_id', $options)) {
			$AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
			$periodObj = $AcademicPeriods
				->findById($options['academic_period_id'])
				->first();
			if (!empty($periodObj)) {
				if ($periodObj->start_date instanceof Time || $periodObj->start_date instanceof Date) {
					$startDate = $periodObj->start_date->format('Y-m-d');
				} else {
					$startDate = date('Y-m-d', strtotime($periodObj->start_date));
				}

				if ($periodObj->end_date instanceof Time || $periodObj->end_date instanceof Date) {
					$endDate = $periodObj->end_date->format('Y-m-d');
				} else {
					$endDate = date('Y-m-d', strtotime($periodObj->end_date));
				}

				if (array_key_exists('beforeEndDate', $options)) {
					$conditions = [];
					$conditions['OR'] = [
						[
							$options['beforeEndDate'] . ' <=' => $endDate
						]
					];
					return $query->where($conditions);
				} else {
					return $query->find('InDateRange', ['start_date' => $startDate, 'end_date' => $endDate]);
				}
			} else {
				return $query->where(['0 = 1']);
			}
		} else {
			return $query;
		}
	}

	public function findInDateRange(Query $query, array $options) {
		$table = $this->_table;

		if (array_key_exists('start_date', $options) && array_key_exists('end_date', $options)) {

			$startDate = $options['start_date'];
			$endDate = $options['end_date'];

			if ($startDate instanceof Time || $startDate instanceof Date) {
				$startDate = $startDate->format('Y-m-d');
			} else {
				$startDate = date('Y-m-d', strtotime($startDate));
			}

			$conditions = [];

			if (!empty($endDate)) {
				if ($endDate instanceof Time || $endDate instanceof Date) {
					$endDate = $endDate->format('Y-m-d');
				} else {
					$endDate = date('Y-m-d', strtotime($endDate));
				}
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
			} else {
				// For records that are still valid after the start date
				$conditions['OR'] = [
					[
						$table->aliasField('end_date') . ' IS NULL'
					],
					[
						$table->aliasField('end_date') . ' IS NOT NULL',
						$table->aliasField('start_date') . ' <=' => $startDate,
						$table->aliasField('end_date') . ' >=' => $startDate
					],
					[
						$table->aliasField('end_date') . ' IS NOT NULL',
						$table->aliasField('start_date') . ' >=' => $startDate
					]
				];
			}

			return $query->where($conditions);
		} else {
			return $query;
		}
	}

	public function findInPeriod(Query $query, array $options) {
		$table = $this->_table;
		$field = $options['field'];
		$academicPeriodId = $options['academic_period_id'];

		$AcademicPeriods = TableRegistry::get('AcademicPeriod.AcademicPeriods');
		$periodEntity = $AcademicPeriods->get($academicPeriodId);

		$startDate = $periodEntity->start_date->format('Y-m-d');
		$endDate = $periodEntity->end_date->format('Y-m-d');

		$conditions = [
			$table->aliasField($field) . ' <=' => $endDate,
			$table->aliasField($field) . ' >=' => $startDate
		];

		return $query->where($conditions);
	}
}
