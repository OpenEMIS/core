<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class StudentDropoutReasonsTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		parent::initialize($config);
		
		$this->hasMany('DropoutRequests', ['className' => 'Institution.DropoutRequests', 'foreignKey' => 'student_dropout_reason_id']);
		$this->hasMany('StudentDropout', ['className' => 'Institution.StudentDropout', 'foreignKey' => 'student_dropout_reason_id']);
	}
}
