<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class StudentAbsenceReasonsTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		parent::initialize($config);
		$this->hasMany('InstitutionStudentAbsences', ['className' => 'Institution.InstitutionStudentAbsences', 'foreignKey' => 'student_absence_reason_id']);
	}
}
