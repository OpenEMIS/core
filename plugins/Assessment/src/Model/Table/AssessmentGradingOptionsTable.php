<?php
namespace Assessment\Model\Table;

use Cake\Validation\Validator;

class AssessmentGradingOptionsTable extends AssessmentsAppTable {

	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('AssessmentGradingTypes', ['className' => 'Assessment.AssessmentGradingTypes']);
		$this->hasMany('AssessmentItemResults', ['className' => 'Assessment.AssessmentItemResults', 'dependent' => true, 'cascadeCallbacks' => true]);

		$this->fields['assessment_grading_type_id']['type'] = 'hidden';
		$this->fields['id']['type'] = 'hidden';
		$this->fields['name']['required'] = true;
		$this->fields['max']['attr']['min'] = 0;
		$this->fields['max']['required'] = true;
		$this->fields['min']['attr']['min'] = 0;
		$this->fields['min']['required'] = true;
	}

	public function getFormFields($action = 'edit') {
		if ($action=='edit') {
			return ['code'=>'', 'name'=>'', 'min'=>'', 'max'=>'', 'assessment_grading_type_id'=>'', 'id'=>''];
		} else {
			return ['code'=>'', 'name'=>'', 'min'=>'', 'max'=>''];
		}
	}

	public function validationDefault(Validator $validator) {
		$validator = parent::validationDefault($validator);

		$validator
			->allowEmpty('code')
			->add('code', 'ruleUniqueCode', [
			    'rule' => ['checkUniqueCode', 'assessment_grading_type_id'],
			    'last' => true
			])
			->add('code', 'ruleUniqueCodeWithinForm', [
			    'rule' => ['checkUniqueCodeWithinForm', $this->AssessmentGradingTypes],
			   
			])
			->requirePresence('name')
			->add('min', [
				'ruleNotMoreThanMax' => [
			    	'rule' => ['checkMinNotMoreThanMax'],
				],
				'ruleIsDecimal' => [
				    'rule' => ['decimal', null],
				]
			])
			->add('max', [
				'ruleNotMoreThanGradingTypeMax' => [
				    'rule' => ['checkNotMoreThanGradingTypeMax', $this->AssessmentGradingTypes],
				    'provider' => 'table'
				],
				'ruleIsDecimal' => [
				    'rule' => ['decimal', null],
				]
			])
			;
		return $validator;
	}

	public static function checkNotMoreThanGradingTypeMax($maxValue, $AssessmentGradingTypes, array $globalData) {
		$formData = $AssessmentGradingTypes->request->data[$AssessmentGradingTypes->alias()];
        return intVal($maxValue) <= intVal($formData['max']);
    }
}
