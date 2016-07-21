<?php
namespace Student\Model\Table;

use App\Model\Table\ControllerActionTable;

class StudentDropoutReasonsTable extends ControllerActionTable {
	public function initialize(array $config)
    {
		$this->addBehavior('FieldOption.FieldOption');
        $this->table('student_dropout_reasons');
		parent::initialize($config);
		$this->hasMany('DropoutRequests', ['className' => 'Institution.DropoutRequests', 'foreignKey' => 'student_dropout_reason_id']);
		$this->hasMany('StudentDropout', ['className' => 'Institution.StudentDropout', 'foreignKey' => 'student_dropout_reason_id']);
	}
}
