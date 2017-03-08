<?php
namespace Examination\Model\Table;

use Examination\Model\Table\ExaminationsAppTable;
use Cake\Validation\Validator;

class ExaminationGradingOptionsTable extends ExaminationsAppTable {

    public function initialize(array $config) {
        parent::initialize($config);

        $this->belongsTo('ExaminationGradingTypes', ['className' => 'Examination.ExaminationGradingTypes']);
        $this->hasMany('ExaminationItemResults', ['className' => 'Examination.ExaminationItemResults', 'dependent' => true, 'cascadeCallbacks' => true]);

        if ($this->behaviors()->has('Reorder')) {
            $this->behaviors()->get('Reorder')->config([
                'filter' => 'examination_grading_type_id'
            ]);
        }

        $this->fields['examination_grading_type_id']['type'] = 'hidden';
        $this->fields['id']['type'] = 'hidden';
        $this->fields['name']['required'] = true;
        $this->fields['max']['attr']['min'] = 0;
        $this->fields['max']['required'] = true;
        $this->fields['max']['length'] = 7;
        $this->fields['min']['attr']['min'] = 0;
        $this->fields['min']['required'] = true;
        $this->fields['min']['length'] = 7;
    }

    public function getFormFields($action = 'edit') {
        if ($action=='edit') {
            return ['code'=>'', 'name'=>'', 'description'=>'', 'min'=>'', 'max'=>'', 'examination_grading_type_id'=>'', 'id'=>''];
        } else {
            return ['code'=>'', 'name'=>'', 'description'=>'', 'min'=>'', 'max'=>''];
        }
    }

    public function validationDefault(Validator $validator) {
        $validator = parent::validationDefault($validator);

        $validator
            ->allowEmpty('code')
            ->add('code', 'ruleUniqueCode', [
                'rule' => ['checkUniqueCode', 'examination_grading_type_id'],
                'last' => true
            ])
            ->add('code', 'ruleUniqueCodeWithinForm', [
                'rule' => ['checkUniqueCodeWithinForm', $this->ExaminationGradingTypes],
            ])
            ->requirePresence('name')
            ->add('min', [
                'ruleNotMoreThanMax' => [
                    'rule' => ['checkMinNotMoreThanMax'],
                ],
                'ruleIsDecimal' => [
                    'rule' => ['decimal', null],
                ],
                'ruleRange' => [
                    'rule' => ['range', 0, 9999.99]
                ]
            ])
            ->add('max', [
                'ruleNotMoreThanGradingTypeMax' => [
                    'rule' => ['checkNotMoreThanGradingTypeMax', $this->ExaminationGradingTypes],
                    'provider' => 'table'
                ],
                'ruleIsDecimal' => [
                    'rule' => ['decimal', null],
                ],
                'ruleRange' => [
                    'rule' => ['range', 0, 9999.99]
                ]
            ])
            ;
        return $validator;
    }

    public static function checkNotMoreThanGradingTypeMax($maxValue, $ExaminationGradingTypes, array $globalData) {
        $formData = $ExaminationGradingTypes->request->data[$ExaminationGradingTypes->alias()];
        return intVal($maxValue) <= intVal($formData['max']);
    }
}
