<?php
namespace Staff\Model\Table;

use App\Model\Table\AppTable;

class CompetencySetCompetenciesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CompentencySets', ['className' => 'Staff.CompentencySets']);
		$this->belongsTo('Competencies', ['className' => 'Staff.Competencies']);
	}
}