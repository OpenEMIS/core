<?php
namespace AcademicPeriod\Model\Table;

use Cake\ORM\TableRegistry;
use App\Model\Table\AppTable;

class AcademicPeriodsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('AcademicPeriodLevels', ['className' => 'AcademicPeriod.AcademicPeriodLevels']);
		$this->belongsTo('Parent', ['className' => 'AcademicPeriod.AcademicPeriods', 'foreignKey' => 'parent_id']);
		
		// $this->hasMany('Shifts', ['className' => 'Institution.Shifts']);
		// $this->hasMany('Sections', ['className' => 'Institution.Sections']);
	}

	public function getList($query = NULL) {
		$where = [
			$this->aliasField('current') => 1,
			$this->aliasField('parent_id') . ' <> ' => 0
		];

		// get the current period
		$data = $this->find('list')
			->find('visible')
			->find('order')
			->where($where)
			->toArray();
		
		// get all other periods
		$where[$this->aliasField('current')] = 0;
		$data += $this->find('list')
			->find('visible')
			->find('order')
			->where($where)
			->toArray();
		
		return $data;
	}

	public function getDate($dateObject) {
		if (is_object($dateObject)) {
			return $dateObject->toDateString();
		}
		return false;
	}

	public function getAttendanceWeeks($id) {
		// $weekdays = array(
		// 	0 => 'sunday',
		// 	1 => 'monday',
		// 	2 => 'tuesday',
		// 	3 => 'wednesday',
		// 	4 => 'thursday',
		// 	5 => 'friday',
		// 	6 => 'saturday',
		// 	//7 => 'sunday'
		// );

		$period = $this->findById($id)->first();
		$ConfigItems = TableRegistry::get('ConfigItems');
		$firstDayOfWeek = $ConfigItems->value('first_day_of_week');
		$daysPerWeek = $ConfigItems->value('days_per_week');

		$lastDayIndex = ($firstDayOfWeek + $daysPerWeek - 1) % 7;
		$startDate = $period->start_date;

		$weekIndex = 1;
		$weeks = [];
		
		do {
			$endDate = $startDate->copy()->next($lastDayIndex);
			if ($endDate->gt($period->end_date)) {
				$endDate = $period->end_date;
			}
			$weeks[$weekIndex++] = [$startDate, $endDate];
			$startDate = $endDate->copy();
			$startDate->addDay();
		} while ($endDate->lt($period->end_date));
		
		return $weeks;
	}
}
