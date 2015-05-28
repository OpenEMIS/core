<?php
namespace AcademicPeriod\Model\Table;

use App\Model\Table\AppTable;

class AcademicPeriodsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('AcademicPeriodLevels', ['className' => 'AcademicPeriod.AcademicPeriodLevels']);
		$this->hasMany('InstitutionSiteShifts', ['className' => 'Institution.InstitutionSiteShifts']);
		$this->hasMany('InstitutionSiteSections', ['className' => 'Institution.InstitutionSiteSections']);
	}
}
