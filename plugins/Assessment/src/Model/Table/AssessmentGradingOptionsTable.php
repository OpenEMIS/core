<?php
namespace Assessment\Model\Table;

use App\Model\Table\ControllerActionTable;
use App\Model\Traits\MessagesTrait;
use Cake\Validation\Validator;

class AssessmentGradingOptionsTable extends ControllerActionTable {
	use MessagesTrait;

	public function initialize(array $config) {
		parent::initialize($config);

		$this->belongsTo('AssessmentGradingTypes', ['className' => 'Assessment.AssessmentGradingTypes']);
		// if ($this->behaviors()->has('Reorder')) {
		// 	$this->behaviors()->get('Reorder')->config([
		// 		'filter' => 'assessment_grading_type_id',
		// 	]);
		// }
		$this->fields['assessment_grading_type_id']['type'] = 'hidden';
		$this->fields['id']['type'] = 'hidden';
		$this->fields['max']['attr']['min'] = 0;
		$this->fields['min']['attr']['min'] = 0;
	}

	public function getFormFields($action = 'edit') {
		if ($action=='edit') {
			return ['code'=>'', 'name'=>'', 'min'=>'', 'max'=>'', 'assessment_grading_type_id'=>'', 'id'=>''];
		} else {
			return ['code'=>'', 'name'=>'', 'min'=>'', 'max'=>'', 'visible'=>''];
		}
	}

	public function validationDefault(Validator $validator) {
		$validator
			->allowEmpty('code')
			->add('code', 'ruleUniqueCode', [
			    'rule' => ['checkUniqueCode', 'assessment_grading_type_id'],
			    'last' => true
			])
			->add('code', 'ruleUniqueCodeWithinForm', [
			    'rule' => ['checkUniqueCodeWithinForm', $this->AssessmentGradingTypes],
			   
			])
			;
		return $validator;
	}

}
