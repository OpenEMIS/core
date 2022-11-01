<?php
namespace Rubric\Model\Table;

use App\Model\Table\AppTable;

class RubricStatusProgrammesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('RubricStatuses', ['className' => 'Rubric.SurveyStatuses']);
		$this->belongsTo('EducationProgrammes', ['className' => 'Education.EducationProgrammes']);
	}
}
