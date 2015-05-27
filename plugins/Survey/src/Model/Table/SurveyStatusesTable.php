<?php
namespace Survey\Model\Table;

use App\Model\Table\AppTable;

class SurveyStatusesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('SurveyTemplates', ['className' => 'Survey.SurveyTemplates']);
	}
}
