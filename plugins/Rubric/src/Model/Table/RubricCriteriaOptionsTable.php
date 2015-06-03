<?php
namespace Rubric\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class RubricCriteriaOptionsTable extends AppTable {
	public function initialize(array $config) {
		parent::initialize($config);
		$this->belongsTo('RubricTemplateOptions', ['className' => 'Rubric.RubricTemplateOptions']);
		$this->belongsTo('RubricCriterias', ['className' => 'Rubric.RubricCriterias']);
	}

	public function validationDefault(Validator $validator) {
		return $validator;
	}
}
