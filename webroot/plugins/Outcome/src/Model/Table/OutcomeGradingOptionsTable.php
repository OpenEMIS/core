<?php
namespace Outcome\Model\Table;

use Cake\Validation\Validator;

use App\Model\Table\ControllerActionTable;

class OutcomeGradingOptionsTable extends ControllerActionTable
{
    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('OutcomeGradingTypes', ['className' => 'Outcome.OutcomeGradingTypes']);

        $this->hasMany('InstitutionOutcomeResults', [
            'className' => 'Institution.InstitutionOutcomeResults',
            'foreignKey' => 'outcome_grading_option_id',
            'dependent' => true,
            'cascadeCallbacks' => true
        ]);
    }

    public function validationDefault(Validator $validator)
    {
        $validator = parent::validationDefault($validator);
        return $validator
            ->allowEmpty('code')
            ->add('code', [
                'ruleUniqueCodeWithinForm' => [
                    'rule' => ['checkUniqueCodeWithinForm', $this->OutcomeGradingTypes],
                    'last' => true
                ],
                'ruleUniqueCode' => [
                    'rule' => ['checkUniqueCode', 'outcome_grading_type_id']
                ]
            ])
            ->requirePresence('name')
            ->allowEmpty('outcome_grading_type_id');
    }
}
