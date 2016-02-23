<?php
namespace Survey\Model\Table;

use CustomField\Model\Table\CustomTableColumnsTable;

class SurveyTableColumnsTable extends CustomTableColumnsTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'Survey.SurveyQuestions', 'foreignKey' => 'survey_question_id']);
		if ($this->behaviors()->has('Reorder')) {
			$this->behaviors()->get('Reorder')->config([
				'filter' => 'survey_question_id',
			]);
		}
	}
}
