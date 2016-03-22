<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

class StudentAbsenceReasonsTable extends ControllerActionTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		$this->table('student_absence_reasons');
		parent::initialize($config);
		$this->hasMany('InstitutionStudentAbsences', ['className' => 'Institution.InstitutionStudentAbsences', 'foreignKey' => 'student_absence_reason_id']);
		$this->behaviors()->get('ControllerAction')->config('actions.remove', 'transfer');
	}
}
