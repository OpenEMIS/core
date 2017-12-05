<?php
namespace Outcome\Model\Table;

use App\Model\Table\ControllerActionTable;

class OutcomeGradingOptionsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('GradingTypes', ['className' => 'Outcome.OutcomeGradingTypes']);

        $this->hasMany('InstitutionOutcomeResults', [
            'className' => 'Institution.InstitutionOutcomeResults',
            'foreignKey' => 'outcome_grading_option_id',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
    }
}
