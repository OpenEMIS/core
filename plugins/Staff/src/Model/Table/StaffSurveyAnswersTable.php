<?php

namespace Staff\Model\Table;

use CustomField\Model\Table\CustomFieldValuesTable;

class StaffSurveyAnswersTable extends CustomFieldValuesTable
{
    public function initialize(array $config)
    {
        $this->table('institution_staff_survey_answers');

        parent::initialize($config);
        $this->belongsTo('CustomFields', ['className' => 'Survey.SurveyQuestions', 'foreignKey' => 'survey_question_id']);
        $this->belongsTo('CustomRecords', ['className' => 'Staff.StaffSurveys', 'foreignKey' => 'institution_staff_survey_id']);
    }
}
