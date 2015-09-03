<?php
namespace Institution\Model\Table;

use CustomField\Model\Table\CustomFieldValuesTable;

class StudentSurveyAnswersTable extends CustomFieldValuesTable {
	public function initialize(array $config) {
		$this->table('institution_student_survey_answers');

		parent::initialize($config);
		$this->belongsTo('CustomFields', ['className' => 'Survey.SurveyQuestions', 'foreignKey' => 'survey_question_id']);
		$this->belongsTo('CustomRecords', ['className' => 'Institution.StudentSurveys', 'foreignKey' => 'institution_student_survey_id']);
	}
}
