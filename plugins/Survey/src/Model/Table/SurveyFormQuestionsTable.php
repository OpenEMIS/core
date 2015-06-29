<?php
namespace Survey\Model\Table;

use App\Model\Table\AppTable;

class SurveyFormQuestionsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('SurveyForms', ['className' => 'Survey.SurveyForms']);
		$this->belongsTo('SurveyQuestions', ['className' => 'Survey.SurveyQuestions']);
	}
}
