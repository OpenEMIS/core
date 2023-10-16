<?php

namespace Staff\Model\Table;

use CustomField\Model\Table\CustomTableCellsTable;

class StaffSurveyTableCellsTable extends CustomTableCellsTable
{
    public function initialize(array $config)
    {
        $this->table('institution_staff_survey_table_cells');

        parent::initialize($config);
        $this->belongsTo('CustomFields', ['className' => 'Survey.SurveyQuestions', 'foreignKey' => 'survey_question_id']);
        $this->belongsTo('CustomRecords', ['className' => 'Staff.StaffSurveys', 'foreignKey' => 'institution_staff_survey_id']);
    }
}
