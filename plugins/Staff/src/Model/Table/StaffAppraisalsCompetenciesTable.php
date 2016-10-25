<?php
namespace Staff\Model\Table;

use App\Model\Table\AppTable;

class StaffAppraisalsCompetenciesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('StaffAppraisal', ['className' => 'Staff.Appraisal']);
		$this->belongsTo('Competencies', ['className' => 'Staff.Competencies']);
	}
}