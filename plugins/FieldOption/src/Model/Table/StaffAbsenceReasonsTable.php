<?php
namespace FieldOption\Model\Table;

use App\Model\Table\AppTable;
use Cake\Validation\Validator;
use App\Model\Table\ControllerActionTable;

class StaffAbsenceReasonsTable extends ControllerActionTable {
	public function initialize(array $config) {
		$this->addBehavior('ControllerAction.FieldOption');
		$this->table('staff_absence_reasons');
		parent::initialize($config);
		$this->hasMany('InstitutionStaffAbsences', ['className' => 'Institution.InstitutionStaffAbsences', 'foreignKey' => 'staff_absence_reason_id']);
		$this->behaviors()->get('ControllerAction')->config('actions.remove', 'transfer');
	}
}
