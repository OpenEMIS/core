<?php
namespace Institution\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class AssessmentResultTypesTable extends AppTable {
	public function initialize(array $config) {
        $this->addBehavior('ControllerAction.FieldOption');
        parent::initialize($config);
				
		// $this->hasMany('AssessmentItemResults', ['className' => 'Institution.AssessmentItemResults']);
	}
}
