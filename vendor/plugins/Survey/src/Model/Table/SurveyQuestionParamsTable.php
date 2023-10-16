<?php
namespace Survey\Model\Table;

use App\Model\Table\AppTable;

class SurveyQuestionParamsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'Survey.SurveyQuestions', 'foreignKey' => 'survey_question_id']);
	}
}
