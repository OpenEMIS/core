<?php
namespace Survey\Model\Table;

use CustomField\Model\Table\CustomFieldOptionsTable;

class SurveyQuestionChoicesTable extends CustomFieldOptionsTable {
	public function initialize(array $config): void {
		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'Survey.SurveyQuestions', 'foreignKey' => 'survey_question_id']);
		if ($this->behaviors()->has('Reorder')) {
			// $this->behaviors()->get('Reorder')->config([
			// 	'filter' => 'survey_question_id',
			// ]);
			$reorderBehavior = $this->behaviors()->get('Reorder');
			$reorderBehavior->setConfig('filter', 'survey_question_id');
		}
        $this->addBehavior('Restful.RestfulAccessControl', [
            'Rules' => ['index']
        ]);
	}
}
