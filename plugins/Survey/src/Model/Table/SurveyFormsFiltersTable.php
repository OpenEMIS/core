<?php
namespace Survey\Model\Table;

use App\Model\Table\AppTable;

class SurveyFormsFiltersTable extends AppTable
{
    public function initialize(array $config)
    {
        $this->table('survey_forms_filters');
        parent::initialize($config);
        $this->belongsTo('CustomForms', [
            'className' => 'Survey.SurveyForms',
            'foreignKey' => 'survey_form_id'
        ]);
        $this->belongsTo('CustomFilters', [
            'className' => 'Institution.Types',
            'foreignKey' => 'survey_filter_id'
        ]);
    }

    public function getIsAllFilterType($id)
    {
        $count = $this
            ->find()
            ->where([
                $this->aliasField('survey_form_id') => $id,
                $this->aliasField('survey_filter_id') => 0
            ])
            ->count();

        return $count > 0;
    }
}
