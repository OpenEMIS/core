<?php
namespace AcademicPeriod\Model\Table;

use App\Model\Table\AppTable;

class AcademicPeriodsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('AcademicPeriodLevels', ['className' => 'AcademicPeriod.AcademicPeriodLevels']);
		
		// $this->hasMany('Shifts', ['className' => 'Institution.Shifts']);
		// $this->hasMany('Sections', ['className' => 'Institution.Sections']);
	}

	public function getList() {
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
}
