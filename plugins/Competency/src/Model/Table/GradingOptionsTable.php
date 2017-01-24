<?php
namespace Competency\Model\Table;

use Cake\Validation\Validator;

class GradingOptionsTable extends CompetenciesAppTable {

    public function initialize(array $config) 
    {
        $this->table('competency_grading_options');
        parent::initialize($config);

        $this->belongsTo('GradingTypes', ['className' => 'Competency.GradingTypes']);
        $this->hasMany('StudentCompetencyResults', ['className' => 'Institution.StudentCompetencyResults', 'foreignKey' => 'competency_grading_option_id']);

        $this->fields['competency_grading_type_id']['type'] = 'hidden';
        $this->fields['id']['type'] = 'hidden';
        $this->fields['name']['required'] = true;
    }

    public function getFormFields($action = 'edit') {
        if ($action=='edit') {
            return ['code'=>'', 'name'=>'', 'description'=>'', 'point'=>'', 'competency_grading_type_id'=>'', 'id'=>''];
        } else {
            return ['code'=>'', 'name'=>'', 'description'=>'', 'point'=>''];
        }
    }

    public function validationDefault(Validator $validator) {
        $validator = parent::validationDefault($validator);

        $validator
            ->allowEmpty('code')
            ->add('code', [
                'ruleUniqueCode' => [
                    'rule' => ['checkUniqueCode', ''],
                    'provider' => 'table'
                ]
            ])
            ->add('code', 'ruleUniqueCodeWithinForm', [
                'rule' => ['checkUniqueCodeWithinForm', $this->GradingTypes],
               
            ])
            ->requirePresence('name')
            ->allowEmpty('point')
            ->add('point', [
                'ruleIsDecimal' => [
                    'rule' => ['decimal', null],
                ],
                'ruleRange' => [
                    'rule' => ['range', 0, 9999.99]
                ]
            ]);
        return $validator;
    }
}