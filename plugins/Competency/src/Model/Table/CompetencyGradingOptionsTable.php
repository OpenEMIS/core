<?php
namespace Competency\Model\Table;

use Cake\Validation\Validator;

class CompetencyGradingOptionsTable extends CompetenciesAppTable {

    public function initialize(array $config)
    {
        parent::initialize($config);
        $this->belongsTo('GradingTypes', ['className' => 'Competency.CompetencyGradingTypes', 'foreignKey' => 'competency_grading_type_id']);
        $this->hasMany('InstitutionCompetencyResults', ['className' => 'Institution.InstitutionCompetencyResults', 'foreignKey' => 'competency_grading_option_id']);

        $this->fields['competency_grading_type_id']['type'] = 'hidden';
        $this->fields['id']['type'] = 'hidden';
        $this->fields['name']['required'] = true;
    }

    public function getFormFields($action = 'edit') {
        if ($action=='edit') {
            return ['code'=>'', 'name'=>'', 'description' => '', 'competency_grading_type_id'=>'', 'id'=>''];
        } else {
            return ['code'=>'', 'name'=>'', 'description' => ''];
        }
    }

    public function validationDefault(Validator $validator) {
        $validator = parent::validationDefault($validator);

        $validator
            ->allowEmpty('code')
            ->add('code', [
                'ruleUniqueCode' => [
                    'rule' => ['validateUnique', ['scope' => 'competency_grading_type_id']],
                    'provider' => 'table'
                ]
            ])
            ->requirePresence('name');
        return $validator;
    }
}