<?php
namespace Survey\Model\Table;

use App\Model\Table\AppTable;

class SurveyModulesTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->hasMany('SurveyTemplates', ['className' => 'Survey.SurveyTemplates']);
	}
}
