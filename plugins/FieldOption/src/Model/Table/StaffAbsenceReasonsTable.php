<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;

class StaffAbsenceReasonsTable extends AppTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		parent::initialize($config);
		$this->hasMany('InstitutionStaffAbsences', ['className' => 'Institution.InstitutionStaffAbsences', 'foreignKey' => 'staff_absence_reason_id']);
	}
}
