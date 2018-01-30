<?php
namespace Survey\Model\Table;

use App\Model\Table\AppTable;

class SurveyFormsFiltersTable extends AppTable
{
    public function initialize(array $config)
    {
        // $this->table('suvery_forms_filters');
        parent::initialize($config);
        $this->belongsTo('SuveryForms', [
            'className' => 'Survey.SuveryForms',
            'foreignKey' => 'survey_form_id'
        ]);
        $this->belongsTo('', [
            'className' => 'Institution.Types',
            'foreignKey' => 'survey_filter_id'
        ]);
    }
}
