<?php
namespace Gpa\Model\Table;

use Cake\Validation\Validator;

class GpaGradingOptionsTable extends AssessmentsAppTable {

	public function initialize(array $config): void {
		parent::initialize($config);
		$this->setTable('gpa_grading_options');
		$this->belongsTo('GpaGradingTypes', ['className' => 'Gpa.GpaGradingTypes','foreignKey' => 'gpa_grading_type_id']);

		$this->fields['gpa_grading_type_id']['type'] = 'hidden';
		$this->fields['id']['type'] = 'hidden';
		$this->fields['name']['required'] = true;
		$this->fields['max']['attr']['min'] = 0;
		$this->fields['max']['required'] = true;
		$this->fields['max']['length'] = 7;
		$this->fields['min']['attr']['min'] = 0;
		$this->fields['min']['required'] = true;
		$this->fields['min']['length'] = 7;
		$this->fields['min']['attr']['step'] = 0.01; 
		$this->fields['point']['required'] = false;

		$this->fields['gpa_grading_type_id']['type'] = 'hidden';
		$this->fields['id']['type'] = 'hidden';
		$this->fields['name']['required'] = true;
		$this->fields['max']['attr']['min'] = 0;
		$this->fields['max']['required'] = true;
		$this->fields['max']['length'] = 7;
		$this->fields['min']['attr']['min'] = 0;
		$this->fields['min']['required'] = true;
		$this->fields['min']['length'] = 7;
		$this->fields['max']['attr']['step'] = 0.01;
		$this->fields['point']['required'] = false;

	}

	public function getFormFields($action = 'edit') {
		if ($action=='edit') {
			return ['code'=>'', 'name'=>'', 'description'=>'', 'min'=>'', 'max'=>'', 'gpa_grading_type_id'=>'', 'id'=>'','point' => ''];
		} else {
			return ['code'=>'', 'name'=>'', 'description'=>'', 'min'=>'', 'max'=>'','point'=>''];
		}
	}

	public function validationDefault(Validator $validator): Validator 
    {
        $validator = parent::validationDefault($validator);
        $validator->setProvider('custom', $this);

        $validator
            ->allowEmptyString('code')  
            ->add('code', 'ruleUniqueCode', [
                'rule' => ['checkUniqueCode', 'gpa_grading_type_id'],
                'last' => true,
                'provider' => 'custom',
                'message' => 'Code must be unique'
            ])
            ->add('code', 'ruleUniqueCodeWithinForm', [
                'rule' => ['checkUniqueCodeWithinForm', $this->GpaGradingTypes],
                'provider' => 'custom',
                'message' => 'Code must be unique within the form'
            ])
            ->requirePresence('name', 'create') // Required only for creating new records

            ->add('min', [
                'ruleNotMoreThanMax' => [
                    'rule' => ['checkMinNotMoreThanMax'],
                    'provider' => 'custom',
                    'message' => 'Min value cannot be more than max value'
                ],
                'ruleIsDecimal' => [
                    'rule' => ['decimal', null],
                    'message' => 'Value must be a valid decimal'
                ],
                'ruleRange' => [
                    'rule' => ['range', 0, 9999.99],
                    'message' => 'Value must be between 0 and 9999.99'
                ]
            ])
            
            ->add('max', [
                'ruleNotMoreThanGradingTypeMax' => [
                    'rule' => ['checkNotMoreThanGradingTypeMax', $this->GpaGradingTypes],
                    'provider' => 'table',
                    'message' => 'Grading Option max value cannot be more than Grading Type max value'
                ],
                'ruleIsDecimal' => [
                    'rule' => ['decimal', null],
                    'message' => 'Value must be a valid decimal'
                ],
                'ruleRange' => [
                    'rule' => ['range', 0, 9999.99],
                    'message' => 'Value must be between 0 and 9999.99'
                ]
            ])
            
            ->allowEmptyString('point')  // CakePHP 4 uses allowEmptyString
            ->add('point', 'ruleIsDecimal', [
                'rule' => ['decimal', null],
                'message' => 'Point value must be a valid decimal'
            ]);

        return $validator;
    }

	public static function checkNotMoreThanGradingTypeMax($maxValue, $GpaGradingTypes, array $globalData) {
		$formData = $GpaGradingTypes->request->getData()[$GpaGradingTypes->getAlias()];
        return intVal($maxValue) <= intVal($formData['max']);
    }

//     public function validationDefault(Validator $validator): Validator
// {
//     $validator->setProvider('custom', $this);
//     $validator
//         ->requirePresence('name')
//         ->notEmptyString('name', 'The name field cannot be empty.');

//     $validator
//         ->add('min', 'validDecimal', [
//             'rule' => 'decimal',
//             'message' => 'Min value must be a valid decimal.'
//         ])
//         ->add('min', 'range', [
//             'rule' => ['range', 0, 9999.99],
//             'message' => 'Min value must be between 0 and 9999.99.'
//         ]);

//     return $validator;
// }

}
