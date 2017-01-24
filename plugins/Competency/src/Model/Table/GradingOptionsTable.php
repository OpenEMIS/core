<?php
namespace Competency\Model\Table;

use Cake\Validation\Validator;

class GradingOptionsTable extends CompetenciesAppTable {

    public function initialize(array $config) {
        $this->table('competency_grading_options');
        parent::initialize($config);

        $this->belongsTo('GradingTypes', ['className' => 'Competency.GradingTypes']);
        // $this->hasMany('StudentCompetencyResults', ['className' => 'Institution.StudentCompetencyResults', 'foreignKey' => ['competency_grading_option_id', 'academic_period_id']]);

        $this->fields['competency_grading_type_id']['type'] = 'hidden';
        $this->fields['id']['type'] = 'hidden';
        $this->fields['name']['required'] = true;
        $this->fields['max']['attr']['min'] = 0;
        // $this->fields['max']['required'] = true;
        $this->fields['max']['length'] = 7;
        $this->fields['min']['attr']['min'] = 0;
        // $this->fields['min']['required'] = true;
        $this->fields['min']['length'] = 7;
    }

    public function getFormFields($action = 'edit') {
        if ($action=='edit') {
            return ['code'=>'', 'name'=>'', 'description'=>'', 'min'=>'', 'max'=>'', 'competency_grading_type_id'=>'', 'id'=>''];
        } else {
            return ['code'=>'', 'name'=>'', 'description'=>'', 'min'=>'', 'max'=>''];
        }
    }

    public function validationDefault(Validator $validator) {
        $validator = parent::validationDefault($validator);

        $validator
            ->allowEmpty('code')
            ->add('code', 'ruleUniqueCode', [
                'rule' => ['checkUniqueCode', 'competency_grading_type_id'],
                'last' => true
            ])
            ->add('code', 'ruleUniqueCodeWithinForm', [
                'rule' => ['checkUniqueCodeWithinForm', $this->GradingTypes],
               
            ])
            ->requirePresence('name')
            // ->add('min', [
            //     'ruleNotMoreThanMax' => [
            //         'rule' => ['checkMinNotMoreThanMax'],
                // ],
                // 'ruleIsDecimal' => [
                //     'rule' => ['decimal', null],
                // ],
    //             'ruleRange' => [
    //                 'rule' => ['range', 0, 9999.99]
            //     ]
            // ])
            // ->add('max', [
            //     'ruleNotMoreThanGradingTypeMax' => [
            //         'rule' => ['checkNotMoreThanGradingTypeMax', $this->GradingTypes],
            //         'provider' => 'table'
                // ],
                // 'ruleIsDecimal' => [
                //     'rule' => ['decimal', null],
                // ],
    //             'ruleRange' => [
    //                 'rule' => ['range', 0, 9999.99]
            //     ]
            // ])
            ;
        return $validator;
    }

    public static function checkNotMoreThanGradingTypeMax($maxValue, $GradingTypes, array $globalData) {
        $formData = $GradingTypes->request->data[$GradingTypes->alias()];
        return intVal($maxValue) <= intVal($formData['max']);
    }
}
